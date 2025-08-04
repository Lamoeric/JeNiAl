'use strict';

angular.module('cpa_admin.emailtemplateview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/emailtemplateview', {
		templateUrl: 'emailtemplateview/emailtemplateview.html',
		controller: 'emailtemplateviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
			}
		}
	});
}])

.controller('emailtemplateviewCtrl', ['$rootScope', '$sce', '$scope', '$http', '$uibModal', '$timeout', 'parseISOdateService', 'dateFilter', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', 'sendEmailTemplateService', function ($rootScope, $sce, $scope, $http, $uibModal, $timeout, parseISOdateService, dateFilter, Upload, anycodesService, dialogService, listsService, authenticationService, translationService, sendEmailTemplateService) {
	$scope.progName = "emailtemplateview";
	$scope.currentEmailtemplate = null;
	$scope.selectedEmailtemplate = null;
	$scope.newEmailtemplate = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.remarkable = new Remarkable({
		html: false,        // Enable HTML tags in source
		xhtmlOut: false,        // Use '/' to close single tags (<br />)
		breaks: false         // Convert '\n' in paragraphs into <br>
	});

	$scope.isDirty = function () {
		if ($scope.detailsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function () {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function () {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.convertParagraph = function (paragraph) {
		if (paragraph) {
			paragraph.msgfr = "<H3>Titre: " + (paragraph.title_fr != null && paragraph.title_fr != '' ? paragraph.title_fr : '') + "</H3>" +
								"<p>" + (paragraph.paragraphtext_fr != null && paragraph.paragraphtext_fr != '' ? $scope.remarkable.render(paragraph.paragraphtext_fr) : '') + "</p>";
			paragraph.msgfr = $sce.trustAsHtml(paragraph.msgfr);
			paragraph.msgen = "<H3>Title: " + (paragraph.title_en != null && paragraph.title_en != '' ? paragraph.title_en : '') + "</H3>" +
								"<p>" + (paragraph.paragraphtext_en != null && paragraph.paragraphtext_en != '' ? $scope.remarkable.render(paragraph.paragraphtext_en) : '') + "</p>";
			paragraph.msgen = $sce.trustAsHtml(paragraph.msgen);
		}
	}

	// This is the function that gets all email template from database
	$scope.getAllEmailtemplate = function () {
		$scope.promise = $http({
			method: 'post',
			url: './emailtemplateview/manageemailtemplate.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllEmailtemplates' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
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
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that gets the current email template from database
	$scope.getEmailtemplateDetails = function (emailtemplate) {
		$scope.promise = $http({
			method: 'post',
			url: './emailtemplateview/manageemailtemplate.php',
			data: $.param({ 'id': emailtemplate.id, 'language': authenticationService.getCurrentLanguage(), 'type': 'getEmailtemplateDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentEmailtemplate = data.data[0];
				if ($scope.currentEmailtemplate.paragraphs == null || $scope.currentEmailtemplate.paragraphs.length == 0) {
					$scope.currentEmailtemplate.paragraphs = [];
					$scope.currentEmailtemplate.paragraphs.push({
						'title_fr': $scope.currentEmailtemplate.title_fr, 'title_en': $scope.currentEmailtemplate.title_en,
						'paragraphtext_fr': $scope.currentEmailtemplate.paragraphtext_fr, 'paragraphtext_en': $scope.currentEmailtemplate.paragraphtext_en
					});
					$scope.convertParagraph($scope.currentEmailtemplate.paragraphs[0]);
				}
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// This is the function that selects or reselects the current email template from database
	$scope.setCurrentInternal = function (emailtemplate, index) {
		if (emailtemplate != null) {
			$scope.selectedLeftObj = emailtemplate;
			$scope.selectedEmailtemplate = emailtemplate;
			$scope.getEmailtemplateDetails(emailtemplate);
			$scope.setPristine();
		} else {
			$scope.selectedEmailtemplate = null;
			$scope.currentEmailtemplate = null;
			$scope.selectedLeftObj = null;
		}
	}

	// This is the function that selects or reselects the current emailtemplate
	$scope.setCurrent = function (emailtemplate, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, emailtemplate, index);
		} else {
			$scope.setCurrentInternal(emailtemplate, index);
		}
	};

	// This is the function that deletes the current email template from database
	$scope.deleteFromDB = function (confirmed) {
		if ($scope.currentEmailtemplate != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './emailtemplateview/manageemailtemplate.php',
				data: $.param({ 'emailtemplate': JSON.stringify($scope.currentEmailtemplate), 'type': 'delete_emailtemplate' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedEmailtemplate), 1);
					$scope.setCurrentInternal(null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).error(function (data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	}

	// This is the function that validates all forms and display error and warning messages
	$scope.validateAllForms = function () {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}

		if ($scope.globalErrorMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function () { $("#mainglobalerrormessage").hide(); });
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function () { $("#mainglobalwarningmessage").hide(); });
		}
		return retVal;
	}

	// This is the function that saves the current email template in the database
	$scope.saveToDB = function () {
		if ($scope.currentEmailtemplate == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './emailtemplateview/manageemailtemplate.php',
				data: $.param({ 'emailtemplate': JSON.stringify($scope.currentEmailtemplate), 'type': 'updateEntireEmailtemplate' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this emailtemplate to reset everything
					$scope.setCurrentInternal($scope.selectedEmailtemplate, null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).error(function (data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	// This is the function that saves the new email template in the database
	$scope.addEmailtemplateToDB = function () {
		$scope.promise = $http({
			method: 'post',
			url: './emailtemplateview/manageemailtemplate.php',
			data: $.param({ 'emailtemplate': $scope.newEmailtemplate, 'type': 'insert_emailtemplate' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				var newEmailtemplate = { id: data.id, templatename: $scope.newEmailtemplate.templatename };
				$scope.leftobjs.push(newEmailtemplate);
				// We could sort the list....
				$scope.setCurrentInternal(newEmailtemplate);
				return true;
			} else {
				dialogService.displayFailure(data);
				return false;
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	// This is the function that creates the modal to create new email template
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newEmailtemplate = {};
			// Send the newEmailtemplate to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'emailtemplateview/newemailtemplate.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newEmailtemplate;
					}
				}
			}).result.then(function (newEmailtemplate) {
				// User clicked OK and everything was valid.
				$scope.newEmailtemplate = newEmailtemplate;
				if ($scope.addEmailtemplateToDB() == true) {
				}
			}, function () {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	$scope.emailSendSuccess = function () {
		dialogService.alertDlg($scope.translationObj.main.msgemailsent);
	}

	// This is the function that creates the modal to send test email
	$scope.sendTestEmail = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.sendTestEmail, null, true, null);
		} else {
			if ($scope.newEmail == null) {
				$scope.newEmail = { 'emailaddress': null, 'title': null, 'mainmessage': null, 'language': 'fr-ca' };
			}
			// Send the newEmailAddress to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'emailtemplateview/sendemailaddress.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newEmail;
					}
				}
			}).result.then(function (newEmail) {
				// User clicked OK and everything was valid.
				// Use new service
				sendEmailTemplateService.sendEmailTemplate($scope.currentEmailtemplate.id, newEmail.language, newEmail.emailaddress, $scope.emailSendSuccess);
			}, function () {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	$scope.refreshAll = function () {
		$scope.getAllEmailtemplate();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'preferedlanguages', 'sequence', 'preferedlanguages');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'emailtemplateview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
