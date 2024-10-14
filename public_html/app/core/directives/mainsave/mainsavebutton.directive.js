angular.module('core').directive("mainSaveButton", ['translationService', 'authenticationService', function (translationService, authenticationService) {
	return {
		restrict: 'E',
		template: '<button class="btn btn-primary glyphicon glyphicon-save" ng-disabled="isFormPristine" data-toggle="tooltip" title="{{translationObj.main.tooltipmainsave}}"></button>',
		scope: {
			callback: '&saveCallback',
			isFormPristine: '=isFormPristine'
		},

		link: function (scope, element, attrs) {
			element.bind("click", function () {
				scope.callback();
			});
			scope.$on('main-save', function(onEvent, keypressEvent) {
				if (!scope.isFormPristine) {
					scope.callback();
				}
			});
			translationService.getTranslation(scope, 'core/directives/mainsave', authenticationService.getCurrentLanguage());

		}
	}
}]);
