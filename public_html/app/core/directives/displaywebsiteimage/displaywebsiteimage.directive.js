/* Directive to display the main image of a website program
*  displayimagefilename : the name of the file to display
*  imageinfo : image info, like heigth, size, etc.
*  Author : Eric Lamoureux
*/
angular.module('core').directive( "displaywebsiteimage", ['translationService', 'authenticationService', function(translationService, authenticationService) {
	return {
		templateUrl: './core/directives/displaywebsiteimage/displaywebsiteimage.template.html',
		scope: {
			displayimagefilename:"=",
			imageinfo:"="
			
		},

		link: function(scope, element, attrs) {
			translationService.getTranslation(scope, 'core/directives/displaywebsiteimage', authenticationService.getCurrentLanguage());
		}
	}
}]);
