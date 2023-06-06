'use strict';

angular.module('cpa_admin.userview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/userview', {
    templateUrl: 'userview/userview.html',
    controller: 'userviewCtrl',
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
          return $q.reject({authenticated: false, newLocation: "/userview"});
        }
      }
    }
  });
}])

.controller('userviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "userView";
	$scope.currentUser = null;
	$scope.selectedUser = null;
	$scope.newUser = null;
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
				templateUrl: 'userview/newrole.template.html',
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
				// alert('canceled');
    });
	};

	$scope.getAllUsers = function () {
		$scope.promise = $http({
	      method: 'post',
	      url: './userview/user.php',
	      data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllUsers' }),
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
				if (!data.success) {
					dialogService.displayFailure(data);
				}
			}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	$scope.getUserDetails = function (user) {
		$scope.promise = $http({
      method: 'post',
      url: './userview/user.php',
      data: $.param({'id' : user.id, 'type' : 'getUserDetails' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success && !angular.isUndefined(data.data) ) {
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
			$scope.selectedLeftObj = user;
			$scope.setPristine();
		} else {
			$scope.selectedUser = null;
			$scope.currentUser = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (user, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, user, index);
		} else {
			$scope.setCurrentInternal(user, index);
		}
	};

	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentUser != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
	    $scope.promise = $http({
	      method: 'post',
	      url: './userview/user.php',
	      data: $.param({'user' : $scope.currentUser, 'type' : 'delete_user' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
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

	$scope.saveToDB = function() {
		if ($scope.currentUser == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
      if ($scope.validateAllForms() == false) return;
	    $scope.promise = $http({
	      method: 'post',
	      url: './userview/user.php',
	      data: $.param({'user' : $scope.currentUser, 'type' : 'updateEntireUser' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
					// Select this user to reset everything
					$scope.setCurrentInternal($scope.selectedUser, null);
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

	$scope.addUserToDB = function() {
    $scope.promise = $http({
      method: 'post',
      url: './userview/user.php',
      data: $.param({'user' : $scope.newUser, 'type' : 'insert_user' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
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
			$scope.newUser = {'active':1};
			// Send the newUser to the modal form
	    $uibModal.open({
					animation: false,
					templateUrl: 'userview/newuser.template.html',
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
				// alert('canceled');
	    });
	  }
	};

  /* TODO - clean this up - how to upload files */
  $scope.uploadFiles = function(file, errFiles) {
      $scope.f = file;
      $scope.errFile = errFiles && errFiles[0];
      if (file) {
        if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
          dialogService.alertDlg('only jpg files are allowed.');
          return;
        }
          // file.upload = Upload.upload({
          //     url: 'https://angular-file-upload-cors-srv.appspot.com/upload',
          //     data: {file: file}
          // });
          file.upload = Upload.upload({
              url: '../backend/changeMainImage.php',
              method: 'POST',
              file: file,
              // data: {
              //     'awesomeThings': $scope.awesomeThings,
              //     'targetPath' : '/media/'
              // }
          });
          file.upload.then(function (data) {
              $timeout(function () {
                if (data.data.success) {

                } else {
                  dialogService.displayFailure(data.data);
                }
              });
          }, function (data) {
              if (!data.success) {
                dialogService.displayFailure(data.data);
              }
          }, function (evt) {
              file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
          });
      }
  }

  $scope.resetAccount = function (confirmed) {
    if (!confirmed) {
      dialogService.confirmDlg($scope.translationObj.main.msgresetaccount, "YESNO", $scope.resetAccount, null, true, null);
    } else {
      return $http({
          method: 'post',
          url: './core/services/authentication/authentication.php',
          data: $.param({'emailorusercode' : $scope.currentUser.userid, 'type' : 'resetPasswordAndSendEmail'}),
          headers: {'Content-Type': 'application/x-www-form-urlencoded'}
      }).
      success(function(data, status, headers, config) {
        if (data.success == 1) {
          dialogService.alertDlg("Courriel envoyé. / email sent.")
          $scope.setCurrentInternal($scope.selectedUser);
        } else {
          dialogService.displayFailure(data);
        }
      }).
      error(function(data, status, headers, config) {
        dialogService.displayFailure(data);
      });
    }
	};

  $scope.emptycontact = function () {
    $scope.currentUser.contactid = "";
    $scope.currentUser.contactfullname = "";
    $scope.setDirty();
  }

	$scope.refreshAll = function() {
		$scope.getAllUsers();
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'preferedlanguages', 		'text', 'preferedlanguages');
		listsService.getAllRoles($scope, authenticationService.getCurrentLanguage());
    translationService.getTranslation($scope, 'userview', authenticationService.getCurrentLanguage());
    $rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
