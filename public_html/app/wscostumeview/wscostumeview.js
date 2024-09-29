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

	$scope.isDirty = function () {
		if ($scope.detailsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function () {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function () {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

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
		}).
		error(function (data, status, headers, config) {
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
		}).
		error(function (data, status, headers, config) {
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
			}).
			error(function (data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	}

	// This is the function that validates all forms and display error and warning messages
	$scope.validateAllForms = function () {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}
		// if ($scope.currentMember.healthcareno == "") {
		// 	$scope.globalWarningMessage.push($scope.translationObj.main.msgerrallmandatory);
		// }

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
			}).
			error(function (data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	// This is the function that saves the new costume in the database
	$scope.addWscostumeToDB = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wscostumeview/managewscostume.php',
			data: $.param({ 'costume': $scope.newWscostume, 'type': 'insert_costume' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				var newWscostume = { id: data.id, name: $scope.newWscostume.name };
				$scope.leftobjs.push(newWscostume);
				// We could sort the list....
				$scope.setCurrentInternal(newWscostume);
				return true;
			} else {
				dialogService.displayFailure(data);
				return false;
			}
		}).
		error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	// This is the function that creates the modal to create new costume
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWscostume = {};
			// Send the newWscostume to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'wscostumeview/newwscostume.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newWscostume;
					}
				}
			}).result.then(function (newWscostume) {
				// User clicked OK and everything was valid.
				$scope.newWscostume = newWscostume;
				if ($scope.addWscostumeToDB() == true) {
				}
			}, function () {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// This is the function that displays the upload error messages
	$scope.displayUploadError = function (errFile) {
		// dialogService.alertDlg($scope.translationObj.details.msgerrinvalidfile);
		if (errFile.$error == 'maxSize') {
			dialogService.alertDlg($scope.translationObj.details.msgerrinvalidfilesize + errFile.$errorParam);
		} else if (errFile.$error == 'maxWidth') {
			dialogService.alertDlg($scope.translationObj.details.msgerrinvalidmaxwidth + errFile.$errorParam);
		} else if (errFile.$error == 'maxHeight') {
			dialogService.alertDlg($scope.translationObj.details.msgerrinvalidmaxheight + errFile.$errorParam);
		}
	}

	// This is the function that uploads the image for the current costume
	$scope.uploadPictureImage = function (file, errFiles) {
		$scope.f = file;
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
		}
		if (file) {
			if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
				dialogService.alertDlg('only jpg files are allowed.');
				return;
			}
			file.upload = Upload.upload({
				url: './wscostumeview/uploadpictures.php',
				method: 'POST',
				file: file,
				data: {
					'mainobj': $scope.currentWscostume
				}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this event to reset everything
						$scope.setCurrentInternal($scope.selectedWscostume, null);
					} else {
						dialogService.displayFailure(data.data);
					}
				});
			}, function (data) {
				if (!data.success) {
					dialogService.displayFailure(data.data);
				}
			}, function (evt) {
				file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
			});
		}
	}

	$scope.refreshAll = function () {
		$scope.getAllWscostume();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wscostumeview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
