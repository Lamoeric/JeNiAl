// This directive creates a button, that when clicked, creates a single charge billing dialog box
// The created/modified bill id is directly copied into the object passed in parameter to the directive.
// TODO : we need a callback function to save this particular object in the database. We can't afford to loose the relation between the object and the bill id.
// NOT SURE *** The state of the form including this directive will be set to dirty if a test registration is created/modified.
//	inputs :
//		object : object into which to copy the bill id (property billid)
//    member : member object
//    charge : charge object
//		itemtype : string. Item type : TEST, CHARGE, DISCOUNT
//		itemid : num. For test, testid, for charge, chargeid. etc...
//		refid : num. Will be the registrationid or testssessionsid
//    productfullname : comment to add to the charge
//    isDisabled : true to disable
//		callback : callback function to call.
angular.module('core').directive( "singlechargebilling", ['$uibModal', '$http', 'listsService', 'anycodesService', 'authenticationService', 'translationService', 'dateFilter', 'dialogService', function($uibModal, $http, listsService, anycodesService, authenticationService, translationService, dateFilter, dialogService) {
	return {
		require: '^form',				// To set the $dirty flag after copying the created/modified test registration
		template:'<button class="btn btn-primary" ng-disabled="isDisabled"><span class="glyphicon glyphicon-usd"><span class="glyphicon glyphicon-ok" style="font-size:100%" ng-if="object.billid!=null"></span></button>',
		scope: {
			object: '=',
      member: '=',
      charge: '=',
      itemtype: '@',
      itemid: '=',
      refid: '=',
      productfullname: '@',
      // language: '=language',
			isDisabled: '=isDisabled',
			callback: '&callback',
		},

		link: function( scope, element, attrs, formCtrl) {
			scope.internalControl = {};			// This object holds all the functions needed by the HTML template. HTML must use control.xxx() to call such function.
			element.bind( "click", function() {
				scope.formObj   = formCtrl;
        scope.billid    = (scope.object.billid ? scope.object.billid : null);
        scope.language  = authenticationService.getCurrentLanguage();
				// For delay display
				scope.delay = 0;
				scope.minDuration = 1;
				scope.message = 'Please Wait...';
				scope.backdrop = true;
				scope.promise = null;

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
        } else {
					editBill();
        }
			});

			// Called when user clicks on the edit billing button.
			// This function opens a dialog box to edit a single charge billing
			function editBill() {
				translationService.getTranslation(scope, 'core/directives/billing', authenticationService.getCurrentLanguage());
				scope.newBilling = {};
				scope.newBilling.member           = scope.member;
				scope.newBilling.memberfullname   = scope.member.firstname + ' ' + scope.member.lastname;
        scope.newBilling.productfullname  = scope.productfullname;
        scope.newBilling.price            = scope.charge.amount;
        scope.newBilling.receivedby       = authenticationService.getUserInfo().userid;
        scope.newBilling.refid  					= scope.refid;
        scope.newBilling.itemid  					= scope.itemid;
        scope.newBilling.itemtype  				= scope.itemtype;
        scope.newBilling.billid  					= scope.billid;
        // if (scope.billid) {
        //   scope.newBilling.transactiontype  = 'REPAYMENT';
        //   scope.newBilling.paymentmethod = 'CASH';
        // } else {
          scope.newBilling.transactiontype  = 'PAYMENT';
          scope.newBilling.paymentmethod = 'CASH';
        // }
				$uibModal.open({
						animation: false,
						templateUrl: './core/directives/billing/editsinglechargebilling.template.html',
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
						scope.object.billid = newBilling.billid;
						// User clicked OK and everything was valid.
						// angular.copy(newBilling, scope.currentBilling);
						// If already saved in DB, put status to Modified, else to New
						// if (scope.currentBilling.billid != null) {
						// 	scope.currentBilling.status = 'Modified';
						// } else {
						// 	scope.currentBilling.status = 'New';
              // TODO : need to insert in DB and copy billid in object
              // scope.object.billid = scope.currentBilling.billid;

						// }
						// if (scope.callback) scope.callback();
						// scope.formObj.$dirty = true;
					}, function() {
							// User clicked CANCEL.
				});
			}

			//
			// Used by the editsinglechargebilling.template.html form
			scope.internalControl.createBill = function(newObj, confirmed) {
				if (newObj) {
					if (!confirmed) {
						dialogService.confirmDlg(scope.translationObj.singlebill.msgcreatebill, "YESNO", scope.internalControl.createBill, null, newObj, true);
					} else {
						scope.newObj = newObj;
						scope.promise = $http({
							method: 'post',
							url: './core/services/billing/bills.php',
							data: $.param({'testregistration' : newObj, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'createSingleTestBill'}),
							headers: {'Content-Type': 'application/x-www-form-urlencoded'}
						}).
						success(function(data, status, headers, config) {
							if (data.success) {
								scope.newObj.billid = data.billid;
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
			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'repaymentmethods', 'sequence', 'repaymentmethods');
		}
	}
}]);
