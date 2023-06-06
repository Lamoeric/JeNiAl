'use strict';

angular.module('cpa_admin.roleview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/roleview', {
		templateUrl: 'roleview/roleview.html',
		controller: 'roleviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.security_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/roleview"});
				}
			}
		}
	});
}])

.controller('roleviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "roleView";
	$scope.currentRole = null;
	$scope.selectedRole = null;
	$scope.newRole = null;
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

	// This is the function that creates the modal to create/edit privilege
	$scope.editPrivilege = function(newPrivilege) {
		$scope.newPrivilege = {};
		if (newPrivilege.id) {
			$scope.currentPrivilege = newPrivilege;
			// Send the newPrivilege to the modal form
			for (var prop in newPrivilege) {
				$scope.newPrivilege[prop] = newPrivilege[prop];
			}
		} else {
			$scope.currentPrivilege = null;
		}
		$uibModal.open({
				animation: false,
				templateUrl: 'roleview/newprivilege.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newPrivilege;
					}
				}
			})
			.result.then(function(newPrivilege) {
				// User clicked OK and everything was valid.
				if ($scope.currentPrivilege != null) {
					for (var prop in newPrivilege) {
						$scope.currentPrivilege[prop] = newPrivilege[prop];
					}
					$scope.currentPrivilege.status = 'Modified';
				} else {
					newPrivilege.status = 'New';
					if ($scope.currentRole.privileges == null)$scope.currentRole.privileges = [];
					$scope.currentRole.privileges.push(newPrivilege);
				}
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	$scope.getAllRoles = function () {
		$scope.promise = $http({
				method: 'post',
				url: './roleview/role.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllRoles' }),
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
				if(!data.success){
					dialogService.displayFailure(data);
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.getRoleDetails = function (role) {
		$scope.promise = $http({
			method: 'post',
			url: './roleview/role.php',
			data: $.param({'id' : role.id, 'type' : 'getRoleDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success && !angular.isUndefined(data.data) ){
				$scope.currentRole = data.data[0];
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (role, index) {
		if (role != null) {
			$scope.selectedRole = role;
			$scope.getRoleDetails(role);
			$scope.selectedLeftObj = role;
			$scope.setPristine();
		} else {
			$scope.selectedRole = null;
			$scope.currentRole = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (role, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, role, index);
		} else {
			$scope.setCurrentInternal(role, index);
		}
	};

	$scope.deleteFromDB = function(confirmed){
		if ($scope.currentRole != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './roleview/role.php',
				data: $.param({'role' : $scope.currentRole, 'type' : 'delete_role' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedRole),1);
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
		if ($scope.currentRole == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './roleview/role.php',
				data: $.param({'role' : $scope.currentRole, 'type' : 'updateEntireRole' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					// Select this role to reset everything
					$scope.setCurrentInternal($scope.selectedRole, null);
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

	$scope.addRoleToDB = function(){
		$scope.promise = $http({
			method: 'post',
			url: './roleview/role.php',
			data: $.param({'role' : $scope.newRole, 'type' : 'insert_role' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success){
				var newRole = {id:data.id, roleid:$scope.newRole.roleid, rolename:$scope.newRole.rolename};
				$scope.leftobjs.push(newRole);
				// We could sort the list....
				$scope.setCurrentInternal(newRole);
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

	// This is the function that creates the modal to create new role
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newRole = {};
			// Send the newRole to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'roleview/newrole.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newRole;
						}
					}
			})
			.result.then(function(newRole) {
					// User clicked OK and everything was valid.
					$scope.newRole = newRole;
					if ($scope.addRoleToDB() == true) {
					}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	$scope.refreshAll = function() {
		$scope.getAllRoles();
		listsService.getAllPrivileges($scope, authenticationService.getCurrentLanguage());
		translationService.getTranslation($scope, 'roleview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
