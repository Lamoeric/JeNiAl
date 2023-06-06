// This module drives the change password modal form.
// Author: Eric Lamoureux
// Created on: 2017-06-20
angular.module('core').controller('changepassword.controller', ['$scope', '$uibModalInstance', 'authenticationService', 'dialogService', 'newObj', function($scope, $uibModalInstance, authenticationService, dialogService, newObj) {

	// Get the newObj from main controller, modify it and return it in case of OK
  $scope.newObj = newObj;
  newObj.showpassword = false;
  $scope.ok = function () {
		if ($scope.editObjForm.$invalid) {
			// Show then hide the error message
			$("#editObjFieldMandatory").fadeTo(2000, 500).slideUp(500, function(){$("#editObjFieldMandatory").hide();});
		} else {
      if ($scope.newObj.newpassword == $scope.newObj.oldpassword) {
  			// Show then hide the error message
  			$("#editNewPasswordMustDiffer").fadeTo(2000, 500).slideUp(500, function(){$("#editNewPasswordMustDiffer").hide();});
      } else {
  			authenticationService.changePassword($scope, $scope.newObj.userid, $scope.newObj.oldpassword, $scope.newObj.newpassword).then(
  			function(retVal) {
  				if (retVal.data.success === undefined) {
  					dialogService.displayFailure(retVal.data);
  				} else if (retVal.data.success == true) {
            angular.copy(retVal.data, $scope.newObj);
  					$uibModalInstance.close($scope.newObj);
            dialogService.alertDlg($scope.translationObj.changepassword.msgpasswordchanged);
  				} else {
  					$("#invalidLogin").fadeTo(2000, 500).slideUp(500, function(){$("#invalidLogin").hide();});
  				}
			   });
       }
		}
  };

	// In case of cancel, forget everything and return
  $scope.cancel = function () {
    $uibModalInstance.dismiss('cancel');
  };
}]);
