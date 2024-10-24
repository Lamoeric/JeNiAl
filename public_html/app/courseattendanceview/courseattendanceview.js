'use strict';

angular.module('cpa_admin.courseattendanceview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/courseattendanceview', {
		templateUrl: 'courseattendanceview/courseattendanceview.html',
		controller: 'courseattendanceviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.attendance_access == true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({ authenticated: true, validRights: false, newLocation: null });
					}
				} else {
					return $q.reject({ authenticated: false, newLocation: "/courseattendanceview" });
				}
			}
		}
	});
}])

.controller('courseattendanceviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'listsService', 'anycodesService', 'dialogService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, listsService, anycodesService, dialogService, authenticationService, translationService) {
	$scope.progName = "courseattendanceView";
	$scope.currentCourseattendance = null;
	$scope.selectedCourseattendance = null;
	$scope.currentCourseDetails = null;
	$scope.newCourseattendance = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;

	$scope.isDirty = function () {
		if ($scope.detailsForm.$dirty) {
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
		$scope.isFormPristine = true;
	};

	$scope.refreshAttendance = function (data) {
		$scope.currentCourseattendance = data.skaters;
		$scope.currentCourseattendanceStaffs = data.staffs;
		$scope.currentCourseDetails = data.coursedetails;
		for (var x = 0; x < $scope.currentCourseattendance.length; x++) {
			if (!$scope.currentCourseattendance[x].ispresent || $scope.currentCourseattendance[x].ispresent == 0) {
				$scope.currentCourseattendance[x].ispresent = false;
			} else {
				$scope.currentCourseattendance[x].ispresent = true;
			}
		}
		for (var x = 0; $scope.currentCourseattendanceStaffs && x < $scope.currentCourseattendanceStaffs.length; x++) {
			if ($scope.currentCourseattendanceStaffs[x].ispresent == null || ($scope.currentCourseattendanceStaffs[x].ispresent != null && isNaN($scope.currentCourseattendanceStaffs[x].ispresent)) == true) {
				$scope.currentCourseattendanceStaffs[x].ispresent = 0;
			}
		}
		$rootScope.repositionLeftColumn();
	}

	$scope.getCourseAttendanceDetails = function (courseattendance) {
		$scope.eventtype = courseattendance.sessionscourse.type;
		$scope.promise = $http({
			method: 'post',
			url: './courseattendanceview/courseattendance.php',
			data: $.param({ 'eventtype': courseattendance.sessionscourse.type, 'sessionscoursesid': courseattendance.sessionscourse.id, 'sessionscoursesdatesid': courseattendance.sessionscoursesdate.id, 'language': authenticationService.getCurrentLanguage(), 'type': 'getCourseAttendanceDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.skaters)) {
					$scope.refreshAttendance(data);
				} else {
					$scope.selectedCourseattendance = null;
					$scope.currentCourseDetails = null;
					$scope.currentCourseattendance = null;
					$scope.selectedLeftObj = null;
				}
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.getCoursesList = function (selectedCourseattendance) {
		$scope.promise = $http({
			method: 'post',
			url: './courseattendanceview/courseattendance.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getCoursesList' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.activeCourses = data.data;
				$scope.selectCourseDate(selectedCourseattendance);
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (courseattendance, index) {
		if (courseattendance != null) {
			$scope.selectedCourseattendance = courseattendance;
			$scope.getCourseAttendanceDetails(courseattendance);
			$scope.selectedLeftObj = courseattendance;
			$scope.setPristine();
		} else {
			$scope.selectedCourseattendance = null;
			$scope.currentCourseDetails = null;
			$scope.currentCourseattendance = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (courseattendance, index) {
		$scope.setCurrentInternal(courseattendance, index);
	};

	$scope.saveToDB = function (memberattendance, newPresence) {
		if (newPresence) memberattendance.ispresent = newPresence;
		$scope.promise = $http({
			method: 'post',
			url: './courseattendanceview/courseattendance.php',
			data: $.param({ 'memberattendance': memberattendance, 
							'eventtype': $scope.selectedCourseattendance.sessionscourse.type, 
							'sessionscoursesid': $scope.selectedCourseattendance.sessionscourse.id, 
							'sessionscoursesdatesid': $scope.selectedCourseattendance.sessionscoursesdate.id, 
							'language': authenticationService.getCurrentLanguage(), 
							'type': 'updateMemberAttendance' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				// The updateMemberAttendance PHP function returns the entire attendance. 
				// This is done so we don't have to call the getCourseAttendanceDetails to refresh everything. We save one round trip.
				$scope.refreshAttendance(data.CourseDetail);
			} else {
				dialogService.displayFailure(data);
				return false;
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	/**
	 * This function is called by the UI (selectcoursedate) when the user changes the course in the list
	 * @param {*} newObj 	The object being edited by the dialog box
	 */
	$scope.changeSessionCourse = function (newObj) {
		// The list of dates is managed directly in the HTML, 
		// but we wanted to make sure that if the list of date was not null, 
		// that the first date would be selected by default
		if (newObj.sessionscourse.dates.length > 0) {
			newObj.sessionscoursesdate = newObj.sessionscourse.dates[0];
		}
	}

	// 
	/**
	 * This is the function that creates the modal to select the course and the course date
	 * @param {*} selectedCourseattendance 	If a choice has been made previously, start by showing this choice in the dialog box
	 */
	$scope.selectCourseDate = function (selectedCourseattendance) {
		$scope.newCourseDate = {};
		$scope.currentCourseDate = selectedCourseattendance;
		// Copy in another object
		angular.copy(selectedCourseattendance, $scope.newCourseDate);
		// Send the newCourseDate to the modal form
		$uibModal.open({
			animation: false,
			templateUrl: 'courseattendanceview/selectcoursedate.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'md',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newCourseDate;
				}
			}
		}).result.then(function (newCourseDate) {
			// User clicked OK and everything was valid.
			$scope.setCurrentInternal(newCourseDate);
		}, function () {
			$scope.setCurrentInternal(null);
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	/**
	 * This function is used to update the list associated with the field listing all the staffs for a specific staff code
	 * @param {*} newObj 	The object being edited by the dialog box
	 */
	$scope.setStaffList = function (newObj) {
		if (newObj.staffcode) {
			if (newObj.staffcode == 'COACH') {
				$scope.staffs = $scope.coaches;
			} else if (newObj.staffcode == 'PA') {
				$scope.staffs = $scope.programassistants;
			} else if (newObj.staffcode == 'pah') {
				$scope.staffs = $scope.programassistanthelpers;
			}
		}
	}

	/**
	 * This function is called by the UI (newStaff) when the user changes the staff code (Coach, Assistant, assistant Helper)
	 * @param {*} newObj The obect being edited by the dialog box
	 */
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
		for (var x = 0; $scope.currentCourseattendanceStaffs && x < $scope.currentCourseattendanceStaffs.length; x++) {
			if ($scope.currentCourseattendanceStaffs[x].memberid == newStaff.memberid) {
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
		$scope.newStaff.eventType = $scope.eventtype / 1;

		$uibModal.open({
			animation: false,
			templateUrl: 'courseattendanceview/newstaff.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'md',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newStaff;
				}
			}
		}).result.then(function (newStaff) {
			newStaff.callback = null;
			// User clicked OK and everything was valid.
			if (newStaff.staffcode == 'COACH') {
				newStaff.fullname = anycodesService.convertIdToDesc($scope, 'coaches', newStaff.memberid);
			} else if (newStaff.staffcode == 'PA') {
				newStaff.fullname = anycodesService.convertIdToDesc($scope, 'programassistants', newStaff.memberid);
			} else if (newStaff.staffcode == 'pah') {
				newStaff.fullname = anycodesService.convertIdToDesc($scope, 'programassistanthelpers', newStaff.memberid);
			}
			// newStaff.ispresent = 0;
			newStaff.staffcodelabel = anycodesService.convertCodeToDesc($scope, 'staffcodes', newStaff.staffcode);
			newStaff.statuscodelabel = anycodesService.convertCodeToDesc($scope, 'personnelstatus', newStaff.statuscode);
			angular.copy(newStaff, $scope.currentStaff);
			if ($scope.currentStaff.id != null) {
				// This should never happen here because we only allow to add new staff members
				$scope.currentStaff.status = 'Modified';
			} else {
				$scope.currentStaff.status = 'New';
				// if ($scope.eventtype == 2) {
				// 	$scope.currentStaff.showid = $scope.currentCourseDetails.showid;
				// 	$scope.currentStaff.numberid = $scope.currentCourseDetails.numberid;
				// }
				if ($scope.currentCourseattendanceStaffs == null) $scope.currentCourseattendanceStaffs = [];
				// Don't insert twice in list
				if ($scope.currentCourseattendanceStaffs.indexOf($scope.currentStaff) == -1) {
					$scope.currentCourseattendanceStaffs.push($scope.currentStaff);
				}
				$scope.saveToDB($scope.currentStaff);
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	/**
	 * This function is called by the UI when user clicks on the presence toggle
	 * @param {*} member 	Member who's presence is to be updated in the DB
	 */
	$scope.onPresenceChange = function (member) {
		$scope.saveToDB(member);
	}

	$scope.refreshAll = function () {
		translationService.getTranslation($scope, 'courseattendanceview', authenticationService.getCurrentLanguage());
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'presencetypes', 'sequence', 'presencetypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'staffcodes', 'sequence', 'staffcodes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'personnelstatus', 'sequence', 'personnelstatus');
		listsService.getCoaches($scope, authenticationService.getCurrentLanguage());
		listsService.getAllProgramAssistants($scope, authenticationService.getCurrentLanguage());
		listsService.getAllProgramAssistantHelpers($scope, authenticationService.getCurrentLanguage());
		$scope.getCoursesList($scope.selectedCourseattendance);
	}

	$scope.refreshAll();
}]);
