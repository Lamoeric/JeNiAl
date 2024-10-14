angular.module('core').directive("mainNewButton", ['translationService', 'authenticationService', function (translationService, authenticationService) {
	return {
		template: '<button class="btn btn-primary glyphicon glyphicon-plus" ng-disabled="isFormPristine" data-toggle="tooltip" title="{{translationObj.main.tooltipmainnew}}"></button>',
		scope: {
			callback: '&newCallback',
			isFormPristine: '=isFormPristine'
		},

		link: function (scope, element, attrs) {
			element.bind("click", function () {
				scope.callback();
			});
			translationService.getTranslation(scope, 'core/directives/mainnew', authenticationService.getCurrentLanguage());
		}
	}
}]);
