// Billing service
angular.module('core').service('billingService', ['dialogService', '$uibModal', '$http', '$rootScope', 'translationService', 'authenticationService', function(dialogService, $uibModal, $http, $rootScope, translationService, authenticationService) {

	this.roundTo = function(n, digits) {
		if (digits === undefined) {
				digits = 0;
		}

		var multiplicator = Math.pow(10, digits);
		n = parseFloat((n * multiplicator).toFixed(11));
		return (Math.round(n) / multiplicator).toFixed(2);
	}

	// Recalculate the bill amounts (detailssubtotal, paymentsubtotal, realtotalamount)
	this.calculateBillAmounts = function(bill) {
		var registrationTmp;
		if (bill) {
			bill.detailssubtotal = 0;
			bill.paymentsubtotal = 0;
			bill.realtotalamount = 0;
			for (var i = 0; bill.transactions && i < bill.transactions.length; i++) {
				if (bill.transactions[i].transactiontype == 'PAYMENT' && bill.transactions[i].iscanceled == 0) {
					bill.paymentsubtotal += bill.transactions[i].transactionamount/1;
				} else if (bill.transactions[i].transactiontype != 'PAYMENT' && bill.transactions[i].iscanceled == 0) {
					bill.paymentsubtotal += bill.transactions[i].transactionamount*-1;
				}
//				bill.paymentsubtotal += (bill.transactions[i].transactiontype == 'PAYMENT' && bill.transactions[i].iscanceled == 0? bill.transactions[i].transactionamount/1 : bill.transactions[i].transactionamount*-1);
			}

			for (var y = 0; bill.registrations && y < bill.registrations.length; y++) {
				registrationTmp = bill.registrations[y];
				for (var i = 0; registrationTmp.courses && i < registrationTmp.courses.length; i++) {
					bill.detailssubtotal += registrationTmp.courses[i].amount/1;
				}
				for (var i = 0; registrationTmp.shownumbers && i < registrationTmp.shownumbers.length; i++) {
					bill.detailssubtotal += registrationTmp.shownumbers[i].amount/1;
				}
				for (var i = 0; registrationTmp.tests && i < registrationTmp.tests.length; i++) {
					bill.detailssubtotal += registrationTmp.tests[i].amount/1;
				}
				for (var i = 0; registrationTmp.charges && i < registrationTmp.charges.length; i++) {
					bill.detailssubtotal += registrationTmp.charges[i].amount/1;
				}
				for (var i = 0; registrationTmp.discounts && i < registrationTmp.discounts.length; i++) {
					bill.detailssubtotal += registrationTmp.discounts[i].amount*-1;
				}
			}
			bill.detailssubtotal = this.roundTo(bill.detailssubtotal, 2);
			bill.paymentsubtotal = this.roundTo(bill.paymentsubtotal, 2);
			bill.realtotalamount = bill.detailssubtotal/1 - bill.paymentsubtotal/1;
		}
	}

	this.getBillingNames = function($scope, memberid, language) {
		return $http({
				method: 'post',
				url: './core/services/billing/bills.php',
				data: $.param({'memberid' : memberid, 'language' : language, 'type' : 'getBillingNames'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success && !angular.isUndefined(data.data) ) {
					$scope.billingnames = data.data;
				} else {
					dialogService.displayFailure(data);
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
			});
	}

	/*
		Opens a modal form to select the name for the bill.
	*/
	this.selectBillingName = function ($scope, memberid, language) {
		translationService.getTranslation($scope, 'core/services/billing', authenticationService.getCurrentLanguage());
		// translationService.getTranslation($rootScope, 'core/services/billing', newLogin.preferedlanguage);
		//
		this.getBillingNames($scope, memberid, language);
		var selectBillingName = {};
		selectBillingName.memberid = memberid;
		selectBillingName.billingname = null;
		return $uibModal.open({
				animation: false,
				templateUrl: './core/services/billing/selectbillingname.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return selectBillingName;
					}
				}
			})
			.result.then(function(selectBillingName) {
				// User clicked OK and everything was valid.
				return selectBillingName;
			}, function() {
					// User clicked CANCEL.
//	        alert('canceled');
		});
	};

	this.selectExistingBill = function($scope, sessionid) {
		translationService.getTranslation($scope, 'core/services/billing', authenticationService.getCurrentLanguage());
		$scope.searchBills = null;
		// Send the searchBills to the modal form
		$scope.searchParams = {'phpfilename':'./core/services/billing/bills.php',
													'language':authenticationService.getCurrentLanguage(),
													'sessionid':sessionid};
		return $uibModal.open({
				animation: false,
				templateUrl: './core/services/billing/searchbills.template.html',
				controller: 'searcheditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					searchParams: function() {
						return $scope.searchParams;
					}
				}
			})
			.result.then(function(selectedBill) {
				// User clicked OK and everything was valid.
				return selectedBill;
			}, function() {
				// User clicked CANCEL.
			});
	}

	this.getBillingEmails = function($scope, billid, language) {
		return $http({
				method: 'post',
				url: './core/services/billing/bills.php',
				data: $.param({'billid' : billid, 'language' : language, 'type' : 'getBillingEmails'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success && !angular.isUndefined(data.data) ) {
					$scope.billingemails = data.data;
				} else {
					dialogService.displayFailure(data);
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
			});
	}

	/*
		Opens a modal form to select the email address for sending the bill.
	*/
	this.selectBillingEmails = function ($scope, billid, language) {
		translationService.getTranslation($scope, 'core/services/billing', authenticationService.getCurrentLanguage());
		this.getBillingEmails($scope, billid, language);
		var selectBillingEmail = {};
		selectBillingEmail.billid = billid;
		return $uibModal.open({
				animation: false,
				templateUrl: './core/services/billing/selectbillingemails.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return selectBillingEmail;
					}
				}
			})
			.result.then(function(selectBillingEmail) {
				// User clicked OK and everything was valid.
				return selectBillingEmail;
			}, function() {
				// User clicked CANCEL.
				return null;
		});
	};

	/*
		Creates the bill pdf file
	*/
	this.createBillPdfFile = function ($scope, billid, language) {
		return $http({
			method: 'post',
			url: './reports/memberBill.php',
			data: $.param({'billid' : billid, 'output': 'F', 'language' : language}),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(filename, status, headers, config) {
			if (filename && filename.indexOf('error') == -1) {
				// filename = filename.replace("C:\\wamp\\www\\", "http://localhost/");
				// return filename;
			} else {
				dialogService.displayFailure(filename);
				return null;
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return null;
		});
	}

	/*
		Sends bill PDF file by email
	*/
	this.sendBillByEmail = function (email, fullname, filename, language) {
		return $http({
			method: 'post',
			url: './core/directives/billing/sendbillbyemail.php',
			data: $.param({'email' : email, 'fullname' : fullname, 'filename' : filename, 'language' : language}),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				return;
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	}

	/*
		Split a registration from a bill, creating 2 new bills
		@author	Eric Lamoureux, 2018/09/14
	*/
	this.splitBill = function ($scope, currentbillid, currentregistrationid, language) {
		translationService.getTranslation($scope, 'core/services/billing', authenticationService.getCurrentLanguage());
		return $http({
			method: 'post',
			url: './core/services/billing/bills.php',
			data: $.param({'billid' : currentbillid, 'registrationid' : currentregistrationid, 'language' : language, 'type' : 'splitBill'}),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			$rootScope.$broadcast('billing.reload');
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	}

}]);
