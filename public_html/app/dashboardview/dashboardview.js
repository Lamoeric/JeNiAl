'use strict';

angular.module('cpa_admin.dashboardview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/dashboardview', {
		templateUrl: 'dashboardview/dashboardview.html',
		controller: 'dashboardviewCtrl',
    resolve: {
			auth: function ($q, authenticationService) {
        var userInfo = authenticationService.getUserInfo();
        if (userInfo) {
          if (userInfo.privileges.admin_access==true) {
            return $q.when(userInfo);
          } else {
            return $q.reject({authenticated: true, validRights: false, newLocation:null});
          }
        } else {
          return $q.reject({authenticated: false, newLocation: "/dashboardview"});
        }
      }
    }
	});
}])

.controller('dashboardviewCtrl', ['$scope', '$http', '$uibModal', '$window', 'dateFilter', 'anycodesService', 'dialogService', 'listsService', 'arenaService', 'authenticationService', 'translationService', 'parseISOdateService', function($scope, $http, $uibModal, $window, dateFilter, anycodesService, dialogService, listsService, arenaService, authenticationService, translationService, parseISOdateService) {
	$scope.progName = "dashboardview";
	// $scope.leftpanetemplatefullpath = "./dashboardview/session.template.html";
	$scope.currentDashboard = {};
	$scope.sessionid = null;
	// $scope.session = null;
	// $scope.selectedSession = null;
	$scope.newSession = null;
	// $scope.selectedLeftObj = null;
	// $scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.globalErrorMessage = [];
	$scope.globalWarningMessage = [];
	// Chart.defaults.global.colors = #97BBCD,#DCDCDC,#F7464A,#46BFBD,#FDB45C,#949FB1,#4D5360;
	Chart.defaults.global.colors = ['#0000FF','#DCDCDC','#F7464A','#46BFBD','#FDB45C','#949FB1','#4D5360'];
	$scope.options = {legend: {display:true, position:'right'}}
	// $scope.translationObj = {};
	// $scope.translationObj.dashboard = {};
	// $scope.translationObj.dashboard.labelnumberofskaters = '';
	// $scope.translationObj.dashboard.labelspacesleft = '';


	// For delay display
	$scope.delay = 0;
	$scope.minDuration = 0;
	$scope.message = 'Please Wait...';
	$scope.backdrop = true;
	$scope.promise = null;

	$scope.data = [3, 57];

	// $scope.isDirty = function() {
	// 	if ($scope.detailsForm.$dirty) {
	// 		return true;
	// 	}
	// 	return false;
	// };

	// $scope.setDirty = function() {
	// 	$scope.detailsForm.$dirty = true;
	// 	$scope.isFormPristine = false;
	// };

	// $scope.setPristine = function() {
	// 	$scope.detailsForm.$setPristine();
	// 	$scope.isFormPristine = true;
	// };

	$scope.getAllDashboardInfo = function () {
		$http({
				method: 'post',
				url: './dashboardview/dashboard.php',
				data: $.param({'sessionid' : $scope.sessionid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllDashboardInfo' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
        if (!angular.isUndefined(data.data)) {
          $scope.currentDashboard = data.data;

					// $scope.currentDashboard.summarycoursescomparison = {};
					// // Try to create a new array for summary with the first 7 course codes, previous session
					// if ($scope.currentDashboard.summarycoursesbyskaterscomparison && $scope.currentDashboard.summarycoursesbyskaterscomparison.length <= 7) {
					// 	$scope.currentDashboard.summarycoursescomparison.series = [$scope.currentDashboard.summarycoursesbyskaterscomparison[0].sessionlabel, $scope.currentDashboard.summarycoursesbyskaterscomparison[0].sessionlabelprevious];
					// 	$scope.currentDashboard.summarycoursescomparison.labels = [];
					// 	$scope.currentDashboard.summarycoursescomparison.data = [];
					// 	var tempdata = [];
					// 	var tempdataprevious = [];
					//
					// 	for (var x = 0; x < $scope.currentDashboard.summarycoursesbyskaterscomparison.length; x++) {
					// 		tempdata.push($scope.currentDashboard.summarycoursesbyskaterscomparison[x].numberofskaters);
					// 		tempdataprevious.push($scope.currentDashboard.summarycoursesbyskaterscomparison[x].numberofskatersprevious);
					// 		$scope.currentDashboard.summarycoursescomparison.labels.push($scope.currentDashboard.summarycoursesbyskaterscomparison[x].codelabel);
					// 	}
					// 	$scope.currentDashboard.summarycoursescomparison.data.push(tempdata);
					// 	$scope.currentDashboard.summarycoursescomparison.data.push(tempdataprevious);
					// }

					// Try to create a new array for financial with the first 7 course codes
					if ($scope.currentDashboard.financialpercode && $scope.currentDashboard.financialpercode.length <= 7) {
						$scope.currentDashboard.financialrevenuepercodelabels = [];
						$scope.currentDashboard.financialrevenuepercodedata = [];
						$scope.currentDashboard.financialrevenuepercodetotal = 0;
						for (var x = 0; x < $scope.currentDashboard.financialpercode.length; x++) {
							$scope.currentDashboard.financialrevenuepercodedata.push($scope.currentDashboard.financialpercode[x].amount);
							$scope.currentDashboard.financialrevenuepercodelabels.push($scope.currentDashboard.financialpercode[x].codelabel + ' ' + $scope.currentDashboard.financialpercode[x].amount);
							$scope.currentDashboard.financialrevenuepercodetotal += $scope.currentDashboard.financialpercode[x].amount / 1;
							$scope.currentDashboard.financialrevenuepercodetotal = ($scope.currentDashboard.financialrevenuepercodetotal/1).toFixed(2);
						}
					}

        }
			} else {
				if(!data.success) {
					dialogService.displayFailure(data);
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.onSessionChange = function() {
		$scope.getAllDashboardInfo();
	}

	// REPORTS
	$scope.printReport = function(reportName) {
		if (reportName == 'sessionschedule') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&sessionid='+$scope.currentSession.id);
		}
	}

	$scope.setActiveSession = function() {
		for (var i = 0; $scope.sessions && i < $scope.sessions.length; i++) {
			if ($scope.sessions[i].active == 1) {
				$scope.sessionid = $scope.sessions[i].id.toString();
				// $scope.session = $scope.sessions[i];
				break;
			}
		}
		if ($scope.sessionid) {
			$scope.getAllDashboardInfo();
		}
	}

	$scope.refreshAll = function() {
		listsService.getAllSessions($scope, $http, authenticationService.getCurrentLanguage(), $scope.setActiveSession);
		if ($scope.sessionid) {
			$scope.getAllDashboardInfo();
		}
		translationService.getTranslation($scope, 'dashboardview', authenticationService.getCurrentLanguage());
	}

	$scope.refreshAll();
}]);
