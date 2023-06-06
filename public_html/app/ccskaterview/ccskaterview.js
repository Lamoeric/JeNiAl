'use strict';

angular.module('cpa_admin.ccskaterview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/ccskaterview', {
    templateUrl: 'ccskaterview/ccskaterview.html',
    controller: 'ccskaterviewCtrl',
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
  .when('/ccskaterview/:skaterid', {
    templateUrl: 'ccskaterview/ccskaterview.html',
    controller: 'ccskaterviewCtrl',
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

.controller('ccskaterviewCtrl', ['$scope', '$rootScope', '$q', '$http', '$window', '$location', '$route',  'authenticationService', 'translationService', 'auth', 'dialogService', 'anycodesService', function($scope, $rootScope, $q, $http, $window, $location, $route, authenticationService, translationService, auth, dialogService, anycodesService) {
  $rootScope.applicationName = "EC";
  $scope.skaterid = $route.current.params.skaterid;

  $scope.getSkaterDetails = function () {
		$scope.promise = $http({
      method: 'post',
        url: './ccskaterview/ccskaterview.php',
      data: $.param({'userid' : $rootScope.userInfo.userid, 'skaterid' : $scope.skaterid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getSkaterDetails' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success && !angular.isUndefined(data.data)) {
    		$scope.currentSkater = data.data[0];
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
    });
	};

  $scope.saveSkaterDetails = function () {
    $scope.globalErrorMessage = [];
    $scope.globalSuccessMessage = [];
    if ($scope.detailsForm.$valid) {
  		$scope.promise = $http({
        method: 'post',
        url: './ccskaterview/ccskaterview.php',
        data: $.param({'userid' : $rootScope.userInfo.userid, 'currentSkater' : $scope.currentSkater, 'type' : 'saveSkaterDetails'}),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
      }).
      success(function(data, status, headers, config) {
      	if (data.success) {
          $scope.globalSuccessMessage.push($scope.translationObj.main.formsuccessmessage);
          $("#globalSuccessMessage").fadeTo(2000, 500).slideUp(500, function(){$("#globalSuccessMessage").hide();});
      	} else {
      		dialogService.displayFailure(data);
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

  $scope.showCSReportCard = function() {
    $window.open('./reports/sessionCourseCSReportCard.php?language='+authenticationService.getCurrentLanguage()+'&memberid='+$scope.currentSkater.id);
  }

  $rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

  $scope.refreshAll = function() {
    $scope.getSkaterDetails();

    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 							'text', 'yesnos');
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'provinces', 					'text', 'provinces');
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'countries', 					'text', 'countries');
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'languages', 					'text', 'languages');
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'genders', 						'text', 'genders');
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'testresults', 				'sequence', 'testresults');

    translationService.getTranslation($scope, 'ccskaterview', authenticationService.getCurrentLanguage());
  }

  $scope.refreshAll();
}]);
