'use strict';

angular.module('cpa_admin.showtaskview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/showtaskview', {
		templateUrl: 'showtaskview/showtaskview.html',
		controller: 'showtaskviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
			}
		}
	});
}])

.controller('showtaskviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "showTaskView";
	$scope.currentTask = null;
	$scope.selectedLeftObj = null;
	$scope.newTask = null;
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

	$scope.getAllTasks = function () {
		$scope.promise = $http({
				method: 'post',
				url: './showtaskview/showtask.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllTasks' }),
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

	$scope.getTaskDetails = function (task) {
		$scope.promise = $http({
			method: 'post',
			url: './showtaskview/showtask.php',
			data: $.param({'id' : task.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getTaskDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success && !angular.isUndefined(data.data) ){
				$scope.currentTask = data.data[0];
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (task, index) {
		if (task != null) {
			$scope.selectedLeftObj = task;
			$scope.getTaskDetails(task);
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentTask = null;
		}
	}

	$scope.setCurrent = function (task, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, task, index);
		} else {
			$scope.setCurrentInternal(task, index);
		}
	};

	$scope.deleteFromDB = function(confirmed){
		if ($scope.currentTask != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './showtaskview/showtask.php',
				data: $.param({'task' : $scope.currentTask, 'type' : 'delete_task' }),
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
		if ($scope.currentTask == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './showtaskview/showtask.php',
				data: $.param({'task' : $scope.currentTask, 'type' : 'updateEntireTask' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					// Select this task to reset everything
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

	$scope.addTaskToDB = function(){
		$http({
			method: 'post',
			url: './showtaskview/showtask.php',
			data: $.param({'task' : $scope.newTask, 'type' : 'insert_task' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success){
				var newTask = {id:data.id, text:(authenticationService.getCurrentLanguage()=='fr-ca' ? $scope.newTask.label_fr : $scope.newTask.label_en)};
				$scope.leftobjs.push(newTask);
				// We could sort the list....
				$scope.setCurrentInternal(newTask);
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

	// This is the function that creates the modal to create new task
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newTask = {};
			// Send the newTask to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'showtaskview/newshowtask.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newTask;
						}
					}
			})
			.result.then(function(newTask) {
					// User clicked OK and everything was valid.
					$scope.newTask = newTask;
					$scope.newTask.label_fr = newTask.name;
					$scope.newTask.label_en = newTask.name;
					if ($scope.addTaskToDB() == true) {
					}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	$scope.refreshAll = function() {
		$scope.getAllTasks();
		listsService.getAllPrivileges($scope, authenticationService.getCurrentLanguage());
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'taskcategories', 'sequence', 'taskcategories');
		translationService.getTranslation($scope, 'showtaskview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
