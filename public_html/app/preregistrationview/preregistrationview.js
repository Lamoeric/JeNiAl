'use strict';

angular.module('cpa_admin.preregistrationview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/preregistrationview', {
		templateUrl: 'preregistrationview/preregistrationview.html',
		controller: 'preregistrationviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.registration_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/preregistrationview"});
				}
			}
		}
	});
}])

.controller('preregistrationviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "preregistrationview";
	$scope.currentPreRegistration = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.globalErrorMessage = [];
	$scope.globalSuccessMessage = [];
	$scope.globalErrorMessageC2 = [];
	$scope.globalSuccessMessageC2 = [];
	$scope.memberErrorMessages = [];

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

	$scope.getAllPreRegistrations = function () {
		$scope.promise = $http({
				method: 'post',
				url: './preregistrationview/preregistration.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllPreRegistrations' }),
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

	$scope.getPreRegistrationDetails = function (preregistration) {
		$scope.promise = $http({
			method: 'post',
			url: './preregistrationview/preregistration.php',
			data: $.param({'id' : preregistration.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getPreRegistrationDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentPreRegistration = data.data[0];
				// Select first contact to reconnect if it exists
				if ($scope.currentPreRegistration.possiblecontact1.length > 0) {
					$scope.currentPreRegistration.tobecopied = 2;
					$scope.currentPreRegistration.contact1 = $scope.currentPreRegistration.possiblecontact1[0];
				}

				// Select second contact to reconnect if it exists
				if ($scope.currentPreRegistration.possiblecontact2.length > 0) {
					$scope.currentPreRegistration.tobecopied2 = 2;
					$scope.currentPreRegistration.contact2 = $scope.currentPreRegistration.possiblecontact2[0];
				}

				// Manage error messages for contact1
				$scope.globalErrorMessage = [];
				if ($scope.currentPreRegistration.countuser > 0) {
					$scope.globalErrorMessage.push($scope.translationObj.main.formerrormessageuser);
				}
				if ($scope.currentPreRegistration.countcontact > 0) {
					$scope.globalErrorMessage.push($scope.translationObj.main.formerrormessagecontact1);
				}
				if ($scope.currentPreRegistration.countcontactname > 0) {
					$scope.globalErrorMessage.push($scope.translationObj.main.formerrormessagecontact2);
				}
				if ($scope.currentPreRegistration.countemail > 0) {
					$scope.globalErrorMessage.push($scope.translationObj.main.formerrormessagecontact3);
				}
				if ($scope.globalErrorMessage.length > 0) {
					$("#globalErrorMessage").fadeTo(2000, 500);
				} else {
					$("#globalErrorMessage").hide();
				}

				// Manage error messages for contact2
				$scope.globalErrorMessageC2 = [];
				if ($scope.currentPreRegistration.countuser2 > 0) {
					$scope.globalErrorMessageC2.push($scope.translationObj.main.formerrormessageuser);
				}
				if ($scope.currentPreRegistration.countcontact2 > 0) {
					$scope.globalErrorMessageC2.push($scope.translationObj.main.formerrormessagecontact1);
				}
				if ($scope.currentPreRegistration.countcontactname2 > 0) {
					$scope.globalErrorMessageC2.push($scope.translationObj.main.formerrormessagecontact2);
				}
				if ($scope.currentPreRegistration.countemail2 > 0) {
					$scope.globalErrorMessageC2.push($scope.translationObj.main.formerrormessagecontact3);
				}
				if ($scope.globalErrorMessageC2.length > 0) {
					$("#globalErrorMessageC2").fadeTo(2000, 500);
				} else {
					$("#globalErrorMessageC2").hide();
				}
				
				// Manage error messages for members
				for (var x = 0; x < $scope.currentPreRegistration.members.length; x++) {
					var member = $scope.currentPreRegistration.members[x];
					$scope.memberErrorMessages[x] = [];
					if (member.countmembername > 0) {
						$scope.memberErrorMessages[x].push($scope.translationObj.main.formerrormessagemember1);
					}
					// Select first member to reconnect if it exists
					if (member.possiblemembers.length > 0) {
						member.tobecopied = 2;
						member.member = member.possiblemembers[0];
					}
				}
				
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (preregistration, index) {
		if (preregistration != null) {
			$scope.selectedLeftObj = preregistration;
			$scope.selectedPreRegistration = preregistration;
			$scope.getPreRegistrationDetails(preregistration);
			$scope.setPristine();
		} else {
			$scope.selectedPreRegistration = null;
			$scope.currentPreRegistration = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (preregistration, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, preregistration, index);
		} else {
			$scope.setCurrentInternal(preregistration, index);
		}
	};

	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentPreRegistration != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$http({
				method: 'post',
				url: './preregistrationview/preregistration.php',
				data: $.param({'preregistration' : $scope.currentPreRegistration, 'type' : 'deletePreRegistration' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedPreRegistration),1);
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

	$scope.saveToDB = function() {
		if ($scope.currentPreRegistration == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './preregistrationview/preregistration.php',
				data: $.param({'preregistration' : $scope.currentPreRegistration, 'type' : 'updateEntirePreRegistration' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this preregistration to reset everything
					$scope.setCurrentInternal($scope.selectedPreRegistration, null);
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

	$scope.markPreRegistration = function(confirmed) {
		if ($scope.currentPreRegistration.treated == 0 && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgmarktreated, "YESNO", $scope.markPreRegistration, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './preregistrationview/preregistration.php',
				data: $.param({'id' : $scope.currentPreRegistration.id, 'type' : 'markPreRegistration' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this preregistration to reset everything
					$scope.selectedPreRegistration.treated = 1;
					$scope.setCurrentInternal($scope.selectedPreRegistration, null);
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

	/**
	 * Validates if there is a change of email in the contacts
	 * @returns {bool}	True i fthe system needs the user vakidation before updating a contact's email
	 * 
	 */
	$scope.validateEmailChange = function() {
		var needConfirmation = false;
		if ($scope.currentPreRegistration.tobecopied == 2) {
			if ($scope.currentPreRegistration.email != $scope.currentPreRegistration.contact1.email) {
				needConfirmation = true;
			}
		}
		if ($scope.currentPreRegistration.tobecopied2 == 2) {
			if ($scope.currentPreRegistration.email2 != $scope.currentPreRegistration.contact2.email) {
				needConfirmation = true;
			}
		}
		return needConfirmation;
	}

	/**
	 * 
	 * @param {bool} confirmed 
	 * @param {bool} confirmUpd 
	 */
	$scope.copyPreRegistration = function(confirmed, confirmUpd) {
		var somethingToBeCopied = true;
		var member;
		if ($scope.currentPreRegistration.treated == 1 && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgalreadytreated, "YESNO", $scope.copyPreRegistration, null, true, null);
		} else {
			if (!confirmUpd) {
				var needConfirmation = $scope.validateEmailChange();
				if (needConfirmation == true) {
					dialogService.confirmDlg($scope.translationObj.main.msgconfirmemailchange, "YESNO", $scope.copyPreRegistration, null, true, true);
				} else {
					confirmUpd = true;
				}
			}

			if (confirmUpd) {
				if (somethingToBeCopied) {
					$scope.promise = $http({
						method: 'post',
						url: './preregistrationview/preregistration.php',
						data: $.param({'preregistration' : $scope.currentPreRegistration, 'type' : 'copyPreRegistration' }),
						headers: {'Content-Type': 'application/x-www-form-urlencoded'}
					}).
					success(function(data, status, headers, config) {
						if (data.success) {
							// Select this preregistration to reset everything
							$scope.setCurrentInternal($scope.selectedPreRegistration, null);
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
				
				} else {
					dialogService.alertDlg($scope.translationObj.main.msgnothingtocopy);
				}
			}
		}
	}

	$scope.refreshAll = function() {
		$scope.getAllPreRegistrations();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 					'text', 		'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'contacttypes', 	'text', 		'contacttypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'languages', 		'sequence', 'languages');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'genders', 			'text', 		'genders');
		translationService.getTranslation($scope, 'preregistrationview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
