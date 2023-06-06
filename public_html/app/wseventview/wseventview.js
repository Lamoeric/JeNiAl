'use strict';

angular.module('cpa_admin.wseventview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/wseventview', {
		templateUrl: 'wseventview/wseventview.html',
		controller: 'wseventviewCtrl',
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
					return $q.reject({authenticated: false, newLocation: "/wseventview"});
				}
			}
		}
	});
}])

.controller('wseventviewCtrl', ['$q', '$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'parseISOdateService', 'dateFilter', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($q, $rootScope, $scope, $http, $uibModal, $timeout, parseISOdateService, dateFilter, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wseventview";
	$scope.currentWsevent = null;
	$scope.selectedWsevent = null;
	$scope.newWsevent = null;
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

	// This is the function that gets all events from database
	$scope.getAllWsevent = function () {
		$scope.promise = $http({
				method: 'post',
				url: './wseventview/managewsevent.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllEvents' }),
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

	// This is the function that gets the current event from database
	$scope.getWseventDetails = function (event) {
		$scope.promise = $http({
			method: 'post',
			url: './wseventview/managewsevent.php',
			data: $.param({'id' : event.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getEventDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWsevent = data.data[0];
				$scope.currentWsevent.imageinfo = data.imageinfo;
				$scope.currentWsevent.displayimagefilename = $scope.currentWsevent.imagefilename + '?decache=' + Math.random();
				$scope.currentWsevent.eventdate = parseISOdateService.parseDateWithoutTime($scope.currentWsevent.eventdate);
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current event from database
	$scope.setCurrentInternal = function (event, index) {
		if (event != null) {
			$scope.selectedLeftObj = event;
			$scope.selectedWsevent = event;
			$scope.getWseventDetails(event);
			$scope.setPristine();
		} else {
			$scope.selectedWsevent = null;
			$scope.currentWsevent = null;
			$scope.selectedLeftObj = null;
		}
	}

	// This is the function that selects or reselects the current event
	$scope.setCurrent = function (event, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, event, index);
		} else {
			$scope.setCurrentInternal(event, index);
		}
	};

	// This is the function that deletes the current event from database
	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentWsevent != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wseventview/managewsevent.php',
				data: $.param({'event' : $scope.currentWsevent, 'type' : 'delete_event' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWsevent),1);
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

	// This is the function that saves the current event in the database
	$scope.saveToDB = function() {
		if ($scope.currentWsevent == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.currentWsevent.eventdatestr = dateFilter($scope.currentWsevent.eventdate, 'yyyy-MM-dd');
			$scope.promise = $http({
				method: 'post',
				url: './wseventview/managewsevent.php',
				data: $.param({'event' : JSON.stringify($scope.currentWsevent), 'type' : 'updateEntireEvent' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this event to reset everything
					$scope.setCurrentInternal($scope.selectedWsevent, null);
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

	// This is the function that saves the new event in the database
	$scope.addWseventToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './wseventview/managewsevent.php',
			data: $.param({'event' : $scope.newWsevent, 'type' : 'insert_event' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newWsevent = {id:data.id, name:$scope.newWsevent.name, eventdate:null};
				$scope.leftobjs.push(newWsevent);
				// We could sort the list....
				$scope.setCurrentInternal(newWsevent);
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

	// This is the function that creates the modal to create new event
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newWsevent = {};
			// Send the newWsevent to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'wseventview/newwsevent.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newWsevent;
						}
					}
			})
			.result.then(function(newWsevent) {
				// User clicked OK and everything was valid.
				$scope.newWsevent = newWsevent;
				if ($scope.addWseventToDB() == true) {
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

	// This is the function that uploads the main image for the current event
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
			$scope.promise = Upload.upload({
					url: './wseventview/uploadmainimage.php',
					method: 'POST',
					file: file,
					data: {
							'mainobj': $scope.currentWsevent
					}
			}).then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
						// Select this event to reset everything
						$scope.setCurrentInternal($scope.selectedWsevent, null);
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

	// TODO : we need to insert the header of the file in the database BEFORE importing the file
	// This is the function that uploads images for the current event
	$scope.uploadPictureImage = function(files, errFiles) {
		$scope.f = files;
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
			return;
		}
		$scope.nboffilesimported = 0;
		$scope.nboffiles = files.length;
		var chain = $q.when();
		angular.forEach(files, function(file) {
			if (file) {
				if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
					dialogService.alertDlg('only jpg files are allowed.');
					return;
				}
				chain = chain.then(function() {
					return $scope.promise = Upload.upload({
							url: './wseventview/uploadpictures.php',
							method: 'POST',
							file: file,
							data: {
									'mainobj': $scope.currentWsevent
							}
					}).then(function(data) {
						$timeout(function() {
							if (data.data.success) {
								$scope.nboffilesimported++;
								// Is this the last file to import?
								if ($scope.nboffilesimported == $scope.nboffiles) {
									// dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
									// Select this event to reset everything
									$scope.setCurrentInternal($scope.selectedWsevent, null);
								}
							} else {
								dialogService.displayFailure(data.data);
							}
						});
					}, function(data) {
							if (!data.success) {
								dialogService.displayFailure(data.data);
							}
					}, function(evt) {
							file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
					});
				});
			}
		});
	}

	$scope.refreshAll = function() {
		$scope.getAllWsevent();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'wseventtypes', 'text', 'wseventtypes');
		translationService.getTranslation($scope, 'wseventview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
