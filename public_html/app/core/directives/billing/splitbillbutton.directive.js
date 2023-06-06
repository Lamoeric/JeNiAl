// This directive creates a button, that when clicked, split the designated registration from the current bill
// 		@author	Eric Lamoureux, 2018/09/14
//	inputs :
//		currentBillId : current bill id from which we want to split the registration.
//    currentRegistrationId : registration id we want to split from the bill.
angular.module('core').directive( "splitBillButton", ['$window', '$http', '$uibModal', 'authenticationService', 'dialogService', 'reportingService', 'translationService', 'billingService', function($window, $http, $uibModal, authenticationService, dialogService, reportingService, translationService, billingService) {
	return {
		template:'<button class="btn btn-primary glyphicon glyphicon-share"></button>',
		scope: {
			currentBillId: '=currentBillId',
			currentRegistrationId: '=currentRegistrationId'
		},

		link: function(scope, element, attrs) {
			translationService.getTranslation(scope, 'core/directives/billing', authenticationService.getCurrentLanguage());
			element.bind("click", function() {
				if (scope.translationObj) {
					dialogService.confirmYesNo(scope.translationObj.splitbill.msgvalidatebillsplitting, function(e) {
						if (e) {
							billingService.splitBill(scope, scope.currentBillId, scope.currentRegistrationId, authenticationService.getCurrentLanguage())
							.then(function() {
								dialogService.alertDlg(scope.translationObj.splitbill.msgbillsplittingreload);
								});
						} else {
							// user clicked "no"
						}
					});
				}
			});
		}
	}
}]);
