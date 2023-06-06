angular.module('core').directive('csSummary', ['translationService', 'authenticationService', function(translationService, authenticationService) {
  return {
    restrict: 'E',
    scope: {
      member: '=',
      isDisabled: '=',
      setDirty: '&',
      // translationObj: '=',
    },
		templateUrl: './core/directives/cssummary/cssummary.template.html',
		link: function(scope, element, attrs) {
			scope.setDirty2 = function() {
				scope.setDirty();
			}
      translationService.getTranslation(scope, 'core/directives/cssummary', authenticationService.getCurrentLanguage());
		}
  };
}]);
