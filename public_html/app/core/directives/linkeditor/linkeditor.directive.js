// This directive creates a button, that when clicked, creates a link editor dialog box
// The created/modified link is directly copied into the link passed in parameter to the directive.
// The state of the form including this directive will be set to dirty if a link is created/modified.
//	inputs :
// 		type : 1 = icon is a pen, 2 = icon is a +
//		link : link object into which to copy the created/modified test registration.
//		parent : parent object. Needed to attach the link to the proper object.
//		isDisabled : expression to determine if button is disabled or not.
//		callback : callback function to call.
angular.module('core').directive("editlink", ['$uibModal', '$http', 'listsService', 'anycodesService', 'authenticationService', 'translationService', 'dateFilter', function ($uibModal, $http, listsService, anycodesService, authenticationService, translationService, dateFilter) {
	return {
		require: '^form',				// To set the $dirty flag after copying the created/modified test registration
		template: '<button class="btn btn-primary glyphicon glyphicon-pencil" ng-disabled="isDisabled" id="editlink" ng-if="type==1"></button><button class="btn btn-primary glyphicon glyphicon-plus" ng-disabled="isDisabled" id="editlink" ng-if="type==2"></button>',
		scope: {
			type: '=',
			link: '=link',
			parent: '=parent',
			isDisabled: '=isDisabled',
			languages:'=',
			callback: '&callback',
		},

		link: function (scope, element, attrs, formCtrl) {
			scope.internalControl = {};			// This object holds all the functions needed by the HTML template. HTML must use control.xxx() to call such function.
			element.bind("click", function () {
				scope.formObj = formCtrl;
				if (!scope.link) scope.link = {};
				editLink(scope.parent, scope.link);
			});

			// Called when user clicks on the edit link button.
			// This function opens a dialog box to edit a link
			function editLink(parent, newLink) {
				translationService.getTranslation(scope, 'core/directives/linkeditor', authenticationService.getCurrentLanguage());
				var userInfo = authenticationService.getUserInfo();
				scope.newLink = {};
				// Keep a pointer to the current registration
				if (newLink && newLink.id) {
					scope.currentLink = newLink;
					// Copy in another object
					angular.copy(newLink, scope.newLink);
					scope.newLink.parent = parent;
					if (scope.newLink.linkpage && scope.newLink.linkpage != '') {
						listsService.getWsSectionsForPage(scope, authenticationService.getCurrentLanguage(), scope.newLink.linkpage);
					}
				} else {
					scope.currentLink = {};
					// This is a new link
					// Need to set the parent id somehow. This id may change wether it's a link for a section or a news
					// scope.newLink['newsid'] = parent.id;

				}
				$uibModal.open({
					animation: false,
					templateUrl: './core/directives/linkeditor/editlink.template.html',
					controller: 'childeditorex.controller',
					scope: scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
						newObj: function () { return scope.newLink; },				// The object to edit
						control: function () { return scope.internalControl; },		// The control object containing all validation functions
						callback: function () { return null; }						// Callback function to overwrite the normal validation
					}
				}).result.then(function (newLink) {
					// User clicked OK and everything was valid.
					angular.copy(newLink, scope.currentLink);
					// If already saved in DB, put status to Modified, else to New
					if (scope.currentLink.id != null) {
						scope.currentLink.status = 'Modified';
					} else {
						scope.currentLink.status = 'New';
						if (parent.links == null) parent.links = [];
						// Don't insert twice in list
						if (parent.links.indexOf(scope.currentLink) == -1) {
							parent.links.push(scope.currentLink);
						}
					}
					if (scope.callback) scope.callback();
					scope.formObj.$dirty = true;
				}, function () {
					// User clicked CANCEL.
				});
			}

			// Called when user changes the link type field
			// Used by the editlink.template.html form
			scope.internalControl.onLinkTypeChange = function (newObj) {
				if (newObj) {
					newObj.linkpage = null;
					newObj.linksection = null;
					newObj.linkdocumentid = null;
					newObj.linkexternal = null;
				}
			}

			// Called when user changes the linkpage field
			// Used by the editlink.template.html form
			scope.internalControl.onLinkPageChange = function (newObj) {
				if (newObj) {
					if (newObj.linktype == '1') {
						newObj.linksection = null;
						if (newObj.linkpage && newObj.linkpage != '') {
							listsService.getWsSectionsForPage(scope, authenticationService.getCurrentLanguage(), newObj.linkpage);
						}
					}
				}
			}

			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'wslinktypes', 'sequence', 'wslinktypes');
			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'wslinkpositions', 'sequence', 'wslinkpositions');
			listsService.getAllWsDocuments(scope, authenticationService.getCurrentLanguage());
			listsService.getAllWsPages(scope, authenticationService.getCurrentLanguage());
		}
	}
}]);
