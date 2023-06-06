angular.module('core').directive( "clearSearchButton", function() {
	return {
		restrict: "A",
//		transclude: true,
		scope: false,
		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
		  	scope.searchFilter = "";
		  	scope.$apply();
  		});
		}
	}
});
 