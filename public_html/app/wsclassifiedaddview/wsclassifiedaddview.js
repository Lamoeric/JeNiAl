'use strict';

angular.module('cpa_admin.wsclassifiedaddview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wsclassifiedaddview', {
		templateUrl: 'wsclassifiedaddview/wsclassifiedaddview.html',
		controller: 'wsclassifiedaddviewCtrl',
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
					return $q.reject({ authenticated: false, newLocation: "/wsclassifiedaddview" });
				}
			}
		}
	});
}])

.controller('wsclassifiedaddviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "wsclassifiedaddview";
	$scope.currentWsclassifiedadd = null;
	$scope.selectedWsclassifiedadd = null;
	$scope.newWsclassifiedadd = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.config = null;
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

	// This is the function that gets all classifiedadds from database
	$scope.getAllWsclassifiedadd = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsclassifiedaddview/managewsclassifiedadd.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllClassifiedadds' }),
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

	// This is the function that gets the current classifiedadd from database
	$scope.getWsclassifiedaddDetails = function (classifiedadd) {
		$scope.promise = $http({
			method: 'post',
			url: './wsclassifiedaddview/managewsclassifiedadd.php',
			data: $.param({ 'id': classifiedadd.id, 'language': authenticationService.getCurrentLanguage(), 'type': 'getClassifiedaddDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsclassifiedadd = data.data[0];
				$scope.currentWsclassifiedadd.imageinfo = data.imageinfo;
				$scope.currentWsclassifiedadd.displayimagefilename = $scope.currentWsclassifiedadd.imagefilename + '?decache=' + Math.random();
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current classifiedadd from database
	$scope.setCurrentInternal = function (classifiedadd, index) {
		if (classifiedadd != null) {
			$scope.selectedLeftObj = classifiedadd;
			$scope.selectedWsclassifiedadd = classifiedadd;
			$scope.getWsclassifiedaddDetails(classifiedadd);
			$scope.setPristine();
		} else {
			$scope.selectedWsclassifiedadd = null;
			$scope.currentWsclassifiedadd = null;
			$scope.selectedLeftObj = null;
		}
	}

	// This is the function that selects or reselects the current classifiedadd
	$scope.setCurrent = function (classifiedadd, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, classifiedadd, index);
		} else {
			$scope.setCurrentInternal(classifiedadd, index);
		}
	};

	// This is the function that deletes the current classifiedadd from database
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentWsclassifiedadd != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wsclassifiedaddview/managewsclassifiedadd.php',
				data: $.param({ 'classifiedadd': $scope.currentWsclassifiedadd, 'type': 'delete_classifiedadd' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsclassifiedadd), 1);
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

	// This is the function that saves the current classifiedadd in the database
	$scope.saveToDB = function () {
		if ($scope.currentWsclassifiedadd == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wsclassifiedaddview/managewsclassifiedadd.php',
				data: $.param({ 'classifiedadd': $scope.currentWsclassifiedadd, 'type': 'updateEntireClassifiedadd' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this classifiedadd to reset everything
					$scope.setCurrentInternal($scope.selectedWsclassifiedadd, null);
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

	// This is the function that saves the new classifiedadd in the database
	$scope.addWsclassifiedaddToDB = function (newElement) {
		if (newElement) {
			$scope.newElement = newElement;
			$scope.promise = $http({
				method: 'post',
				url: './wsclassifiedaddview/managewsclassifiedadd.php',
				data: $.param({'classifiedadd': newElement, 'language': authenticationService.getCurrentLanguage(), 'type': 'insertElement'}),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					var newWsclassifiedadd = { id: data.id, name: $scope.newElement.name };
					$scope.leftobjs.push(newWsclassifiedadd);
					// We could sort the list....
					$scope.setCurrentInternal(newWsclassifiedadd);
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
		$rootScope.createNewObject($scope, false, 'wsclassifiedaddview/newwsclassifiedadd.template.html', $scope.addWsclassifiedaddToDB);
	};

	$scope.refreshAll = function () {
		$scope.getAllWsclassifiedadd();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsclassifiedaddview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
