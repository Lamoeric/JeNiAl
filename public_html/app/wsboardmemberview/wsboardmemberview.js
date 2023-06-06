'use strict';

angular.module('cpa_admin.wsboardmemberview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/wsboardmemberview', {
		templateUrl: 'wsboardmemberview/wsboardmemberview.html',
		controller: 'wsboardmemberviewCtrl',
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
					return $q.reject({authenticated: false, newLocation: "/wsboardmemberview"});
				}
			}
		}
	});
}])

.controller('wsboardmemberviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wsboardmemberview";
	$scope.currentWsboardmember = null;
	$scope.selectedWsboardmember = null;
	$scope.newWsboardmember = null;
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

	// This is the function that gets all boardmembers from database
	$scope.getAllWsboardmember = function () {
		$scope.promise = $http({
				method: 'post',
				url: './wsboardmemberview/managewsboardmember.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllBoardmembers' }),
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

	// This is the function that gets the current boardmember from database
	$scope.getWsboardmemberDetails = function (boardmember) {
		$scope.promise = $http({
			method: 'post',
			url: './wsboardmemberview/managewsboardmember.php',
			data: $.param({'id' : boardmember.id, 'type' : 'getBoardmemberDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsboardmember = data.data[0];
				$scope.currentWsboardmember.imageinfo = data.imageinfo;
				$scope.currentWsboardmember.displayimagefilename = $scope.currentWsboardmember.imagefilename + '?decache=' + Math.random();
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current boardmember from database
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

	// This is the function that selects or reselects the current boardmember
	$scope.setCurrent = function (boardmember, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, boardmember, index);
		} else {
			$scope.setCurrentInternal(boardmember, index);
		}
	};

	// This is the function that deletes the current boardmember from database
	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentWsboardmember != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wsboardmemberview/managewsboardmember.php',
				data: $.param({'boardmember' : $scope.currentWsboardmember, 'type' : 'delete_boardmember' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsboardmember),1);
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

	// This is the function that saves the current boardmember in the database
	$scope.saveToDB = function() {
		if ($scope.currentWsboardmember == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wsboardmemberview/managewsboardmember.php',
				data: $.param({'boardmember' : $scope.currentWsboardmember, 'type' : 'updateEntireBoardmember' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this boardmember to reset everything
					$scope.setCurrentInternal($scope.selectedWsboardmember, null);
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

	// This is the function that saves the new boardmember in the database
	$scope.addWsboardmemberToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './wsboardmemberview/managewsboardmember.php',
			data: $.param({'boardmember' : $scope.newWsboardmember, 'type' : 'insert_boardmember' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newWsboardmember = {id:data.id, firstname:$scope.newWsboardmember.firstname, lastname:$scope.newWsboardmember.lastname};
				$scope.leftobjs.push(newWsboardmember);
				// We could sort the list....
				$scope.setCurrentInternal(newWsboardmember);
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

	// This is the function that creates the modal to create new boardmember
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWsboardmember = {};
			// Send the newWsboardmember to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'wsboardmemberview/newwsboardmember.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newWsboardmember;
						}
					}
			})
			.result.then(function(newWsboardmember) {
				// User clicked OK and everything was valid.
				$scope.newWsboardmember = newWsboardmember;
				if ($scope.addWsboardmemberToDB() == true) {
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

	// This is the function that uploads the image for the current boardmember
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
					url: './wsboardmemberview/uploadmainimage.php',
					method: 'POST',
					file: file,
					data: {
							'mainobj': $scope.currentWsboardmember
					}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this boardmember to reset everything
						$scope.setCurrentInternal($scope.selectedWsboardmember, null);
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
		$scope.getAllWsboardmember();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsboardmemberview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
