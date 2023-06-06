angular.module('core').directive('testSummary', ['translationService', 'authenticationService', function(translationService, authenticationService) {
  return {
    restrict: 'E',
    scope: {
      summary: '=',
      // translationObj: '=',
    },
		templateUrl: './core/directives/testsummary/testsummary.template.html',
		link: function( scope, element, attrs ) {
      translationService.getTranslation(scope, 'core/directives/testsummary', authenticationService.getCurrentLanguage());
		}
  };
}]);
