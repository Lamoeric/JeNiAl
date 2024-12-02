'use strict';

angular.module('cpa_admin.testsessionview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/testsessionview', {
		templateUrl: 'testsessionview/testsessionview.html',
		controller: 'testsessionviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.testsession_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/testsessionview"});
				}
			}
		}
	});
}])

.controller('testsessionviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$window', 'dateFilter', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', 'arenaService', 'parseISOdateService', function($rootScope, $scope, $http, $uibModal, $window, dateFilter, anycodesService, dialogService, listsService, authenticationService, translationService, arenaService, parseISOdateService) {

	$scope.progName = "testsessionView";
	$scope.currentTestsession = null;
	$scope.selectedLeftObj = null;
	$scope.newTestsession = null;
	$scope.isFormPristine = true;
	$scope.canCreateGroups = false;					// Permission to create groups. True if today > registration end date. Create groups only when registrations cannot be added.
	$scope.canUpdateTestSession = false;		// Permission to modify test session. Test session cannot be modified if today >= registration start date.

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty || $scope.testsessionForm.$dirty) {
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
		$scope.testsessionForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllTestsessions = function() {
		$scope.promise = $http({
				method: 'post',
				url: './testsessionview/testsession.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllTestsessions' }),
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

	$scope.getTestsessionDetails = function(testsession) {
		$scope.promise = $http({
			method: 'post',
			url: './testsessionview/testsession.php',
			data: $.param({'id' : testsession.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getTestsessionDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentTestsession = data.data[0];
				$scope.currentTestsession.registrationstartdate  = parseISOdateService.parseDateWithoutTime($scope.currentTestsession.registrationstartdate);
				$scope.currentTestsession.registrationenddate    = parseISOdateService.parseDateWithoutTime($scope.currentTestsession.registrationenddate);
				$scope.currentTestsession.cancellationenddate    = parseISOdateService.parseDateWithoutTime($scope.currentTestsession.cancellationenddate);
				for (var x = 0; x < $scope.currentTestsession.days.length; x++) {
					 $scope.currentTestsession.days[x].testdate = parseISOdateService.parseDateWithoutTime($scope.currentTestsession.days[x].testdatestr);
				}
				// Validate if the time is right to create groups
				if (new Date() > $scope.currentTestsession.registrationenddate) {
					$scope.canCreateGroups = true;
				} else {
					$scope.canCreateGroups = false;
				}
				// Validate if the time is right to update the test session
				if (new Date() >= $scope.currentTestsession.registrationstartdate) {
					$scope.canUpdateTestSession = false;
				} else {
					$scope.canUpdateTestSession = true;
				}
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function(testsession, index) {
		if (testsession != null) {
			$scope.selectedLeftObj = testsession;
			$scope.getTestsessionDetails(testsession);
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentTestsession = null;
		}
	}

	$scope.setCurrent = function(testsession, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, testsession, index);
		} else {
			$scope.setCurrentInternal(testsession, index);
		}
	};

	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentTestsession != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './testsessionview/testsession.php',
				data: $.param({'testsession' : $scope.currentTestsession, 'type' : 'delete_testsession' }),
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

		if ($scope.testsessionForm.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}
		if ($scope.currentTestsession.registrationenddate <= $scope.currentTestsession.registrationstartdate) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerrenddategreaterstartdate);
		}

		if ($scope.currentTestsession.cancellationenddate <= $scope.currentTestsession.registrationenddate) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerrcanceldategreaterenddate);
		}

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

	$scope.saveToDB = function() {
		$scope.updateAllGroupTimes();
		if ($scope.currentTestsession == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.currentTestsession.registrationstartdatestr  = dateFilter($scope.currentTestsession.registrationstartdate, 'yyyy-MM-dd');
			$scope.currentTestsession.registrationenddatestr    = dateFilter($scope.currentTestsession.registrationenddate,   'yyyy-MM-dd');
			$scope.currentTestsession.cancellationenddatestr    = dateFilter($scope.currentTestsession.cancellationenddate,   'yyyy-MM-dd');
			for (var x = 0; x < $scope.currentTestsession.days.length; x++) {
				$scope.currentTestsession.days[x].testdatestr = dateFilter($scope.currentTestsession.days[x].testdate, 'yyyy-MM-dd');
			}
			$scope.promise = $http({
				method: 'post',
				url: './testsessionview/testsession.php',
				data: $.param({'testsession' : $scope.currentTestsession, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'updateEntireTestsession' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this testsession to reset everything
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

	$scope.addTestsessionToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './testsessionview/testsession.php',
			data: $.param({'testsession' : $scope.newTestsession, 'type' : 'insert_testsession' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newTestsession = {'id':data.id, 'name':$scope.newTestsession.name};
				$scope.leftobjs.push(newTestsession);
				// We could sort the list....
				$scope.setCurrentInternal(newTestsession);
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

	$scope.createGroups = function(confirmed) {
		if (!confirmed && $scope.currentTestsession.groupCount != 0) {
			// dialogService.confirmDlg("Groups were already created. Creating the groups will delete all your modifications. Are you sure you want to recreate the groups?", "YESNO", $scope.createGroups, null, true, null);
			dialogService.confirmDlg($scope.translationObj.main.msgcreategroups, "YESNO", $scope.createGroups, null, true, null);
		} else {
			$http({
				method: 'post',
				url: './testsessionview/testsession.php',
				data: $.param({'testsession' : $scope.currentTestsession, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'createGroups' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					$scope.setCurrentInternal($scope.currentTestsession);
				} else {
					dialogService.displayFailure(data);
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
			});
		}
	};

	// This is the function that creates the modal to create new testsession
	$scope.createNew = function(confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newTestsession = {};
			// Send the newTestsession to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'testsessionview/newtestsession.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
						newObj: function() {
							return $scope.newTestsession;
						}
					}
			})
			.result.then(function(newTestsession) {
					// User clicked OK and everything was valid.
					$scope.newTestsession = newTestsession;
					if ($scope.addTestsessionToDB() == true) {
					}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// This is the function that creates the modal to create/edit days
	$scope.editTestSessionDay = function(newDay) {
		$scope.newDay = {};
		// Keep a pointer to the current test
		$scope.currentDay = newDay;
		// Copy in another object
		angular.copy(newDay, $scope.newDay);
			// Send the newDay to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'testsessionview/newday.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function() {
						return $scope.newDay;
					}
				}
			})
			.result.then(function(newDay) {
				// User clicked OK and everything was valid.
				angular.copy(newDay, $scope.currentDay);
				// If already saved in DB
				if ($scope.currentDay.id != null) {
					$scope.currentDay.status = 'Modified';
				} else {
					$scope.currentDay.status = 'New';
					if ($scope.currentTestsession.days == null) $scope.currentTestregistration.days = [];
					// Don't insert twice in list
					if ($scope.currentTestsession.days.indexOf($scope.currentDay) == -1) {
						$scope.currentTestsession.days.push($scope.currentDay);
					}
				}
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit periods
	$scope.editTestSessionDayPeriod = function(newPeriod, day) {
		// if (!day.id) {
		//   dialogService.alertDlg("Please save before adding periods.");
		//   return;
		// }
		$scope.day = day;
		$scope.newPeriod = {};
		// Keep a pointer to the current test
		$scope.currentPeriod = newPeriod;
		// Copy in another object
		angular.copy(newPeriod, $scope.newPeriod);
		// Get the values for the judges drop down list
		// Get the values for the ice drop down list
		$scope.ices = arenaService.getArenaIces($scope, newPeriod.arenaid);
		$uibModal.open({
				animation: false,
				templateUrl: 'testsessionview/newperiod.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function() {
						return $scope.newPeriod;
					}
				}
			})
			.result.then(function(newPeriod) {
				// User clicked OK and everything was valid.
				newPeriod.icelabel = arenaService.convertArenaIceToCurrentDesc($scope, newPeriod.arenaid, newPeriod.iceid);
				angular.copy(newPeriod, $scope.currentPeriod);
				// If already saved in DB
				if ($scope.currentPeriod.id != null) {
					$scope.currentPeriod.status = 'Modified';
				} else {
					$scope.currentPeriod.status = 'New';
					if ($scope.day.periods == null) $scope.day.periods = [];
					// Don't insert twice in list
					if ($scope.day.periods.indexOf($scope.currentPeriod) == -1) {
						$scope.day.periods.push($scope.currentPeriod);
					}
				}
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit periods
	$scope.editTestSessionDayJudge = function(newJudge, day) {
		$scope.day = day;
		$scope.newJudge = {};
		// Keep a pointer to the current judge
		$scope.currentJudge = newJudge;
		// Copy in another object
		angular.copy(newJudge, $scope.newJudge);
		// Send the newJudge to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'testsessionview/newjudge.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function() {
						return $scope.newJudge;
					}
				}
			})
			.result.then(function(newJudge) {
				// User clicked OK and everything was valid.
				angular.copy(newJudge, $scope.currentJudge);
				// If already saved in DB
				if ($scope.currentJudge.id != null) {
					$scope.currentJudge.status = 'Modified';
				} else {
					$scope.currentJudge.status = 'New';
					if ($scope.day.judges == null) $scope.day.judges = [];
					// Don't insert twice in list
					if ($scope.day.judges.indexOf($scope.currentJudge) == -1) {
						$scope.day.judges.push($scope.currentJudge);
					}
				}
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit charges
	$scope.editCharge = function(newCharge) {
		$scope.newCharge = {};
		// Keep a pointer to the current charge
		$scope.currentCharge = newCharge;
		// Copy in another object
		angular.copy(newCharge, $scope.newCharge);
		// Send the newCharge to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'sessionview/newcharge.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function() {
						return $scope.newCharge;
					}
				}
		})
		.result.then(function(newCharge) {
			// User clicked OK and everything was valid.
			angular.copy(newCharge, $scope.currentCharge);
			// If already saved in DB
			if ($scope.currentCharge.id != null) {
				$scope.currentCharge.status = 'Modified';
			} else {
				$scope.currentCharge.status = 'New';
				if ($scope.currentTestsession.charges == null) $scope.currentTestsession.charges = [];
				// Don't insert twice in list
				if ($scope.currentTestsession.charges.indexOf($scope.currentCharge) == -1) {
					$scope.currentTestsession.charges.push($scope.currentCharge);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// Get period by id
	$scope.getPeriodById = function(periodId) {
		var retVal = null, day, period;
		for (var x = 0; x < $scope.currentTestsession.days.length; x++) {
			day = $scope.currentTestsession.days[x];
			for (var y = 0; y < day.periods.length; y++) {
				period = $scope.currentTestsession.days[x].periods[y];
				if (period.id == periodId) {
					retVal = period;
				}
			}
		}
		return retVal;
	}

	// Get all periods from test session, excluding exceptionId
	$scope.getAllPeriods = function(exceptionId) {
		var retVal = [], day, testDateStr, period, dayNb, periodNb;
		var dayLabel = $scope.translationObj.main.daylabel;
		var periodLabel = $scope.translationObj.main.periodlabel;
		for (var x = 0; x < $scope.currentTestsession.days.length; x++) {
			day = $scope.currentTestsession.days[x];
			dayNb = x/1+1;
			for (var y = 0; y < day.periods.length; y++) {
				period = $scope.currentTestsession.days[x].periods[y];
				periodNb = y/1+1;
				if (!exceptionId || (exceptionId && period.id != exceptionId)) {
					testDateStr = dateFilter(day.testdate, 'yyyy-MM-dd');
					retVal.push({'id':period.id, 'fullname': dayLabel + " " + dayNb + " " + periodLabel + " " + periodNb + ' (' + period.arenalabel + (period.icelabel && period.icelabel != '' ? ' ' + period.icelabel : '') + ' - ' + testDateStr + ' ' + period.starttime + ' - ' + period.endtime + ')'})
				}
			}
		}
		return retVal;
	}

	// Get period by id
	$scope.getGroupById = function(groupId) {
		var retVal = null, day, period, group;
		for (var x = 0; x < $scope.currentTestsession.days.length; x++) {
			day = $scope.currentTestsession.days[x];
			for (var y = 0; y < day.periods.length; y++) {
				period = $scope.currentTestsession.days[x].periods[y];
				for (var z = 0; z < period.groups.length; z++) {
					group = period.groups[z];
					if (group.id == groupId) {
						retVal = group;
					}
				}
			}
		}
		return retVal;
	}

	$scope.moveGroupToPeriod = function(group) {
		// Keep a pointer to the current group
		$scope.group = group;
		// Create object for transfer
		$scope.periodMove = {'oldPeriodid':group.testperiodsid};
		$scope.periodMove.periodList = $scope.getAllPeriods(group.testperiodsid);
		// Send the periodMove to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'testsessionview/selectPeriod.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function() {
						return $scope.periodMove;
					}
				}
			})
			.result.then(function(periodMove) {
				// User clicked OK and everything was valid.
				var period;
				// Remove the group from the old period group list
				period = $scope.getPeriodById(periodMove.oldPeriodid);
				period.groups.splice(period.groups.indexOf($scope.group), 1);
				// Put the group in the new period group list
				period = $scope.getPeriodById(periodMove.periodid);
				$scope.group.testperiodsid = period.id;
				if (!period.groups) period.groups = [];
				period.groups.push($scope.group);
				$scope.group.sequence = period.groups.length + 1;
				$scope.group.status = 'Modified';
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	}

	// Get group count
	$scope.getGroupCount = function() {
		var retVal = 0, day, period;
		for (var x = 0; x < $scope.currentTestsession.days.length; x++) {
			day = $scope.currentTestsession.days[x];
			for (var y = 0; y < day.periods.length; y++) {
				retVal += day.periods[y].groups.length;
			}
		}
		return retVal;
	}

	// Get group count for period
	$scope.getGroupCountForPeriodId = function(periodId) {
		var retVal = 0;
		var period = $scope.getPeriodById(periodId);
		if (period) {
			retVal += period.groups.length;
		}
		return retVal;
	}

	// Get all compatible groups from test session, excluding currentGroup or all groups if allowAllGroups is true
	$scope.getCompatibleGroups = function(currentGroup, allowAllGroups) {
		var retVal = [], day, testDateStr, period, periodFullName, group;
		for (var x = 0; x < $scope.currentTestsession.days.length; x++) {
			day = $scope.currentTestsession.days[x];
			testDateStr = dateFilter(day.testdate, 'yyyy-MM-dd');
			for (var y = 0; y < day.periods.length; y++) {
				period = day.periods[y];
				periodFullName = period.arenalabel + (period.icelabel && period.icelabel != '' ? ' ' + period.icelabel : '') + ' - ' + testDateStr + ' ' + period.starttime + ' - ' + period.endtime;
				for (var z = 0; z < period.groups.length; z++) {
					group = period.groups[z];
					if (group.id != currentGroup.id && (allowAllGroups || group.testid == currentGroup.testid)) {
						retVal.push({'id':group.id, 'fullname':group.grouplabel + ' (' + periodFullName + ')'});
					}
				}
			}
		}
		return retVal;
	}

	// Move a skater from one group to a new or existing group
	$scope.moveSkater = function(skaterMove) {
		var group;
		// Find the original group object from the old group id
		group = $scope.getGroupById(skaterMove.oldGroup.id);
		// Remove the skater from the original group
		group.skaters.splice(group.skaters.indexOf(skaterMove.skater), 1);
		// Find the new group object from the new group id
		if (skaterMove.groupid) {
			group = $scope.getGroupById(skaterMove.groupid);
			skaterMove.skater.testsessionsgroupsid = group.id;
		} else {
			// it must be a new group
			group = skaterMove.newGroup;
		}
		// If group doesn't have list of skaters, create it
		if (!group.skaters) group.skaters = [];
		// Add new skater to the list
		group.skaters.push(skaterMove.skater);
		// Adjust sequence and status
		skaterMove.skater.sequence = group.skaters.length;
		skaterMove.skater.status = 'Modified';
		/* WE NEED TO MODIFY THE SKATER'S ORIGINAL REGISTRATION */
		skaterMove.skater.oldTestid = skaterMove.oldGroup.testid;
		skaterMove.skater.newTestid = group.testid;
		$scope.setDirty();
	}

	$scope.moveSkaterToGroup = function(skater) {
		// Keep a pointer to the current skater
		$scope.skater = skater;
		// Create object for transfer
		var oldGroup = $scope.getGroupById(skater.testsessionsgroupsid);
		$scope.skaterMove = {'oldGroup':oldGroup, 'skater':skater};
		$scope.skaterMove.groupList = $scope.getCompatibleGroups(oldGroup, false);
		// Send the periodMove to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'testsessionview/selectGroup.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function() {
						return $scope.skaterMove;
					}
				}
			})
			.result.then(function(skaterMove) {
				// User clicked OK and everything was valid.
				$scope.moveSkater(skaterMove);
			}, function(param) {
				// User clicked CANCEL.
				// alert('canceled');
				// If parameter is createNew, we need to open the "Edit group"
				if (param) {
					$scope.editTestSessionGroup({}, param);
				}
		});
	}

	$scope.editTestSessionGroup = function(newGroup, skaterMove) {
		$scope.newGroup = {};
		// Keep a pointer to the current group
		$scope.currentGroup = newGroup;
		// Copy in another object
		angular.copy(newGroup, $scope.newGroup);
		if ($scope.newGroup.testtype) {
			listsService.getAllTests($scope, $scope.newGroup.testtype, "allTestsByType", authenticationService.getCurrentLanguage());
		}
		$scope.newGroup.periodList = $scope.getAllPeriods();
		// When creating a new group while moving a skater
		if (skaterMove) {
			$scope.newGroup.skaterMove = skaterMove;
			// We can initialize a few value based on the old group
			$scope.newGroup.testtype = skaterMove.oldGroup.testtype;
			listsService.getAllTests($scope, $scope.newGroup.testtype, "allTestsByType", authenticationService.getCurrentLanguage());
			$scope.newGroup.testid = skaterMove.oldGroup.testid;
			$scope.newGroup.testperiodsid = skaterMove.oldGroup.testperiodsid;
		} else {
			// $scope.newGroup.testperiodsid = skaterMove.oldGroup.testperiodsid;
		}
		// Send the newGroup to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'testsessionview/newgroup.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function() {
						return $scope.newGroup;
					}
				}
			})
			.result.then(function(newGroup) {
				// User clicked OK and everything was valid.
				angular.copy(newGroup, $scope.currentGroup);
				// If already saved in DB
				if ($scope.currentGroup.id != null) {
					$scope.currentGroup.status = 'Modified';
					$scope.setDirty();
					$scope.updateAllGroupTimes();
				} else {
					// If this group is created during a move skater operation, we need to attach the skater to this group
					// Somehow, the angular.copy operation messes up the skaterMove structure, so use the one from newwGroup, not $scope.currentGroup
					if (newGroup.skaterMove) {
						newGroup.skaterMove.newGroup = $scope.currentGroup;
						$scope.moveSkater(newGroup.skaterMove);
					}
					$scope.currentGroup.status = 'New';
					$scope.currentGroup.groupno = $scope.getGroupCount()+1;
					var testPeriod = $scope.getPeriodById($scope.currentGroup.testperiodsid);
					if (testPeriod.groups == null) testPeriod.groups = [];
					// Don't insert twice in list
					if (testPeriod.groups.indexOf($scope.currentGroup) == -1) {
						testPeriod.groups.push($scope.currentGroup);
						$scope.currentGroup.sequence = testPeriod.groups.length;
					}
					// We need to save immediately
					$scope.setDirty();
					$scope.saveToDB();
				}

			}, function(param) {
				// User clicked CANCEL.
				// alert('canceled');
		});
	}

	// This is the function that creates the modal to create/edit test
	$scope.editTestSkater = function(group, newSkater) {
		$scope.newSkater = {};
		// Keep a pointer to the current skater
		$scope.currentSkater = newSkater;
		// Copy in another object
		angular.copy(newSkater, $scope.newSkater);
		// Get the values for the testsid drop down list
		if (newSkater.testtype) {
			listsService.getAllTests($scope, newSkater.testtype, "allTestsByType", authenticationService.getCurrentLanguage());
			if (newSkater.testtype == 'DANCE' && newSkater.testsid) {
				listsService.getDanceMusics($scope, newSkater.testsid, authenticationService.getCurrentLanguage());
			}
		} else {
			$scope.allTestsByType = null;
		}
		// Send the newSkater to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'testsessionview/newTest.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newSkater;
					}
				}
			})
			.result.then(function(newSkater) {
				// User clicked OK and everything was valid.
				angular.copy(newSkater, $scope.currentSkater);
				// If already saved in DB
				if ($scope.currentSkater.id != null) {
					$scope.currentSkater.status = 'Modified';
				} else {
					$scope.currentSkater.status = 'New';
					if (group.skaters == null) $scope.group.skaters = [];
					// Don't insert twice in list
					if (group.skaters.indexOf($scope.currentSkater) == -1) {
						group.skaters.push($scope.currentSkater);
					}
				}
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	$scope.setGroupDefaultValue = function(group) {
		if (group) {
			group.warmupduration = group.testdefwarmupduration;
			group.testduration = group.testdefduration * group.skaters.length;
		}
	}

	$scope.updateAllGroupTimes = function() {
		var day, period, group, starttime;
		for (var x = 0; x < $scope.currentTestsession.days.length; x++) {
			day = $scope.currentTestsession.days[x];
			for (var y = 0; day.periods && y < day.periods.length; y++) {
				period = day.periods[y];
				for (var z = 0; period.groups && z < period.groups.length; z++) {
					if (z == 0) {
						starttime = period.starttime;
					} else {
						starttime = period.groups[z-1].endtime;
					}
					group = period.groups[z];
					group.starttime = starttime;
					var dstartTime = parseISOdateService.parseDate("1980/01/01T" + starttime);
					var totalDuration = group.warmupduration/1 + group.testduration/1;
					var dendTime = new Date(dstartTime.getTime() + totalDuration*60000);
					group.endtime = dendTime.toTimeString().substr(0,8);
					if (!group.status) group.status = 'Modified';
				}
			}
		}
	}

	$scope.lockSession = function(confirmed) {
		if ($scope.currentTestsession != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgconfirmlock, "YESNO", $scope.lockSession, null, true);
		} else if ($scope.currentTestsession != null && confirmed) {
			if ($scope.isDirty()) {
				dialogService.alertDlg($scope.translationObj.main.msgerrpleasesavefirst, null);
			} else {
				$http({
						method: 'post',
						url: './teststarsessionview/teststarsession.php',
						data: $.param({'testsessionid' : $scope.currentTestsession.id, 'type' : 'lockSession' }),
						headers: {'Content-Type': 'application/x-www-form-urlencoded'}
					}).
					success(function(data, status, headers, config) {
						if(data.success) {
							dialogService.alertDlg($scope.translationObj.main.msgsessionlocked, null);
							$scope.currentTestsession.islock = 1;
						} else {
							dialogService.displayFailure(data);
						}
					}).
					error(function(data, status, headers, config) {
						dialogService.displayFailure(data);
						return false;
					});
			}
		}
	};

	$scope.unlockSession = function(confirmed) {
		if ($scope.currentTestsession != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgconfirmunlock, "YESNO", $scope.unlockSession, null, true);
		} else if ($scope.currentTestsession != null && confirmed) {
			if ($scope.isDirty()) {
				dialogService.alertDlg($scope.translationObj.main.msgerrpleasesavefirst, null);
			} else {
				$http({
						method: 'post',
						url: './teststarsessionview/teststarsession.php',
						data: $.param({'testsessionid' : $scope.currentTestsession.id, 'type' : 'unlockSession' }),
						headers: {'Content-Type': 'application/x-www-form-urlencoded'}
					}).
					success(function(data, status, headers, config) {
						if(data.success) {
							dialogService.alertDlg($scope.translationObj.main.msgsessionunlocked, null);
							$scope.currentTestsession.islock = 0;
						} else {
							dialogService.displayFailure(data);
						}
					}).
					error(function(data, status, headers, config) {
						dialogService.displayFailure(data);
						return false;
					});
			}
		}
	};

	$scope.onSkaterTestTypeChange = function(newObj) {
		listsService.getAllTestsForMember($scope, newObj.testtype, newObj.memberid, "allTestsByType", authenticationService.getCurrentLanguage());
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

	$scope.OnAllGroupsChange = function(newObj) {
		if (newObj.allowAllGroups == '1') {
			dialogService.alertDlg($scope.translationObj.main.msgchangeregistration);
		}
		newObj.groupList = $scope.getCompatibleGroups(newObj.oldGroup, newObj.allowAllGroups);
	}

	$scope.onArenaChange = function(newObj) {
		newObj.iceid = null;
		$scope.ices = arenaService.getArenaIces($scope, newObj.arenaid);
	}

	$scope.onTestTypeChange = function(newObj) {
		listsService.getAllTests($scope, newObj.testtype, "allTestsByType", authenticationService.getCurrentLanguage());
		newObj.testid = null;
	}

	$scope.onTestResultChange = function(skater) {
		if (skater != null) {
			skater.status = 'Modified';
			$scope.setDirty();
		}
	}

	// REPORTS
	$scope.printReport = function(reportName) {
		if (reportName == 'testsessionsummary') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&testsessionid='+$scope.currentTestsession.id);
		}
		if (reportName == 'testsessionschedule') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&testsessionid='+$scope.currentTestsession.id);
		}
		if (reportName == 'testsessiontestsheets') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&testsessionid='+$scope.currentTestsession.id);
		}
	}

	// When changing site
	$scope.$on('$locationChangeStart', function( event ) {
			// var answer = confirm("Are you sure you want to leave this page?")
			// if (!answer) {
			//     event.preventDefault();
			// }
	});

	// TODO : testing beforeunload
	// When browser or tab closes.
	// Cannot use event.preventDefault(), must return a string.
	// Browser will display message or its own message.
	$(window).on('beforeunload', function() {
		// return "Are you sure you want to leave this page?"
	});

	$scope.refreshAll = function() {
		$scope.getAllTestsessions();
		// anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'homeclubs', 		'text', 'homeclubs');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testtypes', 'text', 'testtypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testresults', 'sequence', 'testresults');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'extrafeesoptions', 'sequence', 'extrafeesoptions');
		arenaService.getAllArenas($scope, authenticationService.getCurrentLanguage());
		listsService.getAllCharges($scope, authenticationService.getCurrentLanguage());
		listsService.getAllTestDirectors($scope, authenticationService.getCurrentLanguage());
		listsService.getAllJudges($scope, authenticationService.getCurrentLanguage());
		listsService.getPartners($scope, authenticationService.getCurrentLanguage());
		listsService.getAllTestsEx($scope, authenticationService.getCurrentLanguage());
		listsService.getAllClubs($scope, authenticationService.getCurrentLanguage());
		listsService.getAllDanceMusics($scope, authenticationService.getCurrentLanguage());
		translationService.getTranslation($scope, 'testsessionview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
