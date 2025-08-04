'use strict';

angular.module('cpa_admin.chargeview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/chargeview', {
		templateUrl: 'chargeview/chargeview.html',
		controller: 'chargeviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
			}
		}
	});
}])

.controller('chargeviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, authenticationService, translationService) {

	$scope.progName = "chargeView";
	$scope.currentCharge = null;
	$scope.selectedLeftObj = null;
	$scope.newCharge = null;
	$scope.isFormPristine = true;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function() {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllCharges = function () {
		$scope.promise = $http({
				method: 'post',
				url: './chargeview/manageCharges.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllCharges' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.leftobjs = data.data;
				} else {
					$scope.leftobjs = [];
				}
				$rootScope.repositionLeftColumn();
			} else {
				if (!data.success) {
					dialogService.displayFailure(data);
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.getChargeDetails = function (charge) {
		$scope.promise = $http({
			method: 'post',
			url: './chargeview/manageCharges.php',
			data: $.param({'id' : charge.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getChargeDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success && !angular.isUndefined(data.data) ){
				$scope.currentCharge = data.data[0];
				// for (var x = 0; x < $scope.currentCharge.rules.length; x++) {
				//   $scope.currentCharge.rules[x] = angular.fromJson($scope.currentCharge.rules[x]);
				// }
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (charge, index) {
		if (charge != null) {
			$scope.selectedLeftObj = charge;
			$scope.getChargeDetails(charge);
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentCharge = null;
		}
	}

	$scope.setCurrent = function (charge, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, charge, index);
		} else {
			$scope.setCurrentInternal(charge, index);
		}
	};

	$scope.deleteFromDB = function(confirmed){
		if ($scope.currentCharge != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './chargeview/manageCharges.php',
				data: $.param({'charge' : $scope.currentCharge, 'type' : 'delete_charge' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedLeftObj),1);
					$scope.setCurrentInternal(null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	}

	$scope.validateAllForms = function() {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}

		if ($scope.globalErrorMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function(){$("#mainglobalerrormessage").hide();});
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function(){$("#mainglobalwarningmessage").hide();});
		}
		return retVal;
	}

	$scope.saveToDB = function(){
		if ($scope.currentCharge == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './chargeview/manageCharges.php',
				data: $.param({'charge' : $scope.currentCharge, 'type' : 'updateEntireCharge' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success){
					// Select this charge to reset everything
					$scope.setCurrentInternal($scope.selectedLeftObj, null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	$scope.addChargeToDB = function(){
		$scope.promise = $http({
			method: 'post',
			url: './chargeview/manageCharges.php',
			data: $.param({'charge' : $scope.newCharge, 'type' : 'insert_charge' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success){
				var newCharge = {id:data.id, code:$scope.newCharge.code, type:$scope.newCharge.type};
				$scope.leftobjs.push(newCharge);
				// We could sort the list....
				$scope.setCurrentInternal(newCharge);
				return true;
			} else {
				dialogService.displayFailure(data);
				return false;
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	// This is the function that creates the modal to create new charge
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNewCharge, null, true, null);
		} else {
			$scope.newCharge = {};
			// Send the newCharge to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'chargeview/newcharge.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: null,
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newCharge;
						}
					}
			})
			.result.then(function(newCharge) {
					// User clicked OK and everything was valid.
					$scope.newCharge = newCharge;
					if ($scope.addChargeToDB() == true) {
					}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	$scope.refreshAll = function() {
		$scope.getAllCharges();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'chargetypes', 'text', 'chargetypes');
		translationService.getTranslation($scope, 'chargeview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
