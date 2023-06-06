'use strict';

angular.module('cpa_admin.clubview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/clubview', {
		templateUrl: 'clubview/clubview.html',
		controller: 'clubviewCtrl',
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
					return $q.reject({authenticated: false, newLocation: "/clubview"});
				}
			}
		}
	});
}])

.controller('clubviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "clubView";
	$scope.currentClub = null;
	$scope.selectedLeftObj = null;
	$scope.newClub = null;
	$scope.isFormPristine = true;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty || $scope.contactForm.$dirty) {
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
		$scope.contactForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllClubs = function () {
		$scope.promise = $http({
				method: 'post',
				url: './clubview/club.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllClubs' }),
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

	$scope.getClubDetails = function (club) {
		$scope.promise = $http({
			method: 'post',
			url: './clubview/club.php',
			data: $.param({'id' : club.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getClubDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success && !angular.isUndefined(data.data) ){
				$scope.currentClub = data.data[0];
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (club, index) {
		if (club != null) {
			$scope.selectedLeftObj = club;
			$scope.getClubDetails(club);
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentClub = null;
		}
	}

	$scope.setCurrent = function (club, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, club, index);
		} else {
			$scope.setCurrentInternal(club, index);
		}
	};

	$scope.deleteFromDB = function(confirmed){
		if ($scope.currentClub != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './clubview/club.php',
				data: $.param({'club' : $scope.currentClub, 'type' : 'delete_club' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedLeftObj),1);
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

	$scope.saveToDB = function(){
		if ($scope.currentClub == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './clubview/club.php',
				data: $.param({'club' : $scope.currentClub, 'type' : 'updateEntireClub' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					// Select this club to reset everything
					$scope.setCurrentInternal($scope.selectedLeftObj, null);
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

	$scope.addClubToDB = function(){
		$http({
			method: 'post',
			url: './clubview/club.php',
			data: $.param({'club' : $scope.newClub, 'type' : 'insert_club' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success){
				var newClub = {id:data.id, code:$scope.newClub.code, text:$scope.newClub.name};
				$scope.leftobjs.push(newClub);
				// We could sort the list....
				$scope.setCurrentInternal(newClub);
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

	// This is the function that creates the modal to create new club
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newClub = {};
			// Send the newClub to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'clubview/newclub.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newClub;
						}
					}
			})
			.result.then(function(newClub) {
					// User clicked OK and everything was valid.
					$scope.newClub = newClub;
					$scope.newClub.label_fr = newClub.name;
					$scope.newClub.label_en = newClub.name;
					if ($scope.addClubToDB() == true) {
					}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	$scope.refreshAll = function() {
		$scope.getAllClubs();
		listsService.getAllPrivileges($scope, authenticationService.getCurrentLanguage());
		translationService.getTranslation($scope, 'clubview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
