// This directive roduces a button to add a contact. It starts by searching for a contact and then editing the selected contact,
// or if not contact were selected, editing a new contact
angular.module('core').directive('addClubContactButton', ['$uibModal', '$http', 'anycodesService', 'editContactService', 'authenticationService', 'translationService', function($uibModal, $http, anycodesService, editContactService, authenticationService, translationService) {
	return {
		restrict: 'E',
		require: '^form',				// To set the $dirty flag after copying the new member
		scope: {
			objlist: '=',
			club: '=',
      isFormPristine: '=isFormPristine',
			callback:'&'					// Not used anymore
		},
		template:'<button class="btn btn-primary" ng-disabled="isFormPristine">{{translationObj.main.buttontitleaddcontact}}</button>',
		link: function( scope, element, attrs, formCtrl ) {
			element.bind("click", function() {
				scope.formObj = formCtrl;
				searchContact();
			});

			// This is the function that creates the modal to search members
			function searchContact() {
				// Set the search parameters for the search contact modal form
				scope.searchParams = {phpfilename:'./core/directives/contacts/searchcontacts.php',
															language:authenticationService.getCurrentLanguage()};
				// Send the parameters to the modal form
				$uibModal.open({
						animation: false,
						templateUrl: './core/directives/contacts/searchcontacts.template.html',
						controller: 'searcheditor.controller',
						scope: scope,
						size: 'xl',
						backdrop: 'static',
						resolve: {
							searchParams: function() {
								return scope.searchParams;
							}
						}
				})
				.result.then(function(selectedObject) {
					// User clicked OK and everything was valid.
					// User has selected a contact, now switch to editing
					editContactService.editClubContact(scope, selectedObject);
				}, function(param) {
					// User clicked CANCEL.
					// Check parameter, if parameter is 'createNew', call the edit contact service, if not, simply exit.
					if (param == 'createNew') {
						editContactService.editClubContact(scope, {});
					}
					return;
				});
			};
			translationService.getTranslation(scope, 'core/directives/clubcontacts', authenticationService.getCurrentLanguage());
		}
	}
}]);
