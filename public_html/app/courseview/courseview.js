'use strict';

angular.module('cpa_admin.courseview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/courseview', {
		templateUrl: 'courseview/courseview.html',
		controller: 'courseviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
			}
		}
	});
}])

.controller('courseviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "courseView";
	$scope.currentCourse = null;
	$scope.selectedLeftObj = null;
	$scope.selectedCourse = null;
	$scope.newCourse = null;
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

	$scope.getAllCourses = function () {
		$scope.promise = $http({
				method: 'post',
				url: './courseview/manageCourses.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllCourses' }),
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

	$scope.getCourseDetails = function(course) {
		$scope.promise = $http({
			method: 'post',
			url: './courseview/manageCourses.php',
			data: $.param({'code' : course.code, 'type' : 'getCourseDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data) ){
				$scope.currentCourse = data.data[0];
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (course, index) {
		if (course != null) {
			$scope.selectedCourse = course;
			$scope.getCourseDetails(course);
			$scope.selectedLeftObj = course;
			$scope.setPristine();
		} else {
			$scope.selectedCourse = null;
			$scope.currentCourse = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (course, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, course, index);
		} else {
			$scope.setCurrentInternal(course, index);
		}
	};

	$scope.deleteFromDB = function(confirmed){
		if ($scope.currentCourse != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './courseview/manageCourses.php',
				data: $.param({'course' : $scope.currentCourse, 'type' : 'delete_course' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedCourse),1);
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

		if ($scope.detailsForm.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
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

	$scope.saveToDB = function(){
		if ($scope.currentCourse == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './courseview/manageCourses.php',
				data: $.param({'course' : $scope.currentCourse, 'type' : 'updateEntireCourse' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					// Select this course to reset everything
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

	$scope.addCourseToDB = function(){
		$scope.promise = $http({
			method: 'post',
			url: './courseview/manageCourses.php',
			data: $.param({'course' : $scope.newCourse, 'type' : 'insert_course' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success){
				var newCourse = {code:$scope.newCourse.code, name:$scope.newCourse.name};
				$scope.leftobjs.push(newCourse);
				// We could sort the list....
				$scope.setCurrentInternal(newCourse);
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

	// This is the function that creates the modal to create new course
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newCourse = {};
			// Send the newCourse to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'courseview/newcourse.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newCourse;
						}
					}
			})
			.result.then(function(newCourse) {
					// User clicked OK and everything was valid.
					$scope.newCourse = newCourse;
					if ($scope.addCourseToDB() == true) {
					}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// This is the function that creates the modal to create/edit level
	$scope.editLevel = function(newLevel) {
		$scope.newLevel = {};
		if (newLevel.code) {
			$scope.currentLevel = newLevel;
			// Send the newLevel to the modal form
			for (var prop in newLevel) {
				$scope.newLevel[prop] = newLevel[prop];
			}
		} else {
			$scope.currentLevel = null;
		}
		$uibModal.open({
				animation: false,
				templateUrl: 'courseview/newlevel.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newLevel;
					}
				}
			})
			.result.then(function(newLevel) {
				// User clicked OK and everything was valid.
				if ($scope.currentLevel != null) {
					for (var prop in newLevel) {
						$scope.currentLevel[prop] = newLevel[prop];
					}
					$scope.currentLevel.status = 'Modified';
				} else {
					newLevel.status = 'New';
					if ($scope.currentCourse.levels == null)$scope.currentCourse.levels = [];
					$scope.currentCourse.levels.push(newLevel);
				}
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	$scope.refreshAll = function() {
		$scope.getAllCourses();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'courseview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
