angular.module('core').directive('addPaymentAgreementButton', ['$uibModal', '$http', '$rootScope', 'anycodesService', 'paymentagreementService', 'translationService', 'authenticationService', function($uibModal, $http, $root, anycodesService, paymentagreementService, translationService, authenticationService) {
	return {
		restrict: 'E',
		require: '^form',				// To set the $dirty flag after adding a transaction
		scope: {
			currentBill: '=',
      isFormPristine: '=isFormPristine'
		},
		template:'<button class="btn btn-primary" ng-disabled="isFormPristine" ng-if="userInfo.privileges.transaction_access==true">{{translationObj.main.buttontitleaddpayment}}</button>',
		link: function(scope, element, attrs, formCtrl) {
			element.bind("click", function() {
				scope.formObj = formCtrl;
				paymentagreementService.editPaymentagreement(scope, scope.currentBill);
			});
			translationService.getTranslation(scope, 'core/directives/paymentagreement', authenticationService.getCurrentLanguage());
			scope.userInfo = authenticationService.getUserInfo();
		}
	};
}]);
