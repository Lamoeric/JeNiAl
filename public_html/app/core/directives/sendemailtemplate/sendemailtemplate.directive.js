// This directive creates a button, that when clicked, creates a "send email template" dialog box
//	inputs :
//		contacts : aray of contact objects to display. {'firstname':null, 'lastname':null,'email':null}
//		isFormPristine : expression to determine if button is disabled or not
angular.module('core').directive("sendemailtemplate", ['$uibModal', '$http', 'anycodesService', 'authenticationService', 'translationService', 'dialogService', 'listsService', 'sendEmailTemplateService', function ($uibModal, $http, anycodesService, authenticationService, translationService, dialogService, listsService, sendEmailTemplateService) {
	return {
		template: '<button class="btn btn-primary glyphicon glyphicon-envelope" ng-disabled="isFormPristine" id="sendemailtemplate"></button>',
		scope: {
			contacts: '=contacts',
			isFormPristine: '=isFormPristine',
			callback: '=callback',
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

			/**
			 * This function validates that the form is valid. Must select a template and an email address.
			 * This is a callback function called by the modal
			 * @param {*} editObjForm 
			 * @param {*} newObj 
			 * @returns null or a component to turn on to indicate the error
			 */
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

			/**
			 * This function creates the modal to display the list of contacts and their emails
			 */
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
						newObj: function () { return scope.newObj; },		    	// The object to edit
						control: function () { return scope.internalControl; },		// The control object containing all validation functions
						callback: function () { return scope.validateForm; }		// Callback function to overwrite the normal validation
					}
				}).result.then(function (selectedObject) {
					// User clicked OK and everything was valid.
					/* Now, send email to all the selected contacts */
					for (var i = 0; i < selectedObject.contacts.length; i++) {
						// For each emeil selected, send the template email
						if (selectedObject.contacts[i].selected && selectedObject.contacts[i].selected == '1') {
							sendEmailTemplateService.sendEmailTemplate(selectedObject.templateselected.id, selectedObject.language, selectedObject.contacts[i].email, null);
						}
					}
					if (scope.callback) scope.callback();
				}, function () {
					// User clicked CANCEL.
				});
			};
			translationService.getTranslation(scope, 'core/directives/sendemailtemplate', authenticationService.getCurrentLanguage());
		}
	}
}]);
