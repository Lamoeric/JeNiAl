'use strict';

angular.module('cpa_admin.teststarsessionview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/teststarsessionview', {
		templateUrl: 'teststarsessionview/teststarsessionview.html',
		controller: 'teststarsessionviewCtrl',
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
					return $q.reject({authenticated: false, newLocation: "/teststarsessionview"});
				}
			}
		}
	});
}])

.controller('teststarsessionviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$window', 'dateFilter', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', 'arenaService', 'parseISOdateService', function($rootScope, $scope, $http, $uibModal, $window, dateFilter, anycodesService, dialogService, listsService, authenticationService, translationService, arenaService, parseISOdateService) {

	$scope.progName = "teststarsessionview";
	$scope.currentTestsession = null;
	$scope.selectedLeftObj = null;
	$scope.newTestsession = null;
	$scope.isFormPristine = true;
	$scope.approbationStatusFilter = -1;

	$scope.isDirty = function() {
		// if ($scope.periodsForm.$dirty || $scope.testsessionForm.$dirty || $scope.summaryForm.$dirty) {
		if ($scope.testsessionForm.$dirty || $scope.summaryForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.summaryForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function() {
		// $scope.periodsForm.$setPristine();
		$scope.testsessionForm.$setPristine();
		$scope.summaryForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllTestsessions = function() {
		$scope.promise = $http({
				method: 'post',
				url: './teststarsessionview/teststarsession.php',
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
		$scope.promise = $scope.promise = $http({
			method: 'post',
			url: './teststarsessionview/teststarsession.php',
			data: $.param({'id' : testsession.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getTestsessionDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentTestsession = data.data[0];
				$scope.currentTestsession.testsessionstartdate  = parseISOdateService.parseDateWithoutTime($scope.currentTestsession.testsessionstartdate);
				$scope.currentTestsession.testsessionenddate    = parseISOdateService.parseDateWithoutTime($scope.currentTestsession.testsessionenddate);
				$rootScope.repositionLeftColumn();
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
				url: './teststarsessionview/teststarsession.php',
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

		if ($scope.currentTestsession.nbofdaysprior < 0 || $scope.currentTestsession.nbofdaysprior > 30) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrnbofdayspriorinvalid);
		}

		if ($scope.testsessionForm.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}

		if ($scope.currentTestsession.testsessionstartdate >= $scope.currentTestsession.testsessionenddate) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrenddategreaterstartdate);
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

	$scope.saveToDbForced = function() {
		$scope.saveToDB(true);
	}

	$scope.saveToDB = function(forced) {
		if (($scope.currentTestsession == null || !$scope.isDirty()) && !forced) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.currentTestsession.testsessionstartdatestr  = dateFilter($scope.currentTestsession.testsessionstartdate, 'yyyy-MM-dd');
			$scope.currentTestsession.testsessionenddatestr    = dateFilter($scope.currentTestsession.testsessionenddate,   'yyyy-MM-dd');
			$scope.promise = $http({
				method: 'post',
				url: './teststarsessionview/teststarsession.php',
				data: $.param({'testsession' : $scope.currentTestsession, 'userid' : authenticationService.getUserInfo().userid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'updateEntireTestsession' }),
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
			url: './teststarsessionview/teststarsession.php',
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

	// This is the function that creates the modal to create new testsession
	$scope.createNew = function(confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newTestsession = {};
			// Send the newTestsession to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'teststarsessionview/newtestsession.template.html',
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

	$scope.copySession = function(confirmed) {
		if ($scope.currentTestsession != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgconfirmcopy, "YESNO", $scope.copySession, null, true);
		} else if ($scope.currentTestsession != null && confirmed) {
			if ($scope.isDirty()) {
				dialogService.alertDlg($scope.translationObj.main.msgerrpleasesavefirst, null);
			} else {
				$scope.promise = $http({
						method: 'post',
						url: './teststarsessionview/teststarsession.php',
						data: $.param({'testsessionid' : $scope.currentTestsession.id, 'type' : 'copySession' }),
						headers: {'Content-Type': 'application/x-www-form-urlencoded'}
					}).
					success(function(data, status, headers, config) {
						if(data.success) {
							dialogService.alertDlg($scope.translationObj.main.msgsessioncopied, null);
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

	$scope.lockSession = function(confirmed) {
		if ($scope.currentTestsession != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgconfirmlock, "YESNO", $scope.lockSession, null, true);
		} else if ($scope.currentTestsession != null && confirmed) {
			if ($scope.isDirty()) {
				dialogService.alertDlg($scope.translationObj.main.msgerrpleasesavefirst, null);
			} else {
				$scope.promise = $http({
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
				$scope.promise = $http({
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

	$scope.fixMemberTests = function() {
		for (var i = 0; $scope.currentTestsession && i < $scope.currentTestsession.periods.length; i++) {
			for (var y = 0; $scope.currentTestsession && y < $scope.currentTestsession.periods[i].registrations.length; y++) {
				$scope.currentTestsession.periods[i].registrations[y].status2 = 'ResultModified';
			}
		}
		$scope.setDirty();
		dialogService.alertDlg($scope.translationObj.main.msgsavetofixmembertests);
	}

	// This is the function that creates the modal to create/edit periods
	$scope.editPeriod = function(newPeriod) {
		$scope.newPeriod = {};
		// Keep a pointer to the current test
		$scope.currentPeriod = newPeriod;
		// Copy in another object
		angular.copy(newPeriod, $scope.newPeriod);
		$scope.newPeriod.perioddate = parseISOdateService.parseDateWithoutTime(newPeriod.perioddate);
		// Get the values for the ice drop down list
		$scope.ices = arenaService.getArenaIces($scope, newPeriod.arenaid);
		$uibModal.open({
				animation: false,
				templateUrl: 'teststarsessionview/newperiod.template.html',
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
				if (!$scope.currentPeriod.day) {
					$scope.currentPeriod.day = newPeriod.perioddate.getDay()/1;
				}
				$scope.currentPeriod.perioddate  = dateFilter(newPeriod.perioddate, 'yyyy-MM-dd');
				// If already saved in DB
				if ($scope.currentPeriod.id != null) {
					$scope.currentPeriod.status = 'Modified';
				} else {
					$scope.currentPeriod.status = 'New';
					$scope.currentPeriod.manual = 1;
					if ($scope.currentPeriod.periods == null) $scope.currentPeriod.periods = [];
					// Don't insert twice in list
					if ($scope.currentTestsession.periods.indexOf($scope.currentPeriod) == -1) {
						$scope.currentTestsession.periods.push($scope.currentPeriod);
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
				templateUrl: 'teststarsessionview/newcharge.template.html',
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

	// This is the function that creates the modal to create/edit charges
	$scope.editSchedule = function(newSchedule) {
		$scope.newSchedule = {};
		// Keep a pointer to the current charge
		$scope.currentSchedule = newSchedule;
		// Copy in another object
		angular.copy(newSchedule, $scope.newSchedule);
		// Send the newSchedule to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'teststarsessionview/newschedule.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function() {
						return $scope.newSchedule;
					}
				}
		})
		.result.then(function(newSchedule) {
			// User clicked OK and everything was valid.
			angular.copy(newSchedule, $scope.currentSchedule);
			// If already saved in DB
			if ($scope.currentSchedule.id != null) {
				$scope.currentSchedule.status = 'Modified';
			} else {
				$scope.currentSchedule.status = 'New';
				if ($scope.currentTestsession.schedules == null) $scope.currentTestsession.schedules = [];
				// Don't insert twice in list
				if ($scope.currentTestsession.schedules.indexOf($scope.currentSchedule) == -1) {
					$scope.currentTestsession.schedules.push($scope.currentSchedule);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	$scope.generatePeriods = function(forced) {
		if (!forced) {
			if ($scope.currentTestsession.periodsgenerated == 1) {
				for (var i = 0; i < $scope.currentTestsession.periods.length; i++) {
					if ($scope.currentTestsession.periods[i].registrations.length != 0) {
						dialogService.alertDlg($scope.translationObj.main.msgcannotgenerateperiods);
						return;
					}
				}
				// Confirm the deletion of existing dates and creation of new dates
				dialogService.confirmDlg($scope.translationObj.main.msgperiodsgenerated, "YESNO", $scope.generatePeriods, null, true, null);
			} else {
				// Confirm creation of dates
				dialogService.confirmDlg($scope.translationObj.main.msggenerateperiods, "YESNO", $scope.generatePeriods, null, true, null);
			}
		} else {
			var dateArr = $scope.generatePeriodArray();
			$scope.insertPeriods(dateArr).then(
			function(retVal) {
				if (retVal.data.success) {
					$scope.setCurrentInternal($scope.selectedLeftObj, null);
				}
			});
		}
	}

	$scope.insertPeriods = function(periodarr) {
			return $http({
				method: 'post',
				url: './teststarsessionview/teststarsession.php',
				data: $.param({'periods' : periodarr, 'type' : 'insertPeriods' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success) {
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

	$scope.generatePeriodArray = function() {
		// We need to generates the periods. First, get the schedule
		// For every schedule, find the first possible date based on the session start date and generate until you reach the enddate of session
		var dateArr = [];
		var day = 60 * 60 * 24 * 1000;
		var endDate = new Date($scope.currentTestsession.testsessionenddate.getTime() + day);
		for (var i = 0; i < $scope.currentTestsession.schedules.length; i++) {
			var schedule = $scope.currentTestsession.schedules[i];
			var day = schedule.day/1;
			// Find first date of course for this schedule
			var startday = $scope.currentTestsession.testsessionstartdate.getDay()/1; // This is the start day of the session
			var diff = (startday <= day) ? day-startday : day + 7 - (startday ); // This is the difference in days
			var firstDate = new Date(new Date($scope.currentTestsession.testsessionstartdate).setDate($scope.currentTestsession.testsessionstartdate.getDate() + diff)); // First course date.
			var scheduleTime = schedule.starttime.split(":");
			firstDate.setHours(scheduleTime[0],scheduleTime[1],scheduleTime[2]);
			do  {
				var periodstr = dateFilter(firstDate, 'yyyy-MM-dd');
				dateArr.push({newtestssessionsid : $scope.currentTestsession.id, arenaid: schedule.arenaid, iceid : schedule.iceid, perioddatestr : periodstr, starttime : schedule.starttime, endtime : schedule.endtime, duration : schedule.duration, day : schedule.day/1});
				firstDate = new Date(new Date(firstDate).setDate(firstDate.getDate() + 7));
			// } while (firstDate <= $scope.currentTestsession.testsessionenddate+1)
			} while (firstDate < endDate)
		}
		return dateArr;
	}

	$scope.onArenaChange = function(newObj) {
		newObj.iceid = null;
		$scope.ices = arenaService.getArenaIces($scope, newObj.arenaid);
	}

	$scope.onTestResultChange = function(registration) {
		if (registration != null) {
			registration.status2 = 'ResultModified';
			$scope.setDirty();
		}
	}

	// Periods can be displayed based on the registration filter.
	$scope.filterPeriods = function(obj) {
		if ($scope.approbationStatusFilter == -1) return true;
		if (obj.registrations.length != 0) {
			for (var i = 0; i < obj.registrations.length; i++) {
				if (obj.registrations[i].approbationstatus/1 == $scope.approbationStatusFilter/1) {
					return true;
				}
			}
		}
	}

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

	// REPORTS
	$scope.printReport = function(reportName) {
		if (reportName == 'testsessionsummary') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&testsessionid='+$scope.currentTestsession.id);
		}
		if (reportName == 'teststarsessiontestsheets') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&testsessionid='+$scope.currentTestsession.id);
		}
	}

	// angular.element(document).ready(function () {
	// 	$rootScope.repositionLeftColumn();
	// 	console.log('page loading completed');
	// });

	$scope.refreshAll = function() {
		$scope.getAllTestsessions();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		// anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'startesttypes', 'text', 'startesttypes');
		// anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testtypes', 'text', 'testtypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testresults', 'sequence', 'testresults');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'days', 'sequence', 'days');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'approbationstatus', 'sequence', 'approbationstatus');
		arenaService.getAllArenas($scope, authenticationService.getCurrentLanguage());
		listsService.getAllCharges($scope, authenticationService.getCurrentLanguage());
		listsService.getAllTestDirectors($scope, authenticationService.getCurrentLanguage());
		listsService.getPartners($scope, authenticationService.getCurrentLanguage());
		listsService.getAllTestsEx($scope, authenticationService.getCurrentLanguage());
		listsService.getAllDanceMusics($scope, authenticationService.getCurrentLanguage());
		listsService.getAllClubs($scope, authenticationService.getCurrentLanguage());
		translationService.getTranslation($scope, 'teststarsessionview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
