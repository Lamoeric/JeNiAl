angular.module('core').directive("labelrequired", [function () {
	return {
		template: '<label for="{{for}}">{{label}}<i style="color:red"> *</i></label>',
		scope: {
			for: '@',
			label: '=',
		},

		link: function (scope, element, attrs) {
			return;
		}
	}
}]);
