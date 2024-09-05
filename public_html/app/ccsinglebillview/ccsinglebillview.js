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

.controller('ccsinglebillviewCtrl', ['$scope', '$rootScope', '$q', '$http', '$window', '$route', '$location', 'authenticationService', 'translationService', 'auth', 'dialogService', 'anycodesService', 'billingService', 'paypalService', function($scope, $rootScope, $q, $http, $window, $route, $location, authenticationService, translationService, auth, dialogService, anycodesService, billingService, paypalService) {
	$rootScope.applicationName = "EC";
	$scope.billid = $route.current.params.billid;
	$scope.token = $route.current.params.token;
	$scope.paymentId = $route.current.params.paymentId;
	$scope.payerId = $route.current.params.PayerID;

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
					$scope.currentBill.step = 1;
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

	$scope.paypalInitPurchase = function () {
		var purchase = {};

		// First, create the purchase object
		purchase = paypalService.createPurchaseData($scope.currentBill.id, $scope.currentBill.realtotalamount/1, window.location.href, $scope.currentBill.billingname, null, null, null, false);
		// Second, Init paypal purchase
		paypalService.initPurchase(purchase);

	}

	$rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

	/**
	 * This function handles the return of the purchase if completed without errors
	 * @param {*} data 
	 */
	$scope.purchaseCompleted = function(data) {
		if (data.success) {
			$scope.response = data.reponse;
			if (!$scope.currentBill) $scope.currentBill = {};
			$scope.currentBill.step = 2;
		} else {
			if (data && data.detail) {
				dialogService.displayFailure(data.detail?data.detail : data);
			}
		}
	}
	
	/**
	 * This function handles the return of the purchase if purchase failed
	 * @param {*} data 
	 */
	$scope.purchaseFailed = function(data) {
		dialogService.displayFailure(data.detail?data.detail : data);
		window.location = "#!/ccwelcomeview";
	}
	
	$scope.refreshAll = function() {
		translationService.getTranslation($scope, 'ccsinglebillview', authenticationService.getCurrentLanguage());
		if ($scope.token != null) {
			if ($scope.paymentId == null) {
				// Payment was cancelled
				if (!$scope.currentBill) $scope.currentBill = {};
				$scope.currentBill.step = 3;
			} else {
				// paymentId is defined, we need to complete the purchase
				paypalService.completePurchase($scope.payerId, $scope.paymentId, $scope.purchaseCompleted, $scope.purchaseFailed);
			}
		} else {
			$scope.getBillDetails();
		}
	}

	// This code injects the paypal API into the DOM.
	// TODO : check if this is really needed because we are using the php module
	if (window.paypalCheckoutReady != null) {
		$scope.showButton = true
	} else {
		var s = document.createElement('script')
		s.src = '//www.paypalobjects.com/api/checkout.js'
		document.body.appendChild(s)
		window.paypalCheckoutReady = function () {
			// return paypalService.loadPaypalButton()
		}
	}

	$scope.refreshAll();
}]);
