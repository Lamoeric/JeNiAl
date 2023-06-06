'use strict';

angular.module('cpa_admin.ccregisterview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/ccregisterview', {
    templateUrl: 'ccregisterview/ccregisterview.html',
    controller: 'ccregisterviewCtrl',
    resolve: {
      auth: function ($q, authenticationService, $location) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          $location.path("ccwelcomeview");
          // return $q.when(userInfo);
        } else {
          // $location.path("ccloginview");
        }
      }
    }
  });
}])

.controller('ccregisterviewCtrl', ['$scope', '$rootScope', '$http', '$location', 'authenticationService', 'translationService', 'dialogService', 'auth', function($scope, $rootScope, $http, $location, authenticationService, translationService, dialogService, auth) {
  $rootScope.applicationName = "EC";
  $scope.emailErrorMessage = [];
  $scope.futurUserInfo = null;

  $scope.getFuturUserInfo = function () {
  		$http({
  	      method: 'post',
  	      url: './ccregisterview/ccregisterview.php',
  	      data: $.param({'email' : $scope.futurUserInfo.email, 'type' : 'getFuturUserInfo' }),
  	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
      }).
      success(function(data, status, headers, config) {
        if (data.success && !angular.isUndefined(data.data)) {
      		$scope.futurUserInfo = data.data[0];
          $scope.futurUserInfo.emailValid = true;
      	} else {
      		dialogService.displayFailure(data);
      	}
      }).
      error(function(data, status, headers, config) {
      	dialogService.displayFailure(data);
      });
	};

  $scope.validateEmail = function () {
    $scope.globalErrorMessage = [];
    if (!$scope.detailsForm.email.$valid) {

    } else {
      // Validate that the email address is a valid email address
  		$http({
  	      method: 'post',
  	      url: './ccregisterview/ccregisterview.php',
  	      data: $.param({'email' : $scope.futurUserInfo.email, 'type' : 'validateEmail' }),
  	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
      }).
      success(function(data, status, headers, config) {
      	if (data.success) {
          $scope.futurUserInfo.emailValid = true;
          $scope.getFuturUserInfo();
      	} else {
      		if (!data.success) {
      			// dialogService.displayFailure(data);
            $scope.futurUserInfo.emailValid = false;
            if (data.alreadyused == true) {
              $scope.globalErrorMessage.push($scope.translationObj.main.msgerremailalreadyinuse);
            } else {
              $scope.globalErrorMessage.push($scope.translationObj.main.msgerremailinvalid);
              // dialogService.alertDlg($scope.translationObj.main.msgerremailinvalid);
            }
            // $scope.$apply();
            $("#emailErrorMessage").fadeTo(2000, 500).slideUp(500, function(){$("#emailErrorMessage").hide();});
      		}
      	}
      }).
      error(function(data, status, headers, config) {
      	dialogService.displayFailure(data);
      });
    }
	};

  $scope.createAccount = function() {
    $scope.globalErrorMessage = [];
    if ($scope.detailsForm.userid.$valid && $scope.detailsForm.email.$valid) {
      $scope.futurUserInfo.fullname = $scope.futurUserInfo.firstname + ' ' + $scope.futurUserInfo.lastname
      $scope.futurUserInfo.preferedlanguage = authenticationService.getCurrentLanguage();
      $scope.futurUserInfo.contactid = $scope.futurUserInfo.id;
      $http({
  	      method: 'post',
  	      url: './ccregisterview/ccregisterview.php',
  	      data: $.param({'newaccountinfo' : $scope.futurUserInfo, 'type' : 'createAccount' }),
  	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
      }).
      success(function(data, status, headers, config) {
      	if (data.success) {
          // email has been sent. We should return the user to the login screen
          authenticationService.setPasswordAndSendWelcomeEmail($scope.futurUserInfo.email, authenticationService.getCurrentLanguage());
          $location.path("ccloginview")
      	} else {
      		if (!data.success) {
            if (data.alreadyused) {
              $scope.globalErrorMessage.push($scope.translationObj.main.msgerruseridalreadyinuse);
              $("#useridErrorMessage").fadeTo(2000, 500).slideUp(500, function(){$("#useridErrorMessage").hide();});
            } else {
              dialogService.displayFailure(data);
            }
      		}
      	}
      }).
      error(function(data, status, headers, config) {
      	dialogService.displayFailure(data);
      });

    }
  }

  $scope.onChangeEmail = function() {
    $scope.futurUserInfo.emailValid = false;
  }

  translationService.getTranslation($scope, 'ccregisterview', authenticationService.getCurrentLanguage());

}]);
