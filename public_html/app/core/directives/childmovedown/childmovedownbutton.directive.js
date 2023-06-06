angular.module('core').directive( "childMovedownButton", [function() {
	return {
		template:'<button class="btn btn-primary glyphicon glyphicon-arrow-down"  ng-disabled="isFormPristine"></button>',
    scope: {
      obj: '=obj',
      objlist: '=objlist',
      isFormPristine: '=isFormPristine',
      callback: '&callback',
			prop:"=prop"
    },

		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
				var oldIndex = scope.objlist.indexOf(scope.obj);
				var newIndex = null;
				if (oldIndex < scope.objlist.length-1) {
					newIndex = oldIndex + 1;
					scope.obj.status = 'Modified';
					scope.obj[scope.prop] = newIndex+1;
					scope.objlist[newIndex].status = 'Modified';
					scope.objlist[newIndex][scope.prop] = oldIndex+1;
					scope.objlist.splice(newIndex, 0, scope.objlist.splice(oldIndex, 1)[0]);
					scope.callback({code:scope.obj});
				}
  		});
		}
	}
}]);
