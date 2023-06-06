'use strict';

angular.module('cpa_admin.courseattendanceview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/courseattendanceview', {
		templateUrl: 'courseattendanceview/courseattendanceview.html',
		controller: 'courseattendanceviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          if (userInfo.privileges.attendance_access==true) {
            return $q.when(userInfo);
          } else {
            return $q.reject({authenticated: true, validRights: false, newLocation:null});
          }
        } else {
          return $q.reject({authenticated: false, newLocation: "/courseattendanceview"});
        }
      }
		}
	});
}])

.controller('courseattendanceviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'listsService', 'anycodesService', 'dialogService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, listsService, anycodesService, dialogService, authenticationService, translationService) {
	$scope.progName = "courseattendanceView";
	$scope.currentCourseattendance = null;
	$scope.selectedCourseattendance = null;
	$scope.currentCourseDetails = null;
	$scope.newCourseattendance = null;
	$scope.selectedLeftObj = null;
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
	};

	$scope.setPristine = function() {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.refreshAttendance = function(data) {
		$scope.currentCourseattendance = data.skaters;
		$scope.currentCourseattendanceStaff = data.personnelperm;
		$scope.currentCourseattendanceStaffrepl = data.personnelrepl;
		$scope.currentCourseDetails = data.coursedetails;
		for (var x = 0; x < $scope.currentCourseattendance.length; x++) {
			if (!$scope.currentCourseattendance[x].ispresent || $scope.currentCourseattendance[x].ispresent == 0) {
				$scope.currentCourseattendance[x].ispresent = false;
			} else {
				$scope.currentCourseattendance[x].ispresent = true;
			}
		}
		for (var x = 0; $scope.currentCourseattendanceStaff && x < $scope.currentCourseattendanceStaff.length; x++) {
			if ($scope.currentCourseattendanceStaff[x].ispresent == null || ($scope.currentCourseattendanceStaff[x].ispresent != null && isNaN($scope.currentCourseattendanceStaff[x].ispresent)) == true) {
				$scope.currentCourseattendanceStaff[x].ispresent = 0;
			}
		}
		for (var x = 0; $scope.currentCourseattendanceStaffrepl && x < $scope.currentCourseattendanceStaffrepl.length; x++) {
			if ($scope.currentCourseattendanceStaffrepl[x].ispresent == null || ($scope.currentCourseattendanceStaffrepl[x].ispresent != null && isNaN($scope.currentCourseattendanceStaffrepl[x].ispresent)) == true) {
				$scope.currentCourseattendanceStaffrepl[x].ispresent = 0;
			}
		}
		$rootScope.repositionLeftColumn();
	}

	$scope.getCourseAttendanceDetails = function(courseattendance) {
		$scope.promise = $http({
			method: 'post',
			url: './courseattendanceview/courseattendance.php',
			data: $.param({'eventtype' : courseattendance.sessionscourse.type, 'sessionscoursesid' : courseattendance.sessionscourse.id, 'sessionscoursesdatesid' : courseattendance.sessionscoursesdate.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getCourseAttendanceDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success) {
				if (!angular.isUndefined(data.skaters) ){
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
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.getCoursesList = function(selectedCourseattendance) {
		$scope.promise = $http({
			method: 'post',
			url: './courseattendanceview/courseattendance.php',
			data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getCoursesList' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success && !angular.isUndefined(data.data) ){
				$scope.activeCourses = data.data;
				$scope.selectCourseDate(selectedCourseattendance);
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
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

	$scope.saveToDB = function(memberattendance, newPresence){
		if (newPresence) memberattendance.ispresent = newPresence;
			$scope.promise = $http({
				method: 'post',
				url: './courseattendanceview/courseattendance.php',
				data: $.param({'memberattendance' : memberattendance, 'eventtype' : $scope.selectedCourseattendance.sessionscourse.type, 'sessionscoursesid' : $scope.selectedCourseattendance.sessionscourse.id, 'sessionscoursesdatesid' : $scope.selectedCourseattendance.sessionscoursesdate.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'updateMemberAttendance' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					$scope.refreshAttendance(data.CourseDetail);
					// NOTE : Maybe this time we don't refresh everything...
					// $scope.setCurrentInternal($scope.selectedCourseattendance, $scope.selectedIndex);
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
		})
		.result.then(function(newCourseDate) {
				// User clicked OK and everything was valid.
				$scope.setCurrentInternal(newCourseDate);
		}, function() {
			$scope.setCurrentInternal(null);
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// $scope.onSessionCourseIdChange = function(newObj) {
	// 	if (newObj.sessionscoursesid != null) {
	// 		newObj.sessionscoursesdatesid = null;
	// 		listsService.getRangeCourseDates($scope, newObj.sessionscoursesid, authenticationService.getCurrentLanguage())
	// 	}
	// }

	$scope.onPresenceChange = function(member) {
		$scope.saveToDB(member);
	}

	$scope.refreshAll = function() {
		translationService.getTranslation($scope, 'courseattendanceview', authenticationService.getCurrentLanguage());
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'presencetypes', 'sequence', 'presencetypes');
		$scope.getCoursesList($scope.selectedCourseattendance);
	}

	$scope.refreshAll();
}]);
