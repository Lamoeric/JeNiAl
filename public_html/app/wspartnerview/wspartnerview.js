'use strict';

angular.module('cpa_admin.wspartnerview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/wspartnerview', {
		templateUrl: 'wspartnerview/wspartnerview.html',
		controller: 'wspartnerviewCtrl',
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
          return $q.reject({authenticated: false, newLocation: "/wspartnerview"});
        }
      }
		}
	});
}])

.controller('wspartnerviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wspartnerview";
	$scope.currentWspartner = null;
	$scope.selectedWspartner = null;
	$scope.newWspartner = null;
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

	// This is the function that gets all partners from database
	$scope.getAllWspartner = function () {
		$scope.promise = $http({
				method: 'post',
				url: './wspartnerview/managewspartner.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllPartners' }),
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

	// This is the function that gets the current partner from database
	$scope.getWspartnerDetails = function (partner) {
		$scope.promise = $http({
			method: 'post',
			url: './wspartnerview/managewspartner.php',
			data: $.param({'id' : partner.id, 'type' : 'getPartnerDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
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
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current partner from database
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

	// This is the function that selects or reselects the current partner
	$scope.setCurrent = function (partner, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, partner, index);
		} else {
			$scope.setCurrentInternal(partner, index);
		}
	};

	// This is the function that deletes the current partner from database
	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentWspartner != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wspartnerview/managewspartner.php',
				data: $.param({'partner' : $scope.currentWspartner, 'type' : 'delete_partner' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWspartner),1);
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

	// This is the function that saves the current partner in the database
	$scope.saveToDB = function() {
		if ($scope.currentWspartner == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wspartnerview/managewspartner.php',
				data: $.param({'partner' : $scope.currentWspartner, 'type' : 'updateEntirePartner' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this partner to reset everything
					$scope.setCurrentInternal($scope.selectedWspartner, null);
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

	// This is the function that saves the new partner in the database
	$scope.addWspartnerToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './wspartnerview/managewspartner.php',
			data: $.param({'partner' : $scope.newWspartner, 'type' : 'insert_partner' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newWspartner = {id:data.id, name:$scope.newWspartner.name};
				$scope.leftobjs.push(newWspartner);
				// We could sort the list....
				$scope.setCurrentInternal(newWspartner);
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

	// This is the function that creates the modal to create new partner
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
			})
			.result.then(function(newWspartner) {
				// User clicked OK and everything was valid.
				$scope.newWspartner = newWspartner;
				if ($scope.addWspartnerToDB() == true) {
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

	// This is the function that uploads the image for the current partner
	$scope.uploadMainImageFr = function(file, errFiles) {
		$scope.f = file;
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
		}
		if (file) {
			// if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
			// 	dialogService.alertDlg('only jpg files are allowed.');
			// 	return;
			// }
			file.upload = Upload.upload({
					url: './wspartnerview/uploadmainimage.php',
					method: 'POST',
					file: file,
					data: {
							'mainobj': $scope.currentWspartner,
							'language' : 'fr-ca'
					}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this partner to reset everything
						$scope.setCurrentInternal($scope.selectedWspartner, null);
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

	// This is the function that uploads the image for the current partner
	$scope.uploadMainImageEn = function(file, errFiles) {
		$scope.f = file;
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
		}
		if (file) {
			// if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
			// 	dialogService.alertDlg('only jpg files are allowed.');
			// 	return;
			// }
			file.upload = Upload.upload({
					url: './wspartnerview/uploadmainimage.php',
					method: 'POST',
					file: file,
					data: {
							'mainobj': $scope.currentWspartner,
							'language' : 'en-ca'
					}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this partner to reset everything
						$scope.setCurrentInternal($scope.selectedWspartner, null);
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
		$scope.getAllWspartner();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wspartnerview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
