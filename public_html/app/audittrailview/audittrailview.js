'use strict';

angular.module('cpa_admin.audittrailview', ['ngRoute'])

	.config(['$routeProvider', function ($routeProvider) {
		$routeProvider.when('/audittrailview', {
			templateUrl: 'audittrailview/audittrailview.html',
			controller: 'audittrailviewCtrl',
			resolve: {
				auth: function ($q, authenticationService) {
					return authenticationService.validateUserRoutingPrivilege();
				}
			}
		});
	}])

	.controller('audittrailviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function ($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {

		$scope.progName = "audittrailview";
		$scope.currentAuditTrail = null;
		$scope.selectedAuditTrail = null;
		$scope.filters = 'toto';

		$scope.getAuditTrails = function (filters) {
			$scope.promise = $http({
				method: 'post',
				url: './audittrailview/manageaudittrail.php',
				data: $.param({ 'filters': filters, 'type': 'getAuditTrails' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					if (!angular.isUndefined(data.data)) {
						$scope.currentAuditTrail = data.data;
					} else {
						// Everything is ok, but there is no data to display (filters, empty table, etc)
						$scope.currentAuditTrail = null;
					}
				} else {
					if (!data.success) {
						dialogService.displayFailure(data);
					}
				}
			}).error(function (data, status, headers, config) {
				dialogService.displayFailure(data);
			});
		};

		$scope.refresh = function () {
			var filters = null;

			// Create the filter object
			if ($scope.userid && $scope.userid && $scope.userid.userid != '') {
				if (!filters) filters = {};
				filters.userid = $scope.userid.userid;
			}
			if ($scope.progname && $scope.progname.progname && $scope.progname.progname != '') {
				if (!filters) filters = {};
				filters.progname = $scope.progname.progname;
			}
			if ($scope.action && $scope.action.action && $scope.action.action != '') {
				if (!filters) filters = {};
				filters.action = $scope.action.action;
			}
			// Refresh
			$scope.getAuditTrails(filters);
		}

		$scope.refreshAll = function () {
			// $scope.getAuditTrails(null);
			anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
			listsService.getAllUsers($scope, authenticationService.getCurrentLanguage()).then(function () { $scope.allUsers.unshift({ 'userid': '', 'fullname': '' }); });
			listsService.getAllPrograms($scope, authenticationService.getCurrentLanguage()).then(function () { $scope.allPrograms.unshift({ 'progname': '' }); });
			listsService.getAllAuditActions($scope, authenticationService.getCurrentLanguage()).then(function () { $scope.allAuditActions.unshift({ 'action': '' }); });
			translationService.getTranslation($scope, 'audittrailview', authenticationService.getCurrentLanguage());
		}

		$scope.refreshAll();
	}]);
