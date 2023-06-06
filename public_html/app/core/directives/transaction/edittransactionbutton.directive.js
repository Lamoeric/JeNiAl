angular.module('core').directive('editTransactionButton', ['$uibModal', '$http', 'anycodesService', 'transactionService', function($uibModal, $http, anycodesService, transactionService) {
	return {
		restrict: 'E',
		require: '^form',				// To set the $dirty flag after copying the new member
		scope: {
			objlist: '=',
			obj: '=',
      isFormPristine: '=isFormPristine',
			callback:'&'
		},
		template:'<button class="btn btn-primary glyphicon glyphicon-pencil" ng-disabled="isFormPristine"></button>',
		link: function(scope, element, attrs, formCtrl) {
			element.bind("click", function() {
				scope.formObj = formCtrl;
				transactionService.editTransaction(scope, scope.obj);
			});
		}
	};
}]);