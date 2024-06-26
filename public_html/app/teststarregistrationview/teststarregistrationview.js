'use strict';

angular.module('cpa_admin.teststarregistrationview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/teststarregistrationview', {
    templateUrl: 'teststarregistrationview/teststarregistrationview.html',
    controller: 'teststarregistrationviewCtrl',
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
          return $q.reject({authenticated: false, newLocation: "/teststarregistrationview"});
        }
      }
    }
  });
}])

.controller('teststarregistrationviewCtrl', ['$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', 'arenaService', 'parseISOdateService', 'dateFilter', function($scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService, arenaService, parseISOdateService, dateFilter) {

	$scope.progName = "teststarregistrationview";
	$scope.leftpanetemplatefullpath = "./teststarregistrationview/arena.template.html";
	$scope.currentTestregistration = {};
	$scope.selectedTestregistration = null;
	$scope.newTestregistration = null;
  $scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
  $scope.globalErrorMessage = [];
	$scope.globalWarningMessage = [];
  $scope.approbationStatusFilter = -1;

	// For delay display
	$scope.delay = 0;
	$scope.minDuration = 0;
	$scope.message = 'Please Wait...';
	$scope.backdrop = true;
	$scope.promise = null;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function() {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllPeriods = function () {
		$scope.promise = $http({
	      method: 'post',
	      url: './teststarregistrationview/teststarregistrationview.php',
	      data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllPeriods' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
        if (!angular.isUndefined(data.data)) {
          $scope.currentTestregistration.periods = data.data;
        } else {
          $scope.currentTestregistration.periods = [];
        }
        $scope.setPristine();
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

	$scope.saveToDB = function() {
    $scope.promise = $http({
      method: 'post',
      url: './teststarregistrationview/teststarregistrationview.php',
      data: $.param({'registration' : $scope.currentTestregistration, 'userid' : authenticationService.getUserInfo().userid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'updateEntireRegistration' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
        // Re-read everything
        $scope.getAllPeriods();
				return true;
    	} else {
				dialogService.displayFailure(data);
        $scope.getAllPeriods();
				return false;
    	}
    }).
    error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
    });
	};

//   $scope.editRegistration = function(period, newRegistration) {
//     var coachid = ($scope.newRegistration ? $scope.newRegistration.coachid : null);
//     var member = ($scope.newRegistration ? $scope.newRegistration.member : null);
//     $scope.canRegister = false;
//     $scope.newRegistration = {};
// 		// Keep a pointer to the current registration
//     if (newRegistration && newRegistration.id) {
//       $scope.currentRegistration = newRegistration;
//       // Copy in another object
//   		angular.copy(newRegistration, $scope.newRegistration);
//       $scope.newRegistration.period = period;
//       listsService.getAllStarTestsForMember($scope, $scope.newRegistration.testtype, $scope.newRegistration.memberid, "allStarTestsByType", authenticationService.getCurrentLanguage());
//       listsService.getDanceMusics($scope, $scope.newRegistration.testid, authenticationService.getCurrentLanguage());
//     } else {
//       $scope.currentRegistration = {};
//       // This is a new registration, put back the coachid and the member object to save time
//       $scope.newRegistration.period = period;
//       $scope.newRegistration.coachid = coachid;
//       $scope.newRegistration.member = member;
//       $scope.newRegistration.newtestssessionsperiodsid = period.id;
//       $scope.newRegistration.newtestssessionsid = period.newtestssessionsid;
//       $scope.newRegistration.approbationstatus = "2"; // Approbation Pending
//     }
// 		$uibModal.open({
// 				animation: false,
// 				templateUrl: 'teststarregistrationview/newregistration.template.html',
// 				controller: 'childeditor.controller',
// 				scope: $scope,
// 				size: 'md',
// 				backdrop: 'static',
// 				resolve: {
// 					newObj: function() {
// 						return $scope.newRegistration;
// 					}
// 				}
// 			})
// 			.result.then(function(newRegistration) {
// 				// User clicked OK and everything was valid.
// 				angular.copy(newRegistration, $scope.currentRegistration);
//         if (($scope.currentRegistration.approbationstatus == 1 || $scope.currentRegistration.approbationstatus == 0) && (!$scope.currentRegistration.approvedby || $scope.currentRegistration.approvedby == '')) {
//           var userInfo = authenticationService.getUserInfo();
//           $scope.currentRegistration.approvedby = userInfo.userid;
//           $scope.currentRegistration.approvedon = new Date();
//           $scope.currentRegistration.approvedonstr = dateFilter($scope.currentRegistration.approvedon, 'yyyy-MM-dd HH:mm:ss');
//         }
// 				// If already saved in DB
// 				if ($scope.currentRegistration.id != null) {
// 					$scope.currentRegistration.status = 'Modified';
// 				} else {
// 					$scope.currentRegistration.status = 'New';
// 					if (period.registrations == null) period.registrations = [];
// 					// Don't insert twice in list
// 					if (period.registrations.indexOf($scope.currentRegistration) == -1) {
// 						period.registrations.push($scope.currentRegistration);
// 					}
// 				}
//         $scope.saveToDB();
// 				// $scope.setDirty();
// 			}, function() {
// 					// User clicked CANCEL.
// //	        alert('canceled');
// 		});
//   }
//
//   $scope.onTestTypeChange = function(newObj) {
// 		listsService.getAllStarTestsForMember($scope, newObj.testtype, newObj.member.id, "allStarTestsByType", authenticationService.getCurrentLanguage());
// 		newObj.testsid = null;
// 		if (newObj.testtype != 'DANCE') {
// 			newObj.partnerid = null;
// 			newObj.musicid = null;
// 			newObj.partnersteps = null;
// 		}
// 	}
//
//   $scope.onTestChange = function(newObj) {
// 		if (newObj.testtype == 'DANCE') {
// 			listsService.getDanceMusics($scope, newObj.testid, authenticationService.getCurrentLanguage());
// 			newObj.musicid = null;
// 		}
// 	}
//
//   $scope.canEditRegistration = function(registration, fieldname) {
//     var retVal = false;
//     var userInfo = authenticationService.getUserInfo();
//     // If registration is approved, do not allow edition. Only cancel.
//     if (registration.approbationstatus == 1 && ($scope.currentRegistration && $scope.currentRegistration.approbationstatus == registration.approbationstatus) && fieldname != 'canceled') {
//       return false;
//     }
//     // If registration is an existing one
//     if (registration.id) {
//       if (!registration.period.canedit) {
//         retVal = false;
//       } else {
//         if (registration.createdby == authenticationService.getUserInfo().userid) {
//           retVal =  true;
//         }
//       }
//     } else {
//       // New registration - all fields are editable
//       retVal = true;
//     }
//     // TODO : should we allow test director to edit the whole registration?
//     if (fieldname == 'approbationstatus') {
//       if (userInfo && userInfo.privileges.testregistration_confirm == true) {
//         retVal =  true;
//       } else {
//         retVal = false;
//       }
//     }
//     return retVal;
//   }
//
  $scope.onApprobationStatusFilterChange = function(newValue) {
    $scope.approbationStatusFilter = newValue/1;
  }

  $scope.filterRegistrations = function(obj) {
    if ($scope.approbationStatusFilter == -1) return true;
    if (obj.approbationstatus/1 == $scope.approbationStatusFilter/1) {
      return true;
    }
    return false;
  }

  // Periods can be displayed based on the perioddate.
  // Periods can have new registration based on nbofdaysprior
  // For example, if test is december 10th and nbofdaysprior = 3, last day of registration is december 7 (3 days before i.e 10th, 9th and 8th).
  // This is done so that the value 0 means the registration can be done the same day of the test.
  $scope.filterPeriods = function(obj) {
    var day = 60 * 60 * 24 * 1000 * obj.nbofdaysprior/1;
    var perioddate = parseISOdateService.parseDateWithoutTime(obj.perioddate);
		var realDate = new Date(perioddate.getTime() - day);
    var today = new Date();
    today.setHours(0,0,0,0);

    // -1 is a special case when we allow registrations to be done in the past
    if (obj.nbofdaysprior == -1) {
      obj.canedit = true;
      return true;
    }
    // if (realDate >= today) {
    if (today <= realDate) {
      obj.canedit = true;
    } else {
      obj.canedit = false;
    }
    if (perioddate >= today) {
      return true;
    } else {
      return false;
    }
    return false;
  }

	$scope.refreshAll = function() {
		$scope.getAllPeriods();
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'startesttypes', 'text', 'startesttypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testresults', 'sequence', 'testresults');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'days', 'sequence', 'days');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'approbationstatus', 'sequence', 'approbationstatus');
    listsService.getCoaches($scope, authenticationService.getCurrentLanguage());
		listsService.getAllCharges($scope, authenticationService.getCurrentLanguage());
		listsService.getPartners($scope, authenticationService.getCurrentLanguage());
		listsService.getAllTestsEx($scope, authenticationService.getCurrentLanguage());
		listsService.getAllDanceMusics($scope, authenticationService.getCurrentLanguage());
    arenaService.getAllArenas($scope, authenticationService.getCurrentLanguage());
    translationService.getTranslation($scope, 'teststarregistrationview', authenticationService.getCurrentLanguage());
	}

	$scope.refreshAll();
}]);
