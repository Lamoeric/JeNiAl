// Used to display one bill in the Customer Center (CC) now called MY SKATING SPACE
'use strict';

angular.module('cpa_admin.ccsinglebillview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/ccsinglebillview', {
		templateUrl: 'ccsinglebillview/ccsinglebillview.html',
		controller: 'ccsinglebillviewCtrl',
		resolve: {
			auth: function ($q, authenticationService, $location) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					// $location.path("ccbillview");
					return $q.when(userInfo);
				} else {
					$location.path("ccloginview");
				}
			}
		}
	})
	.when('/ccsinglebillview/:billid', {
		templateUrl: 'ccsinglebillview/ccsinglebillview.html',
		controller: 'ccsinglebillviewCtrl',
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

.controller('ccsinglebillviewCtrl', ['$scope', '$rootScope', '$q', '$http', '$window', '$route', '$location', 'authenticationService', 'translationService', 'auth', 'dialogService', 'anycodesService', 'billingService', function($scope, $rootScope, $q, $http, $window, $route, $location, authenticationService, translationService, auth, dialogService, anycodesService, billingService) {
	$rootScope.applicationName = "EC";
	$scope.billid = $route.current.params.billid;

	// For delay display
	$scope.delay = 0;
	$scope.minDuration = 0;
	$scope.message = 'Please Wait...';
	$scope.backdrop = true;
	$scope.promise = null;
	
	$scope.getBillDetails = function () {
		$scope.promise = $http({
			method: 'post',
			url: './ccsinglebillview/ccsinglebillview.php',
			data: $.param({'userid' : $rootScope.userInfo.userid, 'billid' : $scope.billid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getBill' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.currentBill = data.data[0];
					billingService.calculateBillAmounts($scope.currentBill);
				} else {
					$location.path("ccbillview");
				}
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// $scope.viewBill = function(billid) {
	//   $window.open('./reports/memberBill.php?language='+authenticationService.getCurrentLanguage()+'&billid='+billid);
	// }

	$rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

	$scope.refreshAll = function() {
		$scope.getBillDetails();
		translationService.getTranslation($scope, 'ccsinglebillview', authenticationService.getCurrentLanguage());
	}

	$scope.refreshAll();
}]);
