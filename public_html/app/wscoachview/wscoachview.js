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

	/**
	 * This function checks if anything is dirty
	 * @returns true if any of the forms are dirty, false otherwise
	 */
	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty || $scope.startestsForm.$dirty) {
			return true;
		}
		return false;
	};

	/**
	 * This function sets one form dirty to indicate the whole thing is dirty
	 */
	$scope.setDirty = function() {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	/**
	 * This function sets all the forms as pristine
	 */
	$scope.setPristine = function() {
		$scope.detailsForm.$setPristine();
		$scope.startestsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	/**
	 * This function gets all coaches from the database
	 */
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

	/**
	 * This function gets the selected coach from the database
	 * @param {*} coach 
	 */
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

	/**
	 * This function selects or reselects the current coach from the database
	 * @param {*} coach 
	 * @param {*} index 
	 */
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

	/**
	 * This function selects or reselects the current coach
	 * @param {*} coach 
	 * @param {*} index 
	 */
	$scope.setCurrent = function (coach, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, coach, index);
		} else {
			$scope.setCurrentInternal(coach, index);
		}
	};

	/**
	 * This function deletes the current coach from database
	 * @param {*} confirmed true if user confirmed the deletion
	 */
	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentWscoach != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wscoachview/managewscoach.php',
				data: $.param({'coach' : $scope.currentWscoach, 'type' : 'delete_coach' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWscoach),1);
					$scope.setCurrentInternal(null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	}

	/**
	 * This function validates all forms and display error and warning messages
	 * @returns false if something is invalid
	 */
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

	/**
	 * This function saves the current coach in the database
	 * @returns 
	 */
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
			}).success(function(data, status, headers, config) {
				if (data.success) {
					// Select this coach to reset everything
					$scope.setCurrentInternal($scope.selectedWscoach, null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	/**
	 * This function adds a new coach in the database
	 */
	$scope.addWscoachToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './wscoachview/managewscoach.php',
			data: $.param({'coach' : $scope.newWscoach, 'type' : 'insert_coach' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).success(function(data, status, headers, config) {
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
		}).error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	/**
	 * This function creates the modal to create new coach
	 * @param {*} confirmed true if form was dirty and user confirmed it's ok to cancel the modifications to the current coach
	 */
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
			}).result.then(function(newWscoach) {
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

	/**
	 * This function displays the upload error messages
	 * @param {*} errFile the file in error
	 */
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

	/**
	 * This function that uploads the image for the current coach
	 * @param {*} file 
	 * @param {*} errFiles 
	 * @returns 
	 */
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
					url: '../../include/uploadmainimage.php',
					method: 'POST',
					file: file,
					data: {
						'subDirectory': '/website/images/coaches/',
						'filePrefix': $scope.currentWscoach.firstname + '_' + $scope.currentWscoach.lastname,
						'tableName': 'cpa_ws_coaches',
						'id': $scope.currentWscoach.id,
						'oldFileName': $scope.currentWscoach.imagefilename
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

	/**
	 * This function is called by the ui when user switch the STAR version
	 */
	$scope.onVersionChanged = function() {
		$scope.currentWscoach.dancelevel = '-1';
		$scope.currentWscoach.skillslevel = '-1';
		$scope.currentWscoach.freestylelevel = '-1';
	}

	/**
	 * This function refreshes everything, called at the start of the program or on a language change
	 */
	$scope.refreshAll = function() {
		$scope.getAllWscoach();
		translationService.getTranslation($scope, 'wscoachview', authenticationService.getCurrentLanguage());
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testversions', 'sequence', 'testversions');
		// On all the code list, add another choice at first position : aucun/none
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testnewlevels', 'sequence', 'testnewlevels')
		.then(function() {
			$scope.testnewlevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
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
