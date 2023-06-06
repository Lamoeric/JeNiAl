angular.module('core').directive( "mainRefreshButton", function() {
	return {
		restrict: "A",
//		transclude: true,
    scope: {
      callback: '&callback'
    },
		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
				scope.callback();
  		});
		}
	}
});
 