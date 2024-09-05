angular.module('core').directive('paypalcheckout', ['paypalService', function (paypalService) {
    return {
        templateUrl: './core/directives/paypalcheckout/paypalcheckout.template.html',
        restrict: 'EA',
        scope: {
            purchase: '=',
            isFormPristine: '='
        },
        link: function (scope, element, attrs) {

            scope.initPaypal = function () {
                if (scope.isFormPristine) {
                    paypalService.initPurchase(scope.purchase);
                }
            }

            // This code initializes the paypal checkout API
            if (window.paypalCheckoutReady != null) {
                scope.showButton = true
            } else {
                var s = document.createElement('script')
                s.src = '//www.paypalobjects.com/api/checkout.js'
                document.body.appendChild(s)
                window.paypalCheckoutReady = function () {
                    // return loadPaypalButton()
                }
            }
        }
    }
}]);
