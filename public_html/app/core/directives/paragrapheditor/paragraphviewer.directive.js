// This directive creates a form to display all the paragraphs fromm a list
//  @author  Eric Lamoureux
//  inputs :
//    paragraphlist : the list of paragraph to display.
//    setParentDirty : the name of the function to call to make the object dirty
angular.module('core').directive('paragraphviewer', ['translationService', 'authenticationService', '$uibModal', 'anycodesService', '$http', function(translationService, authenticationService, $uibModal, anycodesService, $http) {
	return {
		restrict: 'E',
		scope: {
			paragraphlist: '=',
			setParentDirty: '&setDirty',
			convertParagraph: '&convert'
		},
		templateUrl: './core/directives/paragrapheditor/paragraphviewer.template.html',
		link: function(scope, element, attrs) {
			scope.languageSelected = 1; //French

			// This function redirects the setDirty from the underlying directives, like childDelete
			scope.setDirty = function (paragraph) {
				scope.convertParagraph({paragraph:paragraph});
				scope.setParentDirty();
				return;
			}
			
			scope.dropCallback = function(index, item, external, type) {
				scope.convertParagraph({paragraph:item});
				scope.setParentDirty();
				// Return false here to cancel drop. Return true if you insert the item yourself.
				return item;
			};

			
			// This function redirects the editContact from the underlying directive childEdit
			scope.editParagraph = function(newParagraph) {
				scope.newParagraph = {};
				scope.currentParagraph = newParagraph;
				angular.copy(newParagraph, scope.newParagraph);
				scope.newParagraph.languageSelected = scope.languageSelected;
				
				$uibModal.open({
						animation: false,
						templateUrl: './core/directives/paragrapheditor/newparagraph.template.html',
						controller: 'childeditorex.controller',
						scope: scope,
						size: 'lg',
						backdrop: 'static',
						resolve: {
							newObj:   function() {return scope.newParagraph;},            // The object to edit
							control:  function() {return scope.editParagraphControl;},    // The control object containing all validation functions
							callback: function() {return null;}                           // Callback function to overwrite the normal validation
						}
				})
				.result.then(function(newParagraph) {
					// User clicked OK and everything was valid.
					angular.copy(newParagraph, scope.currentParagraph);
					if (scope.currentParagraph.id != null) {
						scope.currentParagraph.status = 'Modified';
					} else {
						scope.currentParagraph.status = 'New';
						if (scope.paragraphlist == null) scope.paragraphlist = [];
						if (scope.paragraphlist.indexOf(scope.currentParagraph) == -1) {
							scope.paragraphlist.push(scope.currentParagraph);
						}
					}
					scope.setDirty(scope.currentParagraph);
				}, function() {
					// User clicked CANCEL.
					// alert('canceled');
				});
			}

			translationService.getTranslation(scope, 'core/directives/paragrapheditor', authenticationService.getCurrentLanguage());
			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'yesno',     'text',     'yesnos');
			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'paragraphlanguages', 'sequence', 'paragraphlanguages');

		}
	};
}]);
