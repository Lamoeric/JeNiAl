'use strict';

angular.module('cpa_admin.wspartnerview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wspartnerview', {
		templateUrl: 'wspartnerview/wspartnerview.html',
		controller: 'wspartnerviewCtrl',
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
					return $q.reject({ authenticated: false, newLocation: "/wspartnerview" });
				}
			}
		}
	});
}])

.controller('wspartnerviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "wspartnerview";
	$scope.currentWspartner = null;
	$scope.selectedWspartner = null;
	$scope.newWspartner = null;
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
	 * This function gets all partners from the database
	 */
	$scope.getAllWspartner = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wspartnerview/managewspartner.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllPartners' }),
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

	/**
	 * This function gets the selected partner from the database
	 * @param {*} partner 
	 */
	$scope.getWspartnerDetails = function (partner) {
		$scope.promise = $http({
			method: 'post',
			url: './wspartnerview/managewspartner.php',
			data: $.param({ 'id': partner.id, 'type': 'getPartnerDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWspartner = data.data[0];
				$scope.currentWspartner.imageinfo_fr = data.imageinfo_fr;
				$scope.currentWspartner.imageinfo_en = data.imageinfo_en;
				$scope.currentWspartner.displayimagefilename_fr = $scope.currentWspartner.imagefilename_fr + '?decache=' + Math.random();
				$scope.currentWspartner.displayimagefilename_en = $scope.currentWspartner.imagefilename_en + '?decache=' + Math.random();
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * This function selects or reselects the current partner from the database
	 * @param {*} partner 
	 * @param {*} index 
	 */
	$scope.setCurrentInternal = function (partner, index) {
		if (partner != null) {
			$scope.selectedLeftObj = partner;
			$scope.selectedWspartner = partner;
			$scope.getWspartnerDetails(partner);
			$scope.setPristine();
		} else {
			$scope.selectedWspartner = null;
			$scope.currentWspartner = null;
			$scope.selectedLeftObj = null;
		}
	}

	/**
	 * This function selects or reselects the partner
	 * @param {*} partner 
	 * @param {*} index 
	 */
	$scope.setCurrent = function (partner, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, partner, index);
		} else {
			$scope.setCurrentInternal(partner, index);
		}
	};

	/**
	 * This function deletes the current partner from database
	 * @param {*} confirmed true if user confirmed the deletion
	 */
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentWspartner != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wspartnerview/managewspartner.php',
				data: $.param({ 'partner': $scope.currentWspartner, 'type': 'delete_partner' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWspartner), 1);
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
	 * This function saves the current partner in the database
	 * @returns 
	 */
	$scope.saveToDB = function () {
		if ($scope.currentWspartner == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wspartnerview/managewspartner.php',
				data: $.param({ 'partner': $scope.currentWspartner, 'type': 'updateEntirePartner' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this partner to reset everything
					$scope.setCurrentInternal($scope.selectedWspartner, null);
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
	 * This function adds a new partner in the database
	 */
	$scope.addWspartnerToDB = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wspartnerview/managewspartner.php',
			data: $.param({ 'partner': $scope.newWspartner, 'type': 'insert_partner' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				var newWspartner = { id: data.id, name: $scope.newWspartner.name };
				$scope.leftobjs.push(newWspartner);
				// We could sort the list....
				$scope.setCurrentInternal(newWspartner);
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
	 * This function creates the modal to create new partner
	 * @param {*} confirmed true if form was dirty and user confirmed it's ok to cancel the modifications to the current partner
	 */
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWspartner = {};
			// Send the newWspartner to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'wspartnerview/newwspartner.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newWspartner;
					}
				}
			}).result.then(function (newWspartner) {
				// User clicked OK and everything was valid.
				$scope.newWspartner = newWspartner;
				if ($scope.addWspartnerToDB() == true) {
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
		$scope.getAllWspartner();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wspartnerview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
