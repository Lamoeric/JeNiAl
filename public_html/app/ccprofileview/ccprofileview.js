'use strict';

angular.module('cpa_admin.ccprofileview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/ccprofileview', {
    templateUrl: 'ccprofileview/ccprofileview.html',
    controller: 'ccprofileviewCtrl',
    resolve: {
      auth: function ($q, authenticationService, $location) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          return $q.when(userInfo);
        } else {
          $location.path("ccloginview");
        }
      }
    }
  });
}])

.controller('ccprofileviewCtrl', ['$scope', '$rootScope', '$q', '$http', 'authenticationService', 'translationService', 'auth', 'dialogService', 'anycodesService', function($scope, $rootScope, $q, $http, authenticationService, translationService, auth, dialogService, anycodesService) {
  $rootScope.applicationName = "EC";

  $scope.getProfileDetails = function () {
		$scope.promise = $http({
      method: 'post',
      url: './ccprofileview/ccprofileview.php',
      data: $.param({'userid' : $rootScope.userInfo.userid, 'type' : 'getProfileDetails' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success && !angular.isUndefined(data.data)) {
    		$scope.currentUser = data.data;
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
    });
	};

  $scope.saveProfileDetails = function () {
    $scope.globalErrorMessage = [];
    $scope.globalSuccessMessage = [];
    if ($scope.detailsForm.$valid) {
  		$scope.promise = $http({
        method: 'post',
        url: './ccprofileview/ccprofileview.php',
        data: $.param({'currentUser' : $scope.currentUser, 'type' : 'saveProfileDetails'}),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
      }).
      success(function(data, status, headers, config) {
      	if (data.success) {
          $scope.globalSuccessMessage.push($scope.translationObj.main.formsuccessmessage);
          $("#globalSuccessMessage").fadeTo(2000, 500).slideUp(500, function(){$("#globalSuccessMessage").hide();});
          // We need to update login info in case the prefered language or user id has changed
          // TODO : This is where we're keeping the user from changing it's prefered language
          authenticationService.updateLoginInfo($scope.currentUser.contact.newuserid, 'fr-ca'/*$scope.currentUser.contact.preferedlanguage*/);
      	} else {
          if (data.errno == 96) {
            $scope.globalErrorMessage.push($scope.translationObj.main.formerrormsguseridalreadyinuse);
            $("#globalErrorMessage").fadeTo(2000, 500).slideUp(500, function(){$("#globalErrorMessage").hide();});
          } else {
            dialogService.displayFailure(data);
          }
      	}
      }).
      error(function(data, status, headers, config) {
  			dialogService.displayFailure(data);
      });
    } else {
      $scope.globalErrorMessage.push($scope.translationObj.main.formerrormessage);
      $("#globalErrorMessage").fadeTo(2000, 500).slideUp(500, function(){$("#globalErrorMessage").hide();});
    }
	};

  $rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

  $scope.refreshAll = function() {
    $scope.getProfileDetails();
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'preferedlanguages', 'text', 'preferedlanguages');
    translationService.getTranslation($scope, 'ccprofileview', authenticationService.getCurrentLanguage());
  }

  $scope.refreshAll();
}]);
