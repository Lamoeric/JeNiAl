// This module drives a standard new/edit modal form.
// Get the newObj from main controller, modify it and return it in case of OK
// Author: Eric Lamoureux
// Created on: 2016-04-28

// newObj - represents the object we are editing in the editor.
// control - represents an object containing methods that are needed by the dialog box, like onChange methods, and such.
//           Caller must use, for example, control.onChange() to call function from HTML
// callback - if this method exists, it is used to validate the form instead of the standard validation.
//            Method receives 2 parameters, the form object and the object being edited
//            Return value must be null or the id of the DIV containing the error message to display.
//            Use this method when validations are more complicated than just "required" or not.
//            Example : 	$scope.validateNewRegistration = function(editObjForm, newRegistration){}

angular.module('core').controller('childeditorex.controller', function ($scope, $uibModalInstance, newObj, control, callback) {
  $scope.newObj = newObj;
  $scope.control = control;
  $scope.callback = callback;

  $uibModalInstance.rendered.then(function() {
    // alert('modal has rendered');
  });

  $uibModalInstance.opened.then(function() {
    // alert('modal has opened');
  });

  $uibModalInstance.closed.then(function() {
    // alert('modal has closed');
  });

  $uibModalInstance.result.then(function() {
    // alert('modal has closed and rejected');
  });

  // Validate and close
  $scope.ok = function () {
    // If call back exists, use this call back function for the validation.
    if ($scope.callback != null) {
      var retVal = $scope.callback($scope.editObjForm, $scope.newObj);
      if (!retVal) {
        $uibModalInstance.close($scope.newObj);
      } else {
        $(retVal).fadeTo(2000, 500).slideUp(500, function(){$(retVal).hide();});
      }
    } else {
      if ($scope.editObjForm.$invalid) {
        // Show then hide the error message
        $("#editObjFieldMandatory").fadeTo(2000, 500).slideUp(500, function(){$("#editObjFieldMandatory").hide();});
      } else {
        $uibModalInstance.close($scope.newObj);
      }
    }
  };

  // In case of cancel, forget everything and return
  $scope.cancel = function (param) {
    if (param) $uibModalInstance.dismiss(param);
    $uibModalInstance.dismiss('cancel');
  };
});
