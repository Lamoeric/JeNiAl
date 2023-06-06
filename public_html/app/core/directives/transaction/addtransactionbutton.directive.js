angular.module('core').directive('addTransactionButton', ['$uibModal', '$http', '$rootScope', 'anycodesService', 'transactionService', 'translationService', 'authenticationService', function($uibModal, $http, $root, anycodesService, transactionService, translationService, authenticationService) {
	return {
		restrict: 'E',
		require: '^form',				// To set the $dirty flag after adding a transaction
		scope: {
			objlist: '=',
			currentBill: '=',
      isFormPristine: '=isFormPristine'
		},
		template:'<button class="btn btn-primary" ng-disabled="isFormPristine" ng-if="userInfo.privileges.transaction_access==true">{{translationObj.main.buttontitleaddtransaction}}</button>',
		link: function(scope, element, attrs, formCtrl) {
			element.bind("click", function() {
				scope.formObj = formCtrl;
				transactionService.editTransaction(scope, scope.objlist, null, scope.currentBill, 1);
			});
			translationService.getTranslation(scope, 'core/directives/transaction', authenticationService.getCurrentLanguage());
			scope.userInfo = authenticationService.getUserInfo();
		}
	};
}]);
