'use strict';

angular.module('cpa_admin.ccpreregistrationview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/ccpreregistrationview', {
		templateUrl: 'ccpreregistrationview/ccpreregistrationview.html',
		controller: 'ccpreregistrationviewCtrl',
		resolve: {
			auth: function ($q, authenticationService, $location) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					$location.path("ccwelcomeview");
				}
			}
		}
	});
}])

.controller('ccpreregistrationviewCtrl', ['$scope', '$rootScope', '$q', '$http', '$location', 'authenticationService', 'translationService', 'auth', 'dialogService', 'anycodesService', function($scope, $rootScope, $q, $http, $location, authenticationService, translationService, auth, dialogService, anycodesService) {
	$rootScope.applicationName = "EC";
	$scope.preregistration = {};
	$scope.preregistration.contact = {};
	$scope.preregistration.members = [];
	$scope.preregistration.members[0] = {'usepreviousaddress':0};
	$scope.globalErrorMessage = [];
	$scope.globalSuccessMessage = [];

	$scope.isValidEmail = function(email) {
		var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
		return emailRegex.test(email);
	}

	$scope.validDetailsForm = function() {
		if (($scope.preregistration.contact.homephone == null || $scope.preregistration.contact.homephone == '' || $scope.preregistration.contact.homephone.length != 14) && 
			($scope.preregistration.contact.cellphone == null || $scope.preregistration.contact.cellphone == '' || $scope.preregistration.contact.cellphone.length != 14)) {
			dialogService.alertDlg($scope.translationObj.main.formerrormessagephonemandatory);
			return false;
		}
		if ($scope.preregistration.usesecondcontact == 1 && $scope.preregistration.contact2 && ($scope.preregistration.contact2.homephone == null || $scope.preregistration.contact2.homephone == '') && 
			($scope.preregistration.contact2.cellphone == null || $scope.preregistration.contact2.cellphone == '')) {
			dialogService.alertDlg($scope.translationObj.main.formerrormessagephonemandatory2);
			return false;
		}
		if ($scope.preregistration.contact.email != null && $scope.preregistration.contact.email != '' && !$scope.isValidEmail($scope.preregistration.contact.email)) {
			dialogService.alertDlg($scope.translationObj.main.formerrormessageemailaddress);
			return false;
		}
		if ($scope.preregistration.usesecondcontact == 1 && $scope.preregistration.contact2 && $scope.preregistration.contact2.email != null && $scope.preregistration.contact2.email != '' && !$scope.isValidEmail($scope.preregistration.contact2.email)) {
			dialogService.alertDlg($scope.translationObj.main.formerrormessageemailaddress2);
			return false;
		}
		for (var i = 0; i < $scope.preregistration.members.length; i++) {
			if ($scope.preregistration.members[i].address1 && $scope.preregistration.members[i].address1.indexOf('@') != -1) {
				dialogService.alertDlg($scope.translationObj.main.formerrormessagepostaladdress + (i/1 + 1));
				return false;
			}
		}
		if (!$scope.detailsForm.$valid) {
			dialogService.alertDlg($scope.translationObj.main.formerrormessage);
			return false;
		}
		if ($scope.preregistration.members.length < 1) {
			dialogService.alertDlg($scope.translationObj.main.formerrormessage);
			return false;
		}
		return true;
	}

	$scope.savePreRegistration = function (confirmed) {
		$scope.globalErrorMessage = [];
		$scope.globalSuccessMessage = [];
		if ($scope.validDetailsForm()) {
			if (!confirmed) {
				dialogService.confirmDlg($scope.translationObj.main.msgconfirmsave, "YESNO", $scope.savePreRegistration, null, true, null);
			} else {
				$scope.promise = $http({
					method: 'post',
					url: './ccpreregistrationview/ccpreregistrationview.php',
					data: $.param({'currentUser' : $scope.currentUser, 'preregistration' : $scope.preregistration, 'premembers' : $scope.preregistration.members,'type' : 'savePreRegistration'}),
					headers: {'Content-Type': 'application/x-www-form-urlencoded'}
				}).
				success(function(data, status, headers, config) {
					if (data.success) {
						dialogService.alertDlg($scope.translationObj.main.formsuccessmessage);
						$location.path("ccloginview");
					}
				}).
				error(function(data, status, headers, config) {
						dialogService.displayFailure(data);
				});
			}
		}
	};

	$scope.addSkater = function() {
		$scope.globalErrorMessage = [];
		$scope.globalSuccessMessage = [];
		$scope.preregistration.members[$scope.preregistration.members.length] = {};
	}
	
	$scope.removeSkater = function() {
		if ($scope.preregistration.members.length > 1) {
			$scope.preregistration.members.pop();
		}
	}
	
  $rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

	$scope.refreshAll = function() {
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'contacttypes',	'text',		'contacttypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'languages',		'sequence',	'languages');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'genders',		'text',		'genders');
		translationService.getTranslation($scope, 'ccpreregistrationview', authenticationService.getCurrentLanguage());
	}

	$scope.refreshAll();
}]);
