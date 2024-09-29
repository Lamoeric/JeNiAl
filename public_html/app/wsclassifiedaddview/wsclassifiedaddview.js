'use strict';

angular.module('cpa_admin.wsclassifiedaddview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/wsclassifiedaddview', {
		templateUrl: 'wsclassifiedaddview/wsclassifiedaddview.html',
		controller: 'wsclassifiedaddviewCtrl',
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
					return $q.reject({ authenticated: false, newLocation: "/wsclassifiedaddview" });
				}
			}
		}
	});
}])

.controller('wsclassifiedaddviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wsclassifiedaddview";
	$scope.currentWsclassifiedadd = null;
	$scope.selectedWsclassifiedadd = null;
	$scope.newWsclassifiedadd = null;
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

	// This is the function that gets all classifiedadds from database
	$scope.getAllWsclassifiedadd = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsclassifiedaddview/managewsclassifiedadd.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllClassifiedadds' }),
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

	// This is the function that gets the current classifiedadd from database
	$scope.getWsclassifiedaddDetails = function (classifiedadd) {
		$scope.promise = $http({
			method: 'post',
			url: './wsclassifiedaddview/managewsclassifiedadd.php',
			data: $.param({ 'id': classifiedadd.id, 'language': authenticationService.getCurrentLanguage(), 'type': 'getClassifiedaddDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsclassifiedadd = data.data[0];
				$scope.currentWsclassifiedadd.imageinfo = data.imageinfo;
				$scope.currentWsclassifiedadd.displayimagefilename = $scope.currentWsclassifiedadd.imagefilename + '?decache=' + Math.random();
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current classifiedadd from database
	$scope.setCurrentInternal = function (classifiedadd, index) {
		if (classifiedadd != null) {
			$scope.selectedLeftObj = classifiedadd;
			$scope.selectedWsclassifiedadd = classifiedadd;
			$scope.getWsclassifiedaddDetails(classifiedadd);
			$scope.setPristine();
		} else {
			$scope.selectedWsclassifiedadd = null;
			$scope.currentWsclassifiedadd = null;
			$scope.selectedLeftObj = null;
		}
	}

	// This is the function that selects or reselects the current classifiedadd
	$scope.setCurrent = function (classifiedadd, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, classifiedadd, index);
		} else {
			$scope.setCurrentInternal(classifiedadd, index);
		}
	};

	// This is the function that deletes the current classifiedadd from database
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentWsclassifiedadd != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wsclassifiedaddview/managewsclassifiedadd.php',
				data: $.param({ 'classifiedadd': $scope.currentWsclassifiedadd, 'type': 'delete_classifiedadd' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsclassifiedadd), 1);
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

	// This is the function that validates all forms and display error and warning messages
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

	// This is the function that saves the current classifiedadd in the database
	$scope.saveToDB = function () {
		if ($scope.currentWsclassifiedadd == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wsclassifiedaddview/managewsclassifiedadd.php',
				data: $.param({ 'classifiedadd': $scope.currentWsclassifiedadd, 'type': 'updateEntireClassifiedadd' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this classifiedadd to reset everything
					$scope.setCurrentInternal($scope.selectedWsclassifiedadd, null);
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

	// This is the function that saves the new classifiedadd in the database
	$scope.addWsclassifiedaddToDB = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsclassifiedaddview/managewsclassifiedadd.php',
			data: $.param({ 'classifiedadd': $scope.newWsclassifiedadd, 'type': 'insert_classifiedadd' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				var newWsclassifiedadd = { id: data.id, name: $scope.newWsclassifiedadd.name };
				$scope.leftobjs.push(newWsclassifiedadd);
				// We could sort the list....
				$scope.setCurrentInternal(newWsclassifiedadd);
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

	// This is the function that creates the modal to create new classifiedadd
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWsclassifiedadd = {};
			// Send the newWsclassifiedadd to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'wsclassifiedaddview/newwsclassifiedadd.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newWsclassifiedadd;
					}
				}
			}).result.then(function (newWsclassifiedadd) {
				// User clicked OK and everything was valid.
				$scope.newWsclassifiedadd = newWsclassifiedadd;
				if ($scope.addWsclassifiedaddToDB() == true) {
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

	// This is the function that uploads the image for the current event
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
				url: './wsclassifiedaddview/uploadpictures.php',
				method: 'POST',
				file: file,
				data: {
					'mainobj': $scope.currentWsclassifiedadd
				}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this event to reset everything
						$scope.setCurrentInternal($scope.selectedWsclassifiedadd, null);
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
		$scope.getAllWsclassifiedadd();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsclassifiedaddview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
