'use strict';

angular.module('cpa_admin.wscoachview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/wscoachview', {
		templateUrl: 'wscoachview/wscoachview.html',
		controller: 'wscoachviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
      		}
		}
	});
}])

.controller('wscoachviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$timeout', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $timeout, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "wscoachview";
	$scope.currentWscoach = 1;
	$scope.selectedWscoach = null;
	$scope.newWscoach = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.config = null;
	$scope.formList = [{name:'detailsForm', errorMsg:'msgerrallmandatory'}, {'name':'startestsForm'}];

	/**
	 * This function checks if anything is dirty
	 * @returns true if any of the forms are dirty, false otherwise
	 */
	$scope.isDirty = function () {
		return $rootScope.isDirty($scope, $scope.formList);
	};

	/**
	 * This function sets one form dirty to indicate the whole thing is dirty
	 */
	$scope.setDirty = function () {
		$rootScope.setDirty($scope, $scope.formList);
	};

	/**
	 * This function sets all the forms as pristine
	 */
	$scope.setPristine = function () {
		$rootScope.setPristine($scope, $scope.formList);
	};

	/**
	 * This function validates all forms and display error and warning messages
	 * @returns false if something is invalid
	 */
	$scope.validateAllForms = function () {
		return $rootScope.validateAllForms($scope, $scope.formList);
	}

	/**
	 * This function gets all coaches from the database
	 */
	$scope.getAllWscoach = function () {
		$scope.promise = $http({
				method: 'post',
				url: './wscoachview/managewscoach.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllCoachs' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).success(function(data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.leftobjs = data.data;
					$scope.config = data.config;
				} else {
					$scope.leftobjs = [];
					$scope.config = data.config;
				}
				$rootScope.repositionLeftColumn();
			} else {
				if (!data.success) {
					dialogService.displayFailure(data);
				}
			}
		}).error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * This function gets the selected coach from the database
	 * @param {*} coach 
	 */
	$scope.getWscoachDetails = function (coach) {
		$scope.promise = $http({
			method: 'post',
			url: './wscoachview/managewscoach.php',
			data: $.param({'id' : coach.id, 'type' : 'getCoachDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentWscoach = data.data[0];
				$scope.currentWscoach.imageinfo = data.imageinfo;
				$scope.currentWscoach.displayimagefilename = $scope.currentWscoach.imagefilename + '?decache=' + Math.random();
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	/**
	 * This function selects or reselects the current coach from the database
	 * @param {*} coach 
	 * @param {*} index 
	 */
	$scope.setCurrentInternal = function (coach, index) {
		if (coach != null) {
			$scope.selectedLeftObj = coach;
			$scope.selectedWscoach = coach;
			$scope.getWscoachDetails(coach);
			$scope.setPristine();
		} else {
			$scope.selectedWscoach = null;
			$scope.currentWscoach = null;
			$scope.selectedLeftObj = null;
		}
	}

	/**
	 * This function selects or reselects the current coach
	 * @param {*} coach 
	 * @param {*} index 
	 */
	$scope.setCurrent = function (coach, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, coach, index);
		} else {
			$scope.setCurrentInternal(coach, index);
		}
	};

	/**
	 * This function deletes the current coach from database
	 * @param {*} confirmed true if user confirmed the deletion
	 */
	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentWscoach != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './wscoachview/managewscoach.php',
				data: $.param({'coach' : $scope.currentWscoach, 'type' : 'delete_coach' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedWscoach),1);
					$scope.setCurrentInternal(null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	}

	/**
	 * This function saves the current coach in the database
	 * @returns 
	 */
	$scope.saveToDB = function() {
		if ($scope.currentWscoach == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wscoachview/managewscoach.php',
				data: $.param({'coach' : $scope.currentWscoach, 'type' : 'updateEntireCoach' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function(data, status, headers, config) {
				if (data.success) {
					// Select this coach to reset everything
					$scope.setCurrentInternal($scope.selectedWscoach, null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	/**
	 * This function adds a new coach in the database
	 */
	$scope.addWscoachToDB = function(newElement) {
		if (newElement) {
			$scope.newElement = newElement;
			$scope.promise = $http({
				method: 'post',
				url: './wscoachview/managewscoach.php',
				data: $.param({'element' : newElement, 'language': authenticationService.getCurrentLanguage(), 'type' : 'insertElement' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function(data, status, headers, config) {
				if (data.success) {
					var newWscoach = {id:data.id, firstname:$scope.newElement.firstname, lastname:$scope.newElement.lastname};
					$scope.leftobjs.push(newWscoach);
					// We could sort the list....
					$scope.setCurrentInternal(newWscoach);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	/**
	 * This function creates the modal to create new boardmember
	 */
	$scope.createNew = function () {
		$rootScope.createNewObject($scope, false, 'wscoachview/newwscoach.template.html', $scope.addWscoachToDB);
	};

	/**
	 * This function is called by the ui when user switch the STAR version
	 */
	$scope.onVersionChanged = function() {
		$scope.currentWscoach.dancelevel = '-1';
		$scope.currentWscoach.skillslevel = '-1';
		$scope.currentWscoach.freestylelevel = '-1';
	}

	/**
	 * This function refreshes everything, called at the start of the program or on a language change
	 */
	$scope.refreshAll = function() {
		$scope.getAllWscoach();
		translationService.getTranslation($scope, 'wscoachview', authenticationService.getCurrentLanguage());
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testversions', 'sequence', 'testversions');
		// On all the code list, add another choice at first position : aucun/none
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testnewlevels', 'sequence', 'testnewlevels')
		.then(function() {
			$scope.testnewlevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
		listsService.getAllTestLevelsByType($scope, 'DANCE', 'dancelevels', authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.dancelevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
		listsService.getAllTestLevelsByType($scope, 'SKILLS', 'skillslevels', authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.skillslevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
		listsService.getAllTestLevelsByType($scope, 'FREE', 'freestylelevels', authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.freestylelevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
		listsService.getAllTestLevelsByType($scope, 'INTER', 'interpretativelevels', authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.interpretativelevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
		listsService.getAllTestLevelsByType($scope, 'COMP', 'competitivelevels', authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.competitivelevels.splice(0, 0, {code:'-1', text:$scope.translationObj.details.msgnolevels});
		});
	}

	$scope.refreshAll();
	$scope.currentWscoach = null;
}]);
