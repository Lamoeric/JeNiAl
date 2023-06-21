'use strict';

angular.module('cpa_admin.arenaview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/arenaview', {
		templateUrl: 'arenaview/arenaview.html',
		controller: 'arenaviewCtrl',
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
					return $q.reject({authenticated: false, newLocation: "/arenaview"});
				}
			}
		}
	});
}])

.controller('arenaviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "arenaView";
	$scope.currentArena = null;
	$scope.selectedArena = null;
	$scope.newArena = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty||$scope.icesForm.$dirty||$scope.roomsForm.$dirty||$scope.websiteForm.$dirty||$scope.seatsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.detailsForm.$dirty = true;
		$scope.icesForm.$dirty = true;
		$scope.roomsForm.$dirty = true;
		$scope.seatsForm.$dirty = true;
		$scope.websiteForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function() {
		$scope.detailsForm.$setPristine();
		$scope.icesForm.$setPristine();
		$scope.roomsForm.$setPristine();
		$scope.seatsForm.$setPristine();
		$scope.websiteForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllArenas = function () {
		$scope.promise = $http({
				method: 'post',
				url: './arenaview/manageArenas.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllArenas' }),
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

	$scope.getArenaDetails = function (arena) {
		$scope.promise = $http({
			method: 'post',
			url: './arenaview/manageArenas.php',
			data: $.param({'id' : arena.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getArenaDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentArena = data.data[0];
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (arena, index) {
		if (arena != null) {
			$scope.selectedLeftObj = arena;
			$scope.selectedArena = arena;
			$scope.getArenaDetails(arena);
			$scope.setPristine();
		} else {
			$scope.selectedArena = null;
			$scope.currentArena = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (arena, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, arena, index);
		} else {
			$scope.setCurrentInternal(arena, index);
		}
	};

	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentArena != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$http({
				method: 'post',
				url: './arenaview/manageArenas.php',
				data: $.param({'arena' : $scope.currentArena, 'type' : 'delete_arena' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedArena),1);
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

	$scope.validateAllForms = function() {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerrdetailsallmandatory);
		}

		if ($scope.websiteForm.$invalid) {
			if ($scope.currentArena.website.publish == 1) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrwebsiteallmandatory);
			}
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

	$scope.saveToDB = function() {
		if ($scope.currentArena == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './arenaview/manageArenas.php',
				data: $.param({'arena' : $scope.currentArena, 'type' : 'updateEntireArena' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this arena to reset everything
					$scope.setCurrentInternal($scope.selectedArena, null);
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

	$scope.addArenaToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './arenaview/manageArenas.php',
			data: $.param({'arena' : $scope.newArena, 'type' : 'insert_arena' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newArena = {id:data.id, name:$scope.newArena.name};
				$scope.leftobjs.push(newArena);
				// We could sort the list....
				$scope.setCurrentInternal(newArena);
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

	// This is the function that creates the modal to create new arena
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newArena = {};
			// Send the newArena to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'arenaview/newarena.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newArena;
						}
					}
			})
			.result.then(function(newArena) {
					// User clicked OK and everything was valid.
					$scope.newArena = newArena;
					if ($scope.addArenaToDB() == true) {
					}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// This is the function that creates the modal to create/edit ice
	$scope.editIce = function(newIce) {
		$scope.newIce = {};
		if (newIce.code) {
			$scope.currentIce = newIce;
			// Send the newIce to the modal form
			for (var prop in newIce) {
				$scope.newIce[prop] = newIce[prop];
			}
		} else {
			$scope.currentIce = null;
		}
		$uibModal.open({
				animation: false,
					templateUrl: 'arenaview/newice.template.html',
					controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newIce;
					}
				}
			})
			.result.then(function(newIce) {
					// User clicked OK and everything was valid.
					if ($scope.currentIce != null) {
						for (var prop in newIce) {
							$scope.currentIce[prop] = newIce[prop];
						}
						$scope.currentIce.status = 'Modified';
					} else {
						newIce.status = 'New';
						if ($scope.currentArena.ices == null)$scope.currentArena.ices = [];
						$scope.currentArena.ices.push(newIce);
					}
					$scope.setDirty();
			}, function() {
					// User clicked CANCEL.
					// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit room
	$scope.editRoom = function(currentIce, newRoom) {
		$scope.newRoom = {};
		$scope.currentRoom = newRoom;
		$scope.currentIce = currentIce;
		angular.copy(newRoom, $scope.newRoom);

		$uibModal.open({
				animation: false,
					templateUrl: 'arenaview/newroom.template.html',
					controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newRoom;
					}
				}
			})
			.result.then(function(newRoom) {
				// User clicked OK and everything was valid.
				angular.copy(newRoom, $scope.currentRoom);
				if ($scope.currentRoom.id != null) {
						$scope.currentRoom.status = 'Modified';
				} else {
					$scope.currentRoom.status = 'New';
					if ($scope.currentIce.rooms == null) $scope.currentIce.rooms = [];
					if ($scope.currentIce.rooms.indexOf($scope.currentRoom) == -1) {
						$scope.currentIce.rooms.push($scope.currentRoom);
					}
				}
				$scope.setDirty();
			}, function() {
					// User clicked CANCEL.
					// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit exception
	$scope.editException = function(currentIce, newException) {
		$scope.newException = {};
		$scope.currentException = newException;
		$scope.currentIce = currentIce;
		angular.copy(newException, $scope.newException);

		$uibModal.open({
				animation: false,
					templateUrl: 'arenaview/newexception.template.html',
					controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newException;
					}
				}
			})
			.result.then(function(newException) {
				// User clicked OK and everything was valid.
				angular.copy(newException, $scope.currentException);
				if ($scope.currentException.id != null) {
						$scope.currentException.status = 'Modified';
				} else {
					$scope.currentException.status = 'New';
					if ($scope.currentIce.exceptions == null) $scope.currentIce.exceptions = [];
					if ($scope.currentIce.exceptions.indexOf($scope.currentException) == -1) {
						$scope.currentIce.exceptions.push($scope.currentException);
					}
				}
				$scope.setDirty();
			}, function() {
					// User clicked CANCEL.
					// alert('canceled');
		});
	};

	$scope.refreshAll = function() {
		$scope.getAllArenas();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'seatingassigntypes', 'sequence', 'seatingassigntypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'seatingexceptionreasons', 'sequence', 'seatingexceptionreasons');
		translationService.getTranslation($scope, 'arenaview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
