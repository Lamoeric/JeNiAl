angular.module('core').directive( "logoutButton", ['authenticationService', '$location', function(authenticationService, $location) {
	return {
		restrict: "A",
//		transclude: true,
		scope: false,
		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
		  	authenticationService.logout(scope).then(
		  	function(isEC) {
					if (isEC) {
						$location.path("/ccloginview");
					} else {
		  			$location.path("/loginview");
					}
		  	});
  		});
		}
	}
}]);
