angular.module('core').directive('cancelTransactionButton', ['$uibModal', '$http', '$rootScope', 'anycodesService', 'transactionService', 'translationService', 'authenticationService', function($uibModal, $http, $root, anycodesService, transactionService, translationService, authenticationService) {
	return {
		restrict: 'E',
		require: '^form',				// To set the $dirty flag after canceling a transaction NOT NEEDED?
		scope: {
			objlist: '=',
			currentTransaction: '=',
			currentBill: '=',
      isFormPristine: '=isFormPristine'
		},
		template:'<button class="btn btn-primary glyphicon glyphicon-remove btn-mini" ng-disabled="isFormPristine" ng-if="userInfo.privileges.transaction_access==true"></button>',
		link: function(scope, element, attrs, formCtrl) {
			element.bind("click", function() {
				scope.formObj = formCtrl;
				transactionService.cancelTransaction(scope, scope.objlist, scope.currentTransaction, scope.currentBill, 1);
			});
			translationService.getTranslation(scope, 'core/directives/transaction', authenticationService.getCurrentLanguage());
			scope.userInfo = authenticationService.getUserInfo();
		}
	};
}]);
