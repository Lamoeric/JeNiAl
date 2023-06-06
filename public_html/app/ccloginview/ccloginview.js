'use strict';

angular.module('cpa_admin.ccloginview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/ccloginview', {
    templateUrl: 'ccloginview/ccloginview.html',
    controller: 'ccloginviewCtrl',
    resolve: {
      auth: function ($q, authenticationService, $location) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          $location.path("ccwelcomeview");
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

.controller('ccloginviewCtrl', ['$scope', '$rootScope', '$location', '$q', '$http', 'authenticationService', 'translationService', 'auth', 'dialogService', function($scope, $rootScope, $location, $q, $http, authenticationService, translationService, auth, dialogService) {
  $rootScope.applicationName = "EC";
  // $scope.$apply();
  $rootScope.selectedLanguage = 'fr-ca'
  $scope.newLogin = null;

  $scope.register = function() {
    $location.path("ccregisterview");
  }

  $scope.preregister = function() {
    $location.path("ccpreregistrationview");
  }

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
          authenticationService.changeExpiredPassword($scope, deferred, $scope.newLogin).then(function(){$location.path("ccwelcomeview");});
        } else {
          that.setLoginInfo($scope.newLogin);
          $location.path("ccwelcomeview");
        }
      } else {
        dialogService.displayFailure($scope.translationObj.main.msgerrinvalidlogin);
        // $("#invalidLogin").fadeTo(2000, 500).slideUp(500, function(){$("#invalidLogin").hide();});
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
    translationService.getTranslation($scope, 'ccloginview', authenticationService.getCurrentLanguage());
		$scope.promise = $http({
      method: 'post',
      url: './ccloginview/ccloginview.php',
      data: $.param({'type' : 'readSessionConfig'}),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
    		$scope.preregistrationok = data.data[0].preregistrationok;
    	}
    }).
    error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
    });

	};

  $scope.refreshAll();
}]);
