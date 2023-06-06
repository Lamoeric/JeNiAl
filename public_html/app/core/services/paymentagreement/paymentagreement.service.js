// This service handles the editing of a current or new payment agreement.
angular.module('core').service('paymentagreementService', ['$uibModal', '$http', 'anycodesService', 'dateFilter', 'dialogService', 'authenticationService', 'translationService', function($uibModal, $http, anycodesService, dateFilter, dialogService, authenticationService, translationService) {
	thisPaymentagreementService = this;
	
	// This is the function that creates the modal to edit paymentagreement
	this.editPaymentagreement = function(scope, currentBill) {
		var newPaymentagreement = {};
			// Keep a pointer to the current objects
//		scope.currentPaymentagreement = (paymentagreement) ? paymentagreement : {};
//		scope.paymentagreements = paymentagreements;
		scope.currentBill = currentBill;
		if (currentBill.haspaymentagreement == null) {
			newPaymentagreement = {'billid':currentBill.id, 'haspaymentagreement':false, 'paymentagreementnote':''};
		} else {
			// Copy in another object
			newPaymentagreement = {'billid':currentBill.id, 'haspaymentagreement':currentBill.haspaymentagreement, 'paymentagreementnote':currentBill.paymentagreementnote};
		}
		translationService.getTranslation(scope, 'core/services/paymentagreement', authenticationService.getCurrentLanguage());
		// Send the newPaymentagreement to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: './core/services/paymentagreement/editpaymentagreement.template.html',
				controller: 'childeditor.controller',
				scope: scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return newPaymentagreement;
					}
				}
			})
			.result.then(function(newPaymentagreement) {
				// User clicked OK and everything was valid.
				// Copy back to the current paymentagreement
				// We need to update the bill with the new information, but just the agreement info
				currentBill.haspaymentagreement = newPaymentagreement.haspaymentagreement;
				anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
				try {
					currentBill.haspaymentagreementstr = anycodesService.convertCodeToDesc(scope, 'yesnos', newPaymentagreement.haspaymentagreement);
				} catch(e) {
					currentBill.haspaymentagreementstr = currentBill.haspaymentagreement;
				}
				currentBill.paymentagreementnote = newPaymentagreement.paymentagreementnote;
				thisPaymentagreementService.updatePaymentagreementToDB(scope, currentBill)
			}, function() {
				// User clicked CANCEL.
			});
	};


	// update the bill, but just the payment agreement part
	this.updatePaymentagreementToDB = function(scope, currentBill) {
		return $http({
	      method: 'post',
	      url: './core/services/billing/bills.php',
	      data: $.param({'currentbill' : currentBill, 'type' : 'updatePaymentAgreement' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	  });
	}

}]);
