'use strict';


angular.module('cpa_admin.configurationview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/configurationview', {
		templateUrl: 'configurationview/configurationview.html',
		controller: 'configurationviewCtrl',
		resolve: {
			auth: function($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.admin_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/configurationview"});
				}
			}
		}
	})
	.when('/configurationview/:token', {
		templateUrl: 'configurationview/configurationview.html',
		controller: 'configurationviewCtrl',
		resolve: {
			auth: function ($q, authenticationService, $location) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo && userInfo.privileges.admin_access==true) {
					return $q.when(userInfo);
				} else {
					return $q.reject({ authenticated: false });
				}
			}
		}
	})
	.when('/configurationview/:PayerId', {
		templateUrl: 'configurationview/configurationview.html',
		controller: 'configurationviewCtrl',
		resolve: {
			auth: function ($q, authenticationService, $location) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo && userInfo.privileges.admin_access==true) {
					return $q.when(userInfo);
				} else {
					return $q.reject({ authenticated: false });
				}
			}
		}
	});
;
}])

.controller('configurationviewCtrl', ['$scope', '$http', '$uibModal', '$window', '$timeout', '$route', 'Upload', 'anycodesService', 'dialogService', 'authenticationService', 'translationService', 'reportingService', function($scope, $http, $uibModal, $window, $timeout, $route, Upload, anycodesService, dialogService, authenticationService, translationService, reportingService) {

	$scope.progName = "configurationView";
	$scope.leftpanetemplatefullpath = "./configurationview/configuration.template.html";
	$scope.currentConfiguration = null;
	$scope.selectedLeftObj = null;
	$scope.newConfiguration = null;
	$scope.isFormPristine = true;
	$scope.token = $route.current.params.token;
	$scope.paymentId = $route.current.params.paymentId;
	$scope.payerId = $route.current.params.PayerID;

	// For delay display
	$scope.delay = 0;
	$scope.minDuration = 0;
	$scope.message = 'Please Wait...';
	$scope.backdrop = true;
	$scope.promise = null;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty || $scope.boardForm.$dirty || $scope.emailForm.$dirty  || $scope.paypalForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function() {
		if ($scope.detailsForm) {
			$scope.detailsForm.$setPristine();
		}
		if ($scope.emailForm) {
			$scope.emailForm.$setPristine();
		}
		if ($scope.paypalForm) {
			$scope.paypalForm.$setPristine();
		}
		if ($scope.boardForm) {
			$scope.boardForm.$setPristine();
		}
		$scope.isFormPristine = true;
	};

	$scope.getAllConfigurations = function() {
		$http({
				method: 'post',
				url: './configurationview/configuration.php',
				data: $.param({ 'type' : 'getAllConfigurations' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.leftobjs = data.data;
				} else {
					$scope.leftobjs = [];
				}
			} else {
				if(!data.success){
					dialogService.displayFailure(data);
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.getConfigurationDetails = function(configuration) {
		$scope.promise = $http({
			method: 'post',
			url: './configurationview/configuration.php',
			data: $.param({'id' : configuration.id, 'type' : 'getConfigurationDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success && !angular.isUndefined(data.data) ){
				$scope.currentConfiguration = data.data[0];

				// var PAYPAL_SCRIPT = 'https://www.paypal.com/sdk/js?client-id=sb';
				// var script = document.createElement('script');
				// script.setAttribute('src', PAYPAL_SCRIPT);
				// document.head.appendChild(script);
				// paypal.Buttons().render('#paypal-button-container');
				// paypal = loadScript({ clientId: "test" });

			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function(configuration, index) {
		if (configuration != null) {
			$scope.selectedLeftObj = configuration;
			$scope.getConfigurationDetails(configuration);
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentConfiguration = null;
		}
	}

	$scope.setCurrent = function(configuration, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, configuration, index);
		} else {
			$scope.setCurrentInternal(configuration, index);
		}
	};

	$scope.deleteFromDB = function(confirmed){
		if ($scope.currentConfiguration != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$http({
				method: 'post',
				url: './configurationview/configuration.php',
				data: $.param({'configuration' : $scope.currentConfiguration, 'type' : 'delete_configuration' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedLeftObj),1);
					$scope.setCurrentInternal(null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	}

	$scope.validateAllForms = function() {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}
		// if ($scope.currentMember.healthcareno == "") {
		// 	$scope.globalWarningMessage.push($scope.translationObj.main.msgerrallmandatory);
		// }

		if ($scope.globalErrorMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function(){$("#mainglobalerrormessage").hide();});
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function(){$("#mainglobalwarningmessage").hide();});
		}
		return retVal;
	}

	$scope.saveToDB = function(){
		if ($scope.currentConfiguration == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './configurationview/configuration.php',
				data: $.param({'configuration' : $scope.currentConfiguration, 'type' : 'updateEntireConfiguration' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					// Select this configuration to reset everything
					$scope.setCurrentInternal($scope.selectedLeftObj, null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	$scope.addConfigurationToDB = function(){
		$http({
			method: 'post',
			url: './configurationview/configuration.php',
			data: $.param({'configuration' : $scope.newConfiguration, 'type' : 'insert_configuration' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success){
				var newConfiguration = {id:data.id, code:$scope.newConfiguration.code, type:$scope.newConfiguration.type};
				$scope.leftobjs.push(newConfiguration);
				// We could sort the list....
				$scope.setCurrentInternal(newConfiguration);
				return true;
			} else {
				dialogService.displayFailure(data);
				return false;
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	// This is the function that creates the modal to create new configuration
	$scope.createNew = function(confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNewConfiguration, null, true, null);
		} else {
			$scope.newConfiguration = {};
			// Send the newConfiguration to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'configurationview/newconfiguration.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: null,
					backdrop: 'static',
					resolve: {
						newObj: function() {
							return $scope.newConfiguration;
						}
					}
			})
			.result.then(function(newConfiguration) {
					// User clicked OK and everything was valid.
					$scope.newConfiguration = newConfiguration;
					if ($scope.addConfigurationToDB() == true) {
					}
			}, function() {
					// User clicked CANCEL.
//	        alert('canceled');
			});
		}
	};

	$scope.previewReport = function(language) {
		$window.open('./reports/preview.php?language='+language);
	}

	$scope.uploadMainImage = function(file, errFiles) {
			$scope.f = file;
			$scope.errFile = errFiles && errFiles[0];
			if (file) {
				if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
					dialogService.alertDlg('only jpg files are allowed.');
					return;
				}
					file.upload = Upload.upload({
							url: '../backend/changeMainImage.php',
							method: 'POST',
							file: file,
							// data: {
							//     'awesomeThings': $scope.awesomeThings,
							//     'targetPath' : '/media/'
							// }
					});
					file.upload.then(function(data) {
							$timeout(function() {
								if (data.data.success) {
									dialogService.alertDlg($scope.translationObj.details.msgmainimagechanged);
								} else {
									dialogService.displayFailure(data.data);
								}
							});
					}, function(data) {
							if (!data.success) {
								dialogService.displayFailure(data.data);
							}
					}, function(evt) {
							file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
					});
			}
	}

	$scope.uploadLogo = function(file, errFiles) {
			$scope.f = file;
			$scope.errFile = errFiles && errFiles[0];
			if (file) {
				if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
					dialogService.alertDlg('only jpg files are allowed.');
					return;
				}
					file.upload = Upload.upload({
							url: '../backend/changelogo.php',
							method: 'POST',
							file: file,
							// data: {
							//     'awesomeThings': $scope.awesomeThings,
							//     'targetPath' : '/media/'
							// }
					});
					file.upload.then(function(data) {
							$timeout(function() {
								if (data.data.success) {
									dialogService.alertDlg($scope.translationObj.details.msglogochanged);
								} else {
									dialogService.displayFailure(data.data);
								}
							});
					}, function(data) {
							if (!data.success) {
								dialogService.displayFailure(data.data);
							}
					}, function(evt) {
							file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
					});
			}
	}

	$scope.sendTestEmail = function() {
		// If sheet exists, add it to the email generation. If not, leave null.
		$scope.promise = $http({
			method: 'post',
			url: './configurationview/configuration.php',
			data: $.param({'emailaddress' : $scope.currentConfiguration.smtptestemailaddress,'type' : 'sendTestEmail' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				dialogService.alertDlg($scope.translationObj.main.msgerremailsent);
				return;
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	}

	$scope.testPaypal = function() {
		$scope.promise = $http({
			method: 'post',
			url: '../../backend/paypal.php',
			data: $.param({'returnurl' :  window.location.href,'type' : 'testPaypal' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				$window.location=data.purchase.redirecturl;
				dialogService.alertDlg($scope.translationObj.main.msgerremailsent);
				return;
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	}

	$scope.importSCRegistrations = function(file, errFiles) {
		$scope.f = file;
		$scope.errFile = errFiles && errFiles[0];
		if (file) {
			if (file.name.indexOf('.csv') === -1) {
				dialogService.alertDlg('only csv files are allowed.');
				return;
			}
			$scope.promise = file.upload = Upload.upload({
				url: './configurationview/importscregistrations.php',
				method: 'POST',
				file: file,
			});
			file.upload.then(function(data) {
				if (data.data.success) {
					$scope.promise = reportingService.createAndDisplayReport("scregistrationimport.php", JSON.stringify({'data':data.data, 'language':authenticationService.getCurrentLanguage()}));
				} else {
					dialogService.displayFailure(data.data);
				}
			}, function(data) {
				if (!data.success) {
					dialogService.displayFailure(data.data);
				}
			}, function(evt) {
				file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
			});
		}
	}

	$scope.importSCTests = function(file, errFiles) {
		$scope.f = file;
		$scope.errFile = errFiles && errFiles[0];
		if (file) {
			if (file.name.indexOf('.csv') === -1) {
				dialogService.alertDlg('only csv files are allowed.');
				return;
			}
			$scope.promise = file.upload = Upload.upload({
				url: './configurationview/importsctests.php',
				method: 'POST',
				file: file,
			});
			file.upload.then(function(data) {
				if (data.data.success) {
					// $scope.promise = reportingService.createAndDisplayReport("scregistrationimport.php", JSON.stringify({'data':data.data, 'language':authenticationService.getCurrentLanguage()}));
				} else {
					dialogService.displayFailure(data.data);
				}
			}, function(data) {
				if (!data.success) {
					dialogService.displayFailure(data.data);
				}
			}, function(evt) {
				file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
			});
		}
	}

	$scope.refreshAll = function() {
		if ($scope.token != null) {
			if ($scope.paymentId == null) {
				alert("Operation cancelled");
				// window.location = window.location.pathname;
				// Reload page without the query parameter
				window.location = window.location.href.split("?")[0];
			} else {
				// paymentId is defined, we need to complete the purchase
				$scope.promise = $http({
					method: 'post',
					url: './core/directives/paypalcheckout/paypal.php',
					data: $.param({'payerid' : $scope.payerId , 'paymentid' : $scope.paymentId, 'type' : 'completePurchase' }),
					headers: {'Content-Type': 'application/x-www-form-urlencoded'}
				}).
				success(function(data, status, headers, config) {
					if (data.success) {
						// $window.location=data.purchase.redirecturl;
						dialogService.alertDlg($scope.translationObj.paypal.msgtransactioncompleted);
						window.location = window.location.href.split("?")[0];
						return;
					} else {
						dialogService.displayFailure(data.detail);
						window.location = window.location.href.split("?")[0];
					}
				}).
				error(function(data, status, headers, config) {
					dialogService.displayFailure(data.detail);
					window.location = window.location.href.split("?")[0];
				});
		

			}
		}
		// $scope.getAllConfigurations();
		$scope.setCurrentInternal({id:1})
		// anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'configurationtypes', 'text', 'configurationtypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'smtpdebuglevels', 'sequence', 'smtpdebuglevels');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'smtpdebugformats', 'sequence', 'smtpdebugformats');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'smtpsecureformats', 'sequence', 'smtpsecureformats');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'authorizationtypes', 'sequence', 'authorizationtypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'authorizationproviders', 'sequence', 'authorizationproviders');
		translationService.getTranslation($scope, 'configurationview', authenticationService.getCurrentLanguage());
	}

	$scope.refreshAll();
}]);
