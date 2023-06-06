// Directive to change the language of a HTML tag. 
angular.module('core').directive('updatelanguage', ['$rootScope', 'authenticationService', function($rootScope, authenticationService) {
  return {
      link: function(scope, element) {
        // Default behavior when loading
        var defaultLang = "fr-ca", currentlang = authenticationService.getCurrentLanguage();
        element.attr("lang", currentlang || defaultLang);

        // When language changes
        $rootScope.$on("authentication.language.changed", function(event, current, previous, eventObj) {
          var defaultLang = "fr-ca", currentlang = authenticationService.getCurrentLanguage();
          element.attr("lang", currentlang || defaultLang);
        });

      }
   };
}]);
