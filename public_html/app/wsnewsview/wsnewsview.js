'use strict';

angular.module('cpa_admin.wsnewsview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wsnewsview', {
		templateUrl: 'wsnewsview/wsnewsview.html',
		controller: 'wsnewsviewCtrl',
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
					return $q.reject({ authenticated: false, newLocation: "/wsnewsview" });
				}
			}
		}
	});
}])

.controller('wsnewsviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'parseISOdateService', 'dateFilter', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, parseISOdateService, dateFilter, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wsnewsview";
	$scope.currentWsnews = null;
	$scope.selectedWsnews = null;
	$scope.newWsnews = null;
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
	 * This function gets all news from the database
	 */
	$scope.getAllWsnews = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsnewsview/managewsnews.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllNewss' }),
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
	 * This function gets the selected news from the database
	 * @param {*} news 
	 */
	$scope.getWsnewsDetails = function (news) {
		$scope.promise = $http({
			method: 'post',
			url: './wsnewsview/managewsnews.php',
			data: $.param({ 'id': news.id, 'language': authenticationService.getCurrentLanguage(), 'type': 'getNewsDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsnews = data.data[0];
				$scope.currentWsnews.imageinfo = data.imageinfo;
				$scope.currentWsnews.displayimagefilename = $scope.currentWsnews.imagefilename + '?decache=' + Math.random();
				$scope.currentWsnews.publishdate = parseISOdateService.parseDateWithoutTime($scope.currentWsnews.publishdate);
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * This function selects or reselects the current news from the database
	 * @param {*} news 
	 * @param {*} index 
	 */
	$scope.setCurrentInternal = function (news, index) {
		if (news != null) {
			$scope.selectedLeftObj = news;
			$scope.selectedWsnews = news;
			$scope.getWsnewsDetails(news);
			$scope.setPristine();
		} else {
			$scope.selectedWsnews = null;
			$scope.currentWsnews = null;
			$scope.selectedLeftObj = null;
		}
	}

	/**
	 * This function selects or reselects the news
	 * @param {*} news 
	 * @param {*} index 
	 */
	$scope.setCurrent = function (news, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, news, index);
		} else {
			$scope.setCurrentInternal(news, index);
		}
	};

	/**
	 * This function deletes the current news from database
	 * @param {*} confirmed true if user confirmed the deletion
	 */
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentWsnews != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wsnewsview/managewsnews.php',
				data: $.param({ 'news': $scope.currentWsnews, 'type': 'delete_news' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsnews), 1);
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
	 * This function saves the current news in the database
	 * @returns 
	 */
	$scope.saveToDB = function () {
		if ($scope.currentWsnews == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.currentWsnews.publishdatestr = dateFilter($scope.currentWsnews.publishdate, 'yyyy-MM-dd');
			$scope.promise = $http({
				method: 'post',
				url: './wsnewsview/managewsnews.php',
				data: $.param({ 'news': $scope.currentWsnews, 'type': 'updateEntireNews' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this news to reset everything
					$scope.setCurrentInternal($scope.selectedWsnews, null);
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
	 * This function adds a new news in the database
	 */
	$scope.addWsnewsToDB = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsnewsview/managewsnews.php',
			data: $.param({ 'news': $scope.newWsnews, 'type': 'insert_news' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				var newWsnews = { id: data.id, title: $scope.newWsnews.name, publishdate: $scope.newWsnews.publishdate };
				$scope.leftobjs.push(newWsnews);
				// We could sort the list....
				$scope.setCurrentInternal(newWsnews);
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
	 * This function creates the modal to create new news
	 * @param {*} confirmed true if form was dirty and user confirmed it's ok to cancel the modifications to the current news
	 */
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWsnews = {};
			// Send the newWsnews to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'wsnewsview/newwsnews.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newWsnews;
					}
				}
			}).result.then(function (newWsnews) {
				// User clicked OK and everything was valid.
				$scope.newWsnews = newWsnews;
				if ($scope.addWsnewsToDB() == true) {
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
		$scope.getAllWsnews();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsnewsview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
