'use strict';

angular.module('cpa_admin.loginview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/loginview', {
    templateUrl: 'loginview/loginview.html',
    controller: 'loginviewCtrl',
    resolve: {
      auth: function ($q, authenticationService, $location) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          $location.path("welcomeview");
        //   return $q.when(userInfo);
        // } else {
        //   authenticationService.login().then(function(retVal) {
        //     return;
        // 	});
        }
      }
    }

  });
}])

.controller('loginviewCtrl', ['$scope', '$rootScope', '$location', '$q', 'authenticationService', 'translationService', 'auth', 'dialogService', function($scope, $rootScope, $location, $q, authenticationService, translationService, auth, dialogService) {
  // $rootScope.applicationName = "EC";
  $rootScope.selectedLanguage = 'fr-ca'
  $scope.newLogin = null;
  $scope.displayimagefilename = '/privateimages/welcomeviewmainimage.jpg?decache=' + Math.random();

  $scope.login = function() {
    $scope.newLogin = {};
    // Validates the login info with the one in the database.
    authenticationService.validateLoginInfo($scope, $scope.login.userid, $scope.login.password)
    .then(function(retVal) {
      if (retVal.data.success === undefined) {
        dialogService.displayFailure(retVal.data);
      } else if (retVal.data.success == true) {
        angular.copy(retVal.data.user, $scope.newLogin);
        if ($scope.newLogin.passwordexpired == 1) {
          var deferred = $q.defer();
          authenticationService.changeExpiredPassword($scope, deferred, $scope.newLogin).then(function(){$location.path("welcomeview");});
        } else {
          that.setLoginInfo($scope.newLogin);
          if ($rootScope.newLocation) {
            $location.path($rootScope.newLocation);
            $rootScope.newLocation = null;
          } else {
            $location.path("welcomeview");
          }
        }
      } else {
        dialogService.displayFailure($scope.translationObj.main.msgerrinvalidlogin);
      }
    });
  };

  $scope.manageForgottenPassword = function() {
    authenticationService.manageForgottenPassword($scope, authenticationService.getCurrentLanguage());
  };

  $rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

  $scope.refreshAll = function() {
    translationService.getTranslation($scope, 'loginview', authenticationService.getCurrentLanguage());
	};

  $scope.refreshAll();
}]);
