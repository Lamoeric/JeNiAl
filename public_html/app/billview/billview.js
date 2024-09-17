'use strict';

angular.module('cpa_admin.billview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/billview', {
		templateUrl: 'billview/billview.html',
		controller: 'billviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.admin_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/billview"});
				}
			}
		}
	})
	.when('/billview/:billid', {
		templateUrl: 'billview/billview.html',
		controller: 'billviewCtrl',
		resolve: {
			auth: function ($q, authenticationService, $location) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo && userInfo.privileges.admin_access==true) {
					return $q.when(userInfo);
				} else {
					return $q.reject({ authenticated: false });
				}
			}
		}
	});
;
}])

.controller('billviewCtrl', ['$rootScope','$scope', '$http', '$uibModal', '$route', '$location', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', 'billingService', function($rootScope, $scope, $http, $uibModal, $route, $location, anycodesService, dialogService, listsService, authenticationService, translationService, billingService) {

	$scope.progName = "billView";
	$scope.leftpanetemplatefullpath = "./billview/bill.template.html";
	$scope.currentBill = null;
	$scope.currentLanguage = authenticationService.getCurrentLanguage();
	$scope.selectedBill = null;
	$scope.newBill = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.globalErrorMessage = [];
	$scope.globalWarningMessage = [];
	$scope.billid = $route.current.params.billid;
	$scope.newFilter = {};
	$scope.newFilter.filterApplied = false;
	$scope.newFilter.registration = 'REGISTERED';
	$scope.newFilter.onlyopenedbills = '1';

	// For delay display
	$scope.delay = 0;
	$scope.minDuration = 0;
	$scope.message = 'Please Wait...';
	$scope.backdrop = true;
	$scope.promise = null;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function() {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllBills = function (newFilter) {
		if (newFilter) {
			$scope.newFilter.filterApplied = true;
		} else {
			$scope.newFilter.filterApplied = false;
		}
		$http({
				method: 'post',
				url: './billview/billview.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'filter' : newFilter, 'type' : 'getAllBills' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.leftobjs = data.data;
				} else {
					$scope.leftobjs = [];
				}
			} else {
				if (!data.success) {
					dialogService.displayFailure(data);
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.getBillDetails = function (billid) {
		$scope.promise = $http({
			method: 'post',
			url: './billview/billview.php',
			data: $.param({'id' : billid, 'language' : authenticationService.getCurrentLanguage(),'type' : 'getBillDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentBill = data.data[0];
				$scope.currentBill.contacts = data.contacts.data;
				billingService.calculateBillAmounts($scope.currentBill);

				/* Let's create the list of email for the "send email template" directive */
				$scope.currentBill.contactsforemail = [];
				for (var x = 0; $scope.currentBill.contacts && x < $scope.currentBill.contacts.length; x++) {
				 	$scope.currentBill.contactsforemail.push({'firstname':$scope.currentBill.contacts[x].firstname, 'lastname':$scope.currentBill.contacts[x].lastname,'email':$scope.currentBill.contacts[x].email});
				}
			} else {
				dialogService.displayFailure(data);
				$scope.currentBill = null;
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (bill, index) {
		if (bill != null) {
			$scope.selectedLeftObj = bill;
			$scope.selectedBill = bill;
			$scope.billid = null;
			$scope.getBillDetails(bill.id);
			$scope.setPristine();
		} else {
			$scope.selectedBill = null;
			$scope.currentBill = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (bill, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, bill, index);
		} else {
			$scope.setCurrentInternal(bill, index);
			// if ($scope.billid) $scope.getBillDetails($scope.billid);
			// $location.path('billview/');

		}
	};

	// $scope.deleteFromDB = function(confirmed) {
	// 	if ($scope.currentBill != null && !confirmed) {
	// 		dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
	// 	} else {
	// 		$http({
	// 			method: 'post',
	// 			url: './billview/billview.php',
	// 			data: $.param({'bill' : $scope.currentBill, 'type' : 'delete_bill' }),
	// 			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	// 		}).
	// 		success(function(data, status, headers, config) {
	// 			if (data.success) {
	// 				$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedBill),1);
	// 				$scope.setCurrentInternal(null);
	// 				return true;
	// 			} else {
	// 				dialogService.displayFailure(data);
	// 				return false;
	// 			}
	// 		}).
	// 		error(function(data, status, headers, config) {
	// 			dialogService.displayFailure(data);
	// 			return false;
	// 		});
	// 	}
	// }

	$scope.validateAllForms = function() {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}
		// if ($scope.currentMember.healthcareno == "") {
		// 	$scope.globalWarningMessage.push($scope.translationObj.main.msgerrallmandatory);
		// }

		if ($scope.globalErrorMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function(){$("#mainglobalerrormessage").hide();});
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function(){$("#mainglobalwarningmessage").hide();});
		}
		return retVal;
	}

	// $scope.saveToDB = function() {
	// 	if ($scope.currentBill == null || !$scope.isDirty()) {
	// 		dialogService.alertDlg("Nothing to save!", null);
	// 	} else {
	// 		if ($scope.validateAllForms() == false) return;
	// 		$http({
	// 			method: 'post',
	// 			url: './billview/billview.php',
	// 			data: $.param({'bill' : $scope.currentBill, 'type' : 'updateEntireBill' }),
	// 			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	// 		}).
	// 		success(function(data, status, headers, config) {
	// 			if (data.success) {
	// 				// Select this bill to reset everything
	// 				$scope.setCurrentInternal($scope.selectedBill, null);
	// 				return true;
	// 			} else {
	// 				dialogService.displayFailure(data);
	// 				return false;
	// 			}
	// 		}).
	// 		error(function(data, status, headers, config) {
	// 			dialogService.displayFailure(data);
	// 			return false;
	// 		});
	// 	}
	// };

	// $scope.addBillToDB = function() {
	// 	$http({
	// 		method: 'post',
	// 		url: './billview/billview.php',
	// 		data: $.param({'bill' : $scope.newBill, 'type' : 'insert_bill' }),
	// 		headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	// 	}).
	// 	success(function(data, status, headers, config) {
	// 		if (data.success) {
	// 			var newBill = {id:data.id, name:$scope.newBill.name};
	// 			$scope.leftobjs.push(newBill);
	// 			// We could sort the list....
	// 			$scope.setCurrentInternal(newBill);
	// 			return true;
	// 		} else {
	// 			dialogService.displayFailure(data);
	// 			return false;
	// 		}
	// 	}).
	// 	error(function(data, status, headers, config) {
	// 		dialogService.displayFailure(data);
	// 		return false;
	// 	});
	// };

	$scope.mainFilter = function() {
		// Send the newFilter to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'billview/filter.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newFilter;
					}
				}
		}).result.then(function(newFilter) {
				// User clicked OK
				if (newFilter.firstname || newFilter.lastname || newFilter.registration || newFilter.billpaid || newFilter.onlyopenedbills == '1') {
					$scope.newFilter = newFilter;
					$scope.getAllBills(newFilter);
				} else {
					dialogService.alertDlg($scope.translationObj.main.msgnofilter, null);
					$scope.newFilter = {};
					$scope.getAllBills(null);
				}
		}, function(dismiss) {
			if (dismiss == true) {
				$scope.getAllBills(null);
			}
			// User clicked CANCEL.
			// alert('canceled');
		});
	}

	// This is the function that creates the modal to create new bill
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newBill = {};
			// Send the newBill to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'billview/newbill.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newBill;
						}
					}
			})
			.result.then(function(newBill) {
				// User clicked OK and everything was valid.
				$scope.newBill = newBill;
				if ($scope.addBillToDB() == true) {
				}
			}, function() {
			// User clicked CANCEL.
	        // alert('canceled');
			});
		}
	};

	/**
	 * This function is called by the sendTemplateEmail directive when all emails are sent
	 */
	$scope.emailTemplateSent = function () {
		dialogService.alertDlg($scope.translationObj.main.msgemailsent);
	}

	$rootScope.$on("billing.reload", function (event, current, previous, eventObj) {
		$scope.setCurrentInternal($scope.selectedBill, null);
	});

	$rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

	$scope.refreshAll = function() {
		$scope.getAllBills($scope.newFilter);
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'registrationfilters',	'sequence', 'registrationfilters');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'billpaidfilters',	'sequence', 'billpaidfilters');
		translationService.getTranslation($scope, 'billview', authenticationService.getCurrentLanguage());
		if ($scope.billid) $scope.getBillDetails($scope.billid);
		// $location.path('billview/');
	}

	$scope.refreshAll();
}]);
