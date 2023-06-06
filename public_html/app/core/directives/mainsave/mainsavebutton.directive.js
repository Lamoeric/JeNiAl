angular.module('core').directive( "mainSaveButton", [function() {
	return {
//		restrict: "A",
//		transclude: true,
//		scope: false,
		template:'<button class="btn btn-primary glyphicon glyphicon-save" ng-disabled="isFormPristine"></button>',
    scope: {
      callback: '&saveCallback',
      isFormPristine: '=isFormPristine'
    },

		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
					scope.callback();
  		});
		}
	}
}]);
