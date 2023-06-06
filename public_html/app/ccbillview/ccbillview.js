'use strict';

angular.module('cpa_admin.ccbillview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/ccbillview', {
    templateUrl: 'ccbillview/ccbillview.html',
    controller: 'ccbillviewCtrl',
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

.controller('ccbillviewCtrl', ['$scope', '$rootScope', '$q', '$http', '$window', 'authenticationService', 'translationService', 'auth', 'dialogService', 'anycodesService', function($scope, $rootScope, $q, $http, $window, authenticationService, translationService, auth, dialogService, anycodesService) {
  $rootScope.applicationName = "EC";

  $scope.getBillDetails = function () {
		$scope.promise = $http({
      method: 'post',
      url: './ccbillview/ccbillview.php',
      data: $.param({'userid' : $rootScope.userInfo.userid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getBillDetails' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success && !angular.isUndefined(data.data)) {
    		$scope.currentBills = data.data;
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
    });
	};

  $scope.viewBill = function(billid) {
    $window.open('./reports/memberBill.php?language='+authenticationService.getCurrentLanguage()+'&billid='+billid);
  }

  $rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

  $scope.refreshAll = function() {
    $scope.getBillDetails();
    translationService.getTranslation($scope, 'ccbillview', authenticationService.getCurrentLanguage());
  }

  $scope.refreshAll();
}]);
