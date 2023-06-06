angular.module('core').directive('contacts', ['translationService', 'authenticationService' ,function(translationService, authenticationService) {
  return {
    restrict: 'E',
    scope: {
      contacts: '=',
      member: '=',
      club: '=',
      newContactDisabled: '=',
      setParentDirty: '&setDirty',
      editParentContact: '&editContact'
    },
		templateUrl: './core/directives/contacts/fullcontacts.template.html',
		link: function( scope, element, attrs ) {
			// This function redirects the setDirty from the underlying directives, like childDelete
  		scope.setDirty = function () {
  			scope.setParentDirty();
  			return;
  		}

			// This function redirects the editContact from the underlying directive childEdit
  		scope.editContact = function () {
  			scope.editParentContact();
  			return;
  		}
      translationService.getTranslation(scope, 'core/directives/contacts', authenticationService.getCurrentLanguage());
		}
  };
}]);
