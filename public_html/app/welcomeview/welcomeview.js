'use strict';

angular.module('cpa_admin.welcomeview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
  $routeProvider.when('/welcomeview', {
    templateUrl: 'welcomeview/welcomeview.html',
    controller: 'welcomeviewCtrl',
    resolve: {
      auth: function ($q, authenticationService) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          return $q.when(userInfo);
        } else {
					return $q.reject({authenticated: false});
        }
      }
    }
  });
}])

.controller('welcomeviewCtrl', ['$scope', '$rootScope', 'authenticationService', 'translationService', 'auth', 'dialogService', function($scope, $rootScope, authenticationService, translationService, auth, dialogService) {
    $rootScope.applicationName = "";
    $scope.displayimagefilename ='/privateimages/welcomeviewmainimage.jpg?decache=' + Math.random();
    if ($rootScope.validRights != null && $rootScope.validRights == false) {
      $rootScope.validRights = null;
      dialogService.alertDlg("Invalid rigths");
    }
}]);

//app.controller('welcomeCtrl', function($window, $rootScope, $scope, $uibModal, $log, $http, dlgService, dateFilter, contextService) {
//	var main = this;
//
//	$scope.refreshAll = function() {
////		$scope.selectedLanguage = 'en-ca';
//	}
//
//	$scope.$on('context.login', function(event, userid) {
//		if (userid) {
//			$scope.refreshAll();
//		}
//	});
//
//	 $window.onfocus = function(){
////	   console.log("focused");
////	   $scope.$apply();
//	 }
//
//	//Init
//	contextService.getContext($scope);
//});
