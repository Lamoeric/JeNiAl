'use strict';

angular.module('cpa_admin.testregistrationview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/testregistrationview', {
		templateUrl: 'testregistrationview/testregistrationview.html',
		controller: 'testregistrationviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          if (userInfo.privileges.testregistration_access==true) {
            return $q.when(userInfo);
          } else {
            return $q.reject({authenticated: true, validRights: false, newLocation:null});
          }
        } else {
          return $q.reject({authenticated: false, newLocation: "/testregistrationview"});
        }
      }
		}
	});
}])

.controller('testregistrationviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'parseISOdateService','anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, parseISOdateService, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "testregistrationView";
	$scope.currentTestregistration = null;
	$scope.selectedLeftObj = null;
	$scope.newTestregistration = null;
	$scope.currentTestsession = {};
	$scope.selectedIndex = null;
	$scope.isFormPristine = true;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
		$scope.recalculate();
	};

	$scope.setPristine = function() {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllTestregistrations = function (testssessionsid) {
		$scope.promise = $http({
				method: 'post',
				url: './testregistrationview/testregistration.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'testssessionsid' : testssessionsid, 'type' : 'getAllTestregistrations' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.leftobjs = data.data;
				} else {
					$scope.leftobjs = [];
				}
				$rootScope.repositionLeftColumn();
			} else {
				if (!data.success) {
					dialogService.displayFailure(data);
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.getTestregistrationDetails = function(testregistration) {
		$scope.promise = $http({
			method: 'post',
			url: './testregistrationview/testregistration.php',
			data: $.param({'id' : testregistration.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getTestregistrationDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data) ) {
				$scope.currentTestregistration = data.data[0];
				$scope.calculateTotal();
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.getTestSessionDetails = function() {
		$scope.promise = $http({
			method: 'post',
			url: './testregistrationview/testregistration.php',
			data: $.param({'id' : $scope.currentTestsession.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getTestSessionDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data) ) {
				$scope.currentTestsession = data.data[0];
				// Get the date of the last day, after this date, close the registration entirely
				$scope.currentTestsession.closedate = null;
				if ($scope.currentTestsession.periods && $scope.currentTestsession.periods.length != 0) {
					// $scope.currentTestsession.closedate = new Date($scope.currentTestsession.periods[$scope.currentTestsession.periods.length-1].testdate + " 23:59:59");
					$scope.currentTestsession.closedate = parseISOdateService.parseDate($scope.currentTestsession.periods[$scope.currentTestsession.periods.length-1].testdate + " 23:59:59");
				}
				// $scope.currentTestsession.registrationstartdate = new Date($scope.currentTestsession.registrationstartdate + " 00:00:00");
				$scope.currentTestsession.registrationstartdate = parseISOdateService.parseDateWithoutTime($scope.currentTestsession.registrationstartdate);
				// $scope.currentTestsession.registrationenddate   = new Date($scope.currentTestsession.registrationenddate + " 23:59:59");
				$scope.currentTestsession.registrationenddate   = parseISOdateService.parseDate($scope.currentTestsession.registrationenddate + " 23:59:59");
				// $scope.currentTestsession.cancellationenddate   = new Date($scope.currentTestsession.cancellationenddate + " 23:59:59");
				$scope.currentTestsession.cancellationenddate   = parseISOdateService.parseDate($scope.currentTestsession.cancellationenddate + " 23:59:59");
				$scope.currentTestsession.isClosed 							= false;		// Session is closed, i.e. today is passed the day of the last period
				$scope.currentTestsession.canRegister 					= false;		// User can register test in the session
				$scope.currentTestsession.canUnregister 				= false;		// User can delete the test registration
				$scope.currentTestsession.canCancelRegistration = false;		// User can only cancel the test registration, not delete it
				$scope.today = new Date();
				if ($scope.currentTestsession.closedate && $scope.today > $scope.currentTestsession.closedate) {
					$scope.currentTestsession.isClosed = true;
				}
				if ($scope.today >= $scope.currentTestsession.registrationstartdate && $scope.today <= $scope.currentTestsession.registrationenddate) {
					$scope.currentTestsession.canRegister = true;
				}
				if ($scope.today > $scope.currentTestsession.registrationstartdate && $scope.today <= $scope.currentTestsession.cancellationenddate) {
					$scope.currentTestsession.canUnregister = true;
				}
				if ($scope.today > $scope.currentTestsession.cancellationenddate && ($scope.currentTestsession.closedate && $scope.today <= $scope.currentTestsession.closedate)) {
					$scope.currentTestsession.canCancelRegistration = true;
				}
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (testregistration, index) {
		if (testregistration != null) {
			$scope.selectedLeftObj = testregistration;
			$scope.getTestregistrationDetails(testregistration);
			$scope.selectedIndex = index;
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentTestregistration = null;
			$scope.selectedIndex = null;
			$scope.setPristine();
		}
	}

	$scope.setCurrent = function (testregistration, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, testregistration, index);
		} else {
			$scope.setCurrentInternal(testregistration, index);
		}
	};

	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentTestregistration != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgconfirmdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './testregistrationview/testregistration.php',
				data: $.param({'testregistration' : $scope.currentTestregistration, 'type' : 'delete_testregistration' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
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

		if ($scope.currentTestregistration.tests.length != 0) {
			var testValid = false;
			for (var i = 0; i < $scope.currentTestregistration.tests.length; i++) {
				if ($scope.currentTestregistration.tests[i].status != 'Deleted') {
					testValid = true;
				}
			}
			if (!testValid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrnomoretestforskater);
			}
		}

		if ($scope.globalErrorMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function() {$("#mainglobalerrormessage").hide();});
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function() {$("#mainglobalwarningmessage").hide();});
		}
		return retVal;
	}

	$scope.saveToDB = function() {
		if ($scope.currentTestregistration == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './testregistrationview/testregistration.php',
				data: $.param({'testregistration' : $scope.currentTestregistration, 'type' : 'updateEntireTestregistration' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this testregistration to reset everything
					$scope.setCurrentInternal($scope.selectedLeftObj, $scope.selectedIndex);
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

	$scope.addTestregistrationToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './testregistrationview/testregistration.php',
			data: $.param({'testregistration' : $scope.newTestregistration, 'type' : 'insert_testregistration' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				$scope.newTestregistration.extskater = data.extskater;
				var newTestregistration = {id:data.id, memberid:$scope.newTestregistration.member.id, firstname:$scope.newTestregistration.member.firstname, lastname:$scope.newTestregistration.member.lastname};
				$scope.leftobjs.push(newTestregistration);
				// We could sort the list....
				$scope.setCurrentInternal(newTestregistration);
				return true;
			} else {
				if (data.message.indexOf("23000") != -1) {
					dialogService.alertDlg($scope.translationObj.main.msgerrduplicatekey);
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	// This is the function that creates the modal to create new testregistration
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newTestregistration = {};
			$scope.newTestregistration.testssessionsid = $scope.currentTestsession.id;
			// Send the newTestregistration to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'testregistrationview/newtestregistration.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newTestregistration;
						}
					}
			})
			.result.then(function(newTestregistration) {
				// User clicked OK and everything was valid.
				$scope.newTestregistration = newTestregistration;
				// $scope.newTestregistration.extskater = 0;
				$scope.addTestregistrationToDB();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// This is the function that creates the modal to create/edit test
	$scope.editTest = function(newTest) {
		$scope.newTest = {};
		// Keep a pointer to the current test
		$scope.currentTest = newTest;
		// Copy in another object
		angular.copy(newTest, $scope.newTest);
		// Get the values for the testsid drop down list
		if (newTest.testtype) {
			listsService.getAllTests($scope, newTest.testtype, "allTestsByType", authenticationService.getCurrentLanguage());
			if (newTest.testtype == 'DANCE' && newTest.testsid) {
				listsService.getDanceMusics($scope, newTest.testsid, authenticationService.getCurrentLanguage());
			}
		} else {
			$scope.allTestsByType = null;
		}
		// Send the newTest to the modal form  
		$uibModal.open({
				animation: false,
				templateUrl: 'testregistrationview/newtest.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newTest;
					}
				}
			})
			.result.then(function(newTest) {
				// User clicked OK and everything was valid.
				angular.copy(newTest, $scope.currentTest);
				// If already saved in DB
				if ($scope.currentTest.id != null) {
					$scope.currentTest.status = 'Modified';
				} else {
					$scope.currentTest.status = 'New';
					if ($scope.currentTestregistration.tests == null) $scope.currentTestregistration.tests = [];
					// Don't insert twice in list
					if ($scope.currentTestregistration.tests.indexOf($scope.currentTest) == -1) {
						$scope.currentTestregistration.tests.push($scope.currentTest);
					}
				}
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	$scope.displayTestSessionDetails = function() {
		$uibModal.open({
				animation: false,
				templateUrl: 'testregistrationview/testsessiondetails.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						// return $scope.newTest;
					}
				}
			})
			.result.then(function(newTest) {
				// User clicked OK and everything was valid.
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	}

	$scope.onTestTypeChange = function(newObj) {
		listsService.getAllTestsForMember($scope, newObj.testtype, $scope.currentTestregistration.memberid, "allTestsByType", authenticationService.getCurrentLanguage());
		newObj.testsid = null;
		if (newObj.testtype != 'DANCE') {
			newObj.partnerid = null;
			newObj.musicid = null;
			newObj.partnersteps = null;
		}
	}

	$scope.onTestChange = function(newObj) {
		if (newObj.testtype == 'DANCE') {
			listsService.getDanceMusics($scope, newObj.testsid, authenticationService.getCurrentLanguage());
			newObj.musicid = null;
		}
	}

	$scope.onTestSessionChange = function() {
		if ($scope.currentTestsession.id != null) {
				$scope.getAllTestregistrations($scope.currentTestsession.id);
				$scope.getTestSessionDetails();
				$scope.selectedLeftObj = null;
				$scope.currentTestregistration = null;
				$scope.selectedIndex = null;
		}
	}

	$scope.getChargeAmount = function(chargecode) {
		var retVal = 0;
		for (var x = 0;  x < $scope.currentTestsession.charges.length; x++) {
			if ($scope.currentTestsession.charges[x].chargecode == chargecode) {
				retVal = $scope.currentTestsession.charges[x].amount/1;
				break;
			}
		}
		return retVal;
	}


	$scope.recalculate = function() {
		var feeAmount = $scope.getChargeAmount('TEST');
		var extrafeeAmount = $scope.getChargeAmount('EXTS');
		var nbOfTest = 0;

		// Count the number of "not deleted" tests
		for (var i = 0; i < $scope.currentTestregistration.tests.length; i++) {
			if ($scope.currentTestregistration.tests[i].status != 'Deleted') {
				nbOfTest++;
			}
		}
		$scope.currentTestregistration.fees = nbOfTest * feeAmount;
		if ($scope.currentTestregistration.extskater == 1) {
			if ($scope.currentTestsession.extrafeesoption == 0) { // Once per registration
				$scope.currentTestregistration.extrafees = extrafeeAmount;
			} else {
				$scope.currentTestregistration.extrafees = extrafeeAmount * nbOfTest;
			}
		}
		$scope.calculateTotal();
	}

	$scope.calculateTotal = function() {
		$scope.currentTestregistration.total = $scope.currentTestregistration.fees/1 + $scope.currentTestregistration.extrafees/1;
	}

	$scope.refreshAll = function() {
		listsService.getAllTestSessions($scope, authenticationService.getCurrentLanguage())
		.then(function() {
			if (!$scope.currentTestsession.id && $scope.allTestsSessions && $scope.allTestsSessions.length != 0) {
				$scope.currentTestsession.id = $scope.allTestsSessions[0].id;
				$scope.getAllTestregistrations($scope.currentTestsession.id);
				$scope.getTestSessionDetails();
			}
		});
		if ($scope.currentTestsession.id != null) {
				$scope.getAllTestregistrations($scope.currentTestsession.id);
				$scope.getTestSessionDetails();
		}
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testtypes', 'text', 'testtypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'extrafeesoptions', 'sequence', 'extrafeesoptions');
		translationService.getTranslation($scope, 'testregistrationview', authenticationService.getCurrentLanguage());
		listsService.getCoaches($scope, authenticationService.getCurrentLanguage());
		listsService.getAllTestsEx($scope, authenticationService.getCurrentLanguage());
		listsService.getPartners($scope, authenticationService.getCurrentLanguage());
		listsService.getAllDanceMusics($scope, authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
