'use strict';

angular.module('cpa_admin.contactview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/contactview', {
		templateUrl: 'contactview/contactview.html',
		controller: 'contactviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
			}
		}
	});
}])

.controller('contactviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "contactView";
	$scope.currentContact = null;
	$scope.selectedContact = null;
	$scope.newContact = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.newFilter = {};
	$scope.newFilter.filterApplied = false;
	$scope.formList = [{name:'detailsForm', errorMsg:'msgerrallmandatory'}];

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

	/**
	 * This function validates all forms and display error and warning messages
	 * @returns false if something is invalid
	 */
	$scope.validateAllForms = function () {
		return $rootScope.validateAllForms($scope, $scope.formList);
	}

	$scope.getAllContacts = function (newFilter) {
		if (newFilter) {
			$scope.newFilter.filterApplied = true;
		} else {
			$scope.newFilter.filterApplied = false;
		}
		$scope.promise = $http({
			method: 'post',
			url: './contactview/contactview.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'filter': newFilter, 'type': 'getAllContacts' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
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
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.getContactDetails = function (contact) {
		$scope.promise = $http({
			method: 'post',
			url: './contactview/contactview.php',
			data: $.param({ 'id': contact.id, 'type': 'getContactDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentContact = data.data[0];
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (contact, index) {
		if (contact != null) {
			$scope.selectedLeftObj = contact;
			$scope.selectedContact = contact;
			$scope.getContactDetails(contact);
			$scope.setPristine();
		} else {
			$scope.selectedContact = null;
			$scope.currentContact = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (contact, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, contact, index);
		} else {
			$scope.setCurrentInternal(contact, index);
		}
	};

	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentContact != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$http({
				method: 'post',
				url: './contactview/contactview.php',
				data: $.param({ 'contact': $scope.currentContact, 'type': 'delete_contact' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedContact), 1);
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

	$scope.saveToDB = function () {
		if ($scope.currentContact == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './contactview/contactview.php',
				data: $.param({ 'contact': $scope.currentContact, 'type': 'updateEntireContact' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this contact to reset everything
					$scope.setCurrentInternal($scope.selectedContact, null);
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

	$scope.addContactToDB = function () {
		$scope.promise = $http({
			method: 'post',
			url: './contactview/contactview.php',
			data: $.param({ 'contact': $scope.newContact, 'type': 'insert_contact' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				var newContact = { id: data.id, name: $scope.newContact.name };
				$scope.leftobjs.push(newContact);
				// We could sort the list....
				$scope.setCurrentInternal(newContact);
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

	// This is the function that creates the modal to create new contact
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newContact = {};
			// Send the newContact to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'contactview/newcontact.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newContact;
					}
				}
			}).result.then(function (newContact) {
				// User clicked OK and everything was valid.
				$scope.newContact = newContact;
				if ($scope.addContactToDB() == true) {
				}
			}, function () {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	/**
	 * This function is used by the new member dialog box to validate that the new member is valid
	 * All fields must be filled and member must not already be in the meber list
	 * @param {*} newMember Coach object being added
	 */
	$scope.validateNewMember = function (formObj, newMember) {
		// Validate the form using the field's rules
		if (formObj.$invalid) {
			return "#editObjFieldMandatory";
		}
		// Make sure that the new coach is not already in the list
		if (!newMember.id) {
			for (var x = 0; $scope.currentContact.members && x < $scope.currentContact.members.length; x++) {
				if ($scope.currentContact.members[x].memberid == newMember.member.id) {
					return "#editObjMemberAlreadyExists";
				}
			}
		}
	}

	// This is the function that creates the modal to create/edit ice
	$scope.editMember = function (newMember) {
		$scope.newMember = {};
		if (newMember.firstname) {
			$scope.currentMember = newMember;
			// Send the newMember to the modal form
			angular.copy(newMember, $scope.newMember);
			$scope.newMember.member = {};
			$scope.newMember.member.firstname = $scope.newMember.firstname;
			$scope.newMember.member.lastname = $scope.newMember.lastname;
		} else {
			$scope.currentMember = null;
		}
		$scope.newMember.callback = $scope.validateNewMember;
		$uibModal.open({
			animation: false,
			templateUrl: 'contactview/newmember.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: null,
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newMember;
				}
			}
		}).result.then(function (newMember) {
			newMember.callback = null;	// weird bug : callback was being called when saving the contact, so I set it to null to stop that behaviour.
			// User clicked OK and everything was valid.
			if ($scope.currentMember != null) {
				angular.copy(newMember, $scope.currentMember);
				$scope.currentMember.memberid = newMember.member.id;
				$scope.currentMember.firstname = newMember.member.firstname;
				$scope.currentMember.lastname = newMember.member.lastname;
				$scope.currentMember.status = 'Modified';
			} else {
				newMember.status = 'New';
				if ($scope.currentContact.members == null) $scope.currentContact.members = [];
				newMember.memberid = newMember.member.id;
				newMember.firstname = newMember.member.firstname;
				newMember.lastname = newMember.member.lastname;
				$scope.currentContact.members.push(newMember);
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	$scope.mainFilter = function (removeFilter) {
		if (removeFilter == true) {
			$scope.getAllContacts(null);
		} else {
			// Send the newFilter to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'contactview/filter.template.html',
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
				if (newFilter.firstname || newFilter.lastname || newFilter.course || newFilter.registration || newFilter.qualification) {
					$scope.newFilter = newFilter;
					$scope.getAllContacts(newFilter);
				} else {
					dialogService.alertDlg($scope.translationObj.main.msgnofilter, null);
					$scope.newFilter = {};
					$scope.getAllContacts(null);
				}
			}, function (dismiss) {
				if (dismiss == true) {
					$scope.getAllContacts(null);
				}
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	}

	$scope.refreshAll = function () {
		$scope.getAllContacts();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'contacttypes', 'text', 'contacttypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'qualifications', 'text', 'qualifications');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'registrationfilters', 'sequence', 'registrationfilters');
		listsService.getAllActiveCourses($scope, authenticationService.getCurrentLanguage());
		translationService.getTranslation($scope, 'contactview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
