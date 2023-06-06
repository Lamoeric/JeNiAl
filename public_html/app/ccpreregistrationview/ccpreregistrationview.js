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
//          return $q.when(userInfo);
//        } else {
//          $location.path("ccloginview");
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

  $scope.savePreRegistration = function (confirmed) {
    $scope.globalErrorMessage = [];
    $scope.globalSuccessMessage = [];
		if ($scope.detailsForm.$valid && $scope.preregistration.members.length > 0) {
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
    } else {
//      $scope.globalErrorMessage.push($scope.translationObj.main.formerrormessage);
//      $("#globalErrorMessage").fadeTo(2000, 500).slideUp(500, function(){$("#globalErrorMessage").hide();});
			dialogService.alertDlg($scope.translationObj.main.formerrormessage);
    }
	};

	$scope.addSkater = function() {
    $scope.globalErrorMessage = [];
    $scope.globalSuccessMessage = [];
//		if ($scope.detailsForm.$valid) {
			$scope.preregistration.members[$scope.preregistration.members.length] = {};
//		} else {
//			$scope.globalErrorMessage.push($scope.translationObj.main.formerrormessage);
//			$("#globalErrorMessage").fadeTo(2000, 500).slideUp(500, function(){$("#globalErrorMessage").hide();});
//		}
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
//    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'preferedlanguages', 'text', 'preferedlanguages');
    anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'contacttypes', 'text', 'contacttypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'languages', 					'sequence', 'languages');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'genders', 						'text', 'genders');
    translationService.getTranslation($scope, 'ccpreregistrationview', authenticationService.getCurrentLanguage());
  }

  $scope.refreshAll();
}]);
