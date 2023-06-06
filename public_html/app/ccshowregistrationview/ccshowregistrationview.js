'use strict';

angular.module('cpa_admin.ccshowregistrationview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
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

.controller('ccshowregistrationviewCtrl', ['$scope', '$rootScope', '$q', '$http', '$window', '$location', '$route', '$sce', 'pricingService', 'dateFilter', 'authenticationService', 'translationService', 'auth', 'dialogService', 'anycodesService', 'agreementdialog', function($scope, $rootScope, $q, $http, $window, $location, $route, $sce, pricingService, dateFilter, authenticationService, translationService, auth, dialogService, anycodesService, agreementdialog) {
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
  $scope.showid = $route.current.params.showid;

	// Converts the paragraph using remarkable to convert markdown text and sanitizes it
	$scope.convertParagraph = function(paragraph) {
		paragraph.markdownmsg =  "<H3>" + (paragraph.title!=null && paragraph.title!='' ? paragraph.title : '') + "</H3>" +
		        "<H4>" + (paragraph.subtitle!=null && paragraph.subtitle!='' ? paragraph.subtitle : '') + "</H4>" +
		        "<p>" + (paragraph.paragraphtext!=null && paragraph.paragraphtext!='' ? $scope.remarkable.render(paragraph.paragraphtext) : '') + "</p>";
		paragraph.markdownmsg =  $sce.trustAsHtml(paragraph.markdownmsg);
	}

  $scope.getShowRules = function () {
    return $http({
      method: 'post',
      url: './ccshowregistrationview/ccshowregistrationview.php',
      data: $.param({'showid' : $scope.showid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getShowRules' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	var len = data.paragraphs.length;
      for (var x = 0;  x < len; x++) {
        $scope.convertParagraph(data.paragraphs[x]);
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
      url: './ccshowregistrationview/ccshowregistrationview.php',
      data: $.param({'userid' : $rootScope.userInfo.userid, 'skaterid' : $scope.skaterid, 'showid' : $scope.showid, 'registrationdate' : $scope.registrationdate, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getSkaterRegistrationDetails' }),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
      if (data.success && !angular.isUndefined(data.data)) {
        $scope.currentRegistration                  = data.data[0];
        $scope.currentRegistration.billingname      = $rootScope.userInfo.userfullname;
        $scope.currentRegistration.contactid        = $rootScope.userInfo.contactid;
        $scope.currentRegistration.status           = 'DRAFT';
        pricingService.applyPricingRules($scope.currentRegistration, false);
      } else if (!data.success && data.errno == 997){
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

  // Validates the registration.
  // At least one number must be selected.
  $scope.validateRegistration = function() {
    var retVal = false;
    // Validate at least one course has been selected
    for (var i = 0; i < $scope.currentRegistration.shownumbers.length; i++) {
      if ($scope.currentRegistration.shownumbers[i].selected == '1') {
        retVal = true;
        break;
      }
    }
    return retVal;
  }

$scope.insertRegistrationInDB = function() {
  $scope.currentRegistration.registrationdatestr = dateFilter(new Date(), 'yyyy-MM-dd');
  // Because we didn't insert a DRAFT registration and read it back, we need to fix some data in order for the functions to work
  $scope.currentRegistration.id = null;
//  $scope.currentRegistration.shownumbers = $scope.currentRegistration.numbers;
  $http({
    method: 'post',
    url: './registrationview/manageregistrations.php',
    data: $.param({'registration' : $scope.currentRegistration, 'billid' : 0, 'language' : authenticationService.getCurrentLanguage(), 'validcount' : 0, 'type' : 'acceptRegistration' }),
    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
  }).
  success(function(data, status, headers, config) {
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
  error(function(data, status, headers, config) {
    dialogService.displayFailure(data);
  });
}

  $scope.createNumbersList = function() {
    var list = [];
    for (var i = 0; i < $scope.currentRegistration.shownumbers.length; i++) {
      if ($scope.currentRegistration.shownumbers[i].selected == '1') {
        var showNumber = $scope.currentRegistration.shownumbers[i];
        list.push("<strong>" + showNumber.label + "</strong>");
      }
    }
    return list.join("<br>");
  }

  // When user clicks the confirm registration button
  // Registration must be valid.
  $scope.confirmRegistration = function() {
    if (!$scope.validateRegistration()) {
      dialogService.alertDlg($scope.translationObj.main.msginvalidregistration);
    } else {
      $scope.getShowRules().then(function(data) {
        // TODO : what if there are no regulations to display?
        // TODO : what if the mandatory regulation acceptance flag is false ?
        agreementdialog.showAgreement($scope, data.data).
          then(function(retVal) {
            if (retVal) {
              $scope.currentRegistration.regulationsread = "1";
              // User accepted the club regulations.

              // TODO : we need to show one last time the show numbers the user selected
              dialogService.confirmYesNo("<font color=red>" + $scope.translationObj.main.msgconfirmregistration + "</font><br><br>"+ $scope.createNumbersList() + "<br><font color=red>" + $scope.translationObj.main.msgconfirmregistration2 + "<font>",
                function(e) {
                  if (e) {
                    // user clicked "yes", so we must insert the new registration directly into ACCEPTED status, link the bill to the existing one for the contact and display
                    $scope.insertRegistrationInDB();
                  } else {
                    // user clicked "no", so do nothing
                  }
                }
              );
            } else {
              // user refused the regulation. What should we do ? return to welcome page ?
            }
          }, function() {
            return false;
          });
        return;
      }, function() {
        return;
      });
    }
  }

  // When a show number is selected (or de-selected)
	$scope.onNumberSelected = function(showNumber) {
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
	$scope.onChargeSelected = function(charge) {
		// TODO: add automatic charge to the list of non clickable charges
		if (charge != null && charge.alwaysselected != '1') {
			if (charge.selected == "1") {
				charge.selected = "0";
			} else {
				charge.selected = "1";
			}
			pricingService.applyPricingRules($scope.currentRegistration, false);
		}
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

  $rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

  $scope.refreshAll = function() {
    $scope.getSkaterRegistrationDetails();
    translationService.getTranslation($scope, 'ccshowregistrationview', authenticationService.getCurrentLanguage());
  }

  $scope.refreshAll();
}]);
