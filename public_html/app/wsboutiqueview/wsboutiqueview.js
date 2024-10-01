'use strict';

angular.module('cpa_admin.wsboutiqueview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wsboutiqueview', {
		templateUrl: 'wsboutiqueview/wsboutiqueview.html',
		controller: 'wsboutiqueviewCtrl',
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
					return $q.reject({ authenticated: false, newLocation: "/wsboutiqueview" });
				}
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

	/**
	 * This function checks if anything is dirty
	 * @returns true if any of the forms are dirty, false otherwise
	 */
	$scope.isDirty = function () {
		if ($scope.detailsForm.$dirty) {
			return true;
		}
		return false;
	};

	/**
	 * This function sets one form dirty to indicate the whole thing is dirty
	 */
	$scope.setDirty = function () {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	/**
	 * This function sets all the forms as pristine
	 */
	$scope.setPristine = function () {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

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
	 * This function validates all forms and display error and warning messages
	 * @returns false if something is invalid
	 */
	$scope.validateAllForms = function () {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}

		if ($scope.globalErrorMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function () { $("#mainglobalerrormessage").hide(); });
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function () { $("#mainglobalwarningmessage").hide(); });
		}
		return retVal;
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
	$scope.addWsgoodToDB = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsboutiqueview/managewsboutique.php',
			data: $.param({ 'good': $scope.newWsgood, 'type': 'insert_good' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				var newWsgood = { id: data.id, mainlabel: $scope.newWsgood.name, publish: 0 };
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
	};

	/**
	 * This function creates the modal to create new good
	 * @param {*} confirmed true if form was dirty and user confirmed it's ok to cancel the modifications to the current good
	 */
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWsgood = {};
			// Send the newWsgood to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'wsboutiqueview/newwsboutique.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newWsgood;
					}
				}
			}).result.then(function (newWsgood) {
				// User clicked OK and everything was valid.
				$scope.newWsgood = newWsgood;
				if ($scope.addWsgoodToDB() == true) {
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
		$scope.getAllWsgood();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsboutiqueview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
