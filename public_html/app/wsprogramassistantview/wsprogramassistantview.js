'use strict';

angular.module('cpa_admin.wsprogramassistantview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/wsprogramassistantview', {
		templateUrl: 'wsprogramassistantview/wsprogramassistantview.html',
		controller: 'wsprogramassistantviewCtrl',
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
          return $q.reject({authenticated: false, newLocation: "/wsprogramassistantview"});
        }
      }
		}
	});
}])

.controller('wsprogramassistantviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wsprogramassistantview";
	$scope.currentWsprogramassistant = null;
	$scope.selectedWsprogramassistant = null;
	$scope.newWsprogramassistant = null;
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

	// This is the function that gets all programassistants from database
	$scope.getAllWsprogramassistant = function () {
		$scope.promise = $http({
				method: 'post',
				url: './wsprogramassistantview/managewsprogramassistant.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllProgramassistants' }),
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

	// This is the function that gets the current programassistant from database
	$scope.getWsprogramassistantDetails = function (programassistant) {
		$scope.promise = $http({
			method: 'post',
			url: './wsprogramassistantview/managewsprogramassistant.php',
			data: $.param({'id' : programassistant.id, 'type' : 'getProgramassistantDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsprogramassistant = data.data[0];
				$scope.currentWsprogramassistant.imageinfo = data.imageinfo;
				$scope.currentWsprogramassistant.displayimagefilename = $scope.currentWsprogramassistant.imagefilename + '?decache=' + Math.random();
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current programassistant from database
	$scope.setCurrentInternal = function (programassistant, index) {
		if (programassistant != null) {
			$scope.selectedLeftObj = programassistant;
			$scope.selectedWsprogramassistant = programassistant;
			$scope.getWsprogramassistantDetails(programassistant);
			$scope.setPristine();
		} else {
			$scope.selectedWsprogramassistant = null;
			$scope.currentWsprogramassistant = null;
			$scope.selectedLeftObj = null;
		}
	}

	// This is the function that selects or reselects the current programassistant
	$scope.setCurrent = function (programassistant, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, programassistant, index);
		} else {
			$scope.setCurrentInternal(programassistant, index);
		}
	};

	// This is the function that deletes the current programassistant from database
	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentWsprogramassistant != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wsprogramassistantview/managewsprogramassistant.php',
				data: $.param({'programassistant' : $scope.currentWsprogramassistant, 'type' : 'delete_programassistant' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsprogramassistant),1);
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

	// This is the function that saves the current programassistant in the database
	$scope.saveToDB = function() {
		if ($scope.currentWsprogramassistant == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wsprogramassistantview/managewsprogramassistant.php',
				data: $.param({'programassistant' : $scope.currentWsprogramassistant, 'type' : 'updateEntireProgramassistant' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this programassistant to reset everything
					$scope.setCurrentInternal($scope.selectedWsprogramassistant, null);
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

	// This is the function that saves the new programassistant in the database
	$scope.addWsprogramassistantToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './wsprogramassistantview/managewsprogramassistant.php',
			data: $.param({'programassistant' : $scope.newWsprogramassistant, 'type' : 'insert_programassistant' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newWsprogramassistant = {id:data.id, firstname:$scope.newWsprogramassistant.firstname, lastname:$scope.newWsprogramassistant.lastname};
				$scope.leftobjs.push(newWsprogramassistant);
				// We could sort the list....
				$scope.setCurrentInternal(newWsprogramassistant);
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

	// This is the function that creates the modal to create new programassistant
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWsprogramassistant = {};
			// Send the newWsprogramassistant to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'wsprogramassistantview/newwsprogramassistant.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newWsprogramassistant;
						}
					}
			})
			.result.then(function(newWsprogramassistant) {
				// User clicked OK and everything was valid.
				$scope.newWsprogramassistant = newWsprogramassistant;
				if ($scope.addWsprogramassistantToDB() == true) {
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

	// This is the function that uploads the image for the current programassistant
	$scope.uploadMainImage = function(file, errFiles) {
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
					url: './wsprogramassistantview/uploadmainimage.php',
					method: 'POST',
					file: file,
					data: {
							'mainobj': $scope.currentWsprogramassistant
					}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this programassistant to reset everything
						$scope.setCurrentInternal($scope.selectedWsprogramassistant, null);
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
		$scope.getAllWsprogramassistant();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsprogramassistantview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
