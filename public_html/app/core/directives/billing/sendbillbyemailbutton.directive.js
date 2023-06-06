angular.module('core').directive( "sendBillByEmailButton", ['$window', '$http', '$uibModal', 'authenticationService', 'dialogService', 'reportingService', 'translationService', 'billingService', function($window, $http, $uibModal, authenticationService, dialogService, reportingService, translationService, billingService) {
	return {
		template:'<button class="btn btn-primary glyphicon glyphicon-envelope"></button>',
    scope: {
      currentBillId: '=currentBillId',
      promise: '=promise'
    },

		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
				billingService.selectBillingEmails(scope, scope.currentBillId, authenticationService.getCurrentLanguage())
				.then(function(selectBillingEmails) {
					if (selectBillingEmails) {
						scope.selectedRecipient = selectBillingEmails.billingemail;
						billingService.createBillPdfFile(scope, scope.currentBillId, authenticationService.getCurrentLanguage())
						.then(function(param, filename) {
							if (param.data) {
								// var filename = param.data.replace("C:\\wamp\\www\\", "localhost/").replace(/\\/g, '/');
								// var filename = param.data;
								// [lamoeric 2018/09/11] Keep the file name, but change \ by / and trim the string
								var filename = param.data.replace(/\\/g, '/').trim();
								scope.promise = billingService.sendBillByEmail(scope.selectedRecipient.email, scope.selectedRecipient.fullname, filename, authenticationService.getCurrentLanguage())
								.then(function(param) {
									return;
								})
							}
						})
					}
					return;
				});
				return;

	    });

		}
	}
}]);
