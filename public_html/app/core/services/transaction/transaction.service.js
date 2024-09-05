// This service handles the editing of a current or new transaction.
angular.module('core').service('transactionService', ['$uibModal', '$http', 'anycodesService', 'dateFilter', 'parseISOdateService', 'billingService', 'dialogService', 'authenticationService', 'translationService', 'errorHandlingService', function ($uibModal, $http, anycodesService, dateFilter, parseISOdateService, billingService, dialogService, authenticationService, translationService, errorHandlingService) {
	thisTransactionService = this;

	/**
	 * This function handles the reading of all codes, translations, userinfo, etc.
	 * @param {*} scope 
	 */
	this.refreshAll = function(scope) {
		// Get the values for the transaction drop down lists
		anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'paymentmethods', 'sequence', 'paymentmethods');
		anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'repaymentmethods', 'sequence', 'repaymentmethods');
		anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'transactiontypes', 'sequence', 'transactiontypes');
		anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'transactioncancels', 'sequence', 'transactioncancels');
		translationService.getTranslation(scope, 'core/services/transaction', authenticationService.getCurrentLanguage());
	}

	/**
	 * This function handles the viewing of a transaction. No modifications allowed.
	 * @param {*} scope 
	 * @param {*} transaction 
	 */
	this.viewTransaction = function(scope, transaction) {
		var newTransaction = {};
		if (scope && transaction) {
			scope.currentTransaction = transaction;
			angular.copy(transaction, newTransaction);
			newTransaction.transactiondate = parseISOdateService.parseDate(newTransaction.transactiondate + "T00:00:00");
			newTransaction.canceleddate = newTransaction.canceleddate != null ? parseISOdateService.parseDate(newTransaction.canceleddate + "T00:00:00") : null;
			this.refreshAll(scope);
			// Send the transaction to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: './core/services/transaction/viewtransaction.template.html',
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
			.result.then(function (newTransaction) {
				// User clicked OK and everything was valid.
			}, function () {
				// User clicked CANCEL.
			});
		}
	}

	/**
	 * This function handles the editing of the transaction. Only used to add transactions.
	 * @param {*} scope 
	 * @param {*} transactions 
	 * @param {*} transaction 
	 * @param {*} currentBill 
	 */
	this.editTransaction = function(scope, transactions, transaction, currentBill) {
		var newTransaction = {};
		// Keep a pointer to the current objects
		scope.currentTransaction = (transaction) ? transaction : {};
		scope.transactions = transactions;
		scope.currentBill = currentBill;
		if (transaction == null) {
			newTransaction = { id: null, billid: currentBill.id, transactiontype: (currentBill.realtotalamount / 1 > 0 ? "PAYMENT" : "REPAYMENT"), paymentmethod: (currentBill.realtotalamount / 1 > 0 ? null : "CHECK"), transactiondate: new Date(), transactionamount: Math.abs(currentBill.realtotalamount / 1), iscanceled: 0, receivedby: authenticationService.getUserInfo().userid };
		} else {
			// Copy in another object
			angular.copy(transaction, newTransaction);
		}
		scope.transactionStep = 1; // TODO : clean this up. We don't need steps anymore, everything is done in one step, to save time
		this.refreshAll(scope);
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
		.result.then(function (newTransaction) {
			// User clicked OK and everything was valid.
			// Copy back to the current transaction
			angular.copy(newTransaction, scope.currentTransaction);
			// Convert transactiontype into related name
			scope.currentTransaction.transactiondate = dateFilter(scope.currentTransaction.transactiondate, 'yyyy-MM-dd');
			scope.currentTransaction.transactiontypelabel = anycodesService.convertCodeToDesc(scope, 'transactiontypes', scope.currentTransaction.transactiontype);
			scope.currentTransaction.paymentmethodlabel = anycodesService.convertCodeToDesc(scope, 'paymentmethods', scope.currentTransaction.paymentmethod);
			// If transaction already exists in DB (id != null)
			if (scope.currentTransaction.id != null) {
				scope.currentTransaction.status = 'Modified';
			} else {
				scope.currentTransaction.id = null;
				// Don't insert twice in list
				if (scope.transactions.indexOf(scope.currentTransaction) == -1) {
					scope.transactions.push(scope.currentTransaction);
					thisTransactionService.insertTransactionToDB(scope, scope.currentTransaction, scope.currentBill);
				}
			}
		}, function () {
			// User clicked CANCEL.
		});
	};

	/**
	 * Ths function handles the cancelation of a transaction.
	 * @param {*} scope 
	 * @param {*} transactions 
	 * @param {*} transaction 
	 * @param {*} currentBill 
	 * @returns 
	 */
	this.cancelTransaction = function(scope, transactions, transaction, currentBill/*, step*/) {
		var newTransaction = {};
		// Keep a pointer to the current objects
		scope.currentTransaction = (transaction) ? transaction : {};
		scope.transactions = transactions;
		scope.currentBill = currentBill;
		if (transaction == null) {
			return;
		} else {
			// Copy in another object
			angular.copy(transaction, newTransaction);
			newTransaction.canceledby = authenticationService.getUserInfo().userid;
			newTransaction.canceleddate = new Date();
		}
		// Get the values for the transaction drop down list
		this.refreshAll(scope);
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
		.result.then(function (newTransaction) {
			// User clicked OK and everything was valid.
			// Copy back to the current transaction
			angular.copy(newTransaction, scope.currentTransaction);

			scope.currentTransaction.canceleddate = dateFilter(scope.currentTransaction.canceleddate, 'yyyy-MM-dd');
			scope.currentTransaction.cancelreasonlabel = anycodesService.convertCodeToDesc(scope, 'transactioncancels', scope.currentTransaction.cancelreason);
			scope.currentTransaction.iscanceled = 1;
			scope.currentTransaction.status = 'Canceled';

			// We need to update the transaction in the DB
			thisTransactionService.cancelTransactionToDB(scope, scope.currentTransaction, scope.currentBill);

		}, function () {
			// User clicked CANCEL.
		});
	};

	/**
	 * This function handles the cancelation of the transaction in the database.
	 * @param {*} scope 
	 * @param {*} transaction 
	 * @param {*} currentBill 
	 * @returns 
	 */
	this.cancelTransactionToDB = function(scope, transaction, currentBill) {
		transaction.canceleddatestr = dateFilter(transaction.canceleddate, 'yyyy-MM-dd');
		return $http({
			method: 'post',
			url: './core/services/transaction/transaction.php',
			data: $.param({ 'transaction': transaction, 'type': 'cancelTransaction' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).
		success(function (data, status, headers, config) {
			if (data.success) {
				billingService.calculateBillAmounts(currentBill);
			} else {
				if (!data.success) {
					errorHandlingService.logDataError(data, 'transactionService');
				}
			}
		}).
		error(function (data, status, headers, config) {
			errorHandlingService.logDataError(data, 'transactionService');
		});
	}

	/**
	 * This function handles the insertion of a new transaction in the database, then update the paidamount for the bill.
	 * @param {*} scope 
	 * @param {*} transaction 
	 * @param {*} currentBill 
	 * @returns 
	 */
	this.insertTransactionToDB = function(scope, transaction, currentBill) {
		transaction.transactiondatestr = dateFilter(transaction.transactiondate, 'yyyy-MM-dd');
		transaction.transactiondatestr = transaction.transactiondatestr;
		return $http({
			method: 'post',
			url: './core/services/transaction/transaction.php',
			data: $.param({ 'transaction': transaction, 'type': 'insertTransaction' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).
		success(function (data, status, headers, config) {
			if (data.success) {
				transaction.id = data.id;
				billingService.calculateBillAmounts(currentBill);
			} else {
				if (!data.success) {
					errorHandlingService.logDataError(data, 'transactionService');
				}
			}
		}).
		error(function (data, status, headers, config) {
			errorHandlingService.logDataError(data, 'transactionService');
		});
	}

}]);
