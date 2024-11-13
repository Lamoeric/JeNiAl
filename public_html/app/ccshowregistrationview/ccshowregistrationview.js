'use strict';

angular.module('cpa_admin.ccshowregistrationview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
    $routeProvider
        .when('/ccshowregistrationview', {
            templateUrl: 'ccshowregistrationview/ccshowregistrationview.html',
            controller: 'ccshowregistrationviewCtrl',
            resolve: {
                auth: function ($q, authenticationService, $location) {
                    var userInfo = authenticationService.getUserInfo();
                    if (userInfo) {
                        return $q.when(userInfo);
                    } else {
                        $location.path("ccloginview");
                    }
                }
            }
        })
        .when('/ccshowregistrationview/:skaterid/:showid', {
            templateUrl: 'ccshowregistrationview/ccshowregistrationview.html',
            controller: 'ccshowregistrationviewCtrl',
            resolve: {
                auth: function ($q, authenticationService, $location) {
                    var userInfo = authenticationService.getUserInfo();
                    if (userInfo) {
                        return $q.when(userInfo);
                    } else {
                        $location.path("ccloginview");
                    }
                }
            }
        });

}])

.controller('ccshowregistrationviewCtrl', ['$scope', '$rootScope', '$q', '$http', '$window', '$location', '$route', '$sce', 'pricingService', 'dateFilter', 'authenticationService', 'translationService', 'auth', 'dialogService', 'anycodesService', 'agreementdialog', 'paypalService', function ($scope, $rootScope, $q, $http, $window, $location, $route, $sce, pricingService, dateFilter, authenticationService, translationService, auth, dialogService, anycodesService, agreementdialog, paypalService) {
    $scope.remarkable = new Remarkable({
        html: false,        // Enable HTML tags in source
        xhtmlOut: false,        // Use '/' to close single tags (<br />)
        breaks: false         // Convert '\n' in paragraphs into <br>
        //		  langPrefix:   'language-',  // CSS language prefix for fenced blocks
        //		  typographer:  false,				// Enable some language-neutral replacement + quotes beautification
        //		  quotes: '����',							// Double + single quotes replacement pairs, when typographer enabled, and smartquotes on. Set doubles to '��' for Russian, '��' for German.
        //		  highlight: function (/*str, lang*/) { return ''; } // Highlighter function. Should return escaped HTML, or '' if the source string is not changed
    });
    $rootScope.applicationName = "EC";
    $scope.skaterid = $route.current.params.skaterid;
    $scope.showid = $route.current.params.showid;
	$scope.token = $route.current.params.token;
	$scope.paymentId = $route.current.params.paymentId;
	$scope.payerId = $route.current.params.PayerID;

	/**
	 * Converts the paragraph using remarkable to convert markdown text and sanitizes it
	 * @param {*} paragraph 
	 */
    $scope.convertParagraph = function (paragraph) {
        paragraph.markdownmsg = "<H3>" + (paragraph.title != null && paragraph.title != '' ? paragraph.title : '') + "</H3>" +
            "<H4>" + (paragraph.subtitle != null && paragraph.subtitle != '' ? paragraph.subtitle : '') + "</H4>" +
            "<p>" + (paragraph.paragraphtext != null && paragraph.paragraphtext != '' ? $scope.remarkable.render(paragraph.paragraphtext) : '') + "</p>";
        paragraph.markdownmsg = $sce.trustAsHtml(paragraph.markdownmsg);
    }

    $scope.getShowRules = function () {
        return $http({
            method: 'post',
            url: './ccshowregistrationview/ccshowregistrationview.php',
            data: $.param({ 'showid': $scope.showid, 'language': authenticationService.getCurrentLanguage(), 'type': 'getShowRules' }),
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).
        success(function (data, status, headers, config) {
            var len = data.paragraphs.length;
            for (var x = 0; x < len; x++) {
                $scope.convertParagraph(data.paragraphs[x]);
            }
        }).
        error(function (data, status, headers, config) {
            dialogService.displayFailure(data);
            return false;
        });
    };


    $scope.getSkaterRegistrationDetails = function () {
        if (!$rootScope.userInfo) return;
        $scope.registrationdate = dateFilter(new Date(), 'yyyy-MM-dd');
        $scope.promise = $http({
            method: 'post',
            url: './ccshowregistrationview/ccshowregistrationview.php',
            data: $.param({ 'userid': $rootScope.userInfo.userid, 'skaterid': $scope.skaterid, 'showid': $scope.showid, 'registrationdate': $scope.registrationdate, 'language': authenticationService.getCurrentLanguage(), 'type': 'getSkaterRegistrationDetails' }),
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).
        success(function (data, status, headers, config) {
            if (data.success && !angular.isUndefined(data.data)) {
                $scope.currentRegistration = data.data[0];
                $scope.currentRegistration.billingname = $rootScope.userInfo.userfullname;
                $scope.currentRegistration.contactid = $rootScope.userInfo.contactid;
                $scope.currentRegistration.status = 'DRAFT';
                pricingService.applyPricingRules($scope.currentRegistration, false);
                $scope.currentRegistration.step = 1;
            } else if (!data.success && data.errno == 997) {
                dialogService.alertDlg($scope.translationObj.main.msgerrskateralreadyhasaregistration);
                $location.path("ccwelcomeview");
            } else {
                dialogService.displayFailure(data);
            }
        }).
        error(function (data, status, headers, config) {
            dialogService.displayFailure(data);
        });
    };

   	/**
	 * Transforms the parameters "selected", "selected_old" and "fees_old" into a delta code
	 * @param {*} course 
	 */
	$scope.setNumberDelta = function(number) {
		var delta = 0;
		if (number.selected_old/1 == 0 && number.selected/1 == 0 && number.fees_old/1 > 0) {
			// Course was removed in a previous revision
			number.deltacode = 'REMOVED_CLOSED';
		} else {
			delta = number.selected_old/1 - number.selected/1;
			if (delta == 0) {
				number.deltacode = 'UNTOUCHED';
			} else if (delta == 1) {
				number.deltacode = 'REMOVED';
			} else if (delta == -1) {
				number.deltacode = 'ADDED';
			}
		}
	}

    // Validates the registration.
    // At least one number must be selected.
    $scope.validateRegistration = function () {
        var retVal = false;
        // Validate at least one number has been selected
        for (var i = 0; i < $scope.currentRegistration.shownumbers.length; i++) {
            if ($scope.currentRegistration.shownumbers[i].selected == '1') {
                retVal = true;
                break;
            }
        }
        return retVal;
    }

    $scope.insertRegistrationInDB = function () {
        $scope.currentRegistration.registrationdatestr = dateFilter(new Date(), 'yyyy-MM-dd');
        // Because we didn't insert a DRAFT registration and read it back, we need to fix some data in order for the functions to work
        $scope.currentRegistration.id = null;
        $http({
            method: 'post',
            url: './registrationview/manageregistrations.php',
            data: $.param({ 'registration': $scope.currentRegistration, 'billid': 0, 'language': authenticationService.getCurrentLanguage(), 'validcount': 0, 'type': 'acceptRegistration' }),
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).
        success(function (data, status, headers, config) {
            if (data.success) {
                dialogService.alertDlg($scope.translationObj.main.msgregistrationcompleted);
                $location.path("ccwelcomeview");
            } else {
                if (!data.success) {
                    if (data.message && data.message.indexOf('9999') != -1) {
                        dialogService.alertDlg($scope.translationObj.main.msgregistrationerror);
                        $location.path("ccwelcomeview");
                    } else {
                        dialogService.displayFailure(data);
                    }
                }
            }
        }).
        error(function (data, status, headers, config) {
            dialogService.displayFailure(data);
        });
    }

	$scope.paypalInsertRegistrationInDB = function() {
		var billId = null;
		if ($scope.currentRegistration.status == "DRAFT-R" && $scope.currentRegistration.newCourseSelected == false) {
			// We are in a revised draft registration and user has not selected a new course. We must not create a new registration, 
			// we also assumed that there is a balance to pay, the validation between the steps should have checked that
			// just allow user to pay current bill if it exists
			if ($scope.currentRegistration.bill && $scope.currentRegistration.bill.id) {
				$scope.paypalInitPurchase($scope.currentRegistration.bill.id);
			} else {
				dialogService.displayFailure($scope.translationObj.main.msgerrtransactioncanceled);
				$location.path("ccwelcomeview");
			}
		} else {
			$scope.currentRegistration.registrationdatestr = dateFilter(new Date(), 'yyyy-MM-dd');
			// Because we didn't insert a DRAFT registration and read it back, we need to fix some data in order for the functions to work
			// If new registration, id will be == 0, if revised registration, id will be current registration.
			if ($scope.currentRegistration.id == 0) {
				billId = null; // means : start a new bill 
			} else {
				// We need to link the new registration that will be created with the old one
				$scope.currentRegistration.relatedoldregistrationid = $scope.currentRegistration.id;
				billId = -1; // means : use the same bill has the original registration
			}
			// One last time, set the delta code
			for (var i = 0; i < $scope.currentRegistration.shownumbers.length; i++) {
				$scope.setNumberDelta($scope.currentRegistration.shownumbers[i]);
			}
			// Charges are not pointing to their proper ancestor
			for (var i = 0; i < $scope.currentRegistration.charges.length; i++) {
				$scope.currentRegistration.charges[i].oldchargeid = $scope.currentRegistration.charges[i].id;
			}

			// For new registration, id needs to be null, and in this case we want to insert a new registration even if we are revising a old one.
			$scope.currentRegistration.id = null;
			$http({
				method: 'post',
				url: './registrationview/manageregistrations.php',
				data: $.param({'registration' : $scope.currentRegistration, 'billid' : billId, 'language' : authenticationService.getCurrentLanguage(), 'validcount' : 0, 'type' : 'acceptRegistration' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					var billid = data.newbillid ? data.newbillid : data.billid;
					$scope.paypalInitPurchase(billid);
				} else {
					if (!data.success) {
						if (data.message && data.message.indexOf('9999') != -1) {
							dialogService.displayFailure($scope.translationObj.main.msgregistrationerror);
							$location.path("ccwelcomeview");
						} else if (data.errno == 8888) {
							dialogService.displayFailure($scope.translationObj.main.msgregistrationnotuptodate);
							$location.path("ccwelcomeview");
						} else {
							dialogService.displayFailure(data);
						}
					}
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
			});
		}
	}

	/**
	 * At the end of step 2, user can confirm the registration if the online payment is unavailable or pay later if online payment is optionnal
	 * if online payment if mandatory, confirm button should be unavailable (by ui rules)
	 */
	$scope.confirmRegistration = function() {
		if ($scope.currentRegistration.onlinepaymentoption != 2) { // online payment is not mandatory
			$scope.insertRegistrationInDB();
		} else {
			// We shouldn't be here...
		}
	}

    /**
	 * When user clicks the continue button from step 1
	 * Registration must be valid and rules must be read and accepted.
	 */
    $scope.goToStep2 = function () {
        if (!$scope.validateRegistration()) {
            dialogService.alertDlg($scope.translationObj.main.msginvalidregistration);
        } else {
            $scope.getShowRules().then(function (data) {
                // TODO : what if there are no regulations to display?
                // TODO : what if the mandatory regulation acceptance flag is false ?
				if ($scope.currentRegistration.regulationsread != "1") {
                    agreementdialog.showAgreement($scope, data.data).
                        then(function (retVal) {
                            if (retVal) {
                                // User accepted the club regulations.
                                $scope.currentRegistration.regulationsread = "1";
                                $scope.currentRegistration.step = 2;
                            } else {
								// user refused the regulation. The ui stays in step 1. User has to cancel registration himself.
                            }
                        }, function () {
                            return false;
                        });
                } else {
                    $scope.currentRegistration.step = 2;
                }
                return;
            }, function () {
                return;
            });
        }
    }

    /**
	 * This function is called by the back to draft button (<-- Back) in step 2
	 */
	$scope.backToDraft = function() {
		$scope.currentRegistration.step = 1;
	}

    // When a show number is selected (or de-selected)
    $scope.onNumberSelected = function (showNumber) {
        if (showNumber != null && showNumber.mandatory != '1') {
            if (showNumber.selected == "1") {
                showNumber.selected = "0";
            } else {
                showNumber.selected = "1";
            }
            pricingService.applyPricingRules($scope.currentRegistration, false);
        }
        // }
    }

    // When a charge is selected (or de-selected)
    $scope.onChargeSelected = function (charge) {
        // TODO: add automatic charge to the list of non clickable charges
        if (charge != null && charge.alwaysselectedonline != '1') {
            if (charge.selected == "1") {
                charge.selected = "0";
            } else {
                charge.selected = "1";
            }
            pricingService.applyPricingRules($scope.currentRegistration, false);
        }
    }

 	/**
	 * Filter the courses diplayed in ccregistration.html, step 2
	 * @param {*} course 
	 * @returns true if the course can be displayed, false otherwise
	 */
     $scope.filterSelectedNumbers = function(number) {
		if (number.selected == 1) {
			return true;
		}
		return false;
	}

	/**
	 * Filter the charges diplayed in ccregistration.html, step 2
	 * @param {*} charge 
	 * @returns true if the charge can be displayed, false otherwise
	 */
	$scope.filterSelectedCharges = function(charge) {
		if (charge.selected == 1) {
			return true;
		}
		return false;
	}

    $scope.filterCharges = function (item) {
        if (item.isonline == '1') {
            if (item.type == 'DISCOUNT') {
                if (item.rules && item.rules.length != 0 && (item.selected == '0' && item.selected_old == '0')) {
                    return false;
                } else if (item.selected == '0' && item.selected_old == '0') {
                    if (item.alwaysdisplay == '0') {
                        return false;
                    }
                }
                if (item.active / 1 == 0) {
                    if (item.selected == '1' || item.selected_old == '1') {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
            if (item.type == 'CHARGE') {
                if (item.selected == '0' && item.selected_old == '0') {
                    if (item.alwaysdisplay == '0') {
                        return false;
                    }
                }
                if (item.active / 1 == 0) {
                    if (item.selected == '1' || item.selected_old == '1') {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
            return true;
        }
    }

	/**
	 * This function handles the initialization of the paypal purchase.
	 * @param {*} billid 
	 * @returns 
	 */
	$scope.paypalInitPurchase = function(billid) {
		var purchase = {};

		if (authenticationService.validLoginDateTime() == false) return;
		// First, create the purchase object
		if ($scope.currentRegistration.status == 'DRAFT') {
			// For DRAFT registration, try to detail the items.
			purchase = paypalService.createPurchaseData(billid, $scope.currentRegistration.totalamount, window.location.href, $scope.currentRegistration.member.firstname, $scope.currentRegistration.member.lastname, $scope.currentRegistration.courses, $scope.currentRegistration.charges, true);
		} else {
			// For DRAFT-R registrations, let's not detail the items. Just go with the total amount
			purchase = paypalService.createPurchaseData(billid, ($scope.currentRegistration.totalamount/1) + ($scope.currentRegistration.subtotalotherskaters/1) - ($scope.currentRegistration.bill.paymentsubtotal/1), window.location.href, $scope.currentRegistration.member.firstname, $scope.currentRegistration.member.lastname, $scope.currentRegistration.courses, $scope.currentRegistration.charges, false);
		}
		// Second, Init paypal purchase
		paypalService.initPurchase(purchase);
    }

    /**
	 * 	 * Called by the event of changing language on the main toolbar
	 */
    $rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
        $scope.refreshAll();
    });

	/**
	 * This function handles the return of the purchase if completed without errors
	 * @param {*} data 
	 */
	$scope.purchaseCompleted = function(data) {
		if (data.success) {
			$scope.response = data.reponse;
			if (!$scope.currentRegistration) $scope.currentRegistration = {};
			$scope.currentRegistration.step = 3;
		} else {
			if (data && data.detail) {
				dialogService.displayFailure(data.detail?data.detail : data);
			}
		}
	}

	/**
	 * This function handles the return of the purchase if purchase failed
	 * @param {*} data 
	 */
	$scope.purchaseFailed = function(data) {
		dialogService.displayFailure(data.detail?data.detail : data);
		window.location = "#!/ccwelcomeview";
	}

	/**
	 * Refreshes all list, ui, etc.
	 * Also, during online payment, reads the parameters returned by paypal and determines if payment was a failure (step 4) or a success (step 3)
	 */
    $scope.refreshAll = function () {
        $scope.getSkaterRegistrationDetails();
        translationService.getTranslation($scope, 'ccshowregistrationview', authenticationService.getCurrentLanguage());
		if ($scope.token != null) {
			if ($scope.paymentId == null) {
				if (!$scope.currentRegistration) $scope.currentRegistration = {};
				$scope.currentRegistration.step = 4;
			} else {
				// paymentId is defined, we need to complete the purchase
				paypalService.completePurchase($scope.payerId, $scope.paymentId, $scope.purchaseCompleted, $scope.purchaseFailed);
			}
		} else {
			$scope.getSkaterRegistrationDetails();
		}
    }

	// This code injects the paypal API into the DOM.
	if (window.paypalCheckoutReady != null) {
		$scope.showButton = true
	} else {
		var s = document.createElement('script')
		s.src = '//www.paypalobjects.com/api/checkout.js'
		document.body.appendChild(s)
		window.paypalCheckoutReady = function () {
			// return paypalService.loadPaypalButton()
		}
	}

    $scope.refreshAll();
}]);
