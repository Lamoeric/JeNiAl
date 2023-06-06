/* Directive to display the main title on top of the page
*  maintitle : main title to display if object is not null
*  defaulttitle : title to display if object is null
*  object : object to check
*  Author : Eric Lamoureux, 2018-10-02
*/
angular.module('core').directive( "displaymaintitle", [function() {
	return {
		template:'<div ng-if="object!=null"><h2>{{maintitle}}</h2></div><div ng-if="object==null"><h2>{{defaulttitle}}</h2></div>',
		scope: {
			maintitle:"=",
			defaulttitle:"=",
			object:"="
		},

		link: function(scope, element, attrs) {
		}
	}
}]);
