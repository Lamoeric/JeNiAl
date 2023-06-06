angular.module('core').directive( "loginButton", ['authenticationService', '$location', function(authenticationService, $location) {
	return {
		restrict: "A",
//		transclude: true,
		scope: false,
		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
		  	authenticationService.login(scope).then(
		  	function(retVal) {
		  		$location.path("/welcomeview");
		  	});
  		});
		}
	}
}]);
 