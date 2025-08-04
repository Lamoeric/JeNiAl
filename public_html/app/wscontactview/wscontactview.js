'use strict';

angular.module('cpa_admin.wscontactview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wscontactview', {
		templateUrl: 'wscontactview/wscontactview.html',
		controller: 'wscontactviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
			}
		}
	});
}])

.controller('wscontactviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wscontactview";
	$scope.leftpanetemplatefullpath = "./wscontactview/wscontact.template.html";
	$scope.currentWscontact = null;
	$scope.selectedWscontact = null;
	$scope.newWscontact = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.globalErrorMessage = [];
	$scope.globalWarningMessage = [];
	$scope.formList = [{name:'detailsForm'}];

	// For delay display
	$scope.delay = 0;
	$scope.minDuration = 0;
	$scope.message = 'Please Wait...';
	$scope.backdrop = true;
	$scope.promise = null;

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

	// This is the function that gets all contacts from database
	// $scope.getAllWscontact = function () {
	// 	$scope.promise = $http({
	// 		method: 'post',
	// 		url: './wscontactview/managewscontact.php',
	// 		data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllContacts' }),
	// 		headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
	// 	}).success(function (data, status, headers, config) {
	// 		if (data.success) {
	// 			if (!angular.isUndefined(data.data)) {
	// 				$scope.leftobjs = data.data;
	// 			} else {
	// 				$scope.leftobjs = [];
	// 			}
	// 		} else {
	// 			if (!data.success) {
	// 				dialogService.displayFailure(data);
	// 			}
	// 		}
	// 	}).
	// 	error(function (data, status, headers, config) {
	// 		dialogService.displayFailure(data);
	// 	});
	// };

	// This is the function that gets the current contact from database
	$scope.getWscontactDetails = function (contact) {
		$scope.promise = $http({
			method: 'post',
			url: './wscontactview/managewscontact.php',
			data: $.param({ 'fscname': 'FSC1', 'type': 'getContactDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWscontact = data.data[0];
				$scope.currentWscontact.displaylogofilename = $scope.currentWscontact.logofilename + '?decache=' + Math.random();
				$scope.currentWscontact.displaysliderfilename = $scope.currentWscontact.sliderfilename + '?decache=' + Math.random();
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current contact from database
	$scope.setCurrentInternal = function (contact, index) {
		if (contact != null) {
			$scope.selectedLeftObj = contact;
			$scope.selectedWscontact = contact;
			$scope.getWscontactDetails(contact);
			$scope.setPristine();
		} else {
			$scope.selectedWscontact = null;
			$scope.currentWscontact = null;
			$scope.selectedLeftObj = null;
		}
	}

	// This is the function that selects or reselects the current contact
	$scope.setCurrent = function (contact, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, contact, index);
		} else {
			$scope.setCurrentInternal(contact, index);
		}
	};

	// This is the function that saves the current contact in the database
	$scope.saveToDB = function () {
		if ($scope.currentWscontact == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wscontactview/managewscontact.php',
				data: $.param({ 'contact': $scope.currentWscontact, 'type': 'updateEntireContact' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this contact to reset everything
					$scope.setCurrentInternal($scope.currentWscontact, null);
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

	// This is the function that uploads the logo for the website
	$scope.uploadMainLogo = function (file, errFiles) {
		$scope.f = file;
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
		}
		if (file) {
			if ((file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) && (file.type.indexOf('png') === -1 || file.name.indexOf('.png') === -1)) {
				dialogService.alertDlg('only jpg files are allowed.');
				return;
			}
			file.upload = Upload.upload({
				url: './wscontactview/uploadmainlogo.php',
				method: 'POST',
				file: file,
				data: {
					'mainobj': $scope.currentWscontact,
					'filename': file.name
				}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this contact to reset everything
						$scope.setCurrentInternal($scope.selectedWscontact, null);
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

	// This is the function that uploads the slider for the website
	$scope.uploadMainSlider = function (file, errFiles) {
		$scope.f = file;
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
		}
		if (file) {
			if ((file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) && (file.type.indexOf('png') === -1 || file.name.indexOf('.png') === -1)) {
				dialogService.alertDlg('only jpg files are allowed.');
				return;
			}
			file.upload = Upload.upload({
				url: './wscontactview/uploadmainslider.php',
				method: 'POST',
				file: file,
				data: {
					'mainobj': $scope.currentWscontact,
					'filename': file.name
				}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this contact to reset everything
						$scope.setCurrentInternal($scope.selectedWscontact, null);
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
		$scope.getWscontactDetails();
		translationService.getTranslation($scope, 'wscontactview', authenticationService.getCurrentLanguage());
	}

	$scope.refreshAll();
}]);
