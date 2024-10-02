'use strict';

angular.module('cpa_admin.wscostumeview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wscostumeview', {
		templateUrl: 'wscostumeview/wscostumeview.html',
		controller: 'wscostumeviewCtrl',
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
					return $q.reject({ authenticated: false, newLocation: "/wscostumeview" });
				}
			}
		}
	});
}])

.controller('wscostumeviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "wscostumeview";
	$scope.currentWscostume = null;
	$scope.selectedWscostume = null;
	$scope.newWscostume = null;
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

	// This is the function that gets all costumes from database
	$scope.getAllWscostume = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wscostumeview/managewscostume.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllCostumes' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.leftobjs = data.data;
					$scope.config = data.config;
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

	// This is the function that gets the current costume from database
	$scope.getWscostumeDetails = function (costume) {
		$scope.promise = $http({
			method: 'post',
			url: './wscostumeview/managewscostume.php',
			data: $.param({ 'id': costume.id, 'language': authenticationService.getCurrentLanguage(), 'type': 'getCostumeDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWscostume = data.data[0];
				$scope.currentWscostume.imageinfo = data.imageinfo;
				$scope.currentWscostume.displayimagefilename = $scope.currentWscostume.imagefilename + '?decache=' + Math.random();
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current costume from database
	$scope.setCurrentInternal = function (costume, index) {
		if (costume != null) {
			$scope.selectedLeftObj = costume;
			$scope.selectedWscostume = costume;
			$scope.getWscostumeDetails(costume);
			$scope.setPristine();
		} else {
			$scope.selectedWscostume = null;
			$scope.currentWscostume = null;
			$scope.selectedLeftObj = null;
		}
	}

	// This is the function that selects or reselects the current costume
	$scope.setCurrent = function (costume, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, costume, index);
		} else {
			$scope.setCurrentInternal(costume, index);
		}
	};

	// This is the function that deletes the current costume from database
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentWscostume != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wscostumeview/managewscostume.php',
				data: $.param({ 'costume': $scope.currentWscostume, 'type': 'delete_costume' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWscostume), 1);
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

	// This is the function that saves the current costume in the database
	$scope.saveToDB = function () {
		if ($scope.currentWscostume == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wscostumeview/managewscostume.php',
				data: $.param({ 'costume': $scope.currentWscostume, 'type': 'updateEntireCostume' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this costume to reset everything
					$scope.setCurrentInternal($scope.selectedWscostume, null);
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

	// This is the function that saves the new costume in the database
	$scope.addWscostumeToDB = function (newElement) {
		if (newElement) {
			$scope.newElement = newElement;
			$scope.promise = $http({
				method: 'post',
				url: './wscostumeview/managewscostume.php',
				data: $.param({ 'element': newElement, 'language': authenticationService.getCurrentLanguage(), 'type': 'insertElement' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					var newWscostume = { id: data.id, name: $scope.newElement.name };
					$scope.leftobjs.push(newWscostume);
					// We could sort the list....
					$scope.setCurrentInternal(newWscostume);
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
		$rootScope.createNewObject($scope, false, 'wscostumeview/newwscostume.template.html', $scope.addWscostumeToDB);
	};

	$scope.refreshAll = function () {
		$scope.getAllWscostume();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wscostumeview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
