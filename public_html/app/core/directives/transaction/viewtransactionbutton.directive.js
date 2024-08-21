angular.module('core').directive('viewTransactionButton', ['transactionService', 'authenticationService', function (transactionService, authenticationService) {
	return {
		restrict: 'E',
		require: '^form',				// To set the $dirty flag after copying the new member
		scope: {
			currentTransaction: '=',
			isFormPristine: '=isFormPristine'
		},
		template: '<button class="btn btn-primary glyphicon glyphicon-eye-open btn-mini" ng-disabled="isFormPristine" ng-if="userInfo.privileges.transaction_access==true"></button>',
		link: function (scope, element, attrs, formCtrl) {
			element.bind("click", function () {
				// To catch exception in our global exception handler, put code in $timeout function
				// $timeout(function() {
				scope.formObj = formCtrl;
				transactionService.viewTransaction(scope, scope.currentTransaction);
				// });
			});
			scope.userInfo = authenticationService.getUserInfo();
		}
	};
}]);