angular.module('core').directive('editContactButton', ['$uibModal', '$http', 'anycodesService', 'editContactService', function($uibModal, $http, anycodesService, editContactService) {
	return {
		restrict: 'E',
		require: '^form',				// To set the $dirty flag after copying the new member
		scope: {
			objlist: '=',
			obj: '=',
			member: '=',
      isFormPristine: '=isFormPristine',
			callback:'&'
		},
		template:'<button class="btn btn-primary glyphicon glyphicon-pencil" ng-disabled="isFormPristine"></button>',
		link: function(scope, element, attrs, formCtrl) {
			element.bind("click", function() {
				scope.formObj = formCtrl;
				editContactService.editContact(scope, scope.obj);
			});
		}
	};
}]);
