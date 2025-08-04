'use strict';

angular.module('cpa_admin.wsboutiqueview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wsboutiqueview', {
		templateUrl: 'wsboutiqueview/wsboutiqueview.html',
		controller: 'wsboutiqueviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
			}
		}
	});
}])

.controller('wsboutiqueviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wsboutiqueview";
	$scope.currentWsgood = null;
	$scope.selectedWsgood = null;
	$scope.newWsgood = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.config = null;
	$scope.formList = [{name:'detailsForm', errorMsg:'msgerrallmandatory'}];

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
	 * This function gets all goods from the database
	 */
	$scope.getAllWsgood = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsboutiqueview/managewsboutique.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllGoods' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.leftobjs = data.data;
					$scope.config = data.config;
				} else {
					$scope.leftobjs = [];
					$scope.config = data.config;
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
	 * This function gets the selected good from the database
	 * @param {*} good 
	 */
	$scope.getWsgoodDetails = function (good) {
		$scope.promise = $http({
			method: 'post',
			url: './wsboutiqueview/managewsboutique.php',
			data: $.param({ 'id': good.id, 'type': 'getGoodDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsgood = data.data[0];
				$scope.currentWsgood.imageinfo = data.imageinfo;
				$scope.currentWsgood.displayimagefilename = $scope.currentWsgood.imagefilename + '?decache=' + Math.random();
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * This function selects or reselects the current good from the database
	 * @param {*} good 
	 * @param {*} index 
	 */
	$scope.setCurrentInternal = function (good, index) {
		if (good != null) {
			$scope.selectedLeftObj = good;
			$scope.selectedWsgood = good;
			$scope.getWsgoodDetails(good);
			$scope.setPristine();
		} else {
			$scope.selectedWsgood = null;
			$scope.currentWsgood = null;
			$scope.selectedLeftObj = null;
		}
	}

	/**
	 * This function selects or reselects the good
	 * @param {*} good 
	 * @param {*} index 
	 */
	$scope.setCurrent = function (good, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, good, index);
		} else {
			$scope.setCurrentInternal(good, index);
		}
	};

	/**
	 * This function deletes the current good from database
	 * @param {*} confirmed true if user confirmed the deletion
	 */
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentWsgood != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wsboutiqueview/managewsboutique.php',
				data: $.param({ 'good': $scope.currentWsgood, 'type': 'delete_good' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsgood), 1);
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
	 * This function saves the current good in the database
	 * @returns 
	 */
	$scope.saveToDB = function () {
		if ($scope.currentWsgood == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wsboutiqueview/managewsboutique.php',
				data: $.param({ 'good': $scope.currentWsgood, 'type': 'updateEntireGood' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this good to reset everything
					$scope.setCurrentInternal($scope.selectedWsgood, null);
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
	 * This function adds a new good in the database
	 */
	$scope.addWsgoodToDB = function (newElement) {
		if (newElement) {
				$scope.newElement = newElement;
				$scope.promise = $http({
				method: 'post',
				url: './wsboutiqueview/managewsboutique.php',
				data: $.param({'element': newElement, 'language': authenticationService.getCurrentLanguage(), 'type': 'insertElement'}),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					var newWsgood = { id: data.id, mainlabel: $scope.newElement.name, publish: 0 };
					$scope.leftobjs.push(newWsgood);
					// We could sort the list....
					$scope.setCurrentInternal(newWsgood);
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
	 * This function creates the modal to create new boardmember
	 */
	$scope.createNew = function () {
		$rootScope.createNewObject($scope, false, 'wsboutiqueview/newwsboutique.template.html', $scope.addWsgoodToDB);
	};

	/**
	 * This function refreshes everything, called at the start of the program or on a language change
	 */
	$scope.refreshAll = function () {
		$scope.getAllWsgood();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsboutiqueview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
