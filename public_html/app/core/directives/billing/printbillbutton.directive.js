angular.module('core').directive("printBillButton", ['$window', 'authenticationService', function($window, authenticationService) {
	return {
		template:'<button class="btn btn-primary glyphicon glyphicon-print"></button>',
    scope: {
      currentBillId: '=currentBillId'
    },

		link: function(scope, element, attrs) {
			element.bind("click", function() {
				$window.open('./reports/memberBill.php?language='+authenticationService.getCurrentLanguage()+'&billid='+scope.currentBillId);
  		});
		}
	}
}]);
