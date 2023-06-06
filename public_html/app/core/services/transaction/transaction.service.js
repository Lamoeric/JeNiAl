// This service handles the editing of a current or new transaction.
angular.module('core').service('transactionService', ['$uibModal', '$http', 'anycodesService', 'dateFilter', 'billingService', 'dialogService', 'authenticationService', 'translationService', function($uibModal, $http, anycodesService, dateFilter, billingService, dialogService, authenticationService, translationService) {
	thisTransactionService = this;
	// This is the function that creates the modal to edit transaction
	this.editTransaction = function(scope, transactions, transaction, currentBill/*, step*/) {
		var newTransaction = {};
			// Keep a pointer to the current objects
		scope.currentTransaction 	= (transaction) ? transaction : {};
		scope.transactions 				= transactions;
		scope.currentBill 				= currentBill;
		if (transaction == null) {
			newTransaction = {id:null, billid:currentBill.id, transactiontype:(currentBill.realtotalamount/1 > 0 ? "PAYMENT" : "REPAYMENT"), paymentmethod:(currentBill.realtotalamount/1 > 0 ? null : "CHECK"), transactiondate:new Date(), transactionamount:Math.abs(currentBill.realtotalamount/1),iscanceled:0,receivedby:authenticationService.getUserInfo().userid};
		} else {
			// Copy in another object
			angular.copy(transaction, newTransaction);
		}
		scope.transactionStep = 1; // TODO : clean this up. We don't need steps anymore, everything is done in one step, to save time
		// Get the values for the transaction drop down list
		anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(),'paymentmethods', 		'sequence', 'paymentmethods');
		anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(),'repaymentmethods', 	'sequence', 'repaymentmethods');
		anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(),'transactiontypes', 	'sequence', 'transactiontypes');
		translationService.getTranslation(scope, 'core/services/transaction', authenticationService.getCurrentLanguage());
		// Send the newTransaction to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: './core/services/transaction/edittransaction.template.html',
				controller: 'childeditor.controller',
				scope: scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return newTransaction;
					}
				}
			})
			.result.then(function(newTransaction) {
				// User clicked OK and everything was valid.
				// Copy back to the current transaction
				angular.copy(newTransaction, scope.currentTransaction);
				// Convert transactiontype into relation name
				scope.currentTransaction.transactiondate 			= dateFilter(scope.currentTransaction.transactiondate, 'yyyy-MM-dd');
				scope.currentTransaction.transactiontypelabel = anycodesService.convertCodeToDesc(scope, 'transactiontypes', scope.currentTransaction.transactiontype);
				scope.currentTransaction.paymentmethodlabel 	= anycodesService.convertCodeToDesc(scope, 'paymentmethods', scope.currentTransaction.paymentmethod);
				// If transaction already exists in DB (id != null)
				if (scope.currentTransaction.id != null) {
					scope.currentTransaction.status = 'Modified';
				} else {
//					scope.currentTransaction.status = 'New';
					scope.currentTransaction.id = null;
					// Don't insert twice in list
					if (scope.transactions.indexOf(scope.currentTransaction) == -1) {
						scope.transactions.push(scope.currentTransaction);
						thisTransactionService.insertTransactionToDB(scope, scope.currentTransaction, scope.currentBill);
					}
				}
				// Set the form in which this directive is inserted to dirty.
				// scope.formObj.$dirty = true;
			}, function() {
				// User clicked CANCEL.
			});
	};

	this.cancelTransaction = function(scope, transactions, transaction, currentBill/*, step*/) {
		var newTransaction = {};
			// Keep a pointer to the current objects
		scope.currentTransaction 	= (transaction) ? transaction : {};
		scope.transactions 				= transactions;
		scope.currentBill 				= currentBill;
		if (transaction == null) {
			return;
		} else {
			// Copy in another object
			angular.copy(transaction, newTransaction);
			newTransaction.canceledby = authenticationService.getUserInfo().userid;
			newTransaction.canceleddate = new Date();
		}
		// Get the values for the transaction drop down list
		anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(),'transactioncancels', 		'sequence', 'transactioncancels');
		translationService.getTranslation(scope, 'core/services/transaction', authenticationService.getCurrentLanguage());
		// Send the newTransaction to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: './core/services/transaction/canceltransaction.template.html',
				controller: 'childeditor.controller',
				scope: scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return newTransaction;
					}
				}
			})
			.result.then(function(newTransaction) {
				// User clicked OK and everything was valid.
				// Copy back to the current transaction
				angular.copy(newTransaction, scope.currentTransaction);

				scope.currentTransaction.canceleddate = dateFilter(scope.currentTransaction.canceleddate, 'yyyy-MM-dd');
				scope.currentTransaction.cancelreasonlabel = anycodesService.convertCodeToDesc(scope, 'transactioncancels', scope.currentTransaction.cancelreason);
				scope.currentTransaction.iscanceled = 1;
				scope.currentTransaction.status = 'Canceled';

				// We need to update the transaction in the DB
				thisTransactionService.cancelTransactionToDB(scope, scope.currentTransaction, scope.currentBill);

				// Set the form in which this directive is inserted to dirty.
				// scope.formObj.$dirty = true;
			}, function() {
				// User clicked CANCEL.
			});
	};

	this.cancelTransactionToDB = function(scope, transaction, currentBill) {
		transaction.canceleddatestr = dateFilter(transaction.canceleddate, 'yyyy-MM-dd');
		return $http({
	      method: 'post',
	      url: './core/services/transaction/transaction.php',
	      data: $.param({'transaction' : transaction, 'type' : 'cancelTransaction' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
					billingService.calculateBillAmounts(currentBill);
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

	// Insert a new transaction in the DB
	// Inserts the transaction, then update the paidamount column for the bill
	this.insertTransactionToDB = function(scope, transaction, currentBill) {
		transaction.transactiondatestr = dateFilter(transaction.transactiondate, 'yyyy-MM-dd');
		transaction.transactiondatestr = transaction.transactiondatestr;
		return $http({
	      method: 'post',
	      url: './core/services/transaction/transaction.php',
	      data: $.param({'transaction' : transaction, 'type' : 'insertTransaction' }),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
	    		transaction.id = data.id;
					billingService.calculateBillAmounts(currentBill);
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
