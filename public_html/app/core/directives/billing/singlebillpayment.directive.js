// This directive creates a button, that when clicked, creates a single bill payment dialog box
//	inputs :
//		billid : The billid to pay
//		isDisabled : True to disable button.
//		callback : callback function to call.
angular.module('core').directive( "singlebillpayment", ['$uibModal', '$http', 'listsService', 'anycodesService', 'authenticationService', 'translationService', 'dateFilter', 'dialogService', function($uibModal, $http, listsService, anycodesService, authenticationService, translationService, dateFilter, dialogService) {
	return {
		require: '^form',				// To set the $dirty flag after copying the created/modified test registration
		template:'<button class="btn btn-primary" ng-disabled="isDisabled"><span class="glyphicon glyphicon-usd"><span class="glyphicon glyphicon-ok" style="font-size:100%" ng-if="paidinfull==1"></span></button>',
		scope: {
			billid: '=',
			paidinfull: '=',
			isDisabled: '=isDisabled',
			callback: '&callback',
		},

		link: function( scope, element, attrs, formCtrl) {
			scope.internalControl = {};			// This object holds all the functions needed by the HTML template. HTML must use control.xxx() to call such function.
			element.bind( "click", function() {
				scope.formObj   = formCtrl;
        scope.language  = authenticationService.getCurrentLanguage();
				// For delay display
				// scope.delay = 0;
				// scope.minDuration = 1;
				// scope.message = 'Please Wait...';
				// scope.backdrop = true;
				// scope.promise = null;

        if (scope.billid) {
	        // Read bill from the database before editing
					scope.promise = $http({
						method: 'post',
						url: './core/services/billing/bills.php',
						data: $.param({'billid' : scope.billid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getBill'}),
						headers: {'Content-Type': 'application/x-www-form-urlencoded'}
					}).
					success(function(data, status, headers, config) {
						if (data.success) {
							scope.bill = data.data[0];
							editBill();
						} else {
							dialogService.displayFailure(data);
						}
					}).
					error(function(data, status, headers, config) {
						dialogService.displayFailure(data);
					});
        }
			});

			// Called when user clicks on the edit billing button.
			// This function opens a dialog box to pay a single bill
			function editBill() {
				translationService.getTranslation(scope, 'core/directives/billing', authenticationService.getCurrentLanguage());
				scope.newBilling = {};
				scope.newBilling.bill  							= scope.bill;
				scope.newBilling.billid  						= scope.billid;
				scope.newBilling.member           	= scope.bill.registrations[0].member;
				scope.newBilling.memberfullname   	= scope.newBilling.member.firstname + ' ' + scope.newBilling.member.lastname;;
        // scope.newBilling.productfullname  = scope.productfullname;
        scope.newBilling.transactionamount  = scope.bill.totalamount/1 + scope.bill.paidamount/1;
        scope.newBilling.receivedby       	= authenticationService.getUserInfo().userid;
        scope.newBilling.paidinfull  				= scope.bill.paidinfull;
        scope.newBilling.transactiontype  	= 'PAYMENT';
        scope.newBilling.paymentmethod 			= 'CASH';
				$uibModal.open({
						animation: false,
						templateUrl: './core/directives/billing/editsinglebillpayment.template.html',
						controller: 'childeditorex.controller',
						scope: scope,
						size: 'md',
						backdrop: 'static',
						resolve: {
							newObj: 	function() {return scope.newBilling;},		    // The object to edit
							control: 	function() {return scope.internalControl;},		// The control object containing all validation functions
							callback: function() {return null;}											// Callback function to overwrite the normal validation
						}
					})
					.result.then(function(newBilling) {
						// User clicked OK and everything was valid.
						scope.paidinfull = newBilling.paidinfull;
						if (scope.callback) scope.callback();
					}, function() {
							// User clicked CANCEL.
				});
			}

			//
			// Used by the editsinglebillpayment.template.html form
			scope.internalControl.payBill = function(newObj, confirmed) {
				if (newObj) {
					if (!confirmed) {
						dialogService.confirmDlg(scope.translationObj.singlebill.msgpaybill, "YESNO", scope.internalControl.payBill, null, newObj, true);
					} else {
						scope.newObj = newObj;
						scope.promise = $http({
							method: 'post',
							url: './core/services/billing/bills.php',
							data: $.param({'bill' : scope.newBilling, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'insertSingleTransaction'}),
							headers: {'Content-Type': 'application/x-www-form-urlencoded'}
						}).
						success(function(data, status, headers, config) {
							if (data.success) {
								// scope.newObj.billid 		= data.billid;
								scope.newObj.paidinfull = 1;
								return true;
							} else {
								dialogService.displayFailure(data);
								return false;
							}
						}).
						error(function(data, status, headers, config) {
							dialogService.displayFailure(data);
							return false;
						});
					}
				}
			}

			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(),'paymentmethods', 'sequence', 'paymentmethods');
			// anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'repaymentmethods', 'sequence', 'repaymentmethods');
		}
	}
}]);
