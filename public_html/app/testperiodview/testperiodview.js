'use strict';

angular.module('cpa_admin.testperiodview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/testperiodview', {
		templateUrl: 'testperiodview/testperiodview.html',
		controller: 'testperiodviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          if (userInfo.privileges.admin_access==true) {
            return $q.when(userInfo);
          } else {
            return $q.reject({authenticated: true, validRights: false, newLocation:null});
          }
        } else {
          return $q.reject({authenticated: false, newLocation: "/testperiodview"});
        }
      }
		}
	})
	.when('/testperiodview/testsessionid/:testsessionid/testperiodid/:testperiodid', {
		templateUrl: 'testperiodview/testperiodview.html',
		controller: 'testperiodviewCtrl',
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

.controller('testperiodviewCtrl', ['$scope', '$http', '$uibModal', '$route', 'listsService', 'anycodesService', 'dialogService', 'authenticationService', 'translationService', function($scope, $http, $uibModal, $route, listsService, anycodesService, dialogService, authenticationService, translationService) {

	$scope.progName = "testperiodView";
	$scope.leftpanetemplatefullpath = "./testperiodview/testperiod.template.html";
	$scope.currentTestperiod = null;
	$scope.selectedTestperiod = null;
	$scope.newTestperiod = null;
	$scope.selectedIndex = null;
	$scope.isFormPristine = true;
	$scope.testsessionid = $route.current.params.testsessionid;
	$scope.testperiodid = $route.current.params.testperiodid;

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
		if ($scope.detailsForm) {
			$scope.detailsForm.$setPristine();
			$scope.isFormPristine = true;
		}
	};

	$scope.getTestsessionPeriodDetails = function (testperiod) {
		$scope.promise = $http({
			method: 'post',
			url: './testperiodview/testperiod.php',
			data: $.param({'testsessionid' : testperiod.testsessionid, 'testperiodid' : testperiod.testperiodid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getTestsessionPeriodDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success && !angular.isUndefined(data.data) ){
				$scope.currentTestperiod = data.data[0];
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (testperiod, index) {
		if (testperiod != null) {
			$scope.selectedTestperiod = testperiod;
			$scope.getTestsessionPeriodDetails(testperiod);
			$scope.selectedIndex = index;
			$scope.setPristine();
		} else {
			$scope.selectedTestperiod = null;
			$scope.currentTestperiod = null;
			$scope.selectedIndex = null;
		}
	}

	$scope.setCurrent = function (testperiod, index) {
		$scope.setCurrentInternal(testperiod, index);
	};

	$scope.saveToDB = function(){
		// if ($scope.currentTestperiod == null || !$scope.isDirty()) {
		// 	dialogService.alertDlg("Nothing to save!", null);
		// } else {
			$http({
				method: 'post',
				url: './testperiodview/testperiod.php',
				data: $.param({'testperiod' : $scope.currentTestperiod, 'type' : 'updateEntireTestperiod' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					// Select this testperiod to reset everything
					// NOTE : Maybe this time we don't refresh everything...
					// $scope.setCurrentInternal($scope.selectedTestperiod, $scope.selectedIndex);
					// return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		// }
	};

	// This is the function that creates the modal to select the test period
	$scope.selectTestPeriod = function () {
		$scope.newTestperiod = {};
		// Send the newTestperiod to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'testperiodview/selecttestperiod.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newTestperiod;
					}
				}
		})
		.result.then(function(newTestperiod) {
				// User clicked OK and everything was valid.
				$scope.setCurrentInternal(newTestperiod);
				// $scope.newTestperiod = newTestperiod;
		}, function() {
				// User clicked CANCEL.
//	        alert('canceled');
		});
	};

	$scope.closeAllSkaters = function(group) {
		var drealendtime = new Date();
		var realendtime = drealendtime.toTimeString().substr(0,8);
		for (var x = 0; x < group.skaters.length; x++) {
			if (group.skaters[x].teststatus != 'ENDDED') {
				group.skaters[x].teststatus = 'ENDDED';
				// If skater has a real start time, give him a real end time. If not, leave blank.
				if (group.skaters[x].realstarttime) {
					group.skaters[x].realendtime = realendtime;
				}
				group.skaters[x].status = 'Modified';
			}
		}
	}

	$scope.updateAllGroupTimes = function() {
		var group, starttime, drealwarmupendtime, realwarmupduration, diffMs, diffMins, totalDuration, destimatedendTime, dstartTime, dwarmuprealendtime;
		for (var z = 0; $scope.currentTestperiod.groups && z < $scope.currentTestperiod.groups.length; z++) {
			group = $scope.currentTestperiod.groups[z];
			// Do not change times of ended groups
			if (group.teststatus != 'ENDED') {
				// Find the start time of the current group
				if (group.teststatus == 'STARTED') {
					// Group is started, use the real start time (warmuprealstarttime)
					if (group.warmuprealendtime) {
						// A group that is started and warmup is ended, must evaluate it's estimatedendtime based on the warmuprealendtime + tesduration
						dstartTime = new Date("1980/01/01 " + group.warmuprealendtime);
						totalDuration = group.testduration/1;
					} else {
						// A group that is started, but not ended, must evaluate it's estimatedendtime based on the warmuprealstarttime + warmupduration + tesduration
						dstartTime = new Date("1980/01/01 " + group.warmuprealstarttime);
						totalDuration = group.warmupduration/1 + group.testduration/1;
					}
					destimatedendTime = new Date(dstartTime.getTime() + totalDuration*60000);
					group.estimatedendtime = destimatedendTime.toTimeString().substr(0,8);
				} else {
					// Group is not started. Start time is either the start time of the test period, or the end time (real or estimated) of the previous group.
					if (z == 0) {
						// Group is the first group and is not started, should we stop here ?
						group.estimatedstarttime = $scope.currentTestperiod.starttime;
					} else {
						if ($scope.currentTestperiod.groups[z-1].teststatus == 'ENDDED') {
							group.estimatedstarttime = $scope.currentTestperiod.groups[z-1].realendtime;
						} else {
							group.estimatedstarttime = $scope.currentTestperiod.groups[z-1].estimatedendtime;
						}
					}
					// A group that is not started must evaluate it's estimatedendtime based on the starttime + warmupduration + tesduration
					dstartTime = new Date("1980/01/01 " + group.estimatedstarttime);
					totalDuration = group.warmupduration/1 + group.testduration/1;
					destimatedendTime = new Date(dstartTime.getTime() + totalDuration*60000);
					group.estimatedendtime = destimatedendTime.toTimeString().substr(0,8);
				}
				// if (group.teststatus == 'WARMENDED') {
					// drealwarmupendtime = new Date("1980/01/01 " + group.warmuprealendtime);
					// diffMs = (drealwarmupendtime - dstartTime);
					// diffMins = Math.round(((diffMs % 86400000) % 3600000) / 60000);
					// group.realwarmupduration = diffMins;
				// }

				// dstartTime = new Date("1980/01/01 " + starttime);
				// totalDuration = group.warmupduration/1 + group.testduration/1;
				// destimatedendTime = new Date(dstartTime.getTime() + totalDuration*60000);
				// group.estimatedendtime = destimatedendTime.toTimeString().substr(0,8);
				if (!group.status) group.status = 'Modified';
			}
		}
	}

	$scope.startPeriod = function() {
		if (!$scope.currentTestperiod.realstarttime) {
			var dstarttime = new Date();
			$scope.currentTestperiod.realstarttime = dstarttime.toTimeString().substr(0,8);
			$scope.currentTestperiod.teststatus = 'STARTED';
			$scope.currentTestperiod.status = 'Modified';
		}
	}

	$scope.stopPeriod = function() {
		var isValid = true;
		for (var x = 0; x < $scope.currentTestperiod.groups.length; x++) {
			if ($scope.currentTestperiod.groups[x].teststatus != 'ENDDED') {
				isValid = false;
				break;
			}
		}
		if (isValid) {
			var dendtime = new Date();
			$scope.currentTestperiod.realendtime = dendtime.toTimeString().substr(0,8);
			$scope.currentTestperiod.teststatus = 'ENDED';
			$scope.currentTestperiod.status = 'Modified';

		}
	}

	$scope.startGroupWarmup = function(group) {
		$scope.startPeriod();
		var dstarttime = new Date();
		group.warmuprealstarttime = dstarttime.toTimeString().substr(0,8);
		group.teststatus = 'STARTED';
		$("#"+group.id).collapse('show');
		$scope.updateAllGroupTimes();
		$scope.saveToDB();
	}

	$scope.setGroupEndTime = function(group) {
		var dendtime = new Date();
		group.realendtime = dendtime.toTimeString().substr(0,8);
		group.teststatus = 'ENDDED';
		$("#"+group.id).collapse('hide');
		$scope.closeAllSkaters(group);
		$scope.updateAllGroupTimes();
		$scope.stopPeriod();
		// $scope.$apply();
		$scope.saveToDB();
	}

	$scope.stopGroup = function(group, verbose) {
		var isValid = true;
		for (var x = 0; x < group.skaters.length; x++) {
			if (group.skaters[x].teststatus != 'ENDDED') {
				isValid = false;
				break;
			}
		}

		if (!isValid && verbose) {
			// dialogService.confirmDlg("Not all tests are done for the group. Do you really want to close this group?", "YESNO", $scope.setGroupEndTime, null, group);
			dialogService.confirmYesNo("Not all tests are done for the group. Do you really want to close this group?",
													function (e) {
														if (e) {
															// user clicked "ok"
															$scope.setGroupEndTime(group);
															$scope.$apply();
														}
													}
			);
		} else if (isValid) {
			$scope.setGroupEndTime(group);
		}
	}

	$scope.stopGroupWarmup = function(group) {
		if (!group.warmuprealendtime) {
			group.warmuprealendtime = new Date().toTimeString().substr(0,8);
			$scope.updateAllGroupTimes();
			group.status = 'Modified';
		}
	}

	$scope.startSkaterTest = function(group, skater) {
		$scope.stopGroupWarmup(group);
		var dstarttime = new Date();
		skater.realstarttime = dstarttime.toTimeString().substr(0,8);
		skater.teststatus = 'STARTED';
		skater.status = 'Modified';
		$scope.saveToDB();
	}

	$scope.stopSkaterTest = function(group, skater) {
		var dendtime = new Date();
		skater.realendtime = dendtime.toTimeString().substr(0,8);
		skater.teststatus = 'ENDDED';
		skater.status = 'Modified';
		$scope.stopGroup(group, false);
		$scope.saveToDB();
	}

	$scope.onTestSessionChange = function(newObj) {
		if (newObj.testsessionid != null) {
			newObj.testperiodid = null;
			listsService.getTestPeriodsForSession($scope, newObj.testsessionid, authenticationService.getCurrentLanguage())
		}
	}

	$scope.refreshAll = function() {
		listsService.getAllTestSessions($scope, authenticationService.getCurrentLanguage())
		// $scope.getAllTestperiods();
		// anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testperiodtypes', 'text', 'testperiodtypes');
		translationService.getTranslation($scope, 'testperiodview', authenticationService.getCurrentLanguage());
		if ($route.current.params.testsessionid) {
			$scope.setCurrentInternal($route.current.params);
		} else {
			$scope.selectTestPeriod();
		}
	}

	$scope.refreshAll();
}]);
