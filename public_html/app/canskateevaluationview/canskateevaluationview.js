'use strict';

angular.module('cpa_admin.canskateevaluationview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/canskateevaluationview', {
		templateUrl: 'canskateevaluationview/canskateevaluationview.html',
		controller: 'canskateevaluationviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          if (userInfo.privileges.evaluation_access==true) {
            return $q.when(userInfo);
          } else {
            return $q.reject({authenticated: true, validRights: false, newLocation:null});
          }
        } else {
          return $q.reject({authenticated: false, newLocation: "/canskateevaluationview"});
        }
      }
		}
	});
}])

.controller('canskateevaluationviewCtrl', ['$scope', '$http', '$uibModal', 'listsService', 'anycodesService', 'dialogService', 'authenticationService', 'translationService', function($scope, $http, $uibModal, listsService, anycodesService, dialogService, authenticationService, translationService) {
	$scope.progName = "canskateevaluationview";
	$scope.currentCourseElement = null;
	$scope.selectedCourseAndElement = null;
	$scope.isFormPristine = true;

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

	$scope.getCourseElementDetails = function(selectedCourseAndElement) {
		if (selectedCourseAndElement) {
			$scope.promise = $http({
				method: 'post',
				url: './canskateevaluationview/canskateevaluation.php',
				data: $.param({'sessionscoursesid' : selectedCourseAndElement.sessionscourse.id, 'sublevelcode' : selectedCourseAndElement.sessionscoursesubgroup ? selectedCourseAndElement.sessionscoursesubgroup.code : '', 'canskatetestid' : selectedCourseAndElement.canskatetest.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getCourseElementDetails' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success && !angular.isUndefined(data.data) ){
					$scope.currentCourseElement = data.data;
					$scope.currentCourseElement.sessionscourse = selectedCourseAndElement.sessionscourse;
					$scope.currentCourseElement.sessionscoursesubgroup = selectedCourseAndElement.sessionscoursesubgroup;
					$scope.currentCourseElement.canskatecategory = selectedCourseAndElement.canskatecategory;
					$scope.currentCourseElement.canskatetest = selectedCourseAndElement.canskatetest;
				} else {
					dialogService.displayFailure(data);
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
			});
		}
	};

	$scope.setCurrentInternal = function (selectedCourseAndElement, index) {
		if (selectedCourseAndElement != null) {
			$scope.selectedCourseAndElement = selectedCourseAndElement;
			$scope.getCourseElementDetails(selectedCourseAndElement);
			$scope.setPristine();
		} else {
			$scope.selectedCourseAndElement = null;
		}
	}

	$scope.setCurrent = function (selectedCourseAndElement, index) {
		$scope.setCurrentInternal(selectedCourseAndElement, index);
	};

	$scope.saveToDB = function(member, test){
			$http({
				method: 'post',
				url: './canskateevaluationview/canskateevaluation.php',
				data: $.param({'member' : member, 'test' : test, 'type' : 'updateMemberTest' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					// NOTE : Maybe this time we don't refresh everything...
					for (var i = 0, ln = $scope.currentCourseElement.members.length; i < ln; i++) {
						if ($scope.currentCourseElement.members[i].memberid == data.memberid) {
							$scope.currentCourseElement.members[i].testscount = data.testscount;
						}
					}
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
	$scope.selectCourseAndElement = function (selectedCourseAndElement) {
		$scope.newCourseAndElement = {};
		$scope.currentCourseAndElement = selectedCourseAndElement;
		// Copy in another object
		angular.copy(selectedCourseAndElement, $scope.newCourseAndElement);
		// Send the newCourseAndElement to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'canskateevaluationview/selectcourseandelement.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newCourseAndElement;
					}
				}
		})
		.result.then(function(newCourseAndElement) {
				// User clicked OK and everything was valid.
				$scope.setCurrentInternal(newCourseAndElement);
		}, function() {
				// User clicked CANCEL.
				$scope.setCurrentInternal(null);
//	        alert('canceled');
		});
	};

	$scope.onSessionCourseIdChange = function(newObj) {
		if (newObj.sessionscoursesid != null) {
			newObj.sessionscoursesdatesid = null;
			listsService.getRangeCourseDates($scope, newObj.sessionscoursesid, authenticationService.getCurrentLanguage())
		}
	}

	$scope.onTestChange = function(member, test) {
		$scope.saveToDB(member, test);
	}

	$scope.onCanskatetestChange = function() {
			$scope.setCurrentInternal($scope.currentCourseElement);
	}

	$scope.refreshAll = function() {
		listsService.getAllActiveCoursesWithSubGroups($scope, authenticationService.getCurrentLanguage());
		listsService.getAllCanskateTests($scope, authenticationService.getCurrentLanguage());
		translationService.getTranslation($scope, 'canskateevaluationview', authenticationService.getCurrentLanguage());
			$scope.selectCourseAndElement($scope.selectedCourseAndElement);
	}

$('#myNavbar').collapse('hide');
	$scope.refreshAll();
}]);
