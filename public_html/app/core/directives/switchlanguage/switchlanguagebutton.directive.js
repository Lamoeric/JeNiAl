angular.module('core').directive( "switchLanguageButton", ['authenticationService', function(authenticationService) {
	return {
		restrict: "A",
//		transclude: true,
		scope: false,
		link: function(scope, element, attrs) {
			element.bind("click", function() {
	  		authenticationService.switchLanguage();
  		});
		}
	}
}]);
