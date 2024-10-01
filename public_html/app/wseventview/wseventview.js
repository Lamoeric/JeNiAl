'use strict';

angular.module('cpa_admin.wseventview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wseventview', {
		templateUrl: 'wseventview/wseventview.html',
		controller: 'wseventviewCtrl',
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
					return $q.reject({ authenticated: false, newLocation: "/wseventview" });
				}
			}
		}
	});
}])

.controller('wseventviewCtrl', ['$q', '$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'parseISOdateService', 'dateFilter', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($q, $rootScope, $scope, $http, $uibModal, $timeout, parseISOdateService, dateFilter, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "wseventview";
	$scope.currentWsevent = null;
	$scope.selectedWsevent = null;
	$scope.newWsevent = null;
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
	 * This function gets all events from the database
	 */
	$scope.getAllWsevent = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wseventview/managewsevent.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllEvents' }),
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
	 * This function gets the selected event from the database
	 * @param {*} event 
	 */
	$scope.getWseventDetails = function (event) {
		$scope.promise = $http({
			method: 'post',
			url: './wseventview/managewsevent.php',
			data: $.param({ 'id': event.id, 'language': authenticationService.getCurrentLanguage(), 'type': 'getEventDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsevent = data.data[0];
				$scope.currentWsevent.imageinfo = data.imageinfo;
				$scope.currentWsevent.displayimagefilename = $scope.currentWsevent.imagefilename + '?decache=' + Math.random();
				$scope.currentWsevent.eventdate = parseISOdateService.parseDateWithoutTime($scope.currentWsevent.eventdate);
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * This function selects or reselects the current event from the database
	 * @param {*} event 
	 * @param {*} index 
	 */
	$scope.setCurrentInternal = function (event, index) {
		if (event != null) {
			$scope.selectedLeftObj = event;
			$scope.selectedWsevent = event;
			$scope.getWseventDetails(event);
			$scope.setPristine();
		} else {
			$scope.selectedWsevent = null;
			$scope.currentWsevent = null;
			$scope.selectedLeftObj = null;
		}
	}

	/**
	 * This function selects or reselects the event
	 * @param {*} event 
	 * @param {*} index 
	 */
	$scope.setCurrent = function (event, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, event, index);
		} else {
			$scope.setCurrentInternal(event, index);
		}
	};

	/**
	 * This function deletes the current event from database
	 * @param {*} confirmed true if user confirmed the deletion
	 */
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentWsevent != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wseventview/managewsevent.php',
				data: $.param({ 'event': $scope.currentWsevent, 'type': 'delete_event' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsevent), 1);
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
	 * This function saves the current event in the database
	 * @returns 
	 */
	$scope.saveToDB = function () {
		if ($scope.currentWsevent == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.currentWsevent.eventdatestr = dateFilter($scope.currentWsevent.eventdate, 'yyyy-MM-dd');
			$scope.promise = $http({
				method: 'post',
				url: './wseventview/managewsevent.php',
				data: $.param({ 'event': JSON.stringify($scope.currentWsevent), 'type': 'updateEntireEvent' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this event to reset everything
					$scope.setCurrentInternal($scope.selectedWsevent, null);
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
	 * This function adds a new event in the database
	 */
	$scope.addWseventToDB = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wseventview/managewsevent.php',
			data: $.param({ 'event': $scope.newWsevent, 'type': 'insert_event' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				var newWsevent = { id: data.id, name: $scope.newWsevent.name, eventdate: null };
				$scope.leftobjs.push(newWsevent);
				// We could sort the list....
				$scope.setCurrentInternal(newWsevent);
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
	 * This function creates the modal to create new event
	 * @param {*} confirmed true if form was dirty and user confirmed it's ok to cancel the modifications to the current event
	 */
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWsevent = {};
			// Send the newWsevent to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'wseventview/newwsevent.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newWsevent;
					}
				}
			}).result.then(function (newWsevent) {
				// User clicked OK and everything was valid.
				$scope.newWsevent = newWsevent;
				if ($scope.addWseventToDB() == true) {
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
		$scope.getAllWsevent();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'wseventtypes', 'text', 'wseventtypes');
		translationService.getTranslation($scope, 'wseventview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
