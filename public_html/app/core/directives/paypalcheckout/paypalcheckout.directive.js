angular.module('core').directive('paypalcheckout', ['$http', '$q', '$timeout', 'dialogService', function ($http, $q, $timeout, dialogService) {
    return {
        templateUrl: './core/directives/paypalcheckout/paypalcheckout.template.html',
        restrict: 'EA',
        scope: {},
        link: function (scope, element, attrs) {
            var environment = 'sandbox'       // CHANGE AS NEEDED
            var clientId = 'AUYM9WuoLSQ-fgCGSF3B6T9zCKluygOkWjvfxAnWHuoT6-N_EZqnvvu9AY8PMcAbfu8l14h3QkryPd14' // YOUR MERCHANT ID HERE (or import with scope)
            var clientSecret = 'EDWWjVZ9awHWMeln35wm6gAUMJIDabjgFv-hH8B6NtJRPkBFoJaM3jX0UZm0MSmv1jrUTVLLnGyImSSf' // YOUR MERCHANT ID HERE (or import with scope)
            var req = {
                method: 'POST',
                url: 'http://foo.bar',          // YOUR SERVER HERE (or import with scope)
                data: { foo: 'bar' }            // YOUR DATA HERE (or import with scope)
            }
            scope.showButton = false

            function sendRequest(data) {
                var deferred = $q.defer()
                $http(data)
                    .success(function (data, status) {
                        return deferred.resolve(data)
                    }).error(function (data, status) {
                        if (status === 0) { data = 'Connection error' }
                        return deferred.reject(data)
                    })
                return deferred.promise
            }

            function showButton() {
                scope.showButton = true
                scope.$apply()
            }

            function delayAndShowButton() {
                $timeout(showButton, 1000)
            }

            function loadPaypalButton() {
                paypal.checkout.setup(clientId, {
                    environment: environment,
                    buttons: [{ container: 't1', shape: 'rect', size: 'medium' }]
                })
                delayAndShowButton()
            }

            scope.initPaypal = function () {
                if (scope.showButton == false) { return }
                paypal.checkout.initXO();
                paypal.checkout.closeFlow();


                scope.promise = $http({
                    method: 'post',
                    url: './core/directives/paypalcheckout/paypal.php',
                    data: $.param({'clientid' : clientId, 'clientsecret' : clientSecret, 'type' : 'testPaypal' }),
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
        


                // return sendRequest(req)
                //     .then(function (res) {
                //         return paypal.checkout.startFlow(res.href)
                //     })
                //     .catch(function (err) {
                //         console.log('Problem with checkout flow', err)
                //         return paypal.checkout.closeFlow()
                //     })
            }

            if (window.paypalCheckoutReady != null) {
                scope.showButton = true
            } else {
                var s = document.createElement('script')
                s.src = '//www.paypalobjects.com/api/checkout.js'
                document.body.appendChild(s)
                window.paypalCheckoutReady = function () {
                    return loadPaypalButton()
                }
            }

        }
    }
}]);
