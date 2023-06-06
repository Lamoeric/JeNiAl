'use strict';

angular.module('cpa_admin.ccwelcomeview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/ccwelcomeview', {
    templateUrl: 'ccwelcomeview/ccwelcomeview.html',
    controller: 'ccwelcomeviewCtrl',
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
  })
}])

.controller('ccwelcomeviewCtrl', ['$scope', '$rootScope', '$q', '$http', '$window', '$location', '$route',  'authenticationService', 'translationService', 'auth', 'dialogService', 'anycodesService', function($scope, $rootScope, $q, $http, $window, $location, $route, authenticationService, translationService, auth, dialogService, anycodesService) {
  $rootScope.applicationName = "EC";

  $scope.changePassword = function() {
    var newLogin = {};
    angular.copy($rootScope.userInfo, newLogin);
    authenticationService.changePasswordOnDemand($scope, newLogin);
  }

  $scope.getWelcomeDetails = function () {
    if (!$rootScope.userInfo) return;
		$scope.promise = $http({
      method: 'post',
      url: './ccwelcomeview/ccwelcomeview.php',
      data: $.param({'userid' : $rootScope.userInfo.userid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getWelcomeDetails' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success && !angular.isUndefined(data.data)) {
    		$scope.currentDetails = data.data;
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
    });
	};

  $scope.viewSkater = function(skaterid) {
    $location.path('ccskaterview/' + skaterid);
    // $route.reload();
  }

  $scope.registerSkater = function(skaterid, sessionid) {
    $location.path('ccregistrationview/' + skaterid + '/' + sessionid);
  }

  $scope.registerSkaterShow = function(skaterid, showid) {
    $location.path('ccshowregistrationview/' + skaterid + '/' + showid);
  }

  $rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

  $scope.refreshAll = function() {
    $scope.getWelcomeDetails();
    translationService.getTranslation($scope, 'ccwelcomeview', authenticationService.getCurrentLanguage());
  }

  $scope.refreshAll();
}]);
