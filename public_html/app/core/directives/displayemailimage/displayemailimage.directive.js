/* Directive to display the email image
*  displayimagefilename : the name of the file to display
*  imageinfo : image info, like heigth, size, etc.
*  Author : Eric Lamoureux
*/
angular.module('core').directive( "displayemailimage", ['translationService', 'authenticationService', function(translationService, authenticationService) {
	return {
		templateUrl: './core/directives/displayemailimage/displayemailimage.template.html',
		scope: {
			displayimagefilename:"=",
			imageinfo:"="
		},

		link: function(scope, element, attrs) {
			translationService.getTranslation(scope, 'core/directives/displayemailimage', authenticationService.getCurrentLanguage());
		}
	}
}]);
