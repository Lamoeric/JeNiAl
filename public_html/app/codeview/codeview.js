'use strict';

angular.module('cpa_admin.codeview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/codeview', {
		templateUrl: 'codeview/codeview.html',
		controller: 'codeviewCtrl',
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
					return $q.reject({authenticated: false, newLocation: "/codeview"});
				}
			}
		}
	});
}])

.controller('codeviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, authenticationService, translationService) {

	$scope.progName = "codeView";
	$scope.currentCodeGroup = null;
	$scope.selectedLeftObj = null;
	$scope.codeGroups = null;
	$scope.newCode = {};
	$scope.isFormPristine = true;
	$scope.selectedCodeGroup;

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

	// This is the function that creates the modal to create new code group
	$scope.createNew = function () {
		$scope.newCodegroup = {ctname:"!TOC", active:"1"};
		// Send the newCode to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'codeview/editcodegroups.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newCodegroup;
					}
				}
			})
			.result.then(function(newCodegroup) {
				// User clicked OK and everything was valid.
				$scope.codes = ($scope.codes ? $scope.codes : []);
				$scope.codes.push(newCodegroup);
				$scope.currentCode = newCodegroup;
				$scope.currentCode.status = 'New';
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	// This is the function that creates the modal to create new code
	$scope.createNewCode = function (confirmed) {
		var nextSequence = 0;
		for (var i = 0; i < $scope.codes.length; i++) {
			if ($scope.codes[i].sequence > nextSequence) {
				nextSequence = $scope.codes[i].sequence/1 + 1;
			}
		}
		$scope.newCode = {'status':"New", 'ctname':$scope.currentCodeGroup.code, 'active':"1", 'sequence':nextSequence};
		// Send the newCode to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: './codeview/editcodes.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newCode;
					}
				}
			})
			.result.then(function(newCode) {
					// User clicked OK and everything was valid.
					$scope.codes = ($scope.codes ? $scope.codes : []);
					for (var i = 0; i < $scope.codes.length; i++) {
						if ($scope.codes[i].code == newCode.code) {
							dialogService.alertDlg($scope.translationObj.main.msgcodealreadydefined);
							exit;
						}
						if ($scope.codes[i].sequence == newCode.sequence) {
							dialogService.alertDlg($scope.translationObj.main.msgsequencealreadydefined);
							exit;
						}
					}
					$scope.codes.push(newCode);
					$scope.currentCode = newCode;
					$scope.currentCode.status = 'New';
					$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit code
	$scope.editCode = function (code) {
		$scope.currentCode = code;
		if (code.status == undefined) code.status = 'Old';

		for (var prop in code) {
			$scope.newCode[prop] = code[prop];
		}
		// Send the newCode to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: './codeview/editcodes.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newCode;
					}
				}
			})
			.result.then(function(newCode) {
					// User clicked OK and everything was valid.
					// Copy back the newCode into existing one
					for (var prop in newCode) {
						$scope.currentCode[prop] = newCode[prop];
					}
					$scope.currentCode.status = 'Modified';
					$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	$scope.getDetails = function (codeGroup) {
		$scope.promise = $http({
				method: 'post',
				url: './codeview/manageCodes.php',
				data: $.param({'ctname' : codeGroup.code, 'type' : 'getCodeGroupDetails' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if(data.success && !angular.isUndefined(data.data) ){
					$scope.codes = data.data;
				} else {
					if (data.success) {
						$scope.codes = [];
					} else {
						dialogService.messageFailure(data.message);
					}
				}
				$rootScope.repositionLeftColumn();
			}).
			error(function(data, status, headers, config) {
				dialogService.messageFailure(data.message);
			});
	};

	$scope.saveToDB = function(){
		if (!$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null, null, null, null);
		} else {
				$scope.promise = $http({
					method: 'post',
					url: './codeview/manageCodes.php',
					data: $.param({'codes' : $scope.codes, 'type' : 'updateEntireCodes' }),
					headers: {'Content-Type': 'application/x-www-form-urlencoded'}
				}).
				success(function(data, status, headers, config) {
					if(data.success){
						$scope.setCurrentInternal($scope.selectedCodeGroup, null);
					} else {
						dialogService.messageFailure(data.message);
					}
				}).
				error(function(data, status, headers, config) {
						dialogService.messageFailure(data.message);
				});
		}
	};

	$scope.setCurrentInternal = function (codeGroup, index) {
		if (codeGroup != null) {
			$scope.selectedCodeGroup = codeGroup;
			$scope.selectedLeftObj = codeGroup;
			$scope.currentCodeGroup = codeGroup;
			$scope.getDetails(codeGroup);
			$scope.setPristine();
		} else {
			$scope.currentCodeGroup = null;
			$scope.selectedCodeGroup = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (codeGroup, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, codeGroup, index);
		} else {
			$scope.setCurrentInternal(codeGroup, index);
		}
	};

	$scope.refreshAll = function() {
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'!TOC', 'code', 'leftobjs');
		translationService.getTranslation($scope, 'codeview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
