angular.module('core').service('paypalService', ['$http', '$q', function($http, $q) {

    var those = this;

    this.initPurchase = function (purchase) {
        paypal.checkout.initXO();
        paypal.checkout.closeFlow();

        $http({
            method: 'post',
            url: './core/services/paypal/paypal.php',
            data: $.param({'purchase' : purchase, 'type' : 'startPurchase' }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).
        success(function(data, status, headers, config) {
            if (data.success) {
                // $window.location=data.purchase.redirecturl;
                // dialogService.alertDlg($scope.translationObj.main.msgerremailsent);
                paypal.checkout.startFlow(data.purchase.redirecturl+'&useraction=commit');
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

}]);
