angular.module('core').service("authenticationService", ["$http", "$q", "$window", '$rootScope', '$route', '$uibModal', 'translationService', 'dialogService', function ($http, $q, $window, $rootScope, $route, $uibModal, translationService, dialogService) {

that = this;
// Constant for the version of the userInfo structure to validate its validity when read from the local storage
const LOGIN_VERSION = 1;
const NUMBER_HOURS_ALIVE = 4;	// Number of hours that the application does not refresh the login info. After that time, user must login again.

	/**
	 * Handles the language switching in the different pages. Handling differs if in MY SKATING SPACE or not.
	 * @param {*} forceLanguage 
	 */
	this.switchLanguage = function(forceLanguage) {
		var currentLanguage = ($rootScope.selectedLanguage ? $rootScope.selectedLanguage : 'fr-ca');
		// Changing from preferedlanguage to selectedLanguage to make a difference between the prefered communication language and the working language
		if (!forceLanguage) {
			// We need to check if the user is in the MY SKATING SPACE and check if the new language is supported
			if ($rootScope.applicationName && $rootScope.applicationName == 'EC') {
				if ($rootScope.selectedLanguage == "fr-ca") {
					if ($rootScope.userInfo && $rootScope.userInfo.supportenglish == 1 ) {
						$rootScope.selectedLanguage = "en-ca";
					}
				} else if ($rootScope.selectedLanguage == "en-ca") {
					if ($rootScope.userInfo && $rootScope.userInfo.supportfrench == 1 ) {
						$rootScope.selectedLanguage = "fr-ca";
					}
				}
			} else {
				// Not in MY SKATING SPACE
				if ($rootScope.selectedLanguage == "fr-ca") {
					$rootScope.selectedLanguage = "en-ca";
				} else if ($rootScope.selectedLanguage == "en-ca") {
					$rootScope.selectedLanguage = "fr-ca";
				}
			}
		} else {	// forcing a language
			if ($rootScope.applicationName && $rootScope.applicationName == 'EC') {
				if (forceLanguage == 'en-ca') {
					if ($rootScope.userInfo && $rootScope.userInfo.supportenglish == 1 ) {
						$rootScope.selectedLanguage = "en-ca";
					}
				} else if (forceLanguage == 'fr-ca') {
					if ($rootScope.userInfo && $rootScope.userInfo.supportfrench == 1 ) {
						$rootScope.selectedLanguage = "fr-ca";
					}
				}
			} else {
				if (forceLanguage == 'en-ca') {
					$rootScope.selectedLanguage = "en-ca";
				} else if (forceLanguage == 'fr-ca') {
					$rootScope.selectedLanguage = "fr-ca";
				}
			}
		}
		if (currentLanguage != $rootScope.selectedLanguage) {
			$rootScope.$apply();
			$rootScope.$broadcast("authentication.language.changed", $rootScope.selectedLanguage);
		}
	};

	/**
	 * Validates the login info with the one in the database.
	 * @param {*} $scope 
	 * @param {*} userid 
	 * @param {*} password 
	 * @returns 
	 */
	this.validateLoginInfo = function($scope, userid, password) {
		return $http({
				method: 'post',
				url: './core/services/authentication/authentication.php',
				data: $.param({'userid' : userid, 'password' : password, 'type' : 'validatelogin'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function(data, status, headers, config) {
			}).error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
			});
	};

	/**
	 * Validates the user code or email address in the database. Called by the forgotpassword controller.
	 * @param {*} $scope 
	 * @param {*} emailorusercode 
	 * @returns 
	 */
	this.validateUserOrEmail = function($scope, emailorusercode) {
		return $http({
				method: 'post',
				url: './core/services/authentication/authentication.php',
				data: $.param({'emailorusercode' : emailorusercode, 'type' : 'validateuseroremail'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).success(function(data, status, headers, config) {
		}).error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * Changes the user password in the database.
	 * @param {*} $scope 
	 * @param {*} userid 
	 * @param {*} oldpassword 
	 * @param {*} newpassword 
	 * @returns 
	 */
	this.changePassword = function($scope, userid, oldpassword, newpassword) {
		return $http({
				method: 'post',
				url: './core/services/authentication/authentication.php',
				data: $.param({'userid' : userid, 'oldpassword' : oldpassword, 'newpassword' : newpassword, 'type' : 'changepassword'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).success(function(data, status, headers, config) {
		}).error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * Sets the structure used to hold the user info for all views
	 * @param {*} newLogin 
	 */
	this.setLoginInfo = function(newLogin) {
		$rootScope.userInfo = {
				version:LOGIN_VERSION,
				accessToken: '',
				userfullname: newLogin.fullname,
				preferedlanguage: newLogin.preferedlanguage,
				supportfrench: newLogin.supportfrench,
				supportenglish: newLogin.supportenglish,
				userid: newLogin.userid,
				contactid: newLogin.contactid,
				id: newLogin.id,
				privileges: newLogin.privileges,
				logindatetime: new Date()
		};
		// Explodes the privileges into an array
		for (var i = 0; $rootScope.userInfo.privileges && i <  $rootScope.userInfo.privileges.length; i++) {
			$rootScope.userInfo.privileges[$rootScope.userInfo.privileges[i].code] = true;
		}
		// Saves the user info in the local storage
		$window.localStorage["userInfo"] = JSON.stringify($rootScope.userInfo);
		// $window.sessionStorage["userInfo"] = JSON.stringify($rootScope.userInfo);
		// Set the supported language for the website and MY SKATING SPACE
	};

	/**
	 * Updates the structure used to hold the user info for all views.
	 * @param {*} userid 
	 * @param {*} preferedlanguage 
	 */
	this.updateLoginInfo = function(userid, preferedlanguage) {
		$rootScope.userInfo.userid = userid;
		$rootScope.userInfo.preferedlanguage = preferedlanguage;
		// Saves the user info in the local storage
		$window.localStorage["userInfo"] = JSON.stringify($rootScope.userInfo);
		// $window.sessionStorage["userInfo"] = JSON.stringify($rootScope.userInfo);
	};

	/**
	 * Manages the "Change expired password" functionnality.
	 * @param {*} $scope 
	 * @param {*} deferred 
	 * @param {*} newLogin 
	 * @returns 
	 */
	this.changeExpiredPassword = function($scope, deferred, newLogin) {
		translationService.getTranslation($rootScope, 'core/services/authentication', newLogin.preferedlanguage);
		var changePassword = {};
		changePassword.userid = newLogin.userid;
		changePassword.passwordexpired = 1;	// show cancel button in changepassword dialog box
		return ($uibModal.open({
					animation: false,
					templateUrl: './core/services/authentication/changepassword.template.html',
					controller: 'changepassword.controller',
					scope: $scope,
					size: 'sm',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return changePassword;
						}
					}
			}).result.then(function(changePassword) {
				that.setLoginInfo(newLogin);
				deferred.resolve($rootScope.userInfo);
			}, function() {
				// User clicked CANCEL.
				deferred.reject("Invalid password change");
			})
		)
	}

	/**
	 * Manages the "Change password" functionnality.
	 * @param {*} $scope 
	 * @param {*} newLogin 
	 * @returns 
	 */
	this.changePasswordOnDemand = function($scope, newLogin) {
		translationService.getTranslation($rootScope, 'core/services/authentication', newLogin.preferedlanguage);
		var changePassword = {};
		changePassword.userid = newLogin.userid;
		changePassword.passwordexpired = 0;	// Hide cancel button in changepassword dialog box
		return ($uibModal.open({
					animation: false,
					templateUrl: './core/services/authentication/changepassword.template.html',
					controller: 'changepassword.controller',
					scope: $scope,
					size: 'sm',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return changePassword;
						}
					}
			}).result.then(function(changePassword) {
				// that.setLoginInfo(newLogin);
			}, function() {
				// User clicked CANCEL.
			})
		)
	}

	/**
	 * Manages the "Reset password and send email" functionnality.
	 * @param {*} email 
	 * @param {*} language 
	 * @returns 
	 */
	this.resetPasswordAndSendEmail = function(email, language) {
		// We need to change password and send email
		translationService.getTranslation($rootScope, 'core/services/authentication', language);
		return $http({
				method: 'post',
				url: './core/services/authentication/authentication.php',
					data: $.param({'emailorusercode' : email, 'type' : 'resetPasswordAndSendEmail'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).success(function(data, status, headers, config) {
			if (data.success == 1) {
				dialogService.alertDlg($rootScope.translationObj.main.msgemailsent);
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	}

	/**
	 * Manages the "Set password and send welcome email" functionnality.
	 * @param {*} email 
	 * @param {*} language 
	 * @returns 
	 */
	this.setPasswordAndSendWelcomeEmail = function(email, language) {
		// We need to change password and send the welcome email to MY SKATING SPACE
		translationService.getTranslation($rootScope, 'core/services/authentication', language);
		return $http({
				method: 'post',
				url: './core/services/authentication/authentication.php',
				data: $.param({'emailorusercode' : email, 'language' : language, 'type' : 'setPasswordAndSendWelcomeEmail'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).success(function(data, status, headers, config) {
			if (data.success == 1) {
				dialogService.alertDlg($rootScope.translationObj.main.msgemailsent);
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	}
	
	/**
	 * Manages the "Forgottern password" functionnality.
	 * @param {*} $scope 
	 * @param {*} language 
	 */
	this.manageForgottenPassword = function($scope, language) {
		var forgotPassword = {};
		translationService.getTranslation($rootScope, 'core/services/authentication', language);
		$uibModal.open({
				animation: false,
				templateUrl: './core/services/authentication/forgotpassword.template.html',
				controller: 'forgotpassword.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return forgotPassword;
					}
				}
		}).result.then(function(forgotPassword) {
			// We need to change password and send email
			that.resetPasswordAndSendEmail(forgotPassword.emailorusercode, language);
		}, function() {
			// User clicked CANCEL.
			// deferred.reject("Canceled forgot password");
		});
	}

	/**
	 * Opens the login dialog window.
	 * @param {*} $scope 
	 * @returns 
	 */
	this.login = function($scope) {
		var deferred = $q.defer();
		$uibModal.open({
				animation: false,
				templateUrl: './core/services/authentication/login.template.html',
				controller: 'login.controller',
				scope: $scope,
				size: 'sm',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return {};
					}
				}
		}).result.then(function(newLogin) {
			var deferred = $q.defer();
			// In case if password is expired, user must select a new one.
			if (newLogin.passwordexpired == 1) {
				that.changeExpiredPassword($scope, deferred, newLogin);
			} else {
				// User forgot it's password
				if (newLogin.forgotPassword) {
					that.manageForgottenPassword($scope);
				} else {
					that.setLoginInfo(newLogin);
					deferred.resolve($rootScope.userInfo);
				}
			}
		}, function() {
			// User clicked CANCEL.
			deferred.reject("Invalid login");
		});
		return deferred.promise;
	};

	/**
	 * Disconnects the user and deletes local storage.
	 * @returns 
	 */
	this.logout = function() {
		var deferred = $q.defer();

		$rootScope.userInfo = null;
		$window.localStorage.clear();
		// $window.localStorage["userInfo"] = null;
		// $window.sessionStorage["userInfo"] = null;
		deferred.resolve($rootScope.applicationName == 'EC');

		return deferred.promise;
	};

	/**
	 * Get the current user info. If the userInfo structure is null, initialize the authentication service.
	 * @returns userInfo object
	 */
	this.getUserInfo = function() {
		if (!$rootScope.userInfo) {
			this.init();
		} else {
			if (this.validLoginDateTime($rootScope.userInfo) == false) {
				// return null;
				$rootScope.userInfo = null;
			}
		}
		return $rootScope.userInfo;
	};

	/**
	 * This function validates that user has the proper privilege
	 * @param {*} privilege The privilege to check
	 * @returns promise
	 */
	this.validateUserRoutingPrivilege = function() {
		var deferred = $q.defer();
		var userInfo = this.getUserInfo();
		if (userInfo) {
			$http({
				method: 'post',
				url: './core/services/authentication/authentication.php',
				data: $.param({'userid' : userInfo.userid, 'progname' : $route.current.originalPath.replace('/', ''), 'type' : 'validateUserRoutingPrivilege'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function(data, status, headers, config) {
				if (data.success == true) {
					deferred.resolve(userInfo);
				} else {
					deferred.reject({authenticated: true, validRights: false, newLocation:null});
				}
			}).error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
			});

		} else {
			deferred.reject({authenticated: false, newLocation: "/"+$route.current.originalPath.replace('/', '')});
		}
		return deferred.promise;
	}

	/**
	 * Gets the current language. If the current application does not support the selected language, switch.
	 * @returns Return the selectedLanguage if set, if not the default is fr-ca.
	 */
	this.getCurrentLanguage = function() {
		if (!$rootScope.selectedLanguage) {
			$rootScope.selectedLanguage = 'fr-ca';
		}
		
		// Check if selected language is permitted for the current application, if not, switch language
		if ($rootScope.applicationName && $rootScope.applicationName == 'EC') {
			if ($rootScope.selectedLanguage == "fr-ca") {
				if ($rootScope.userInfo && $rootScope.userInfo.supportfrench == 0 ) {
					$rootScope.selectedLanguage = "en-ca";
				}
			} else if ($rootScope.selectedLanguage == "en-ca") {
				if ($rootScope.userInfo && $rootScope.userInfo.supportenglish == 0 ) {
					$rootScope.selectedLanguage = "fr-ca";
				}
			}
		}
		
		return $rootScope.selectedLanguage;
	};

	/** 
	 * Validates if the version and the date time of the user info is less than NUMBER_HOURS_ALIVE hours
	 * 
	*/
	this.validLoginDateTime = function(userInfo) {
		if (!userInfo) {
			userInfo = JSON.parse($window.localStorage["userInfo"]);
		}
		if (userInfo) {
			// old userInfo structure, the logindatetime did not exists
			// old userInfo structure, the version is wrong
			if (!userInfo.logindatetime || !userInfo.version || (userInfo.version && userInfo.version < LOGIN_VERSION)) {
				return false;
			} else {
				var delayInHours = Math.abs(new Date() - new Date(userInfo.logindatetime)) / 36e5;
				if (delayInHours > NUMBER_HOURS_ALIVE) return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Reads the login information from local storage and returns it if it exists and it's not expired and it's version is up to date.
	 * If not ok, the user will need to login again.
	 * @returns null if something is wrong
	 */
	this.init = function() {
		if (!$rootScope.userInfo) {
			// console.log("localStorage : " + $window.localStorage["userInfo"]);
			if ($window.localStorage["userInfo"]) {
				var tmp = JSON.parse($window.localStorage["userInfo"]);
				$rootScope.userInfo = null;
				if (tmp) {
					// if (this.validLoginDateTime(tmp) == false) return;
					// old userInfo structure, the logindatetime did not exists
					// old userInfo structure, the version is wrong
					if (!tmp.logindatetime || !tmp.version || (tmp.version && tmp.version < LOGIN_VERSION)) {
						return;
					} else {
						// console.log("Last login datetime : " + tmp.logindatetime);
						var delayInHours = Math.abs(new Date() - new Date(tmp.logindatetime)) / 36e5;
						// console.log("Delay : " + delayInHours);
						if (delayInHours > NUMBER_HOURS_ALIVE) return;
					}
					for (var i = 0; tmp && tmp.privileges && i < tmp.privileges.length; i++) {
						tmp.privileges[tmp.privileges[i].code] = true;
					}
					$rootScope.userInfo = tmp;
				}
			}
		}
	};

	this.init();
}]);
