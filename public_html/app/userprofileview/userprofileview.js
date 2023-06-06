'use strict';

angular.module('cpa_admin.userprofileview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/userprofileview', {
    templateUrl: 'userprofileview/userprofileview.html',
    controller: 'userprofileviewCtrl',
    resolve: {
      auth: function ($q, authenticationService) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          return $q.when(userInfo);
        } else {
          return $q.reject({authenticated: false, newLocation: "/userprofileview"});
        }
      }
    }
  });
}])

.controller('userprofileviewCtrl', ['$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "userprofileView";
	// $scope.leftpanetemplatefullpath = "./user.template.html";
	$scope.currentUser = null;
	$scope.selectedUser = null;
	$scope.newUser = null;
	$scope.selectedIndex = null;
	$scope.isFormPristine = true;

	// For delay display
	$scope.delay = 0;
	$scope.minDuration = 0;
	$scope.message = 'Please Wait...';
	$scope.backdrop = true;
	$scope.promise = null;

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
		// $scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	// This is the function that creates the modal to create/edit role
  $scope.editRole = function(newRole) {
		$scope.newRole = {};
		if (newRole.id) {
			$scope.currentRole = newRole;
			// Send the newRole to the modal form
			for (var prop in newRole) {
				$scope.newRole[prop] = newRole[prop];
			}
		} else {
			$scope.currentRole = null;
		}
    $uibModal.open({
				animation: false,
				templateUrl: 'userprofileview/newrole.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
	        newObj: function () {
	          return $scope.newRole;
	        }
	      }
	    })
	    .result.then(function(newRole) {
    		// User clicked OK and everything was valid.
    		if ($scope.currentRole != null) {
					for (var prop in newRole) {
						$scope.currentRole[prop] = newRole[prop];
					}
					$scope.currentRole.status = 'Modified';
				} else {
					newRole.status = 'New';
					if ($scope.currentUser.roles == null)$scope.currentUser.roles = [];
					$scope.currentUser.roles.push(newRole);
				}
				$scope.setDirty();
	    }, function() {
	    		// User clicked CANCEL.
//	        alert('canceled');
    });
	};

	// $scope.getAllUsers = function () {
	// 	$http({
	//       method: 'post',
	//       url: './userprofileview/userprofile.php',
	//       data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllUsers' }),
	//       headers: {'Content-Type': 'application/x-www-form-urlencoded'}
  //   }).
  //   success(function(data, status, headers, config) {
  //   	if(data.success && !angular.isUndefined(data.data) ){
  //   		$scope.leftobjs = data.data;
  //   	} else {
  //   		if(!data.success){
  //   			dialogService.displayFailure(data);
  //   		}
  //   	}
  //   }).
  //   error(function(data, status, headers, config) {
  //   	dialogService.displayFailure(data);
  //   });
	// };

	$scope.getUserDetails = function (user) {
		$scope.promise = $http({
      method: 'post',
      url: './userprofileview/userprofile.php',
      data: $.param({'id' : user.id, 'type' : 'getUserDetails' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if(data.success && !angular.isUndefined(data.data) ){
    		$scope.currentUser = data.data[0];
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
    });
	};

	$scope.setCurrentInternal = function (user, index) {
		if (user != null) {
			$scope.selectedUser = user;
			$scope.getUserDetails(user);
			$scope.selectedIndex = index;
			$scope.setPristine();
		} else {
			$scope.selectedUser = null;
			$scope.currentUser = null;
			$scope.selectedIndex = null;
		}
	}

	$scope.setCurrent = function (user, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, user, index);
		} else {
			$scope.setCurrentInternal(user, index);
		}
	};

	$scope.deleteFromDB = function(confirmed){
		if ($scope.currentUser != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
	    $http({
	      method: 'post',
	      url: './userprofileview/userprofile.php',
	      data: $.param({'user' : $scope.currentUser, 'type' : 'delete_user' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if(data.success){
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedUser),1);
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
		if ($scope.currentUser == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
      if ($scope.validateAllForms() == false) return;
	    $http({
	      method: 'post',
	      url: './userprofileview/userprofile.php',
	      data: $.param({'user' : $scope.currentUser, 'type' : 'updateEntireUser' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if(data.success){
					// Select this user to reset everything
					$scope.setCurrentInternal($scope.selectedUser, $scope.selectedIndex);
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

	$scope.addUserToDB = function(){
    $http({
      method: 'post',
      url: './userprofileview/userprofile.php',
      data: $.param({'user' : $scope.newUser, 'type' : 'insert_user' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if(data.success){
    		var newUser = {id:data.id, code:$scope.newUser.code, type:$scope.newUser.type};
    		$scope.leftobjs.push(newUser);
    		// We could sort the list....
    		$scope.setCurrentInternal(newUser);
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

	// This is the function that creates the modal to create new user
  $scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newUser = {};
			// Send the newUser to the modal form
	    $uibModal.open({
					animation: false,
					templateUrl: 'userprofileview/newuser.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
		        newObj: function () {
		          return $scope.newUser;
		        }
		      }
	    })
	    .result.then(function(newUser) {
	    		// User clicked OK and everything was valid.
	    		$scope.newUser = newUser;
					if ($scope.addUserToDB() == true) {
					}
	    }, function() {
	    		// User clicked CANCEL.
//	        alert('canceled');
	    });
	  }
	};

  $scope.changePassword = function (confirmed) {
    translationService.getTranslation($scope, 'core/services/authentication', authenticationService.getCurrentLanguage());
    var changePassword = {};
    changePassword.passwordexpired = 0; 	// Show cancel button in changepassword dialog box
    changePassword.userid = $scope.currentUser.userid;
    $uibModal.open({
        animation: false,
        templateUrl: './core/services/authentication/changepassword.template.html',
        controller: 'changepassword.controller',
        scope: $scope,
        size: 'sm',
        backdrop: 'static',
        resolve: {
          newObj: function () {
            return changePassword;
          }
        }
    })
    .result.then(function(changePassword) {
      dialogService.alertDlg($scope.translationObj.main.msgpasswordchanged)
    }, function() {
      // User clicked CANCEL.
    });
	};

	$scope.refreshAll = function() {
    // $scope.getAllUsers();
    var userInfo = authenticationService.getUserInfo();
    $scope.setCurrentInternal({id:userInfo.id})
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'preferedlanguages', 		'text', 'preferedlanguages');
    listsService.getAllRoles($scope, authenticationService.getCurrentLanguage());
    translationService.getTranslation($scope, 'userprofileview', authenticationService.getCurrentLanguage());
	}

	$scope.refreshAll();
}]);
