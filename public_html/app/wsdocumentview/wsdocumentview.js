'use strict';

angular.module('cpa_admin.wsdocumentview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wsdocumentview', {
		templateUrl: 'wsdocumentview/wsdocumentview.html',
		controller: 'wsdocumentviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
			}
		}
	});
}])

.controller('wsdocumentviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'parseISOdateService', 'dateFilter', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, parseISOdateService, dateFilter, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "wsdocumentview";
	$scope.currentWsdocument = null;
	$scope.selectedWsdocument = null;
	$scope.newWsdocument = null;
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

	// This is the function that gets all documents from database
	$scope.getAllWsdocument = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsdocumentview/managewsdocument.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllDocuments' }),
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

	// This is the function that gets the current document from database
	$scope.getWsdocumentDetails = function (document) {
		$scope.promise = $http({
			method: 'post',
			url: './wsdocumentview/managewsdocument.php',
			data: $.param({ 'id': document.id, 'type': 'getDocumentDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsdocument = data.data[0];
				$scope.currentWsdocument.fileinfo_fr = data.fileinfo_fr;
				$scope.currentWsdocument.fileinfo_en = data.fileinfo_en;
				$scope.currentWsdocument.displayfilename_fr = $scope.currentWsdocument.filename_fr + '?decache=' + Math.random();
				$scope.currentWsdocument.displayfilename_en = $scope.currentWsdocument.filename_en + '?decache=' + Math.random();
				$scope.currentWsdocument.publishon = parseISOdateService.parseDateWithoutTime($scope.currentWsdocument.publishon);
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current document from database
	$scope.setCurrentInternal = function (document, index) {
		if (document != null) {
			$scope.selectedLeftObj = document;
			$scope.selectedWsdocument = document;
			$scope.getWsdocumentDetails(document);
			$scope.setPristine();
		} else {
			$scope.selectedWsdocument = null;
			$scope.currentWsdocument = null;
			$scope.selectedLeftObj = null;
		}
	}

	// This is the function that selects or reselects the current document
	$scope.setCurrent = function (document, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, document, index);
		} else {
			$scope.setCurrentInternal(document, index);
		}
	};

	// This is the function that deletes the current document from database
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentWsdocument != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wsdocumentview/managewsdocument.php',
				data: $.param({ 'document': $scope.currentWsdocument, 'type': 'delete_document' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsdocument), 1);
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

	// This is the function that saves the current document in the database
	$scope.saveToDB = function () {
		if ($scope.currentWsdocument == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.currentWsdocument.publishonstr = dateFilter($scope.currentWsdocument.publishon, 'yyyy-MM-dd');
			$scope.promise = $http({
				method: 'post',
				url: './wsdocumentview/managewsdocument.php',
				data: $.param({ 'document': $scope.currentWsdocument, 'type': 'updateEntireDocument' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this document to reset everything
					$scope.setCurrentInternal($scope.selectedWsdocument, null);
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

	// This is the function that saves the new document in the database
	$scope.addWsdocumentToDB = function (newElement) {
		if (newElement) {
			$scope.newElement = newElement;
			$scope.promise = $http({
				method: 'post',
				url: './wsdocumentview/managewsdocument.php',
				data: $.param({ 'element': newElement, 'language': authenticationService.getCurrentLanguage(), 'type': 'insertElement' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					var newWsdocument = { id: data.id, documentname: $scope.newElement.documentname };
					$scope.leftobjs.push(newWsdocument);
					// We could sort the list....
					$scope.setCurrentInternal(newWsdocument);
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
		$rootScope.createNewObject($scope, false, 'wsdocumentview/newwsdocument.template.html', $scope.addWsdocumentToDB);
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

	// This is the function that uploads the french document
	$scope.uploadMainFileFr = function (file, errFiles) {
		$scope.f = file;
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
		}
		if (file) {
			file.upload = Upload.upload({
				url: './wsdocumentview/uploadmainimage.php',
				method: 'POST',
				file: file,
				data: {
					'mainobj': $scope.currentWsdocument,
					'language': 'fr-ca',
					'filename': file.name
				}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this document to reset everything
						$scope.setCurrentInternal($scope.selectedWsdocument, null);
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

	// This is the function that uploads the english document
	$scope.uploadMainFileEn = function (file, errFiles) {
		$scope.f = file;
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
		}
		if (file) {
			file.upload = Upload.upload({
				url: './wsdocumentview/uploadmainimage.php',
				method: 'POST',
				file: file,
				data: {
					'mainobj': $scope.currentWsdocument,
					'language': 'en-ca',
					'filename': file.name
				}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this document to reset everything
						$scope.setCurrentInternal($scope.selectedWsdocument, null);
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
		$scope.getAllWsdocument();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsdocumentview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
