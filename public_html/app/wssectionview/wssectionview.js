'use strict';

angular.module('cpa_admin.wssectionview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/wssectionview', {
    templateUrl: 'wssectionview/wssectionview.html',
    controller: 'wssectionviewCtrl',
    resolve: {
      auth: function ($q, authenticationService) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          if (userInfo.privileges.admin_access==true) {
            return $q.when(userInfo);
          } else {
            return $q.reject({authenticated: true, validRights: false, newLocation:null});
          }
        } else {
          return $q.reject({authenticated: false, newLocation: "/wssectionview"});
        }
      }
    }
  });
}])

.controller('wssectionviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

  $scope.progName = "wssectionview";
  $scope.currentWssection = null;
  $scope.selectedWssection = null;
  $scope.newWssection = null;
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

  $scope.getAllWssection = function () {
    $scope.promise = $http({
      method: 'post',
      url: './wssectionview/managewssection.php',
      data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllSections' }),
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

  $scope.getWssectionDetails = function (section) {
    $scope.promise = $http({
      method: 'post',
      url: './wssectionview/managewssection.php',
      data: $.param({'name' : section.name, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getSectionDetails' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
      if (data.success && !angular.isUndefined(data.data)) {
        $scope.currentWssection = data.data[0];
        $scope.currentWssection.imageinfo = data.imageinfo;
        $scope.currentWssection.displayimagefilename = $scope.currentWssection.imagefilename + '?decache=' + Math.random();
      } else {
        dialogService.displayFailure(data);
      }
    }).
    error(function(data, status, headers, config) {
      dialogService.displayFailure(data);
    });
  };

  $scope.setCurrentInternal = function (section, index) {
    if (section != null) {
      $scope.selectedLeftObj = section;
      $scope.selectedWssection = section;
      $scope.getWssectionDetails(section);
      $scope.setPristine();
    } else {
      $scope.selectedWssection = null;
      $scope.currentWssection = null;
      $scope.selectedLeftObj = null;
    }
  }

  $scope.setCurrent = function (section, index) {
    if ($scope.isDirty()) {
      dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, section, index);
    } else {
      $scope.setCurrentInternal(section, index);
    }
  };

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
    if ($scope.currentWssection == null || !$scope.isDirty()) {
      dialogService.alertDlg("Nothing to save!", null);
    } else {
      if ($scope.validateAllForms() == false) return;
      $scope.promise = $http({
        method: 'post',
        url: './wssectionview/managewssection.php',
        data: $.param({'section' : $scope.currentWssection, 'type' : 'updateEntireSection' }),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
      }).
      success(function(data, status, headers, config) {
        if (data.success) {
          // Select this section to reset everything
          $scope.setCurrentInternal($scope.selectedWssection, null);
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

  // This is the function that displays the upload error messages
  $scope.displayUploadError = function(errFile) {
    // dialogService.alertDlg($scope.translationObj.details.msgerrinvalidfile);
    if (errFile.$error == 'maxSize') {
      dialogService.alertDlg($scope.translationObj.details.msgerrinvalidfilesize + errFile.$errorParam);
    } else if (errFile.$error == 'maxWidth') {
      dialogService.alertDlg($scope.translationObj.details.msgerrinvalidmaxwidth + errFile.$errorParam);
    } else if (errFile.$error == 'maxHeight') {
      dialogService.alertDlg($scope.translationObj.details.msgerrinvalidmaxheight + errFile.$errorParam);
    }
  }

  // This is the function that uploads the image for the current event
  $scope.uploadMainImage = function(file, errFiles) {
    $scope.f = file;
    if (errFiles && errFiles[0]) {
      $scope.displayUploadError(errFiles[0]);
    }
    if (file) {
      if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
        dialogService.alertDlg('only jpg files are allowed.');
        return;
      }
      file.upload = Upload.upload({
        url: './wssectionview/uploadmainimage.php',
        method: 'POST',
        file: file,
        data: {
          'mainobj': $scope.currentWssection
        }
      });
      file.upload.then(function (data) {
        $timeout(function () {
          if (data.data.success) {
            dialogService.alertDlg($scope.translationObj.details.msguploadcompleted);
            // Select this event to reset everything
            $scope.setCurrentInternal($scope.selectedWssection, null);
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

  // This is the function that creates the modal to create/edit ice
  $scope.editParagraph = function(newParagraph) {
    $scope.newParagraph = {};
    // Keep a pointer to the current paragraph
    $scope.currentParagraph = newParagraph;
    // Copy in another object
    angular.copy(newParagraph, $scope.newParagraph);
    $uibModal.open({
      animation: false,
      templateUrl: 'wssectionview/newparagraph.template.html',
      controller: 'childeditor.controller',
      scope: $scope,
      size: 'lg',
      backdrop: 'static',
      resolve: {
        newObj: function () {
          return $scope.newParagraph;
        }
      }
    })
    .result.then(function(newParagraph) {
      // User clicked OK and everything was valid.
      angular.copy(newParagraph, $scope.currentParagraph);
      if ($scope.currentParagraph.id != null) {
        $scope.currentParagraph.status = 'Modified';
      } else {
        $scope.currentParagraph.status = 'New';
        if ($scope.currentWssection.paragraphs == null) $scope.currentWssection.paragraphs = [];
        if ($scope.currentWssection.paragraphs.indexOf($scope.currentParagraph) == -1) {
          $scope.currentWssection.paragraphs.push($scope.currentParagraph);
        }
      }
      $scope.setDirty();
    }, function() {
      // User clicked CANCEL.
      // alert('canceled');
    });
  };

  $scope.refreshAll = function() {
    $scope.getAllWssection();
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
    translationService.getTranslation($scope, 'wssectionview', authenticationService.getCurrentLanguage());
    $rootScope.repositionLeftColumn();
  }

  $scope.refreshAll();
}]);
