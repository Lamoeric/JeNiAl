/* Directive for the left pane.
*  Object list must be "leftobjs" and each object is called "leftobj".
*  Author : Eric Lamoureux, 2018-10-02
*/
angular.module('core').directive('newleftpane', ['$rootScope', 'translationService', 'authenticationService', function($rootScope, translationService, authenticationService) {
  return {
    restrict: 'E',
    templateUrl: './core/directives/leftpane/newleftpane.template.html',

    link: function( scope, element, attrs, formCtrl) {
      scope.leftpanetemplatefullpath = attrs.leftpanetemplatefullpath;
      $rootScope.repositionLeftColumn();

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
      translationService.getTranslation(scope, 'core/directives/leftpane', authenticationService.getCurrentLanguage());
    }

  };
}]);
