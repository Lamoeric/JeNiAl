'use strict';

angular.module('cpa_admin.privilegeview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/privilegeview', {
    templateUrl: 'privilegeview/privilegeview.html',
    controller: 'privilegeviewCtrl',
    resolve: {
      auth: function ($q, authenticationService) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          if (userInfo.privileges.security_access==true) {
            return $q.when(userInfo);
          } else {
            return $q.reject({authenticated: true, validRights: false, newLocation:null});
          }
        } else {
          return $q.reject({authenticated: false, newLocation: "/privilegeview"});
        }
      }
    }
  });
}])

.controller('privilegeviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "privilegeView";
	$scope.currentPrivilege = null;
	$scope.selectedPrivilege = null;
	$scope.newPrivilege = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function() {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllPrivileges = function () {
		$scope.promise = $http({
	      method: 'post',
	      url: './privilegeview/privilege.php',
	      data: $.param({'language' : 'en-ca'/*$scope.context.preferedlanguage*/, 'type' : 'getAllPrivileges' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
      if (data.success) {
        if (!angular.isUndefined(data.data)) {
          $scope.leftobjs = data.data;
        } else {
          $scope.leftobjs = [];
        }
        $rootScope.repositionLeftColumn();
    	} else {
    		if(!data.success){
    			dialogService.displayFailure(data);
    		}
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	$scope.getPrivilegeDetails = function (privilege) {
		$scope.promise = $http({
      method: 'post',
      url: './privilegeview/privilege.php',
      data: $.param({'id' : privilege.id, 'type' : 'getPrivilegeDetails' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if(data.success && !angular.isUndefined(data.data) ){
    		$scope.currentPrivilege = data.data[0];
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
    });
	};

	$scope.setCurrentInternal = function (privilege, index) {
		if (privilege != null) {
			$scope.selectedPrivilege = privilege;
			$scope.getPrivilegeDetails(privilege);
			$scope.selectedLeftObj = privilege;
			$scope.setPristine();
		} else {
			$scope.selectedPrivilege = null;
			$scope.currentPrivilege = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (privilege, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, privilege, index);
		} else {
			$scope.setCurrentInternal(privilege, index);
		}
	};

	$scope.deleteFromDB = function(confirmed){
		if ($scope.currentPrivilege != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
	    $scope.promise = $http({
	      method: 'post',
	      url: './privilegeview/privilege.php',
	      data: $.param({'privilege' : $scope.currentPrivilege, 'type' : 'delete_privilege' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if(data.success){
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedPrivilege),1);
					$scope.setCurrentInternal(null);
					return true;
	    	} else {
					dialogService.displayFailure(data);
					return false;
	    	}
	    }).
	    error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
	    });
	  }
	}

  $scope.validateAllForms = function() {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}
		// if ($scope.currentMember.healthcareno == "") {
		// 	$scope.globalWarningMessage.push($scope.translationObj.main.msgerrallmandatory);
		// }

		if ($scope.globalErrorMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function(){$("#mainglobalerrormessage").hide();});
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function(){$("#mainglobalwarningmessage").hide();});
		}
		return retVal;
	}

	$scope.saveToDB = function(){
		if ($scope.currentPrivilege == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
      if ($scope.validateAllForms() == false) return;
	    $scope.promise = $http({
	      method: 'post',
	      url: './privilegeview/privilege.php',
	      data: $.param({'privilege' : $scope.currentPrivilege, 'type' : 'updateEntirePrivilege' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if(data.success){
					// Select this privilege to reset everything
					$scope.setCurrentInternal($scope.selectedPrivilege, null);
					return true;
	    	} else {
					dialogService.displayFailure(data);
					return false;
	    	}
	    }).
	    error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
	    });
		}
	};

	$scope.addPrivilegeToDB = function(){
    $scope.promise = $http({
      method: 'post',
      url: './privilegeview/privilege.php',
      data: $.param({'privilege' : $scope.newPrivilege, 'type' : 'insert_privilege' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if(data.success){
    		var newPrivilege = {id:data.id, code:$scope.newPrivilege.code, type:$scope.newPrivilege.type};
    		$scope.leftobjs.push(newPrivilege);
    		// We could sort the list....
    		$scope.setCurrentInternal(newPrivilege);
				return true;
    	} else {
    		dialogService.displayFailure(data);
				return false;
    	}
    }).
    error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
    });
	};

	// This is the function that creates the modal to create new privilege
  $scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newPrivilege = {};
			// Send the newPrivilege to the modal form
	    $uibModal.open({
					animation: false,
					templateUrl: 'privilegeview/newprivilege.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
		        newObj: function () {
		          return $scope.newPrivilege;
		        }
		      }
	    })
	    .result.then(function(newPrivilege) {
	    		// User clicked OK and everything was valid.
	    		$scope.newPrivilege = newPrivilege;
					if ($scope.addPrivilegeToDB() == true) {
					}
	    }, function() {
        // User clicked CANCEL.
				// alert('canceled');
	    });
	  }
	};

	$scope.refreshAll = function() {
		$scope.getAllPrivileges();
    translationService.getTranslation($scope, 'privilegeview', authenticationService.getCurrentLanguage());
    $rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
