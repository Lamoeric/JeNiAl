// This module drives the login modal form.
// Author: Eric Lamoureux
// Created on: 2016-04-28
angular.module('core').controller('login.controller', ['$scope', '$uibModalInstance', 'authenticationService', 'dialogService', function($scope, $uibModalInstance, authenticationService, dialogService, newObj) {

	// Get the newObj from main controller, modify it and return it in case of OK
  $scope.newObj = newObj;
  $scope.ok = function () {
		if ($scope.editObjForm.$invalid) {
			// Show then hide the error message
			$("#editObjFieldMandatory").fadeTo(2000, 500).slideUp(500, function(){$("#editObjFieldMandatory").hide();});
		} else {
			authenticationService.validateLoginInfo($scope, $scope.newObj.userid, $scope.newObj.password).then(
			function(retVal) {
				if (retVal.data.success === undefined) {
					dialogService.displayFailure(retVal.data);
				} else if (retVal.data.success == true) {
          angular.copy(retVal.data.user, $scope.newObj);
					$uibModalInstance.close($scope.newObj);
				} else {
					$("#invalidLogin").fadeTo(2000, 500).slideUp(500, function(){$("#invalidLogin").hide();});
				}
			});
		}
  };

	// In case of cancel, forget everything and return
  $scope.cancel = function () {
    $uibModalInstance.dismiss('cancel');
  };

  $scope.forgotPassword = function () {
    $scope.newObj.forgotPassword = true;
    $uibModalInstance.close($scope.newObj);
  };

}]);
