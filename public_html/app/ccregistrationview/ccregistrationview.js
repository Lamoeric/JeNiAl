'use strict';

angular.module('cpa_admin.ccregistrationview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider
	.when('/ccregistrationview', {
		templateUrl: 'ccregistrationview/ccregistrationview.html',
		controller: 'ccregistrationviewCtrl',
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
	.when('/ccregistrationview/:skaterid/:sessionid', {
		templateUrl: 'ccregistrationview/ccregistrationview.html',
		controller: 'ccregistrationviewCtrl',
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

.controller('ccregistrationviewCtrl', ['$scope', '$rootScope', '$q', '$http', '$window', '$location', '$route', '$sce', 'pricingService', 'dateFilter', 'authenticationService', 'translationService', 'auth', 'dialogService', 'anycodesService', 'agreementdialog', 'billingService', 'paypalService', function($scope, $rootScope, $q, $http, $window, $location, $route, $sce, pricingService, dateFilter, authenticationService, translationService, auth, dialogService, anycodesService, agreementdialog, billingService, paypalService) {
	$scope.remarkable = new Remarkable({
		html:         false,        // Enable HTML tags in source
		xhtmlOut:     false,        // Use '/' to close single tags (<br />)
		breaks:       false         // Convert '\n' in paragraphs into <br>
//		  langPrefix:   'language-',  // CSS language prefix for fenced blocks
//		  typographer:  false,				// Enable some language-neutral replacement + quotes beautification
//		  quotes: '“”‘’',							// Double + single quotes replacement pairs, when typographer enabled, and smartquotes on. Set doubles to '«»' for Russian, '„“' for German.
//		  highlight: function (/*str, lang*/) { return ''; } // Highlighter function. Should return escaped HTML, or '' if the source string is not changed
  	});
  	$rootScope.applicationName = "EC";
	$scope.skaterid = $route.current.params.skaterid;
	$scope.sessionid = $route.current.params.sessionid;
	$scope.token = $route.current.params.token;
	$scope.paymentId = $route.current.params.paymentId;
	$scope.payerId = $route.current.params.PayerID;

	// Converts the paragraph using remarkable to convert markdown text and sanitizes it
	$scope.convertParagraph = function(paragraph) {
		paragraph.markdownmsg =  "<H3>" + (paragraph.title!=null && paragraph.title!='' ? paragraph.title : '') + "</H3>" +
				"<H4>" + (paragraph.subtitle!=null && paragraph.subtitle!='' ? paragraph.subtitle : '') + "</H4>" +
				"<p>" + (paragraph.paragraphtext!=null && paragraph.paragraphtext!='' ? $scope.remarkable.render(paragraph.paragraphtext) : '') + "</p>";
		paragraph.markdownmsg =  $sce.trustAsHtml(paragraph.markdownmsg);
	}
	
	$scope.getSessionRules = function () {
		return $http({
			method: 'post',
			url: './ccregistrationview/ccregistrationview.php',
			data: $.param({'sessionid' : $scope.sessionid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getSessionRules' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.newrule == true) {
				var len = data.paragraphs.length;
				for (var x = 0; x < len; x++) {
					$scope.convertParagraph(data.paragraphs[x]);
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	$scope.getSkaterRegistrationDetails = function () {
		if (!$rootScope.userInfo) return;
		$scope.registrationdate = dateFilter(new Date(), 'yyyy-MM-dd');
		$scope.promise = $http({
			method: 'post',
			url: './ccregistrationview/ccregistrationview.php',
			data: $.param({'userid' : $rootScope.userInfo.userid, 'skaterid' : $scope.skaterid, 'sessionid' : $scope.sessionid, 'registrationdate' : $scope.registrationdate, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getSkaterRegistrationDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
					$scope.currentRegistration                  = data.data[0];
					$scope.currentRegistration.billingname      = $rootScope.userInfo.userfullname;
					$scope.currentRegistration.contactid        = $rootScope.userInfo.contactid;
					if ($scope.currentRegistration.id == 0) {
						$scope.currentRegistration.status = 'DRAFT';
						$scope.currentRegistration.step = 1;
						for (var i = 0; i < $scope.currentRegistration.courses.length; i++) {
							$scope.setCourseDelta($scope.currentRegistration.courses[i]);
						}
					} else {
						$scope.currentRegistration.status = 'DRAFT-R';
						$scope.currentRegistration.step = 1;
						// We need to simulate a revised draft (DRAFT-R) for the applyPricingRules to work properly
						for (var i = 0; i < $scope.currentRegistration.courses.length; i++) {
							if ($scope.currentRegistration.courses[i].selected == '1') {
								$scope.currentRegistration.courses[i].fees_old = $scope.currentRegistration.courses[i].realpaidamount ? $scope.currentRegistration.courses[i].realpaidamount : $scope.currentRegistration.courses[i].fees;
								$scope.currentRegistration.courses[i].selected_old = $scope.currentRegistration.courses[i].selected;
								// $scope.currentRegistration.courses[i].selected = '0';
							} else if ($scope.currentRegistration.courses[i].selected_old == '1') {
								$scope.currentRegistration.courses[i].selected_old = '0';
								$scope.currentRegistration.courses[i].fees_old = $scope.currentRegistration.courses[i].realpaidamount;
							}
							$scope.setCourseDelta($scope.currentRegistration.courses[i]);
						}
					}
					pricingService.applyPricingRules($scope.currentRegistration, false);
					billingService.calculateBillAmounts($scope.currentRegistration.bill);
			} else if (!data.success && data.errno == 997){
				// THIS MUST NOT HAPPEN - WE NOW HAVE THE POSSIBILITY TO MODIFY THE CURRENT REGISTRATION FOR A SKATER
				dialogService.alertDlg($scope.translationObj.main.msgerrskateralreadyhasaregistration);
				$location.path("ccwelcomeview");
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// Transforms the parameters "selected", "selected_old" and "fees_old" into a delta code
	$scope.setCourseDelta = function(course) {
		var delta = 0;
		if (course.selected_old/1 == 0 && course.selected/1 == 0 && course.fees_old/1 > 0) {
			// Course was removed in a previous revision
			course.deltacode = 'REMOVED_CLOSED';
		} else {
			delta = course.selected_old/1 - course.selected/1;
			if (delta == 0) {
				course.deltacode = 'UNTOUCHED';
			} else if (delta == 1) {
				course.deltacode = 'REMOVED';
			} else if (delta == -1) {
				course.deltacode = 'ADDED';
			}
		}
	}

	// Converts the deltacode into a text
	// Called directly by ccregistrationview.html
	$scope.getCourseDelta = function(course) {
		// List may not have finished loading when this method is first called
		if ($scope.coursedeltatypes) {
			if (course.deltacode != 'UNTOUCHED') {
				return '(' + anycodesService.convertCodeToDesc($scope, "coursedeltatypes", course.deltacode) + ')';
			}
		}
		return null;
    }

	// Validates the registration.
	// if status = DRAFT, at least one course must be selected.
	// if status = DRAFT-R, at least one course must be selected, unless online payment is on, in which case let pass
	$scope.validateRegistration = function() {
		var retVal = false;
		$scope.currentRegistration.newCourseSelected = false;
		// Validate at least one NEW course has been selected
		for (var i = 0; i < $scope.currentRegistration.courses.length; i++) {
			if ($scope.currentRegistration.courses[i].selected == '1' && $scope.currentRegistration.courses[i].selected_old != '1') {
				retVal = true;
				$scope.currentRegistration.newCourseSelected = true;
				break;
			}
		}
		// if status = DRAFT-R, at least one course must be selected, unless online payment is on, and there is a balance to pay, in which case let pass
		if ($scope.currentRegistration.status == 'DRAFT-R' && $scope.currentRegistration.onlinepaymentoption >= 1 && ($scope.currentRegistration.totalamount - $scope.currentRegistration.bill.paymentsubtotal > 0)) {
			retVal = true;
		}
		return retVal;
	}

	$scope.insertRegistrationInDB = function() {
		var billId = null;
		$scope.currentRegistration.registrationdatestr = dateFilter(new Date(), 'yyyy-MM-dd');
		// Because we didn't insert a DRAFT registration and read it back, we need to fix some data in order for the functions to work
		// If new registration, id will be == 0, if revised registration, id will be current registration.
		if ($scope.currentRegistration.id == 0) {
			billId = 0; // means : start or continue a bill for the current contact (the one connected for this session)
		} else {
			// We need to link the new registration that will be created with the old one
			$scope.currentRegistration.relatedoldregistrationid = $scope.currentRegistration.id;
			billId = -1; // means : use the same bill has the original registration
		}
		// One last time, set the delta code
		for (var i = 0; i < $scope.currentRegistration.courses.length; i++) {
			$scope.setCourseDelta($scope.currentRegistration.courses[i]);
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
			data: $.param({'registration' : $scope.currentRegistration, 'billid' : billId, 'language' : authenticationService.getCurrentLanguage(), 'validcount' : true, 'type' : 'acceptRegistration' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				dialogService.alertDlg($scope.translationObj.main.msgregistrationcompleted);
				$location.path("ccwelcomeview");
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
			for (var i = 0; i < $scope.currentRegistration.courses.length; i++) {
				$scope.setCourseDelta($scope.currentRegistration.courses[i]);
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
				data: $.param({'registration' : $scope.currentRegistration, 'billid' : billId, 'language' : authenticationService.getCurrentLanguage(), 'validcount' : true, 'type' : 'acceptRegistration' }),
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

	$scope.createCoursesList = function() {
		var list = [];
		for (var i = 0; i < $scope.currentRegistration.courses.length; i++) {
			if ($scope.currentRegistration.courses[i].selected == '1' && $scope.currentRegistration.courses[i].selected_old != '1') {
				var course = $scope.currentRegistration.courses[i];
				list.push("<strong>" + course.label + (course.courselevellabel ? "&nbsp;" + course.courselevellabel : "") + "</strong><br>" + (course.schedule ? "<small>" + course.schedule + "</small>" : ""));
			}
		}
		return list.join("<br>");
	}

	// At the end of step 2, user can confirm the registration if the online payment is unavailable or optionnal
	// if online payment if mandatory, confirm button should be unavailable
	$scope.confirmRegistration = function() {
		if ($scope.currentRegistration.onlinepaymentoption != 2) { // online payment is not mandatory
			$scope.insertRegistrationInDB();
		} else {
			// We shouldn't be here...
		}
	}

	// When user clicks the continue button from step 1
	// Registration must be valid and rules must be read and accepted.
	$scope.goToStep2 = function() {
		if (!$scope.validateRegistration()) {
			dialogService.alertDlg($scope.translationObj.main.msginvalidregistration);
		} else {
			$scope.getSessionRules().then(function(data) {
				// TODO : what if there are no regulations to display?
				// TODO : what if the mandatory regulation acceptance flag is false ?
				if ($scope.currentRegistration.regulationsread != "1") {
					agreementdialog.showAgreement($scope, data.data).
						then(function(retVal) {
							if (retVal) {
								$scope.currentRegistration.regulationsread = "1";
								// User accepted the club regulations.
								$scope.currentRegistration.step = 2;
								// TODO : we need to show one last time the courses the user selected
								// dialogService.confirmYesNo("<font color=red>" + $scope.translationObj.main.msgconfirmregistration + "</font><br><br>"+ $scope.createCoursesList() + "<br><font color=red>" + $scope.translationObj.main.msgconfirmregistration2 + "<font>",
								// 	function(e) {
								// 		if (e) {
								// 			// user clicked "yes", so we must insert the new registration directly into ACCEPTED status, link the bill to the existing one for the contact and display
								// 			$scope.insertRegistrationInDB();
								// 		} else {
								// 			// user clicked "no", so do nothing
								// 		}
								// 	}
								// );
							} else {
								// user refused the regulation. What should we do ? return to welcome page ?
							}
						}, function() {
							return false;
						});
					} else {
						$scope.currentRegistration.step = 2;
					}
				return;
			}, function() {
				return;
			});
		}
	}
	
	$scope.backToDraft = function() {
		$scope.currentRegistration.step = 1;
	}

	// When a coursecode is selected (or de-selected) for the filter
	$scope.onCourseCodeSelected = function(coursecode) {
		for (var i = 0; i < $scope.currentRegistration.coursecodes.length; i++) {
			if ($scope.currentRegistration.coursecodes[i].selected == '1') {
				$scope.coursecodefilter = 1;
				return;
			}
		}
		$scope.coursecodefilter = null;
	}

	// When a course is selected (or de-selected)
	$scope.onCourseSelected = function(course) {
		// We can add new courses, but we cannot removed old ones
		if (course != null && course.selected_old == '0' && course.deltacode != 'REMOVED_CLOSED') {
			if (course.selected == "1") {
				course.selected = "0";
			} else {
				if ((course.maxnumberskater/1 - course.nbofskaters/1) > 0) {
					course.selected = "1";
				}
			}
			pricingService.applyPricingRules($scope.currentRegistration, false);
			billingService.calculateBillAmounts($scope.currentRegistration.bill);
		}
	}

	// When a charge is selected (or de-selected)
	$scope.onChargeSelected = function(charge) {
		// TODO: add automatic charge to the list of non clickable charges
		if (charge != null && charge.alwaysselectedonline != '1' && charge.issystem != '1') {
			if (charge.selected == "1") {
				charge.selected = "0";
			} else {
				charge.selected = "1";
			}
			// $scope.setDirty();
			// $scope.calculateTotal();
			// $scope.currentRegistration.totalamount = pricingService.applyPricingRules($scope.currentRegistration);
			pricingService.applyPricingRules($scope.currentRegistration, false);
		}
	}

	// Filter the courses diplayed in ccregistration.html
	// A course that is not available online must be shown anyways if customer already paid for it (item.fees_old/1 != 0)
	$scope.filterCourses = function(item) {
		if (item.availableonline == 0 && item.fees_old/1 == 0) {
			return false;
		} else {
			if ($scope.coursecodefilter == 1) {
				for (var i = 0; i < $scope.currentRegistration.coursecodes.length; i++) {
					if ($scope.currentRegistration.coursecodes[i].selected == '1' && item.coursecode == $scope.currentRegistration.coursecodes[i].code) {
						return true;
					}
				}
			} else {
				return true;
			}
		}
		return false;
	}

	$scope.filterSelectedCourses = function(item) {
		if (item.selected == 1) {
			return true;
		}
		return false;
	}

	$scope.filterSelectedCharges = function(item) {
		if (item.selected == 1) {
			return true;
		}
		return false;
	}

	// NOT USED ANYMORE
	$scope.filterOldCourses = function(item) {
		if (item.selected_old == 0) {
			return false;
		} else {
			return true;
		}
	}

	$scope.clearCourseCodesFilter = function() {
		for (var i = 0; i < $scope.currentRegistration.coursecodes.length; i++) {
			$scope.currentRegistration.coursecodes[i].selected = '0';
		}
		$scope.coursecodefilter = null;
	}

	$scope.filterCharges = function(item) {
		if (item.isonline == '1') {
			if (item.type == 'DISCOUNT') {
				if (item.rules && item.rules.length!=0 && (item.selected == '0' && item.selected_old == '0')) {
					return false;
				}
				if (item.active/1 == 0) {
					if (item.selected == '1' || item.selected_old == '1') {
						return true;
					} else {
						return false;
					}
				}
				// Hide rebate that are not selectable
				// alwaysselectedonline are selected by default only for new registrations
				if (item.alwaysselectedonline == '1' && item.selected == '0' && item.selected_old == '0') {
					return false;
				}
				// Hide system rebates that are not already selected
				if (item.issystem == '1' && item.selected == '0' && item.selected_old == '0') {
					return false;
				}
			}
			if (item.type == 'CHARGE') {
				if (item.selected == '0' && item.selected_old == '0') {
					if (item.alwaysdisplay == '0') {
						return false;
					}
				}
				if (item.active/1 == 0) {
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

	$scope.paypalInitPurchase = function(billid) {
		var course = null;
		var charge = null;
		var item = {};
		var purchase = {};
		var otherCharges = 0;

		if (authenticationService.validLoginDateTime() == false) return;
		purchase.amount = {};
		purchase.amount.currency = 'CAD';
		purchase.description = $scope.currentRegistration.member.firstname + " " + $scope.currentRegistration.member.lastname;
		purchase.custom = 'custom';
		purchase.billid = billid;
		purchase.returnUrl = window.location.href;
		
		// Create one item for each course
		purchase.item_list = {};
		purchase.item_list.items = [];
		if ($scope.currentRegistration.status == 'DRAFT') {
			purchase.amount.total = $scope.currentRegistration.totalamount;
			for (var i = 0; i < $scope.currentRegistration.courses.length; i++) {
				course = $scope.currentRegistration.courses[i];
				if (course.selected == 1) {
					item = {'name' : course.name, 'price' : course.fees, 'quantity' : 1, 'currency' : 'CAD', 'description' : course.label};
					purchase.item_list.items.push(item);
				}
			}

			// Combine all other charges into one
			for (var i = 0; i < $scope.currentRegistration.charges.length; i++) {
				charge = $scope.currentRegistration.charges[i];
				if (charge.selected == 1) {
					if (charge.type == 'CHARGE') {
						otherCharges += charge.amount/1;
					} else {
						otherCharges -= charge.amount/1;
					}
				}
			}
			item = {'name' : 'Autres charges', 'price' : otherCharges, 'quantity' : 1, 'currency' : 'CAD'};
			purchase.item_list.items.push(item);
		} else {
			// For DRAFT-R registrations, let's not detail the items. Just go with the total amount
			purchase.amount.total = $scope.currentRegistration.totalamount - $scope.currentRegistration.bill.paymentsubtotal;
		}

		// Initiate paypal
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
				if (data.purchase.success == false){
					var message = data.purchase.response;
					for (var i = 0; i < data.purchase.detail.details.length; i++) {
						message += '<br>' + data.purchase.detail.details[i].issue;
					}
					dialogService.displayFailure(message);
				} else {
					paypal.checkout.startFlow(data.purchase.redirecturl+'&useraction=commit');
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


	$rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

	$scope.refreshAll = function() {
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'coursedeltatypes',	'text', 'coursedeltatypes');
		translationService.getTranslation($scope, 'ccregistrationview', authenticationService.getCurrentLanguage());
		if ($scope.token != null) {
			if ($scope.paymentId == null) {
				if (!$scope.currentRegistration) $scope.currentRegistration = {};
				$scope.currentRegistration.step = 4;
			} else {
				// paymentId is defined, we need to complete the purchase
				$scope.promise = $http({
					method: 'post',
					url: './core/services/paypal/paypal.php',
				 	data: $.param({'payerid' : $scope.payerId , 'paymentid' : $scope.paymentId, 'type' : 'completePurchase' }),
				 	headers: {'Content-Type': 'application/x-www-form-urlencoded'}
				}).
				success(function(data, status, headers, config) {
					if (data.success) {
						$scope.response = data.reponse;
						if (!$scope.currentRegistration) $scope.currentRegistration = {};
						$scope.currentRegistration.step = 3;
				 	} else {
						if (data && data.detail) {
							dialogService.displayFailure(data.detail);
						} else {
							dialogService.alertDlg($scope.translationObj.main.msgerrtransactioncanceled);
						}
						window.location = "#!/ccwelcomeview"
				 	}
				}).
				error(function(data, status, headers, config) {
				 	dialogService.displayFailure(data.detail);
				 	window.location = "#!/ccwelcomeview"; // window.location.href.split("?")[0];
				});
			}
		} else {
			$scope.getSkaterRegistrationDetails();
		}
	}

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
