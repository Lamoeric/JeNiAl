angular.module('core').directive("mainDeleteButton", ['translationService', 'authenticationService', function (translationService, authenticationService, ) {
	return {
		template: '<button class="btn btn-primary glyphicon glyphicon-trash" ng-disabled="isFormPristine" data-toggle="tooltip" title="{{translationObj.main.tooltipmaindelete}}"></button>',
		scope: {
			callback: '&deleteCallback',
			isFormPristine: '=isFormPristine'
		},

		link: function (scope, element, attrs) {
			element.bind("click", function () {
				scope.callback();
			});
			translationService.getTranslation(scope, 'core/directives/maindelete', authenticationService.getCurrentLanguage());
		}
	}
}]);
