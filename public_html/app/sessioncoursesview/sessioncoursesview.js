'use strict';

angular.module('cpa_admin.sessioncoursesview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/sessioncoursesview', {
		templateUrl: 'sessioncoursesview/sessioncoursesview.html',
		controller: 'sessioncoursesviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.sessioncourse_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/sessioncoursesview"});
				}
			}
		}
	});
}])

.controller('sessioncoursesviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$window', 'anycodesService', 'dialogService', 'listsService', 'sessionCourseSublevelService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $window, anycodesService, dialogService, listsService, sessionCourseSublevelService, authenticationService, translationService) {

	$scope.progName = "sessioncoursesView";
	$scope.currentCourse = null;
	$scope.selectedCourse = null;
	$scope.newCourse = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.backCurrentEvent = null;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty || $scope.staffsForm.$dirty) {
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
		$scope.staffsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllCourses = function () {
		$scope.promise = $http({
				method: 'post',
				url: './sessioncoursesview/sessioncourses.php',
				data: $.param({'eventType' : $scope.currentEvent.type, 'eventId' : $scope.currentEvent.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllCourses' }),
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

	$scope.getCourseDetails = function(sessioncourses) {
		$scope.promise = $http({
			method: 'post',
			url: './sessioncoursesview/sessioncourses.php',
			data: $.param({'eventType' : $scope.currentEvent.type, 'id' : sessioncourses.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getCourseDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentCourse = data.data[0];
				sessionCourseSublevelService.getCourseSublevelCodes($scope, $http, $scope.currentCourse.id, authenticationService.getCurrentLanguage());
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
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

	$scope.saveToDB = function() {
		if ($scope.currentCourse == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './sessioncoursesview/sessioncourses.php',
				data: $.param({'eventType' : $scope.currentEvent.type, 'sessioncourse' : JSON.stringify($scope.currentCourse), 'type' : 'updateEntireCourse' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this sessioncourses to reset everything
					$scope.setCurrentInternal($scope.selectedCourse, null);
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
	$scope.onCurrentEventChange = function() {
		$scope.getAllCourses();
		$scope.selectedCourse = null;
		$scope.currentCourse = null;
		$scope.selectedLeftObj = null;
	}

	/**
	*			Callback from listsService.getAllSessionsAndShows to force the selection of the first element in the list
	*/	
	$scope.callback = function() {
		$scope.currentEvent = $scope.allSessionsAndShows[0];
		$scope.onCurrentEventChange();
	}
	
	$scope.refreshAll = function() {
		if (!$scope.currentEvent) {
			listsService.getAllSessionsAndShows($scope, authenticationService.getCurrentLanguage(), $scope.callback);
		}
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'presencetypes', 'sequence', 'presencetypes');
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
	$scope.printReport = function(reportName) {
		if (reportName == 'sessionCourseAttendance') {
			if ($scope.currentEvent.type == 1) {
				$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&sessionid='+$scope.currentCourse.sessionid+'&sessionscoursesid='+$scope.currentCourse.id);
			} else {
				$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentCourse.showid+'&showsnumbersid='+$scope.currentCourse.id);
			}
		}
		if (reportName == 'sessionCourseAttendanceBySubLevel') {
			$window.open('./reports/sessionCourseAttendance.php?language='+authenticationService.getCurrentLanguage()+'&sessionid='+$scope.currentCourse.sessionid+'&sessionscoursesid='+$scope.currentCourse.id+'&bysubgroup=true');
		}
		if (reportName == 'sessionCourseSchedule') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&sessionid='+$scope.currentCourse.sessionid+'&sessionscoursesid='+$scope.currentCourse.id);
		}
		if (reportName == 'sessionCoursesList') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&sessionid='+$scope.currentCourse.sessionid+'&sessionscoursesid='+$scope.currentCourse.id);
		}
		if (reportName == 'sessionCoursesListActive') {
			$window.open('./reports/sessionCoursesList.php?language='+authenticationService.getCurrentLanguage()+'&sessionid='+$scope.currentCourse.sessionid+'&sessionscoursesid='+$scope.currentCourse.id+'&activeonly=true');
		}
		if (reportName == 'showPracticeSchedule') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentEvent.id+'&showsnumbersid='+$scope.currentCourse.id);
		}
		if (reportName == 'showNumbersInvitesList') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentEvent.id+'&showsnumbersid='+$scope.currentCourse.id);
		}
		if (reportName == 'showNumbersInvitesListActive') {
			$window.open('./reports/showNumbersInvitesList'+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentEvent.id+'&showsnumbersid='+$scope.currentCourse.id+'&activeonly=true');
		}
		if (reportName == 'sessionCourseCSProgress') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&sessionid='+$scope.currentCourse.sessionid+'&sessionscoursesid='+$scope.currentCourse.id);
		}
		if (reportName == 'sessionCourseCSReportCard') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&sessionid='+$scope.currentCourse.sessionid+'&sessionscoursesid='+$scope.currentCourse.id);
		}
		if (reportName == 'sessionCoursePreCSReportCard') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&sessionid='+$scope.currentCourse.sessionid+'&sessionscoursesid='+$scope.currentCourse.id);
		}
	}

	$scope.refreshAll();
}]);
