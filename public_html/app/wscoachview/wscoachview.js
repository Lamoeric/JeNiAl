'use strict';

angular.module('cpa_admin.wscoachview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/wscoachview', {
		templateUrl: 'wscoachview/wscoachview.html',
		controller: 'wscoachviewCtrl',
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
          return $q.reject({authenticated: false, newLocation: "/wscoachview"});
        }
      }
		}
	});
}])

.controller('wscoachviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wscoachview";
	$scope.currentWscoach = 1;
	$scope.selectedWscoach = null;
	$scope.newWscoach = null;
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

	// This is the function that gets all coaches from database
	$scope.getAllWscoach = function () {
		$scope.promise = $http({
				method: 'post',
				url: './wscoachview/managewscoach.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllCoachs' }),
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

	// This is the function that gets the current coach from database
	$scope.getWscoachDetails = function (coach) {
		$scope.promise = $http({
			method: 'post',
			url: './wscoachview/managewscoach.php',
			data: $.param({'id' : coach.id, 'type' : 'getCoachDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWscoach = data.data[0];
				$scope.currentWscoach.imageinfo = data.imageinfo;
				$scope.currentWscoach.displayimagefilename = $scope.currentWscoach.imagefilename + '?decache=' + Math.random();
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current coach from database
	$scope.setCurrentInternal = function (coach, index) {
		if (coach != null) {
			$scope.selectedLeftObj = coach;
			$scope.selectedWscoach = coach;
			$scope.getWscoachDetails(coach);
			$scope.setPristine();
		} else {
			$scope.selectedWscoach = null;
			$scope.currentWscoach = null;
			$scope.selectedLeftObj = null;
		}
	}

	// This is the function that selects or reselects the current coach
	$scope.setCurrent = function (coach, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, coach, index);
		} else {
			$scope.setCurrentInternal(coach, index);
		}
	};

	// This is the function that deletes the current coach from database
	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentWscoach != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wscoachview/managewscoach.php',
				data: $.param({'coach' : $scope.currentWscoach, 'type' : 'delete_coach' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWscoach),1);
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

	// This is the function that saves the current coach in the database
	$scope.saveToDB = function() {
		if ($scope.currentWscoach == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wscoachview/managewscoach.php',
				data: $.param({'coach' : $scope.currentWscoach, 'type' : 'updateEntireCoach' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this coach to reset everything
					$scope.setCurrentInternal($scope.selectedWscoach, null);
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

	// This is the function that saves the new coach in the database
	$scope.addWscoachToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './wscoachview/managewscoach.php',
			data: $.param({'coach' : $scope.newWscoach, 'type' : 'insert_coach' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newWscoach = {id:data.id, firstname:$scope.newWscoach.firstname, lastname:$scope.newWscoach.lastname};
				$scope.leftobjs.push(newWscoach);
				// We could sort the list....
				$scope.setCurrentInternal(newWscoach);
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

	// This is the function that creates the modal to create new coach
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWscoach = {};
			// Send the newWscoach to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'wscoachview/newwscoach.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newWscoach;
						}
					}
			})
			.result.then(function(newWscoach) {
				// User clicked OK and everything was valid.
				$scope.newWscoach = newWscoach;
				if ($scope.addWscoachToDB() == true) {
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

	// This is the function that uploads the image for the current coach
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
					url: './wscoachview/uploadmainimage.php',
					method: 'POST',
					file: file,
					data: {
							'mainobj': $scope.currentWscoach
					}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this coach to reset everything
						$scope.setCurrentInternal($scope.selectedWscoach, null);
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
		$scope.getAllWscoach();
		translationService.getTranslation($scope, 'wscoachview', authenticationService.getCurrentLanguage());
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		listsService.getAllTestLevelsByType($scope, 'DANCE', 'dancelevels', authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.dancelevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
		listsService.getAllTestLevelsByType($scope, 'SKILLS', 'skillslevels', authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.skillslevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
		listsService.getAllTestLevelsByType($scope, 'FREE', 'freestylelevels', authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.freestylelevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
		listsService.getAllTestLevelsByType($scope, 'INTER', 'interpretativelevels', authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.interpretativelevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
		listsService.getAllTestLevelsByType($scope, 'COMP', 'competitivelevels', authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.competitivelevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
	}

	$scope.refreshAll();
	$scope.currentWscoach = null;
}]);
