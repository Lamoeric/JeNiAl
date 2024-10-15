'use strict';

angular.module('cpa_admin.sessioncoursesview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/sessioncoursesview', {
		templateUrl: 'sessioncoursesview/sessioncoursesview.html',
		controller: 'sessioncoursesviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.sessioncourse_access == true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({ authenticated: true, validRights: false, newLocation: null });
					}
				} else {
					return $q.reject({ authenticated: false, newLocation: "/sessioncoursesview" });
				}
			}
		}
	});
}])

.controller('sessioncoursesviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$window', 'anycodesService', 'dialogService', 'listsService', 'sessionCourseSublevelService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $window, anycodesService, dialogService, listsService, sessionCourseSublevelService, authenticationService, translationService) {

	$scope.progName = "sessioncoursesView";
	$scope.currentCourse = null;
	$scope.selectedCourse = null;
	$scope.newCourse = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.backCurrentEvent = null;

	$scope.isDirty = function () {
		if ($scope.detailsForm.$dirty || $scope.staffsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function () {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function () {
		$scope.detailsForm.$setPristine();
		$scope.staffsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllCourses = function () {
		$scope.promise = $http({
			method: 'post',
			url: './sessioncoursesview/sessioncourses.php',
			data: $.param({ 'eventType': $scope.currentEvent.type, 'eventId': $scope.currentEvent.id, 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllCourses' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
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
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.getCourseDetails = function (sessioncourses) {
		$scope.promise = $http({
			method: 'post',
			url: './sessioncoursesview/sessioncourses.php',
			data: $.param({ 'eventType': $scope.currentEvent.type, 'id': sessioncourses.id, 'language': authenticationService.getCurrentLanguage(), 'type': 'getCourseDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentCourse = data.data[0];
				sessionCourseSublevelService.getCourseSublevelCodes($scope, $http, $scope.currentCourse.id, authenticationService.getCurrentLanguage());
				// Now, in case the length of dates is < 10, add a few fake dates to help the display
				if ($scope.currentCourse.dates.length < 10) {
					for (var x = $scope.currentCourse.dates.length; x <= 25; x++) {
						$scope.currentCourse.dates.push({'id':null,'coursedate':'[N/D]'});
					}
				}
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (sessioncourses, index) {
		if (sessioncourses != null) {
			$scope.selectedCourse = sessioncourses;
			$scope.getCourseDetails(sessioncourses);
			$scope.selectedLeftObj = sessioncourses;
			$scope.setPristine();
		} else {
			$scope.selectedCourse = null;
			$scope.currentCourse = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (sessioncourses, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, sessioncourses, index);
		} else {
			$scope.setCurrentInternal(sessioncourses, index);
		}
	};

	$scope.saveToDB = function () {
		if ($scope.currentCourse == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './sessioncoursesview/sessioncourses.php',
				data: $.param({ 'eventType': $scope.currentEvent.type, 'sessioncourse': JSON.stringify($scope.currentCourse), 'type': 'updateEntireCourse' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this sessioncourses to reset everything
					$scope.setCurrentInternal($scope.selectedCourse, null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).error(function (data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	$scope.setStaffList = function (newObj) {
		if (newObj.staffcode == 'COACH') {
			$scope.staffs = $scope.coaches;
		} else if (newObj.staffcode == 'PA') {
			$scope.staffs = $scope.programassistants;
		} else if (newObj.staffcode == 'pah') {
			$scope.staffs = $scope.programassistanthelpers;
		}
	}

	$scope.onStaffcodeChange = function (newObj) {
		newObj.memberid = null;
		$scope.setStaffList(newObj);
	}

	/**
	 * This function is used by the new staff dialog box to validate that the new staff is valid
	 * All fields must be filled and staff member must not already be in the staff list
	 * @param {*} newStaff Staff object being added
	 */
	$scope.validateNewStaff = function (formObj, newStaff) {
		// Validate the form using the field's rules
		if (formObj.$invalid) {
			return "#editObjFieldMandatory";
		}
		// Make sure that the new staff is not already in one of the list
		for (var x = 0; $scope.currentCourse.staffs && x < $scope.currentCourse.staffs.length; x++) {
			if ($scope.currentCourse.staffs[x].memberid == newStaff.memberid) {
				return "#editObjStaffAlreadyExists";
			}
		}
	}

	// This is the function that creates the modal to create/edit courses' staffs
	$scope.editSessionCourseStaff = function (course, newStaff) {
		$scope.course = course;
		$scope.newStaff = {};
		$scope.setStaffList(newStaff);
		// Keep a pointer to the current staff
		$scope.currentStaff = newStaff;
		// Copy in another object
		angular.copy(newStaff, $scope.newStaff);
		$scope.newStaff.callback = $scope.validateNewStaff;
		$scope.newStaff.eventType = $scope.currentEvent.type/1;

		$uibModal.open({
			animation: false,
			templateUrl: 'sessioncoursesview/newstaff.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newStaff;
				}
			}
		}).result.then(function (newStaff) {
			// User clicked OK and everything was valid.
			if (newStaff.staffcode == 'COACH') {
				newStaff.fullname = anycodesService.convertIdToDesc($scope, 'coaches', newStaff.memberid);
			} else if (newStaff.staffcode == 'PA') {
				newStaff.fullname = anycodesService.convertIdToDesc($scope, 'programassistants', newStaff.memberid);
			} else if (newStaff.staffcode == 'pah') {
				newStaff.fullname = anycodesService.convertIdToDesc($scope, 'programassistanthelpers', newStaff.memberid);
			}
			newStaff.staffcodelabel = anycodesService.convertCodeToDesc($scope, 'staffcodes', newStaff.staffcode);
			newStaff.statuscodelabel = anycodesService.convertCodeToDesc($scope, 'personnelstatus', newStaff.statuscode);
			angular.copy(newStaff, $scope.currentStaff);
			$scope.currentStaff.sessionscoursesid = $scope.currentCourse.id;
			if ($scope.currentStaff.id != null) {
				$scope.currentStaff.status = 'Modified';
			} else {
				$scope.currentStaff.status = 'New';
				if ($scope.currentEvent.type == 2) {
					// Staffs for event "show" are always permanent
					$scope.currentStaff.statuscode = 'PERM';
					$scope.currentStaff.showid = $scope.currentEvent.id;
					$scope.currentStaff.numberid = $scope.currentCourse.id;
				}
				if ($scope.course.staffs == null) $scope.course.staffs = [];
				// Don't insert twice in list
				if ($scope.course.staffs.indexOf($scope.currentStaff) == -1) {
					$scope.course.staffs.push($scope.currentStaff);
				}
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};
	
	$scope.onCurrentEventChange = function () {
		$scope.getAllCourses();
		$scope.selectedCourse = null;
		$scope.currentCourse = null;
		$scope.selectedLeftObj = null;
	}

	/**
	*			Callback from listsService.getAllSessionsAndShows to force the selection of the first element in the list
	*/
	$scope.callback = function () {
		$scope.currentEvent = $scope.allSessionsAndShows[0];
		$scope.onCurrentEventChange();
	}

	$scope.refreshAll = function () {
		if (!$scope.currentEvent) {
			listsService.getAllSessionsAndShows($scope, authenticationService.getCurrentLanguage(), $scope.callback);
		}
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'presencetypes', 'sequence', 'presencetypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'staffcodes', 'sequence', 'staffcodes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'personnelstatus', 'sequence', 'personnelstatus');
		listsService.getCoaches($scope, authenticationService.getCurrentLanguage());
		listsService.getAllProgramAssistants($scope, authenticationService.getCurrentLanguage());
		listsService.getAllProgramAssistantHelpers($scope, authenticationService.getCurrentLanguage());
		translationService.getTranslation($scope, 'sessioncoursesview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
		$scope.selectedCourse = null;
		$scope.currentCourse = null;
		$scope.selectedLeftObj = null;
		if ($scope.currentEvent) {
			$scope.getAllCourses();
		} else {
			$scope.leftobjs = [];
		}
	}

	// REPORTS
	$scope.printReport = function (reportName) {
		if (reportName == 'sessionCourseAttendance') {
			if ($scope.currentEvent.type == 1) {
				$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentCourse.sessionid + '&sessionscoursesid=' + $scope.currentCourse.id);
			} else {
				$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&showid=' + $scope.currentCourse.showid + '&showsnumbersid=' + $scope.currentCourse.id);
			}
		}
		if (reportName == 'sessionCourseAttendanceBySubLevel') {
			$window.open('./reports/sessionCourseAttendance.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentCourse.sessionid + '&sessionscoursesid=' + $scope.currentCourse.id + '&bysubgroup=true');
		}
		if (reportName == 'sessionCourseSchedule') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentCourse.sessionid + '&sessionscoursesid=' + $scope.currentCourse.id);
		}
		if (reportName == 'sessionCoursesList') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentCourse.sessionid + '&sessionscoursesid=' + $scope.currentCourse.id);
		}
		if (reportName == 'sessionCoursesListActive') {
			$window.open('./reports/sessionCoursesList.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentCourse.sessionid + '&sessionscoursesid=' + $scope.currentCourse.id + '&activeonly=true');
		}
		if (reportName == 'showPracticeSchedule') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&showid=' + $scope.currentEvent.id + '&showsnumbersid=' + $scope.currentCourse.id);
		}
		if (reportName == 'showNumbersInvitesList') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&showid=' + $scope.currentEvent.id + '&showsnumbersid=' + $scope.currentCourse.id);
		}
		if (reportName == 'showNumbersInvitesListActive') {
			$window.open('./reports/showNumbersInvitesList' + '.php?language=' + authenticationService.getCurrentLanguage() + '&showid=' + $scope.currentEvent.id + '&showsnumbersid=' + $scope.currentCourse.id + '&activeonly=true');
		}
		if (reportName == 'sessionCourseCSProgress') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentCourse.sessionid + '&sessionscoursesid=' + $scope.currentCourse.id);
		}
		if (reportName == 'sessionCourseCSReportCard') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentCourse.sessionid + '&sessionscoursesid=' + $scope.currentCourse.id);
		}
		if (reportName == 'sessionCoursePreCSReportCard') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentCourse.sessionid + '&sessionscoursesid=' + $scope.currentCourse.id);
		}
	}

	$scope.refreshAll();
}]);
