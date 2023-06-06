angular.module('core').directive( "childUndeleteButton", [function() {
	return {
//		restrict: "A",
//		transclude: true,
//		scope: false,
		template:'<button class="btn btn-primary glyphicon glyphicon-repeat" ng-show="obj.status && obj.status == \'Deleted\'" ng-disabled="isFormPristine"></button>',
    scope: {
      obj: '=obj',
      objlist: '=objlist',
			isFormPristine: '=isFormPristine',
      callback: '&callback'
    },

		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
				if (scope.obj.status && scope.obj.status == 'Deleted') {
					scope.obj.status = 'Modified';
					scope.callback();
				}
  		});
		}
	}
}]);
