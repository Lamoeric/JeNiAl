angular.module('core').directive( "childDeleteButton", [function() {
	return {
//		restrict: "A",
//		transclude: true,
//		scope: false,
		template:'<button class="btn btn-primary glyphicon glyphicon-trash" ng-show="(!obj.status || (obj.status && (obj.status != \'Deleted\' || obj.status == null)))"  ng-disabled="isFormPristine"></button>',
    scope: {
      obj: '=obj',
      objlist: '=objlist',
      isFormPristine: '=isFormPristine',
      callback: '&callback'
    },

		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
				if (scope.obj.status && scope.obj.status == 'New') {
					scope.objlist.splice(scope.objlist.indexOf(scope.obj), 1);
				} else {
					scope.obj.status = 'Deleted';
				}
				scope.callback();
  		});
		}
	}
}]);
