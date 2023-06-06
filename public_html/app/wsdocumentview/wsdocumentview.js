'use strict';

angular.module('cpa_admin.wsdocumentview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/wsdocumentview', {
		templateUrl: 'wsdocumentview/wsdocumentview.html',
		controller: 'wsdocumentviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          if (userInfo.privileges.admin_access==true) {
            return $q.when(userInfo);
          } else {
            return $q.reject({authenticated: true, validRights: false, newLocation:null});
          }
        } else {
          return $q.reject({authenticated: false, newLocation: "/wsdocumentview"});
        }
      }
		}
	});
}])

.controller('wsdocumentviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'parseISOdateService', 'dateFilter', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $timeout, parseISOdateService, dateFilter, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wsdocumentview";
	$scope.currentWsdocument = null;
	$scope.selectedWsdocument = null;
	$scope.newWsdocument = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function() {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	// This is the function that gets all documents from database
	$scope.getAllWsdocument = function () {
		$scope.promise = $http({
				method: 'post',
				url: './wsdocumentview/managewsdocument.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllDocuments' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.leftobjs = data.data;
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
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that gets the current document from database
	$scope.getWsdocumentDetails = function (document) {
		$scope.promise = $http({
			method: 'post',
			url: './wsdocumentview/managewsdocument.php',
			data: $.param({'id' : document.id, 'type' : 'getDocumentDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
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
		}).
		error(function(data, status, headers, config) {
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
	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentWsdocument != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wsdocumentview/managewsdocument.php',
				data: $.param({'document' : $scope.currentWsdocument, 'type' : 'delete_document' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsdocument),1);
					$scope.setCurrentInternal(null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	}

	// This is the function that validates all forms and display error and warning messages
	$scope.validateAllForms = function() {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}

		if ($scope.globalErrorMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function(){$("#mainglobalerrormessage").hide();});
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function(){$("#mainglobalwarningmessage").hide();});
		}
		return retVal;
	}

	// This is the function that saves the current document in the database
	$scope.saveToDB = function() {
		if ($scope.currentWsdocument == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.currentWsdocument.publishonstr = dateFilter($scope.currentWsdocument.publishon, 'yyyy-MM-dd');
			$scope.promise = $http({
				method: 'post',
				url: './wsdocumentview/managewsdocument.php',
				data: $.param({'document' : $scope.currentWsdocument, 'type' : 'updateEntireDocument' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this document to reset everything
					$scope.setCurrentInternal($scope.selectedWsdocument, null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	// This is the function that saves the new document in the database
	$scope.addWsdocumentToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './wsdocumentview/managewsdocument.php',
			data: $.param({'document' : $scope.newWsdocument, 'type' : 'insert_document' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newWsdocument = {id:data.id, documentname:$scope.newWsdocument.documentname};
				$scope.leftobjs.push(newWsdocument);
				// We could sort the list....
				$scope.setCurrentInternal(newWsdocument);
				return true;
			} else {
				dialogService.displayFailure(data);
				return false;
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	// This is the function that creates the modal to create new document
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWsdocument = {};
			// Send the newWsdocument to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'wsdocumentview/newwsdocument.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newWsdocument;
						}
					}
			})
			.result.then(function(newWsdocument) {
				// User clicked OK and everything was valid.
				$scope.newWsdocument = newWsdocument;
				if ($scope.addWsdocumentToDB() == true) {
				}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// This is the function that displays the upload error messages
	$scope.displayUploadError = function(errFile) {
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
	$scope.uploadMainFileFr = function(file, errFiles) {
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
							'language' : 'fr-ca',
							'filename' : file.name
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
	$scope.uploadMainFileEn = function(file, errFiles) {
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
							'language' : 'en-ca',
							'filename' : file.name
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

	$scope.refreshAll = function() {
		$scope.getAllWsdocument();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsdocumentview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
