angular.module('core').directive( "clearSearchButtonEx", function() {
	return {
		template:'<span id="searchclear" class="glyphicon glyphicon-remove-circle"></span>',
		scope: {
			searchfilter: '=searchfilter',
		},
		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
				scope.searchfilter = "";
				scope.$apply();
			});
		}
	}
});
 