'use strict';

angular.module('cpa_admin.roleview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/roleview', {
		templateUrl: 'roleview/roleview.html',
		controller: 'roleviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
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
	$scope.formList = [{name:'detailsForm'}];

	/**
	 * This function checks if anything is dirty
	 * @returns true if any of the forms are dirty, false otherwise
	 */
	$scope.isDirty = function () {
		return $rootScope.isDirty($scope, $scope.formList);
	};

	/**
	 * This function sets one form dirty to indicate the whole thing is dirty
	 */
	$scope.setDirty = function () {
		$rootScope.setDirty($scope, $scope.formList);
	};

	/**
	 * This function sets all the forms as pristine
	 */
	$scope.setPristine = function () {
		$rootScope.setPristine($scope, $scope.formList);
	};

	// This is the function that creates the modal to add privilege to the role.
	// Privileges cannot be edited once added, they can only be deleted.
	$scope.editPrivilege = function() {
		$scope.newPrivilege = {};
		$scope.currentPrivilege = null;
		$scope.tmpPrivileges = [];
		// We need to adjust the list of available privileges for this role to avoid adding a privilege twice
		for (var i = 0; i < $scope.privileges.length; i++) {
			var found = false;
			for (var y = 0; y < $scope.currentRole.privileges.length; y++) {
				if ($scope.currentRole.privileges[y].privilegeid == $scope.privileges[i].id) {
					found = true;
					break;
				}
			}
			if (!found) {
				$scope.tmpPrivileges.push($scope.privileges[i]);
			}
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
				newPrivilege.status = 'New';
				if ($scope.currentRole.privileges == null) $scope.currentRole.privileges = [];
				$scope.currentRole.privileges.push(newPrivilege);
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	/**
	 * This function gets all the roles from the DB
	 */
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

	/**
	 * This function gets the details of the selected role from the DB
	 * @param {object} role the selected role
	 */
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

	/**
	 * This function set the selected role as current
	 * @param {object} role
	 * @param {integer} index
	 */
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

	/**
	 * This function set the selected role as current if the current one is not dirty
	 * @param {object} role
	 * @param {integer} index
	 */
	$scope.setCurrent = function (role, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, role, index);
		} else {
			$scope.setCurrentInternal(role, index);
		}
	};

	/**
	 * This function deletes the current role from database
	 * @param {boolean} confirmed true if user confirmed the deletion
	 */
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

	/**
	 * This function validates all forms and display error and warning messages
	 * @returns false if something is invalid
	 */
	$scope.validateAllForms = function () {
		return $rootScope.validateAllForms($scope, $scope.formList);
	}

	/**
	 * This function saves the current role in the database
	 * @returns 
	 */
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

	/**
	 * This function adds a new role in the database
	 * @param {object} newElement the new element to add to DB
	 */
	$scope.addRoleToDB = function(newElement){
		$scope.newRole = newElement;
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

	/**
	 * This function creates the modal to create new boardmember
	 */
	$scope.createNew = function () {
		$rootScope.createNewObject($scope, false, 'roleview/newrole.template.html', $scope.addRoleToDB);
	};
	
	$scope.refreshAll = function() {
		$scope.getAllRoles();
		listsService.getAllPrivileges($scope, authenticationService.getCurrentLanguage());
		translationService.getTranslation($scope, 'roleview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
