angular.module('core').directive( "mainNewButton", [function() {
	return {
//		restrict: "A",
//		transclude: true,
//		scope: false,
		template:'<button class="btn btn-primary glyphicon glyphicon-plus" ng-disabled="isFormPristine"></button>',
    scope: {
      callback: '&newCallback',
			isFormPristine: '=isFormPristine'
    },

		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
					scope.callback();
  		});
		}
	}
}]);
