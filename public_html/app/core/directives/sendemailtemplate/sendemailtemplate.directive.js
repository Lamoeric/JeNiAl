// This directive creates a button, that when clicked, creates a "send email template" dialog box
//	inputs :
//		contacts : aray of contact objects to display. {'firstname':null, 'lastname':null,'email':null}
//		isFormPristine : expression to determine if button is disabled or not
angular.module('core').directive("sendemailtemplate", ['$uibModal', '$http', 'anycodesService', 'authenticationService', 'translationService', 'dialogService', 'listsService', function ($uibModal, $http, anycodesService, authenticationService, translationService, dialogService, listsService) {
	return {
		template: '<button class="btn btn-primary glyphicon glyphicon-envelope" ng-disabled="isFormPristine" id="sendemailtemplate"></button>',
		scope: {
			contacts: '=contacts',
			isFormPristine: '=isFormPristine',
		},

		link: function (scope, element, attrs, formCtrl) {
			element.bind("click", function () {
				scope.formObj = formCtrl;
				if (scope.newObj == null) {
					scope.newObj = { 'language': 'fr-ca' };
				}
				scope.remarkable = new Remarkable({
					html: false,        // Enable HTML tags in source
					xhtmlOut: false,        // Use '/' to close single tags (<br />)
					breaks: false         // Convert '\n' in paragraphs into <br>
				});
				scope.newObj.contacts = scope.contacts;
				showContactsEmails();
			});

			/* Must select a template and an email address */
			scope.validateForm = function (editObjForm, newObj) {
				if (editObjForm.$invalid) {
					return "#editObjFieldMandatory";
				} else {
					for (var x = 0; newObj.contacts && x < newObj.contacts.length; x++) {
						if (newObj.contacts[x].selected == 1) {
							return null;
						}
					}
				}
				return "#editObjFieldMandatory";
			}

			function sendEmailTemplate(selectedObject) {
				scope.selectedObject = selectedObject;
				/* Retrieve the email template details */
				scope.promise = $http({
					method: 'post',
					url: './core/directives/sendemailtemplate/sendemailtemplate.php',
					data: $.param({ 'id': selectedObject.templateselected.id, 'language': selectedObject.language, 'type': 'getEmailtemplateDetails' }),
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
				}).success(function (data, status, headers, config) {
					if (data.success && !angular.isUndefined(data.data)) {
						scope.currentEmailtemplate = data.data[0];
						scope.newEmail = { 'emailaddress': null, 'title': scope.currentEmailtemplate.title, 'mainmessage': scope.remarkable.render(scope.currentEmailtemplate.paragraphtext), 'language': scope.selectedObject.language };
						for (var i = 0; i < scope.selectedObject.contacts.length; i++) {
							if (scope.selectedObject.contacts[i].selected && scope.selectedObject.contacts[i].selected == '1') {
								scope.newEmail.emailaddress = scope.selectedObject.contacts[i].email;
								/* This is where we need to send the email */
								scope.promise = $http({
									method: 'post',
									url: './core/directives/sendemailtemplate/sendemailtemplate.php',
									data: $.param({ 'newEmail': scope.newEmail, 'type': 'sendTestEmail' }),
									headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
								}).success(function (data, status, headers, config) {
									if (data.success) {
										dialogService.alertDlg(scope.translationObj.main.msgemailsent);
										return;
									} else {
										dialogService.displayFailure(data);
									}
								}).error(function (data, status, headers, config) {
									dialogService.displayFailure(data);
								});
							}
						}

					} else {
						dialogService.displayFailure(data);
					}
				}).error(function (data, status, headers, config) {
					dialogService.displayFailure(data);
				});
			}

			// This is the function that creates the modal to display the list of contacts and their emails
			function showContactsEmails() {
				translationService.getTranslation(scope, 'core/directives/sendemailtemplate', authenticationService.getCurrentLanguage());
				listsService.getAllEmailTemplates(scope, authenticationService.getCurrentLanguage());
				anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'preferedlanguages', 'sequence', 'preferedlanguages');
				// Send the parameters to the modal form
				$uibModal.open({
					animation: false,
					templateUrl: './core/directives/sendemailtemplate/sendemailtemplate.template.html',
					controller: 'childeditorex.controller',
					scope: scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function () { return scope.newObj; },		    		// The object to edit
						control: function () { return scope.internalControl; },		// The control object containing all validation functions
						callback: function () { return scope.validateForm; }				// Callback function to overwrite the normal validation
					}
				}).result.then(function (selectedObject) {
					// User clicked OK and everything was valid.
					/* Now, send email to all the selected contacts */
					sendEmailTemplate(selectedObject);
					return;
				}, function () {
					// User clicked CANCEL.
				});
			};
			translationService.getTranslation(scope, 'core/directives/sendemailtemplate', authenticationService.getCurrentLanguage());
		}
	}
}]);
