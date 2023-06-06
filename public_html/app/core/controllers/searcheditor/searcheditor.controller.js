// This module drives a standard search editor
// Author: Eric Lamoureux
// Created on: 2016-09-16
// Info needed to do the search
//			template for the left pane list
//			template for the right pane details
//		php file for the SQL command
//			name of php function to read list of objects
//			name of php function to get details of object
//		language
angular.module('core').controller('searcheditor.controller', function ($scope, $uibModalInstance, dialogService, $http, searchParams) {

	// Get the newObj from main controller, modify it and return it in case of OK
  $scope.selectedObject = null;
  $scope.searchParams = searchParams;
  $scope.ok = function () {
		if ($scope.selectedObject == null) {
			// Show then hide the error message
			$("#editObjFieldMandatory").fadeTo(2000, 500).slideUp(500, function(){$("#editObjFieldMandatory").hide();});
		} else {
			$uibModalInstance.close($scope.currentObject);
		}
  };

	// In case of cancel, forget everything and return
	// param: if defined, return param as the cancel value, if not, return 'cancel'
  $scope.cancel = function (param) {
  	if (param) $uibModalInstance.dismiss(param);
    $uibModalInstance.dismiss('cancel');
  };

	$scope.getAllObjects = function () {
    if (searchParams && !searchParams.type) {
      searchParams.type = 'getAllObjects';
    }
		$http({
	      method: 'post',
	      url: searchParams.phpfilename,
	      // data: $.param({ 'type' : 'getAllObjects' }),
	      data: $.param(searchParams),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if(data.success && !angular.isUndefined(data.data) ){
    		$scope.leftobjs = data.data;
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	$scope.getObjectDetails = function (leftobj) {
		$scope.promise = $http({
	      method: 'post',
	      url: searchParams.phpfilename,
	      data: $.param({'id' : leftobj.id, 'language' : searchParams.language, 'type' : 'getObjectDetails'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if(data.success && !angular.isUndefined(data.data) ){
    		$scope.currentObject = data.data[0];
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
    });
	};

	$scope.setCurrent = function (leftobj, index) {
		if (leftobj != null) {
			$scope.selectedObject = leftobj;
			$scope.selectedObjectIndex = index;
			$scope.getObjectDetails(leftobj);
		} else {
			$scope.selectedObject = null;
			$scope.selectedObjectIndex = null;
			$scope.currentObject = null;
		}
	}

	$scope.getAllObjects();
});
