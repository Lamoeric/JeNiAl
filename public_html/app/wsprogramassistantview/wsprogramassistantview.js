'use strict';

angular.module('cpa_admin.wsprogramassistantview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wsprogramassistantview', {
		templateUrl: 'wsprogramassistantview/wsprogramassistantview.html',
		controller: 'wsprogramassistantviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.admin_access == true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({ authenticated: true, validRights: false, newLocation: null });
					}
				} else {
					return $q.reject({ authenticated: false, newLocation: "/wsprogramassistantview" });
				}
			}
		}
	});
}])

.controller('wsprogramassistantviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "wsprogramassistantview";
	$scope.currentWsprogramassistant = null;
	$scope.selectedWsprogramassistant = null;
	$scope.newWsprogramassistant = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.formList = [{name:'detailsForm'}];

	/**
	 * This function checks if anything is dirty
	 * @returns true if any of the forms are dirty, false otherwise
	 */
	$scope.isDirty = function () {
		return $rootScope.isDirty($scope, $scope.formList);
	};

	/**
	 * This function sets one form dirty to indicate the whole thing is dirty
	 */
	$scope.setDirty = function () {
		$rootScope.setDirty($scope, $scope.formList);
	};

	/**
	 * This function sets all the forms as pristine
	 */
	$scope.setPristine = function () {
		$rootScope.setPristine($scope, $scope.formList);
	};

	/**
	 * This function validates all forms and display error and warning messages
	 * @returns false if something is invalid
	 */
	$scope.validateAllForms = function () {
		return $rootScope.validateAllForms($scope, $scope.formList);
	}

	/**
	 * This function gets all programassistants from the database
	 */
	$scope.getAllWsprogramassistant = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsprogramassistantview/managewsprogramassistant.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllProgramassistants' }),
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

	/**
	 * This function gets the selected programassistant from the database
	 * @param {*} programassistant 
	 */
	$scope.getWsprogramassistantDetails = function (programassistant) {
		$scope.promise = $http({
			method: 'post',
			url: './wsprogramassistantview/managewsprogramassistant.php',
			data: $.param({ 'id': programassistant.id, 'type': 'getProgramassistantDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsprogramassistant = data.data[0];
				$scope.currentWsprogramassistant.imageinfo = data.imageinfo;
				$scope.currentWsprogramassistant.displayimagefilename = $scope.currentWsprogramassistant.imagefilename + '?decache=' + Math.random();
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * This function selects or reselects the current programassistant from the database
	 * @param {*} programassistant 
	 * @param {*} index 
	 */
	$scope.setCurrentInternal = function (programassistant, index) {
		if (programassistant != null) {
			$scope.selectedLeftObj = programassistant;
			$scope.selectedWsprogramassistant = programassistant;
			$scope.getWsprogramassistantDetails(programassistant);
			$scope.setPristine();
		} else {
			$scope.selectedWsprogramassistant = null;
			$scope.currentWsprogramassistant = null;
			$scope.selectedLeftObj = null;
		}
	}

	/**
	 * This function selects or reselects the programassistant coach
	 * @param {*} programassistant 
	 * @param {*} index 
	 */
	$scope.setCurrent = function (programassistant, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, programassistant, index);
		} else {
			$scope.setCurrentInternal(programassistant, index);
		}
	};

	/**
	 * This function deletes the current programassistant from database
	 * @param {*} confirmed true if user confirmed the deletion
	 */
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentWsprogramassistant != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wsprogramassistantview/managewsprogramassistant.php',
				data: $.param({ 'programassistant': $scope.currentWsprogramassistant, 'type': 'delete_programassistant' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsprogramassistant), 1);
					$scope.setCurrentInternal(null);
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
	}

	/**
	 * This function saves the current programassistant in the database
	 * @returns 
	 */
	$scope.saveToDB = function () {
		if ($scope.currentWsprogramassistant == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wsprogramassistantview/managewsprogramassistant.php',
				data: $.param({ 'programassistant': $scope.currentWsprogramassistant, 'type': 'updateEntireProgramassistant' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this programassistant to reset everything
					$scope.setCurrentInternal($scope.selectedWsprogramassistant, null);
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

	/**
	 * This function adds a new programassistant in the database
	 */
	$scope.addWsprogramassistantToDB = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsprogramassistantview/managewsprogramassistant.php',
			data: $.param({ 'programassistant': $scope.newWsprogramassistant, 'type': 'insert_programassistant' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				var newWsprogramassistant = { id: data.id, firstname: $scope.newWsprogramassistant.firstname, lastname: $scope.newWsprogramassistant.lastname };
				$scope.leftobjs.push(newWsprogramassistant);
				// We could sort the list....
				$scope.setCurrentInternal(newWsprogramassistant);
				return true;
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
	 * This function creates the modal to create new programassistant
	 * @param {*} confirmed true if form was dirty and user confirmed it's ok to cancel the modifications to the current programassistant
	 */
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWsprogramassistant = {};
			// Send the newWsprogramassistant to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'wsprogramassistantview/newwsprogramassistant.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newWsprogramassistant;
					}
				}
			}).result.then(function (newWsprogramassistant) {
				// User clicked OK and everything was valid.
				$scope.newWsprogramassistant = newWsprogramassistant;
				if ($scope.addWsprogramassistantToDB() == true) {
				}
			}, function () {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	/**
	 * This function refreshes everything, called at the start of the program or on a language change
	 */
	$scope.refreshAll = function () {
		$scope.getAllWsprogramassistant();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsprogramassistantview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
