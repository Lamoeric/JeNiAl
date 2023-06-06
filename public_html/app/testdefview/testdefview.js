'use strict';

angular.module('cpa_admin.testdefview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/testdefview', {
		templateUrl: 'testdefview/testdefview.html',
		controller: 'testdefviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.design_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/testdefview"});
				}
			}
		}
	});
}])

.controller('testdefviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "testdefView";
	$scope.currentTest = null;
	$scope.selectedLeftObj = null;
	// $scope.selectedTest = null;
	$scope.newTest = null;
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

	$scope.getAllTests = function () {
		$scope.promise = $http({
				method: 'post',
				url: './testdefview/manageTestsDefinitions.php',
				data: $.param({'language' : 'en-ca'/*$scope.context.preferedlanguage*/, 'type' : 'getAllTests' }),
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

	$scope.getTestDetails = function (test) {
		$scope.promise = $http({
			method: 'post',
			url: './testdefview/manageTestsDefinitions.php',
			data: $.param({'id' : test.id, 'type' : 'getTestDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success && !angular.isUndefined(data.data) ){
				$scope.currentTest = data.data[0];
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (test, index) {
		if (test != null) {
			$scope.selectedLeftObj = test;
			$scope.getTestDetails(test);
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentTest = null;
		}
	}

	$scope.setCurrent = function (test, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, test, index);
		} else {
			$scope.setCurrentInternal(test, index);
		}
	};

	$scope.deleteFromDB = function(confirmed){
		if ($scope.currentTest != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './testdefview/manageTestsDefinitions.php',
				data: $.param({'test' : $scope.currentTest, 'type' : 'delete_test' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
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
		if ($scope.currentTest == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './testdefview/manageTestsDefinitions.php',
				data: $.param({'test' : $scope.currentTest, 'type' : 'updateEntireTest' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					// Select this test to reset everything
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

	$scope.addTestToDB = function(){
		$scope.promise = $http({
			method: 'post',
			url: './testdefview/manageTestsDefinitions.php',
			data: $.param({'test' : $scope.newTest, 'type' : 'insert_test' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success){
				var newTest = {id:data.id, code:$scope.newTest.code, type:$scope.newTest.type};
				$scope.leftobjs.push(newTest);
				// We could sort the list....
				$scope.setCurrentInternal(newTest);
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

	// This is the function that creates the modal to create new test
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newTest = {};
			// Send the newTest to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'testdefview/newtestdef.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newTest;
						}
					}
			})
			.result.then(function(newTest) {
					// User clicked OK and everything was valid.
					$scope.newTest = newTest;
					if ($scope.addTestToDB() == true) {
					}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	$scope.refreshAll = function() {
		$scope.getAllTests();
		anycodesService.getAnyCodes($scope, $http, 'en-ca'/*$scope.context.preferedlanguage*/,'testtypes', 'text', 'testtypes');

		anycodesService.getAnyCodes($scope, $http, 'en-ca'/*$scope.context.preferedlanguage*/,'testlevels', 			'sequence', 'testlevels');
		anycodesService.getAnyCodes($scope, $http, 'en-ca'/*$scope.context.preferedlanguage*/,'testtypes', 				'text', 		'testtypes');
		anycodesService.getAnyCodes($scope, $http, 'en-ca'/*$scope.context.preferedlanguage*/,'testsubtypes', 	 	'text', 		'testsubtypes');
		anycodesService.getAnyCodes($scope, $http, 'en-ca'/*$scope.context.preferedlanguage*/,'testsubsubtypes', 	'text', 		'testsubsubtypes');

		listsService.getAllTestsDefinitions($scope, $http, 'en-ca'/*$scope.context.preferedlanguage*/);
		translationService.getTranslation($scope, 'testdefview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
