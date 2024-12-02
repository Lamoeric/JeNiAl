'use strict';

angular.module('cpa_admin.wsboardmemberview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wsboardmemberview', {
		templateUrl: 'wsboardmemberview/wsboardmemberview.html',
		controller: 'wsboardmemberviewCtrl',
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
					return $q.reject({ authenticated: false, newLocation: "/wsboardmemberview" });
				}
			}
		}
	});
}])

.controller('wsboardmemberviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "wsboardmemberview";
	$scope.currentWsboardmember = null;
	$scope.selectedWsboardmember = null;
	$scope.newWsboardmember = null;
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
	 * This function gets all boardmembers from the database
	 */
	$scope.getAllWsboardmember = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsboardmemberview/managewsboardmember.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllBoardmembers' }),
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
	 * This function gets the selected boardmember from the database
	 * @param {*} boardmember 
	 */
	$scope.getWsboardmemberDetails = function (boardmember) {
		$scope.promise = $http({
			method: 'post',
			url: './wsboardmemberview/managewsboardmember.php',
			data: $.param({ 'id': boardmember.id, 'type': 'getBoardmemberDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsboardmember = data.data[0];
				// $scope.currentWsboardmember.imageinfo = data.imageinfo;
				$scope.currentWsboardmember.displayimagefilename = $scope.currentWsboardmember.imagefilename + '?decache=' + Math.random();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * This function selects or reselects the current boardmember from the database
	 * @param {*} boardmember 
	 * @param {*} index 
	 */
	$scope.setCurrentInternal = function (boardmember, index) {
		if (boardmember != null) {
			$scope.selectedLeftObj = boardmember;
			$scope.selectedWsboardmember = boardmember;
			$scope.getWsboardmemberDetails(boardmember);
			$scope.setPristine();
		} else {
			$scope.selectedWsboardmember = null;
			$scope.currentWsboardmember = null;
			$scope.selectedLeftObj = null;
		}
	}

	/**
	 * This function selects or reselects the boardmember
	 * @param {*} boardmember 
	 * @param {*} index 
	 */
	$scope.setCurrent = function (boardmember, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, boardmember, index);
		} else {
			$scope.setCurrentInternal(boardmember, index);
		}
	};

	/**
	 * This function deletes the current boardmember from database
	 * @param {*} confirmed true if user confirmed the deletion
	 */
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentWsboardmember != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wsboardmemberview/managewsboardmember.php',
				data: $.param({ 'boardmember': $scope.currentWsboardmember, 'type': 'delete_boardmember' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsboardmember), 1);
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
	 * This function saves the current boardmember in the database
	 * @returns 
	 */
	$scope.saveToDB = function () {
		if ($scope.currentWsboardmember == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wsboardmemberview/managewsboardmember.php',
				data: $.param({ 'boardmember': $scope.currentWsboardmember, 'language': authenticationService.getCurrentLanguage(), 'type': 'updateEntireBoardmember' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this boardmember to reset everything
					$scope.setCurrentInternal($scope.selectedWsboardmember, null);
					angular.copy(data.element, $scope.selectedWsboardmember);
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
	 * This function adds a new boardmember in the database
	 */
	$scope.addWsboardmemberToDB = function (newElement) {
		if (newElement) {
			$scope.promise = $http({
				method: 'post',
				url: './wsboardmemberview/managewsboardmember.php',
				data: $.param({'element': newElement, 'language': authenticationService.getCurrentLanguage(), 'type': 'insertElement'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.push(data.element);
					$scope.setCurrentInternal(data.element);
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
		$rootScope.createNewObject($scope, false, 'wsboardmemberview/newwsboardmember.template.html', $scope.addWsboardmemberToDB);
	};

	/**
	 * This function refreshes everything, called at the start of the program or on a language change
	 */
	$scope.refreshAll = function () {
		$scope.getAllWsboardmember();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsboardmemberview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
