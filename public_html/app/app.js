	'use strict';

// Declare app level module which depends on views, and components
angular.module('cpa_admin', ['ngAnimate','ui.bootstrap','ngResource','ng-currency','cgBusy','ngRoute','ui.toggle','ngFileUpload','chart.js','dndLists','cpa_admin.emailtemplateview','cpa_admin.codeview','cpa_admin.chargeview','cpa_admin.testview','cpa_admin.testdefview','cpa_admin.testcsview','cpa_admin.arenaview','cpa_admin.clubview','cpa_admin.courseview','cpa_admin.preregistrationview','cpa_admin.registrationview','cpa_admin.sessioncoursesview','cpa_admin.sessionview','cpa_admin.showview','cpa_admin.showtaskview','cpa_admin.memberview','cpa_admin.contactview','cpa_admin.privilegeview','cpa_admin.roleview','cpa_admin.userview','cpa_admin.userprofileview','cpa_admin.testsessionview','cpa_admin.teststarsessionview','cpa_admin.testperiodview','cpa_admin.testperiodfollowview','cpa_admin.testregistrationview','cpa_admin.teststarregistrationview', 'cpa_admin.courseattendanceview', 'cpa_admin.canskateevaluationview', 'cpa_admin.configurationview', 'cpa_admin.welcomeview','cpa_admin.loginview','cpa_admin.billview','cpa_admin.testexternalapprobview','cpa_admin.dashboardview', 'cpa_admin.sessiontaxreceiptview', 'cpa_admin.ccwelcomeview', 'cpa_admin.ccloginview', 'cpa_admin.ccregisterview', 'cpa_admin.ccprofileview', 'cpa_admin.ccbillview', 'cpa_admin.ccsinglebillview', 'cpa_admin.ccskaterview', 'cpa_admin.ccregistrationview', 'cpa_admin.ccpreregistrationview', 'cpa_admin.ccshowregistrationview', 'cpa_admin.wsnewpageview', 'cpa_admin.wsprogramassistantview', 'cpa_admin.wsboardmemberview', 'cpa_admin.wspartnerview', 'cpa_admin.wscoachview', 'cpa_admin.wsnewsview', 'cpa_admin.wseventview', 'cpa_admin.wscontactview', 'cpa_admin.wsdocumentview', 'cpa_admin.wscostumeview', 'cpa_admin.wsboutiqueview', 'cpa_admin.wsclassifiedaddview', 'cpa_admin.sessionscheduleview','core'])

.config(['$locationProvider', '$routeProvider', function($locationProvider, $routeProvider) {
	$locationProvider.hashPrefix('!');
	$routeProvider.otherwise({redirectTo: '/loginview'});
}])

.factory('$exceptionHandler', ['$injector', 'errorHandlingService', function($injector, errorHandlingService) {
	// This factory intercept all non handled exceptions
    return function myExceptionHandler(exception, cause) {
		var scope = $injector.get('$rootScope');	// use $injector to avoid loop exception between modules
		// We need to call a service that manages all error handling (save to DB, log to console, display message to user)
		// Used the title of the program found in $rootScope to pass as a program name to increase amount of info logged
		errorHandlingService.logException(exception, cause, scope.title);
    };
 }])

/**
 * 	This is an interceptor for all HTTP calls. If the call is a POST, we add the current language, current userid and current program name 
 * 	to the data being passed (if not already passed).
 */
 .config(['$httpProvider', '$injector', function ($httpProvider, $injector) {
	$httpProvider.interceptors.push(function ($q, $injector) {
		return {
			'request': function (config) {
				if (config.method == 'POST') {
					if (config.data != null) {
						var route = $injector.get('$route');	// use $injector to avoid loop exception between modules
						var rootScope = $injector.get('$rootScope');	// use $injector to avoid loop exception between modules
						var authentication = $injector.get('authenticationService');	// use $injector to avoid loop exception between modules
						if (config.data.indexOf("language=") == -1) {
							// Let's add language to the request
							config.data += "&language=" + (authentication ? authentication.getCurrentLanguage() : '');
						}
						if (config.data.indexOf("userid=") == -1) {
							// Let's add userid to the request
							config.data += "&userid=" + (rootScope && rootScope.userInfo ? rootScope.userInfo.userid : '');
						}
						if (config.data.indexOf("progname=") == -1) {
							// Let's add progname to the request
							config.data += "&progname=" + (route && route.current && route.current.originalPath ? route.current.originalPath.replace('/', '') : '');
						}
					}
				}
				return config || $q.when(config);
			}
		}
	});
}])
//  .directive('keypressEvents', ['$document', '$rootScope', function($document, $rootScope) {
// 	return {
// 	restrict: 'A',
// 	link: function() {
// 		$document.bind('keypress', function(e) {
// 				console.log('Got keypress:', e.which);
// 				$rootScope.$broadcast('keypress', e);
// 				$rootScope.$broadcast('keypress:' + e.which, e);
// 			});
// 		}
// 	};
// }])

.run(["$rootScope", "$location", '$window', '$route', '$timeout', '$uibModal', '$document', 'translationService', 'dialogService', 'authenticationService', function ($rootScope, $location, $window, $route, $timeout, $uibModal, $document, translationService, dialogService, authenticationService) {

	/**
	 * This function checks if anything is dirty
	 * @param {*} scope 
	 * @param {*} formList 
	 * @returns true if any of the forms are dirty, false otherwise
	 */
	$rootScope.isDirty = function(scope, formList) {
		if (scope && formList && formList.length > 0) {
			for (var x = 0; x < formList.length; x++) {
				if (scope[formList[x].name].$dirty) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * This function sets one form dirty to indicate the whole thing is dirty
	 * @param {*} scope 
	 * @param {*} formList 
	 */
	$rootScope.setDirty = function(scope, formList) {
		if (scope && formList && formList.length > 0) {
			scope[formList[0].name].$dirty = true;
			scope.isFormPristine = false;
		}
	}

	/**
	 * This function sets all forms as pristine
	 * @param {*} scope 
	 * @param {*} formList 
	 * @returns 
	 */
	$rootScope.setPristine = function(scope, formList) {
		if (scope && formList && formList.length > 0) {
			for (var x = 0; x < formList.length; x++) {
				scope[formList[x].name].$setPristine();
			}
			scope.isFormPristine = true;
		}
	}

	/**
	 * 
	 * @param {*} scope 
	 * @param {*} formList Array of form objects : 
	 * 								name : name of the form. used like scope[formList[0].name]
	 * 								errorMsg : message property to use instead of the default. used like scope.translationObj.main[formList[0].errorMsg]
	 * 								validFct : name of the validation function to call to validate the form. Use like scope[formList[0].validFct]()
	 * 											Must return an object with errorMsg and/or warningMsg
	 * @returns true if everything is valid, false otherwise
	 */
	$rootScope.validateAllForms = function(scope, formList) {
		var retVal = true;
		scope.globalErrorMessage = [];
		scope.globalWarningMessage = [];

		for (var x = 0; x < formList.length; x++) {
			var form = scope[formList[x].name];
			// If form has a custom validation function, call it
			if (formList[x].validFct) {
				var tmpMsg = scope[formList[x].validFct]();
				if (tmpMsg) {
 					if (tmpMsg.errorMsg) {
						scope.globalErrorMessage.push(tmpMsg.errorMsg);
 					}
 					if (tmpMsg.warningMsg) {
						scope.globalWarningMessage.push(tmpMsg.warningMsg);
 					}
				}
			} else {
				// if no custom validation function, just check $invalid property
				if (form.$invalid) {
					// Check if form has a custom error message
					if (formList[x].errorMsg) {
						scope.globalErrorMessage.push(scope.translationObj.main[formList[x].errorMsg]);
					} else {
						// If not, use the default error message
						scope.globalErrorMessage.push($rootScope.translationObj.general.msgerrallmandatory);
					}
				}
			}
		}
		if (scope.globalErrorMessage.length != 0) {
			retVal = false;
		}

		// $timeout function is to avoid problems with the $apply() that sometimes conflicted with another $apply()
		$timeout(function() {
			// Display the error messages and the warning messages
			if (scope.globalErrorMessage.length != 0) {
				scope.$apply();
				$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function () { $("#mainglobalerrormessage").hide(); });
			}
			if (scope.globalWarningMessage.length != 0) {
				scope.$apply();
				$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function () { $("#mainglobalwarningmessage").hide(); });
			}
		}, 0, false);

		return retVal;
	}

	$rootScope.createNewObjectDlg = function(newObject) {
		newObject.newObj = {};
		$rootScope.newObject = newObject;
		$uibModal.open({
			animation: false,
			templateUrl: newObject.templateUrl,
			controller: 'childeditor.controller',
			scope: newObject.scope,
			size: 'md',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return newObject.newObj;
				}
			}
		}).result.then(function (newObject) {
			// User clicked OK and everything was valid.
			$rootScope.newObject.callback(newObject);
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	}

	$rootScope.createNewObject = function(scope, isConfirmed, templateUrl, callback) {
		var newObject = {'scope':scope, 'templateUrl':templateUrl, 'callback':callback};
		if (scope.isDirty() && !isConfirmed) {
			dialogService.confirmDlg($rootScope.translationObj.general.msgformdirty, "YESNO", $rootScope.createNewObjectDlg, null, newObject, null);
		} else {
			$rootScope.createNewObjectDlg(newObject);
		}
	}

	$rootScope.$on("$routeChangeSuccess", function (event, current, previous, eventObj) {
		if ($rootScope.translationObj && $rootScope.translationObj.main) {
			// Change the main tab title
			$rootScope.title = $rootScope.translationObj.main[$route.current.controller];
			// Close the navbar as soon as a choice is made
			$('#myNavbar').collapse('hide');
		}
		console.log(eventObj);
	});

	$rootScope.$on("$routeChangeError", function (event, current, previous, eventObj) {
		if (eventObj.authenticated === false) {
			// console.log(eventObj.authenticated);
			// console.log(previous);
			// console.log(eventObj);
			// dialogService.alertDlg("Invalid rigths");
			$rootScope.newLocation = eventObj.newLocation;
			$location.path("/loginview");
		} else {
			if (eventObj.validRights != null && eventObj.validRights == false) {
				$rootScope.validRights = false;
				$location.path("/welcomeview");
				// dialogService.alertDlg("Invalid rigths");
			} else {
				$location.path("/loginview");
			}
		}
		// console.log(eventObj.authenticated);
		return;
	});

	$rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		translationService.getNavbarTranslation($rootScope, authenticationService.getCurrentLanguage());
	});

	$rootScope.$on("navbartranslation.loaded", function (event, current, previous, eventObj) {
		$rootScope.title = $rootScope.translationObj.main[$route.current.controller];
	});

	// $document.bind("keypress", function(event) {
	// 	// console.debug(event)
	// 	$rootScope.$broadcast('keypress', event);
	// });

	$document.bind("keydown", function(event) {
		// console.log(event.which);
		if (event.which == 83 && event.altKey == true) {
			$rootScope.$broadcast('main-save', event);
		}
	});

	//when browser closed - psedocode
	$(window).unload(function(){
		return;
	  // $window.localStorage.removeItem("userInfo");
	  // $window.sessionStorage.removeItem("userInfo");
	});

	// $rootScope.memberAccess = function ($q, authenticationService) {
	// 	var userInfo = authenticationService.getUserInfo();
	// 	if (userInfo && userInfo.privileges.member_access == true) {
	// 		return $q.when(userInfo);
	// 	} else {
	// 		return $q.reject({authenticated: false});
	// 	}
	// }

	// var w = angular.element($window);
	// w.bind('resize', function () {
	// console.log('resize');
	// });

	$rootScope.repositionLeftColumn = function() {
		var newSize = 0;
		var intro = angular.element('#intro');
		var mainColumnLeft = angular.element('#maincolumnleft');
		var leftSelector = angular.element('#leftselector');
		var navBar = angular.element('#navbar');
		var mainform = angular.element('#mainform');
		var maintab = angular.element('#maintab');
		var maintabheader = angular.element('#maintabheader');
		var maintabcontent = angular.element('#maintabcontent');
		if (intro[0] && leftSelector[0] && mainColumnLeft[0] && navBar[0]) {

			intro.css('height', $window.innerHeight - Math.min(navBar[0].clientHeight, 50) -12 + 'px');
			// leftSelector.css('height', $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - 122 + 'px');
			if (leftSelector[0].offsetTop == 0) {
				newSize = $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - 120;
			} else {
				newSize = $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - leftSelector[0].offsetTop - 2;
			}
			leftSelector.css('height', newSize + 'px');
		}
		if (mainform[0] && navBar[0]) {
			if (mainform[0].offsetTop == 0) {
				newSize = $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - 108;
			} else {
				newSize = $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - mainform[0].offsetTop - 2;
			}
			mainform.css('height', newSize + 'px');
		}
		if (maintab[0] && navBar[0]) {
			// This is becausse in the case of multiple tabs, the tabs header take up all the available space
			// var newSize = $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - maintab[0].offsetTop - 2;
			newSize = $window.innerHeight - maintab[0].offsetTop - 11;
			newSize = Math.max(newSize - maintabheader[0].offsetHeight, maintabheader[0].offsetHeight + 500);
			maintab.css('height', newSize + 'px');
		}
		if (maintabcontent[0] && maintabheader[0] && maintab[0]) {
			maintabcontent.css('height', Math.max(maintab[0].clientHeight - maintabheader[0].offsetHeight, 500) + 'px');
		}
		return;
	}

	$(window).resize(function(event){
		// console.log('resize');
		// console.log(event.target.innerHeight);
		$rootScope.repositionLeftColumn();
	});
	// $rootScope.repositionLeftColumn();

	translationService.getNavbarTranslation($rootScope, authenticationService.getCurrentLanguage());

}]);
