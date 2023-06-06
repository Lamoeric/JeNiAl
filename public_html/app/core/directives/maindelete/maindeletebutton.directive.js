angular.module('core').directive( "mainDeleteButton", [function() {
	return {
//		restrict: "A",
//		transclude: true,
//		scope: false,
		template:'<button class="btn btn-primary glyphicon glyphicon-trash" ng-disabled="isFormPristine"></button>',
//		template:'<button class="btn btn-primary"><span>Delete</span></button>',
    scope: {
      callback: '&deleteCallback',
      isFormPristine: '=isFormPristine'
    },

		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
					scope.callback();
  		});
		}
	}
}]);
