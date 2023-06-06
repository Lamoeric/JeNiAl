'use strict';

angular.module('cpa_admin.testperiodfollowview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/testperiodfollowview', {
		templateUrl: 'testperiodfollowview/testperiodfollowview.html',
		controller: 'testperiodfollowviewCtrl'
		// Anyone can follow the test period
	})
	.when('/testperiodfollowview/testsessionid/:testsessionid/testperiodid/:testperiodid', {
		templateUrl: 'testperiodfollowview/testperiodfollowview.html',
		controller: 'testperiodfollowviewCtrl'
		// Anyone can follow the test period
	});
;
}])

.controller('testperiodfollowviewCtrl', ['$scope', '$http', '$uibModal', '$route', '$interval', 'listsService', 'anycodesService', 'dialogService', 'authenticationService', 'translationService', function($scope, $http, $uibModal, $route, $interval, listsService, anycodesService, dialogService, authenticationService, translationService) {

	$scope.progName = "testperiodfollowView";
	$scope.leftpanetemplatefullpath = "./testperiodfollowview/testperiodfollow.template.html";
	$scope.currentTestperiod = null;
	$scope.selectedTestperiod = null;
	$scope.newTestperiod = null;
	$scope.isFormPristine = true;
	$scope.testsessionid = $route.current.params.testsessionid;
	$scope.testperiodid = $route.current.params.testperiodid;

	// For delay display
	$scope.delay = 0;
	$scope.minDuration = 0;
	$scope.message = 'Please Wait...';
	$scope.backdrop = true;
	$scope.promise = null;
	var refresh;

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

	$scope.getTestsessionPeriodDetails = function (testperiod) {
		$scope.promise = $http({
			method: 'post',
			url: './testperiodfollowview/testperiodfollow.php',
			data: $.param({'testsessionid' : testperiod.testsessionid, 'testperiodid' : testperiod.testperiodid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getTestsessionPeriodDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success && !angular.isUndefined(data.data) ){
				$scope.currentTestperiod = data.data[0];
				$scope.updateAllGroupTimes();
				if ($scope.currentTestperiod.groups.length > 0) {
					$("#"+$scope.currentTestperiod.groups[0].id).collapse('show');
				}
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
			$scope.startRefresh();
		} else {
			$scope.selectedTestperiod = null;
			$scope.currentTestperiod = null;
		}
	}

	$scope.setCurrent = function (testperiod, index) {
		$scope.setCurrentInternal(testperiod, index);
	};

	// This is the function that creates the modal to select the test period
	$scope.selectTestPeriod = function () {
		$scope.newTestperiod = {};
		// Send the newTestperiod to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'testperiodfollowview/selecttestperiod.template.html',
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

	$scope.startRefresh = function() {
		// Don't start a new interval if one exists
		if (angular.isDefined(refresh)) return;
		refresh = $interval(function() {
			if ($route.current.params.testsessionid) {
				$scope.getTestsessionPeriodDetails($route.current.params);
			} else if ($scope.selectedTestperiod != null) {
				$scope.getTestsessionPeriodDetails($scope.selectedTestperiod);
			}
		}, 5000);
	};


	$scope.stopRefresh = function() {
		if (angular.isDefined(refresh)) {
			$interval.cancel(refresh);
			refresh = undefined;
		}
	};

	$scope.onTestSessionChange = function(newObj) {
		if (newObj.testsessionid != null) {
			newObj.testperiodid = null;
			listsService.getTestPeriodsForSession($scope, newObj.testsessionid, authenticationService.getCurrentLanguage())
		}
	}

	$scope.$on('$destroy', function() {
		 // Make sure that the interval is destroyed too
		 $scope.stopRefresh();
	 });

	$scope.refreshAll = function() {
		listsService.getAllTestSessions($scope, authenticationService.getCurrentLanguage())
		translationService.getTranslation($scope, 'testperiodfollowview', authenticationService.getCurrentLanguage());
		if ($route.current.params.testsessionid) {
			$scope.getTestsessionPeriodDetails($route.current.params);
			$scope.startRefresh();
		} else {
			if ($scope.selectedTestperiod != null) {
				$scope.getTestsessionPeriodDetails($scope.selectedTestperiod);
				$scope.startRefresh();
			} else {
				$scope.selectTestPeriod();
			}
		}
	}

	$scope.refreshAll();
}]);
