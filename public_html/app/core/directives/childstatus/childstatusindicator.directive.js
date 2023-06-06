// Directive to display a red, glyphicon, status indicator (new, delete, edited) based on the status property of an "obj" object.
angular.module('core').directive( "childStatusIndicator", [function() {
	return {
//		restrict: "A",
//		transclude: true,
//		scope: false,
		template:'<span class="glyphicon glyphicon-remove" 	style="color:red" ng-show="obj.status && obj.status == \'Deleted\'"></span><span class="glyphicon glyphicon-pencil" 	style="color:red" ng-show="obj.status && obj.status == \'Modified\'"></span><span class="glyphicon glyphicon-plus" 		style="color:red" ng-show="obj.status && obj.status == \'New\'"></span>',
    scope: {
      obj: '=obj'
    }//,

//		link: function( scope, element, attrs ) {
//			element.bind( "click", function() {
//					scope.callback();
//  		});
//		}
	}
}]);
