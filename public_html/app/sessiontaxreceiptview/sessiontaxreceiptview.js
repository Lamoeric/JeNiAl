'use strict';

angular.module('cpa_admin.sessiontaxreceiptview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/sessiontaxreceiptview', {
		templateUrl: 'sessiontaxreceiptview/sessiontaxreceiptview.html',
		controller: 'sessiontaxreceiptviewCtrl',
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
          return $q.reject({authenticated: false, newLocation: "/sessiontaxreceiptview"});
        }
      }
		}
	});
}])

.controller('sessiontaxreceiptviewCtrl', ['$scope', '$http', '$uibModal', '$q', '$interval', 'listsService', 'anycodesService', 'dialogService', 'authenticationService', 'translationService', function($scope, $http, $uibModal, $q, $interval, listsService, anycodesService, dialogService, authenticationService, translationService) {
	$scope.progName = "sessiontaxreceiptView";
	$scope.leftpanetemplatefullpath = "./sessiontaxreceiptview/sessiontaxreceipt.template.html";
	$scope.currentSessiontaxreceipt = {};
	$scope.selectedSessiontaxreceipt = null;
	$scope.newSessiontaxreceipt = null;
	$scope.isFormPristine = true;
	$scope.globalErrorMessage = [];
	$scope.globalWarningMessage = [];

	// For delay display
	$scope.delay = 0;
	$scope.minDuration = 0;
	$scope.message = 'Please Wait...';
	$scope.backdrop = true;
	$scope.promise = null;

	$scope.getSessionTaxReceiptMembers = function(sessionid) {
		return ($scope.promise = $http({
			method: 'post',
			url: './sessiontaxreceiptview/sessiontaxreceipt.php',
			data: $.param({'sessionid' : sessionid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getSessionTaxReceiptMembers' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if(data.success && !angular.isUndefined(data.data) ){
				$scope.members = data.data;
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		}));
	};

	/*
		Opens a modal form to select the email address for sending the receipt.
	*/
	$scope.selectMemberEmails = function(memberid, language) {
		// translationService.getTranslation($scope, 'core/services/billing', authenticationService.getCurrentLanguage());
		var selectedMemberEmail = {};
		selectedMemberEmail.memberid = memberid;
		return $uibModal.open({
				animation: false,
				templateUrl: './sessiontaxreceiptview/selectmemberemails.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function() {
						return selectedMemberEmail;
					}
				}
			})
			.result.then(function(selectedMemberEmail) {
				// User clicked OK and everything was valid.
				return selectedMemberEmail;
			}, function() {
				// User clicked CANCEL.
				return null;
		});
	};

	/*
		Creates the receipt pdf file
	*/
	$scope.createMemberTaxReceiptPdfFile = function(sessionid, memberid, language) {
		return $http({
			method: 'post',
			url: './reports/sessionTaxReceipt.php',
			data: $.param({'sessionid' : sessionid, 'memberid' : memberid, 'output': 'F', 'language' : language}),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(filename, status, headers, config) {
			if (filename && filename.indexOf('error') == -1) {
				filename = filename.replace("C:\\wamp\\www\\", "http://localhost/");
				return filename;
			} else {
				dialogService.displayFailure(filename);
				return null;
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return null;
		});
	}

	/*
		Sends receipt PDF file by email
	*/
	$scope.sendReceiptByEmail = function(sessionlabel, email, fullname, filename, language) {
		return $http({
			method: 'post',
			url: './sessiontaxreceiptview/sendtaxreceiptbyemail.php',
			data: $.param({'sessionlabel' : sessionlabel, 'email' : email, 'fullname' : fullname, 'filename' : filename, 'language' : language}),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				return;
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	}

	/*
		Main function to send tax receipt to a member
	*/
	$scope.sendTaxReceiptToMember = function() {
		listsService.getMemberEmails($scope, $scope.currentSessiontaxreceipt.memberid, authenticationService.getCurrentLanguage())
		.then(function() {
			$scope.selectMemberEmails($scope.currentSessiontaxreceipt.memberid, authenticationService.getCurrentLanguage())
			.then(function(selectedMemberEmail) {
				if (selectedMemberEmail) {
					$scope.selectedRecipient = selectedMemberEmail.email;
					$scope.promise = $scope.createMemberTaxReceiptPdfFile($scope.currentSessiontaxreceipt.sessionidformember.id, $scope.currentSessiontaxreceipt.memberid, authenticationService.getCurrentLanguage())
					.then(function(param) {
						if (param.data) {
							$scope.sendReceiptByEmail($scope.currentSessiontaxreceipt.sessionidformember.origlabel, $scope.selectedRecipient.email, $scope.selectedRecipient.fullname, param.data, authenticationService.getCurrentLanguage())
							.then(function(param) {
								return;
							})
						}
					})
				}
				return;
			});
		});
		return;
	}

	/*
		Main function to send tax receipt to all members of a session
	*/
	$scope.sendTaxReceiptToAll = function() {
		$scope.emailindex = 0;
		$scope.getSessionTaxReceiptMembers($scope.currentSessiontaxreceipt.session.id)
		.then(function() {
			for (var x = 0; x < $scope.members.length; x++) {
				var member = $scope.members[x];
				$scope.promise = $scope.createMemberTaxReceiptPdfFile($scope.currentSessiontaxreceipt.session.id, member.id, authenticationService.getCurrentLanguage())
				.then(function(param) {
					if (param.data) {
						// member.email = "jenial.info@gmail.com";
						$scope.emailindex++;
						$scope.sendReceiptByEmail($scope.currentSessiontaxreceipt.session.origlabel, member.email, member.fullname, param.data, authenticationService.getCurrentLanguage())
						.then(function(param) {
							return;
						})
					}
				})
			}
		})
	}

	$scope.stop = undefined;
	$scope.startSending = function() {
		// Don't start sending if we're already sending
		if (angular.isDefined($scope.stop)) return;
		// Get the members for the selected session
		$scope.getSessionTaxReceiptMembers($scope.currentSessiontaxreceipt.session.id)
		$scope.memberIndex = 0;
		$scope.stop = $interval(function() {
			if ($scope.memberIndex < $scope.members.length) {
				var member = $scope.members[$scope.memberIndex];
				$scope.memberIndex++;
				$scope.promise = $scope.createMemberTaxReceiptPdfFile($scope.currentSessiontaxreceipt.session.id, member.id, authenticationService.getCurrentLanguage())
				.then(function(param) {
					if (param.data) {
						// member.email = "jenial.info@gmail.com";
						$scope.sendReceiptByEmail($scope.currentSessiontaxreceipt.session.origlabel, member.email, member.fullname, param.data, authenticationService.getCurrentLanguage())
						.then(function(param) {
							return;
						})
					}
				})
			} else {
				$scope.stopSending();
			}
		}, 37000);
	};

	$scope.stopSending = function() {
		if (angular.isDefined($scope.stop)) {
			$interval.cancel($scope.stop);
			$scope.stop = undefined;
			dialogService.alertDlg("Done/Terminé");
		}
	};

	$scope.onSessionChange = function() {
	}

	$scope.onSessionForMemberChange = function() {
		$scope.currentSessiontaxreceipt.memberid = null;
		$scope.getSessionTaxReceiptMembers($scope.currentSessiontaxreceipt.sessionidformember.id);
	}

	$scope.setActiveSession = function() {
		for (var i = 0; $scope.sessions && i < $scope.sessions.length; i++) {
			if ($scope.sessions[i].active == 1) {
				$scope.sessionid = $scope.sessions[i].id.toString();
				break;
			}
		}
		if ($scope.sessionid) {
		}
	}

	$scope.refreshAll = function() {
		translationService.getTranslation($scope, 'sessiontaxreceiptview', authenticationService.getCurrentLanguage());
		listsService.getAllSessions($scope, $http, authenticationService.getCurrentLanguage(), $scope.setActiveSession);
	}

	$scope.refreshAll();
}]);
