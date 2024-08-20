	'use strict';

// Declare app level module which depends on views, and components
angular.module('cpa_admin', ['ngAnimate','ui.bootstrap','ngResource','ng-currency','cgBusy','ngRoute','ui.toggle','ngFileUpload','chart.js','dndLists','cpa_admin.emailtemplateview','cpa_admin.codeview','cpa_admin.chargeview','cpa_admin.testview','cpa_admin.testdefview','cpa_admin.testcsview','cpa_admin.arenaview','cpa_admin.clubview','cpa_admin.courseview','cpa_admin.preregistrationview','cpa_admin.registrationview','cpa_admin.sessioncoursesview','cpa_admin.sessionview','cpa_admin.showview','cpa_admin.showtaskview','cpa_admin.memberview','cpa_admin.contactview','cpa_admin.privilegeview','cpa_admin.roleview','cpa_admin.userview','cpa_admin.userprofileview','cpa_admin.testsessionview','cpa_admin.teststarsessionview','cpa_admin.testperiodview','cpa_admin.testperiodfollowview','cpa_admin.testregistrationview','cpa_admin.teststarregistrationview', 'cpa_admin.courseattendanceview', 'cpa_admin.canskateevaluationview', 'cpa_admin.configurationview', 'cpa_admin.welcomeview','cpa_admin.loginview','cpa_admin.billview','cpa_admin.testexternalapprobview','cpa_admin.dashboardview', 'cpa_admin.sessiontaxreceiptview', 'cpa_admin.ccwelcomeview', 'cpa_admin.ccloginview', 'cpa_admin.ccregisterview', 'cpa_admin.ccprofileview', 'cpa_admin.ccbillview', 'cpa_admin.ccsinglebillview', 'cpa_admin.ccskaterview', 'cpa_admin.ccregistrationview', 'cpa_admin.ccpreregistrationview', 'cpa_admin.ccshowregistrationview', 'cpa_admin.wssectionview', 'cpa_admin.wspageview', 'cpa_admin.wsnewpageview', 'cpa_admin.wsprogramassistantview', 'cpa_admin.wsboardmemberview', 'cpa_admin.wspartnerview', 'cpa_admin.wscoachview', 'cpa_admin.wsnewsview', 'cpa_admin.wseventview', 'cpa_admin.wsarenaview', 'cpa_admin.wscontactview', 'cpa_admin.wsdocumentview', 'cpa_admin.wscostumeview', 'cpa_admin.wsboutiqueview', 'cpa_admin.wsclassifiedaddview', 'cpa_admin.sessionscheduleview','core'])

.config(['$locationProvider', '$routeProvider', function($locationProvider, $routeProvider) {
	$locationProvider.hashPrefix('!');
	$routeProvider.otherwise({redirectTo: '/loginview'});
}])

.factory('$exceptionHandler', ['$injector', 'errorHandlingService', function($injector, errorHandlingService) {
	// This factory intercept all non handled exceptions
    return function myExceptionHandler(exception, cause) {
		var scope = $injector.get('$rootScope');	// use $injector to avoid loop exception between modules
		// We need to call a service that manages all error handling (save to DB, log to console, display message to user)
		// Used the title of the program found in $rootScope to pass as a program name to increase amount of info logged
		errorHandlingService.logException(exception, cause, scope.title);
    };
 }])

.run(["$rootScope", "$location", '$window', '$route', 'translationService', 'dialogService', 'authenticationService', function ($rootScope, $location, $window, $route, translationService, dialogService, authenticationService) {

	$rootScope.$on("$routeChangeSuccess", function (userInfo) {
		if ($rootScope.translationObj && $rootScope.translationObj.main) {
			// Change the main tab title
			$rootScope.title = $rootScope.translationObj.main[$route.current.controller];
			// Close the navbar as soon as a choice is made
			$('#myNavbar').collapse('hide');
		}
		// console.log(userInfo);
	});

	$rootScope.$on("$routeChangeError", function (event, current, previous, eventObj) {
		if (eventObj.authenticated === false) {
			// console.log(eventObj.authenticated);
			// console.log(previous);
			// console.log(eventObj);
			// dialogService.alertDlg("Invalid rigths");
			$rootScope.newLocation = eventObj.newLocation;
			$location.path("/loginview");
		} else {
			if (eventObj.validRights != null && eventObj.validRights == false) {
				$rootScope.validRights = false;
				$location.path("/welcomeview");
				// dialogService.alertDlg("Invalid rigths");
			} else {
				$location.path("/loginview");
			}
		}
		// console.log(eventObj.authenticated);
		return;
	});

	$rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		translationService.getNavbarTranslation($rootScope, authenticationService.getCurrentLanguage());
	});

	$rootScope.$on("navbartranslation.loaded", function (event, current, previous, eventObj) {
		$rootScope.title = $rootScope.translationObj.main[$route.current.controller];
	});

	//when browser closed - psedocode
	$(window).unload(function(){
		return;
	  // $window.localStorage.removeItem("userInfo");
	  // $window.sessionStorage.removeItem("userInfo");
	});

	// $rootScope.memberAccess = function ($q, authenticationService) {
	// 	var userInfo = authenticationService.getUserInfo();
	// 	if (userInfo && userInfo.privileges.member_access == true) {
	// 		return $q.when(userInfo);
	// 	} else {
	// 		return $q.reject({authenticated: false});
	// 	}
	// }

	// var w = angular.element($window);
	// w.bind('resize', function () {
	// console.log('resize');
	// });

	$rootScope.repositionLeftColumn = function() {
		var newSize = 0;
		var intro = angular.element('#intro');
		var mainColumnLeft = angular.element('#maincolumnleft');
		var leftSelector = angular.element('#leftselector');
		var navBar = angular.element('#navbar');
		var mainform = angular.element('#mainform');
		var maintab = angular.element('#maintab');
		var maintabheader = angular.element('#maintabheader');
		var maintabcontent = angular.element('#maintabcontent');
		if (intro[0] && leftSelector[0] && mainColumnLeft[0] && navBar[0]) {

			intro.css('height', $window.innerHeight - Math.min(navBar[0].clientHeight, 50) -12 + 'px');
			// leftSelector.css('height', $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - 122 + 'px');
			if (leftSelector[0].offsetTop == 0) {
				newSize = $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - 120;
			} else {
				newSize = $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - leftSelector[0].offsetTop - 2;
			}
			leftSelector.css('height', newSize + 'px');
		}
		if (mainform[0] && navBar[0]) {
			if (mainform[0].offsetTop == 0) {
				newSize = $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - 108;
			} else {
				newSize = $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - mainform[0].offsetTop - 2;
			}
			mainform.css('height', newSize + 'px');
		}
		if (maintab[0] && navBar[0]) {
			// This is becausse in the case of multiple tabs, the tabs header take up all the available space
			// var newSize = $window.innerHeight - Math.min(navBar[0].clientHeight, 50) - maintab[0].offsetTop - 2;
			newSize = $window.innerHeight - maintab[0].offsetTop - 11;
			newSize = Math.max(newSize - maintabheader[0].offsetHeight, maintabheader[0].offsetHeight + 500);
			maintab.css('height', newSize + 'px');
		}
		if (maintabcontent[0] && maintabheader[0] && maintab[0]) {
			maintabcontent.css('height', Math.max(maintab[0].clientHeight - maintabheader[0].offsetHeight, 500) + 'px');
		}
		return;
	}

	$(window).resize(function(event){
		// console.log('resize');
		// console.log(event.target.innerHeight);
		$rootScope.repositionLeftColumn();
	});
	// $rootScope.repositionLeftColumn();

	translationService.getNavbarTranslation($rootScope, authenticationService.getCurrentLanguage());

}]);
