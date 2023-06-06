'use strict';

angular.module('cpa_admin.wspageview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/wspageview', {
    templateUrl: 'wspageview/wspageview.html',
    controller: 'wspageviewCtrl',
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
          return $q.reject({authenticated: false, newLocation: "/wspageview"});
        }
      }
    }
  });
}])

.controller('wspageviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

  $scope.progName = "wspageview";
  $scope.currentWspage = null;
  $scope.selectedWspage = null;
  $scope.newWspage = null;
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

  $scope.getAllWspage = function () {
    $scope.promise = $http({
      method: 'post',
      url: './wspageview/managewspage.php',
      data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllPages' }),
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

  $scope.getWspageDetails = function (page) {
    $scope.promise = $http({
      method: 'post',
      url: './wspageview/managewspage.php',
      data: $.param({'name' : page.name, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getPageDetails' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
      if (data.success && !angular.isUndefined(data.data)) {
        $scope.currentWspage = data.data[0];
      } else {
        dialogService.displayFailure(data);
      }
    }).
    error(function(data, status, headers, config) {
      dialogService.displayFailure(data);
    });
  };

  $scope.setCurrentInternal = function (page, index) {
    if (page != null) {
      $scope.selectedLeftObj = page;
      $scope.selectedWspage = page;
      $scope.getWspageDetails(page);
      $scope.setPristine();
    } else {
      $scope.selectedWspage = null;
      $scope.currentWspage = null;
      $scope.selectedLeftObj = null;
    }
  }

  $scope.setCurrent = function (page, index) {
    if ($scope.isDirty()) {
      dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, page, index);
    } else {
      $scope.setCurrentInternal(page, index);
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
    if ($scope.currentWspage == null || !$scope.isDirty()) {
      dialogService.alertDlg("Nothing to save!", null);
    } else {
      if ($scope.validateAllForms() == false) return;
      $scope.promise = $http({
        method: 'post',
        url: './wspageview/managewspage.php',
        data: $.param({'page' : $scope.currentWspage, 'type' : 'updateEntirePage' }),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
      }).
      success(function(data, status, headers, config) {
        if (data.success) {
          // Select this page to reset everything
          $scope.setCurrentInternal($scope.selectedWspage, null);
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

  // This is the function that creates the modal to create/edit ice
  $scope.editSection = function(newSection) {
    $scope.newSection = {};
    // Keep a pointer to the current section
    $scope.currentSection = newSection;
    // Copy in another object
    angular.copy(newSection, $scope.newSection);
    $uibModal.open({
      animation: false,
      templateUrl: 'wspageview/newsection.template.html',
      controller: 'childeditor.controller',
      scope: $scope,
      size: 'lg',
      backdrop: 'static',
      resolve: {
        newObj: function () {
          return $scope.newSection;
        }
      }
    })
    .result.then(function(newSection) {
      // User clicked OK and everything was valid.
      angular.copy(newSection, $scope.currentSection);
      if ($scope.currentSection.pagename != null) {
        $scope.currentSection.status = 'Modified';
      } else {
        $scope.currentSection.status = 'New';
        if ($scope.currentWspage.sections == null) $scope.currentWspage.sections = [];
        if ($scope.currentWspage.sections.indexOf($scope.currentSection) == -1) {
          $scope.currentWspage.sections.push($scope.currentSection);
        }
      }
      $scope.setDirty();
    }, function() {
      // User clicked CANCEL.
      // alert('canceled');
    });
  };

  $scope.refreshAll = function() {
    $scope.getAllWspage();
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
    translationService.getTranslation($scope, 'wspageview', authenticationService.getCurrentLanguage());
    $rootScope.repositionLeftColumn();
  }

  $scope.refreshAll();
}]);
