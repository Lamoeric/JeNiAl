// This directive creates a form to display the specified bill
//  @author  Eric Lamoureux
//	inputs :
//    currentBill : the current bill to display.
//    currentLanguage : current language in which to display the bill
//    allowSplitting : whether or not to allow to split a registration from a bill. If not, split button is not displayed.
angular.module('core').directive('billing', ['$location', '$route', 'translationService', 'authenticationService', function($location, $route, translationService, authenticationService) {
	return {
		restrict: 'E',
		scope: {
			currentBill: '=',
			currentLanguage: '=currentLanguage',
			allowSplitting: '=allowSplitting'
		},
		templateUrl: './core/directives/billing/bill.template.html',
		link: function( scope, element, attrs ) {
			translationService.getTranslation(scope, 'core/directives/billing', authenticationService.getCurrentLanguage());

			scope.viewBill = function(billid) {
				$location.path('billview/' + billid);
				$route.reload();
			}

		}


	};
}]);
