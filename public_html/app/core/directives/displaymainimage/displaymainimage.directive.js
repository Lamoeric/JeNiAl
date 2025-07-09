/* Directive to display the main image
*  displayimagefilename : the name of the file to display
*  imageinfo : image info, like heigth, size, etc.
*  Author : Eric Lamoureux
*/
angular.module('core').directive( "displaymainimage", ['translationService', 'authenticationService', function(translationService, authenticationService) {
	return {
		templateUrl: './core/directives/displaymainimage/displaymainimage.template.html',
		scope: {
			displayimagefilename:"=",
			imageinfo:"="
		},

		link: function(scope, element, attrs) {
			translationService.getTranslation(scope, 'core/directives/displaymainimage', authenticationService.getCurrentLanguage());
		}
	}
}]);
