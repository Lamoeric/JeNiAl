angular.module('core').directive('starTestSummary', ['translationService', 'authenticationService', function(translationService, authenticationService) {
  return {
    restrict: 'E',
    scope: {
      summary: '=',
      // translationObj: '=',
    },
		templateUrl: './core/directives/testsummary/startestsummary.template.html',
		link: function( scope, element, attrs ) {
      translationService.getTranslation(scope, 'core/directives/testsummary', authenticationService.getCurrentLanguage());
		}
  };
}]);
