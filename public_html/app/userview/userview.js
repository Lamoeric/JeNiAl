'use strict';

angular.module('cpa_admin.userview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/userview', {
		templateUrl: 'userview/userview.html',
		controller: 'userviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
			}
		}
	});
}])

.controller('userviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "userView";
	$scope.currentUser = null;
	$scope.selectedUser = null;
	$scope.newUser = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.newFilter = {};
	$scope.newFilter.filterApplied = false;
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

	// This is the function that creates the modal to add role.
	// Roles cannot be edited once added, they can only be deleted.
	$scope.editRole = function (newRole) {
		$scope.newRole = {};
		$scope.currentRole = null;
		$scope.tmpRoles = [];
		// We need to adjust the list of available roles for this user to avoid adding a role twice
		for (var i = 0; i < $scope.roles.length; i++) {
			var found = false;
			for (var y = 0; y < $scope.currentUser.roles.length; y++) {
				if ($scope.currentUser.roles[y].roleid == $scope.roles[i].id) {
					found = true;
					break;
				}
			}
			if (!found) {
				$scope.tmpRoles.push($scope.roles[i]);
			}
		}
		$uibModal.open({
			animation: false,
			templateUrl: 'userview/newrole.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: null,
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newRole;
				}
			}
		})
		.result.then(function (newRole) {
			// User clicked OK and everything was valid.
			newRole.status = 'New';
			if ($scope.currentUser.roles == null) $scope.currentUser.roles = [];
			$scope.currentUser.roles.push(newRole);
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	/**
	 * This function gets all the users from the DB
	 */
	$scope.getAllUsers = function (newFilter) {
		if (newFilter) {
			$scope.newFilter.filterApplied = true;
		} else {
			$scope.newFilter.filterApplied = false;
		}
		$scope.promise = $http({
			method: 'post',
			url: './userview/user.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'filter': newFilter, 'type': 'getAllUsers' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).
		success(function (data, status, headers, config) {
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
		error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * This function gets the details of the selected user from the DB
	 * @param {object} user the selected user
	 */
	$scope.getUserDetails = function (user) {
		$scope.promise = $http({
			method: 'post',
			url: './userview/user.php',
			data: $.param({ 'id': user.id, 'type': 'getUserDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).
		success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentUser = data.data[0];
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * This function set the selected user as current
	 * @param {object} user
	 * @param {integer} index
	 */
	$scope.setCurrentInternal = function (user, index) {
		if (user != null) {
			$scope.selectedUser = user;
			$scope.getUserDetails(user);
			$scope.selectedLeftObj = user;
			$scope.setPristine();
		} else {
			$scope.selectedUser = null;
			$scope.currentUser = null;
			$scope.selectedLeftObj = null;
		}
	}

	/**
	 * This function set the selected user as current if the current one is not dirty
	 * @param {object} user
	 * @param {integer} index
	 */
	$scope.setCurrent = function (user, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, user, index);
		} else {
			$scope.setCurrentInternal(user, index);
		}
	};

	/**
	 * This function deletes the current user from database
	 * @param {boolean} confirmed true if user confirmed the deletion
	 */
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentUser != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './userview/user.php',
				data: $.param({ 'user': $scope.currentUser, 'type': 'delete_user' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).
			success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedUser), 1);
					$scope.setCurrentInternal(null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function (data, status, headers, config) {
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
	$scope.saveToDB = function () {
		if ($scope.currentUser == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.currentUser.password = $scope.currentUser.passwordstr;
			$scope.promise = $http({
				method: 'post',
				url: './userview/user.php',
				data: $.param({ 'user': $scope.currentUser, 'type': 'updateEntireUser' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).
			success(function (data, status, headers, config) {
				if (data.success) {
					// Select this user to reset everything
					$scope.setCurrentInternal($scope.selectedUser, null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function (data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	/**
	 * This function adds a new role in the database
	 * @param {object} newElement the new element to add to DB
	 */
	$scope.addUserToDB = function (newElement) {
		$scope.newUser = newElement;
		$scope.promise = $http({
			method: 'post',
			url: './userview/user.php',
			data: $.param({ 'user': $scope.newUser, 'type': 'insert_user' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).
		success(function (data, status, headers, config) {
			if (data.success) {
				var newUser = { id: data.id, userid: $scope.newUser.userid, fullname: $scope.newUser.fullname };
				$scope.leftobjs.push(newUser);
				// We could sort the list....
				$scope.setCurrentInternal(newUser);
				return true;
			} else {
				dialogService.displayFailure(data);
				return false;
			}
		}).
		error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	/**
	 * This function creates the modal to create new boardmember
	 */
	$scope.createNew = function () {
		$rootScope.createNewObject($scope, false, 'userview/newuser.template.html', $scope.addUserToDB);
	};

	/**
	 * This function let the user resets the password of an user. An email will be sent with the temporary password.
	 * @param {boolean} confirmed 
	 * @returns 
	 */
	$scope.resetAccount = function (confirmed) {
		if (!confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgresetaccount, "YESNO", $scope.resetAccount, null, true, null);
		} else {
			return $http({
				method: 'post',
				url: './core/services/authentication/authentication.php',
				data: $.param({ 'emailorusercode': $scope.currentUser.userid, 'type': 'resetPasswordAndSendEmail' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).
			success(function (data, status, headers, config) {
				if (data.success == 1) {
					dialogService.alertDlg("Courriel envoyé. / email sent.")
					$scope.setCurrentInternal($scope.selectedUser);
				} else {
					dialogService.displayFailure(data);
				}
			}).
			error(function (data, status, headers, config) {
				dialogService.displayFailure(data);
			});
		}
	};

	/**
	 * This function empties the contact field. Called by HTML
	 */
	$scope.emptycontact = function () {
		$scope.currentUser.contactid = "";
		$scope.currentUser.contactfullname = "";
		$scope.setDirty();
	}

	$scope.mainFilter = function (removeFilter) {
		if (removeFilter == true) {
			$scope.getAllUsers(null);
		} else {
			// Send the newFilter to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'userview/filter.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newFilter;
					}
				}
			}).result.then(function (newFilter) {
				// User clicked OK
				if (newFilter.firstname || newFilter.lastname || newFilter.role || newFilter.onlyexpiredpassword || newFilter.nbmonthsinceloggin) {
					$scope.newFilter = newFilter;
					$scope.getAllUsers(newFilter);
				} else {
					dialogService.alertDlg($scope.translationObj.main.msgnofilter, null);
					$scope.newFilter = {};
					$scope.getAllUsers(null);
				}
			}, function (dismiss) {
				if (dismiss == true) {
					$scope.getAllUsers(null);
				}
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	}

	/**
	 * Refresh all list
	 */
	$scope.refreshAll = function () {
		$scope.getAllUsers($scope.newFilter.filterApplied ? $scope.newFilter : null);
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'preferedlanguages', 'text', 'preferedlanguages');
		listsService.getAllRoles($scope, authenticationService.getCurrentLanguage());
		translationService.getTranslation($scope, 'userview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
