// This service handles the editing of a current or new contact.
angular.module('core').service('editContactService', ['$uibModal', '$http', 'anycodesService', 'authenticationService', 'translationService', function($uibModal, $http, anycodesService, authenticationService, translationService) {
	// This is the function that creates the modal to edit contact
	this.editContact = function(scope, contact) {
				var newContact = {};
				// Set the default value for the new object
				if (!contact.contacttype)	contact.contacttype = '3';		// Default value : Other
				// Keep a pointer to the current contact
				scope.currentContact = contact;
				// Copy in another object
				angular.copy(contact, newContact);
				// Get the values for the contacttype drop down list
				anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'contacttypes', 'text', 'contacttypes');
				// Send the newContact to the modal form
				$uibModal.open({
						animation: false,
						templateUrl: './core/directives/contacts/editcontact.template.html',
						controller: 'childeditor.controller',
						scope: scope,
						size: 'lg',
						backdrop: 'static',
						resolve: {
							newObj: function () {
								return newContact;
							},
							member: function () {
								return scope.member;
							}
						}
					})
					.result.then(function(newContact) {
						// User clicked OK and everything was valid.
						// Copy back to the current contact
						angular.copy(newContact, scope.currentContact);
						// Convert contacttype into relation name
						scope.currentContact.relationname = anycodesService.convertCodeToDesc(scope, 'contacttypes', scope.currentContact.contacttype);
						// If contact already exists in DB (cmcid != null)
						if (scope.currentContact.cmcid != null) {
							scope.currentContact.status = 'Modified';
						} else {
							scope.currentContact.status = 'New';
							scope.currentContact.cmcid = null;
							// Don't insert twice in list
							if (scope.objlist.indexOf(scope.currentContact) == -1) {
								scope.objlist.push(scope.currentContact);
							}
						}
						// Set the form in which this directive is inserted to dirty.
						scope.formObj.$dirty = true;
					}, function() {
						// User clicked CANCEL.
				});
	};

	this.editClubContact = function(scope, contact) {
		var newContact = {};
		// Set the default value for the new object
		if (!contact.contacttype)	contact.contacttype = '1';		// Default value : Board member
		// Keep a pointer to the current contact
		scope.currentContact = contact;
		// Copy in another object
		angular.copy(contact, newContact);
		// Get the values for the contacttype drop down list
		anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'clubcontacttypes', 'text', 'contacttypes');
		// Send the newContact to the modal form
		translationService.getTranslation(scope, 'core/directives/clubcontacts', authenticationService.getCurrentLanguage());
		$uibModal.open({
				animation: false,
				templateUrl: './core/directives/clubcontacts/editclubcontact.template.html',
				controller: 'childeditor.controller',
				scope: scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return newContact;
					},
					member: function () {
						return scope.member;
					}
				}
			})
			.result.then(function(newContact) {
				// User clicked OK and everything was valid.
				// Copy back to the current contact
				angular.copy(newContact, scope.currentContact);
				// Convert contacttype into relation name
				scope.currentContact.relationname = anycodesService.convertCodeToDesc(scope, 'contacttypes', scope.currentContact.contacttype);
				// If contact already exists in DB (cmcid != null)
				if (scope.currentContact.cmcid != null) {
					scope.currentContact.status = 'Modified';
				} else {
					scope.currentContact.status = 'New';
					scope.currentContact.cmcid = null;
					// Don't insert twice in list
					if (scope.objlist.indexOf(scope.currentContact) == -1) {
						scope.objlist.push(scope.currentContact);
					}
				}
				// Set the form in which this directive is inserted to dirty.
				scope.formObj.$dirty = true;
			}, function() {
				// User clicked CANCEL.
		});
	};

			
}]);
