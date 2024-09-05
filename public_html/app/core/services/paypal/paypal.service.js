angular.module('core').service('paypalService', ['$http', 'dialogService', function($http, dialogService) {

    var those = this;

    /**
     * This function handles the cration of the item_list array of the purchase object
     * @param {*} totalAmount Total amount to charge
     * @param {*} courses       Array of courses, minimum properties : name, fees, label 
     * @param {*} charges       Array of charges, minimum properties : type, amount
     * @returns An array of items or an empty array if the total amount of all items is not equal to the total amount or that otherCharges < 0
     */
    this.createPurchaseItemList = function(totalAmount, courses, charges) {
        var course = null;
		var charge = null;
		var coursesTotal = 0;
		var otherCharges = 0;
		var item = {};
		var items = [];
        for (var i = 0; courses && i < courses.length; i++) {
            course = courses[i];
            if (course.selected == 1) {
                coursesTotal += course.fees/1;
                item = {'name' : course.name, 'price' : course.fees, 'quantity' : 1, 'currency' : 'CAD', 'description' : course.label};
                items.push(item);
            }
        }

        // Combine all other charges into one
        if (charges && charges.length > 0) {
            for (var i = 0; charges && i < charges.length; i++) {
                charge = charges[i];
                if (charge.selected == 1) {
                    if (charge.type == 'CHARGE') {
                        otherCharges += charge.amount/1;
                    } else {
                        otherCharges -= charge.amount/1;
                    }
                }
            }
            item = {'name' : 'Autres charges', 'price' : otherCharges, 'quantity' : 1, 'currency' : 'CAD'};
            items.push(item);
        }
        // Validate that the total amount of all items is equal to the total amount and that otherCharges >= 0. If not, return an empty array.
        if (coursesTotal + otherCharges == totalAmount && otherCharges >= 0) {
            return items;
        } else {
            return [];
        }
    }

    /**
     * This function handles the cration of the purchase object for paypal
     * @param {*} billId        The billid to add to the paypal transaction
     * @param {*} totalAmount   The total amount to charge
     * @param {*} returnUrl     The return url when the transaction is completed or cancelled (window.location.href)
     * @param {*} firstName     The first name of the skater (or the complete billingname)
     * @param {*} lastName      The last name of the skater (or null if complete billingname is given in firstname)
     * @param {*} courses       The list of courses to charge (or null if doItemList is false)
     * @param {*} charges       The list of other charges to charge (or null if doItemList is false)
     * @param {*} doItemList    If true, do the item_list if false do not do the item_list
     * @returns The purchase object
     */
    this.createPurchaseData = function(billId, totalAmount, returnUrl, firstName, lastName, courses, charges, doItemList) {
		var purchase = {};
        purchase.amount = {};
		purchase.amount.currency = 'CAD';
        purchase.amount.total = totalAmount;
		purchase.custom = 'custom';
        if (billId != null) {
		    purchase.billid = billId;
        }
		purchase.returnUrl = returnUrl;             // window.location.href;
        purchase.description = (firstName ? firstName : '') + (lastName ? ' ' + lastName : '');
		purchase.item_list = {};
		purchase.item_list.items = [];
        if (doItemList) {
            purchase.item_list.items = this.createPurchaseItemList(totalAmount, courses, charges);
        }
        return purchase;
    }

    /**
     * This function handles the initiation of the purchase on the paypal site
     * @param {*} purchase The purchase object (made by the createPurchaseData function)
     */
    this.initPurchase = function (purchase) {
        paypal.checkout.initXO();
        paypal.checkout.closeFlow();

        $http({
            method: 'post',
            url: './core/services/paypal/paypal.php',
            data: $.param({'purchase' : JSON.stringify(purchase), 'type' : 'startPurchase' }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).
        success(function(data, status, headers, config) {
            if (data.success) { 
                if (data.purchase.success) {
                    // $window.location=data.purchase.redirecturl;
                    // dialogService.alertDlg($scope.translationObj.main.msgerremailsent);
                    paypal.checkout.startFlow(data.purchase.redirecturl+'&useraction=commit');
                } else {
                    paypal.checkout.closeFlow();
                    dialogService.displayFailure(data.purchase.response);
                }
            } else {
                paypal.checkout.closeFlow();
                dialogService.displayFailure(data);
            }
        }).
        error(function(data, status, headers, config) {
            paypal.checkout.closeFlow();
            dialogService.displayFailure(data);
        });
    }

    this.completePurchase = function(payerId, paymentId, callbacksuccess, callbackfailure) {
        $http({
            method: 'post',
            url: './core/services/paypal/paypal.php',
            data: $.param({'payerid' : payerId , 'paymentid' : paymentId, 'type' : 'completePurchase' }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).
        success(function(data, status, headers, config) {
            if (data.success) {
                callbacksuccess(data);
                return;
            } else {
                callbackfailure(data);
                // dialogService.displayFailure(data.detail?data.detail : data);
                // window.location = window.location.href.split("?")[0];
            }
        }).
        error(function(data, status, headers, config) {
            dialogService.displayFailure(data.detail);
            window.location = window.location.href.split("?")[0];
        });
    }

}]);
