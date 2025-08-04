'use strict';

angular.module('cpa_admin.dashboardview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/dashboardview', {
		templateUrl: 'dashboardview/dashboardview.html',
		controller: 'dashboardviewCtrl',
    resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
      		}
   		}
	});
}])

.controller('dashboardviewCtrl', ['$scope', '$http', '$uibModal', '$window', 'dateFilter', 'anycodesService', 'dialogService', 'listsService', 'arenaService', 'authenticationService', 'translationService', 'parseISOdateService', function($scope, $http, $uibModal, $window, dateFilter, anycodesService, dialogService, listsService, arenaService, authenticationService, translationService, parseISOdateService) {
	$scope.progName = "dashboardview";
	$scope.currentDashboard = {};
	// Chart.defaults.global.colors = #97BBCD,#DCDCDC,#F7464A,#46BFBD,#FDB45C,#949FB1,#4D5360;
	Chart.defaults.global.colors = ['#0000FF','#DCDCDC','#F7464A','#46BFBD','#FDB45C','#949FB1','#4D5360'];
	$scope.options = {legend: {display:true, position:'right'}};
	// New option for pie charts. This one has a custom label that concatenate the amount to the label
	// Do not delete the comments, these are examples of other things we can do in options.
	$scope.options2 = {
							legend: {
								display:true, 
								position:'right',align:'start',
								
								labels: {
									// Long version that fixes the strikethrought 
									generateLabels(chart) {
										const data = chart.data;
										if (data.labels.length && data.datasets.length) {
											const {labels: {pointStyle}} = chart.legend.options;
						
											return data.labels.map((label, i) => {
												const meta = chart.getDatasetMeta(0);
												const style = meta.controller.getStyle(i);
						
												return {
													text: `${label}: ${data['datasets'][0].data[i]}`,
													fillStyle: style.backgroundColor,
													strokeStyle: style.borderColor,
													lineWidth: style.borderWidth,
													pointStyle: pointStyle,
													hidden: meta.data[i].hidden,
						
													// Extra data used for toggling the correct item
													index: i
												};
											});
										}
										return [];
									}
									// Short version that works, but does not strike the label if clicked on
									// generateLabels: (chart) => {
									// 	// const labels = pieGenerateLabelsLegend(chart);
									// 	const datasets = chart.data.datasets;
									// 	return datasets[0].data.map((data, i) => ({
									// 		text: `${chart.data.labels[i]} ${data}`,
									// 		fillStyle: datasets[0].backgroundColor[i],
									// 		index: i
									// 	}))
									// }
							},
							// How to add a custom title to the chart
							//title: {display:false, text: 'Test'},
							// How to manage tooltips, with the callbacks for the tooltip's title and label
							// tooltips: {
							// 	//'enabled':false,
							// 	callbacks: {
							// 		title: function(tooltipItems, data) {
							// 		return '';
							// 	  },
							// 	  label: function(tooltipItem, data) {
							// 		var datasetLabel = '';
							// 		var label = data.labels[tooltipItem.index];
							// 		return data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
							// 	  }
							// 	}
							}
						};
						

	// For delay display
	$scope.delay = 0;
	$scope.minDuration = 0;
	$scope.message = 'Please Wait...';
	$scope.backdrop = true;
	$scope.promise = null;

	// $scope.data = [3, 57];

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

	// $scope.setActiveSession = function() {
	// 	for (var i = 0; $scope.sessions && i < $scope.sessions.length; i++) {
	// 		if ($scope.sessions[i].active == 1) {
	// 			$scope.sessionid = $scope.sessions[i].id.toString();
	// 			break;
	// 		}
	// 	}
	// 	if ($scope.sessionid) {
	// 		$scope.getAllDashboardInfo();
	// 	}
	// }

	$scope.refreshAll = function() {
		listsService.getAllSessions($scope, authenticationService.getCurrentLanguage()).
		then(function(){
			for (var i = 0; $scope.sessions && i < $scope.sessions.length; i++) {
				if ($scope.sessions[i].active == 1) {
					$scope.sessionid = $scope.sessions[i].id.toString();
					break;
				}
			}
			if ($scope.sessionid) {
				$scope.getAllDashboardInfo();
			}
		});
		if ($scope.sessionid) {
			$scope.getAllDashboardInfo();
		}
		translationService.getTranslation($scope, 'dashboardview', authenticationService.getCurrentLanguage());
	}

	$scope.refreshAll();
}]);
