// This directive roduces a button to add a contact. It starts by searching for a contact and then editing the selected contact,
// or if not contact were selected, editing a new contact
angular.module('core').directive('selectContactButton', ['$uibModal', '$http', 'anycodesService', 'editContactService', 'authenticationService', 'translationService', function($uibModal, $http, anycodesService, editContactService, authenticationService, translationService) {
	return {
		restrict: 'E',
		require: '^form',				// To set the $dirty flag after returning the contactid
		scope: {
			contactid: '=',
			contactfullname: '='
		},
		template:'<button class="btn btn-primary" ng-disabled="isFormPristine">{{translationObj.main.buttontitleselectcontact}}</button>',
		link: function( scope, element, attrs, formCtrl ) {
			element.bind("click", function() {
				scope.formObj = formCtrl;
				searchContact();
			});

			scope.customSearch = function(actual, expected) {
        // Ignore object.
        if (angular.isObject(actual)) return false;
        function removeAccents(value) {
            return value.toString()
                                  .replace(/á/g, 'a')
                                  .replace(/â/g, 'a')
                                  .replace(/à/g, 'a')
                                  .replace(/é/g, 'e')
                                  .replace(/è/g, 'e')
                                  .replace(/ê/g, 'e')
                                  .replace(/ë/g, 'e')
                                  .replace(/í/g, 'i')
                                  .replace(/ï/g, 'i')
                                  .replace(/ì/g, 'i')
                                  .replace(/ó/g, 'o')
                                  .replace(/ô/g, 'o')
                                  .replace(/ú/g, 'u')
                                  .replace(/ü/g, 'u')
                                  .replace(/û/g, 'u')
                                  .replace(/ç/g, 'c');
        }
        actual = removeAccents(angular.lowercase('' + actual));
        expected = removeAccents(angular.lowercase('' + expected));

        return actual.indexOf(expected) !== -1;
      }

			// This is the function that creates the modal to search members
			function searchContact() {
				// Set the search parameters for the search contact modal form
				scope.searchParams = {phpfilename:'./core/directives/contacts/searchcontacts.php',
															language:authenticationService.getCurrentLanguage(),
															nocreatenew:true};
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
					// editContactService.editContact(scope, selectedObject);
					scope.contactid = selectedObject.id;
					scope.contactfullname = selectedObject.firstname + " " + selectedObject.lastname;
					scope.formObj.$dirty = true;
				}, function(param) {
					// User clicked CANCEL.
					// Check parameter, if parameter is 'createNew', call the edit contact service, if not, simply exit.
					// if (param == 'createNew') {
					// 	editContactService.editContact(scope, {});
					// }
					return;
				});
			};
			translationService.getTranslation(scope, 'core/directives/contacts', authenticationService.getCurrentLanguage());
		}
	}
}]);
