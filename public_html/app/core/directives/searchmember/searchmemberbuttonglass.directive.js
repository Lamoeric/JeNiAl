// This directive creates a button, that when clicked, creates a search member dialog box
// The selected member is directly copied into the member passed in parameter to the directive.
// The state of the form including this directive will be set to dirty if a member is selected. 
//	inputs :
//		member : member object into which to copy selected member
//		isFormPristine : expression to determine if button is disabled or not
//		isFullCopy : false to copy some info from member or true to copy full member info
//		callback : callback function to call for copy full member only.
angular.module('core').directive( "searchMemberButtonGlass", ['$uibModal', '$http', 'anycodesService', 'authenticationService', 'translationService', function($uibModal, $http, anycodesService, authenticationService, translationService) {
	return {
		require: '^form',				// To set the $dirty flag after copying the new member
		template:'<i class="pointerlist glyphicon glyphicon-search" ng-disabled="isFormPristine" id="searchmember"></i>',
		scope: {
			member: '=member',
			isFormPristine: '=isFormPristine',
			isFullCopy: '=',
			callback: '&callback',
		},

		link: function( scope, element, attrs, formCtrl) {
			element.bind( "click", function() {
				scope.formObj = formCtrl;
				searchMember();
			});

			scope.customSearch = function(actual, expected) { 
        // Ignore object.
        if (angular.isObject(actual)) return false;
        function removeAccents(value) {
            return value.toString()
                                  .replace(/á/g, 'a') 
                                  .replace(/â/g, 'a')
                                  .replace(/à/g, 'a')
                                  .replace(/é/g, 'e')
                                  .replace(/è/g, 'e')
                                  .replace(/ê/g, 'e')
                                  .replace(/ë/g, 'e')
                                  .replace(/í/g, 'i')
                                  .replace(/ï/g, 'i')
                                  .replace(/ì/g, 'i')
                                  .replace(/ó/g, 'o')
                                  .replace(/ô/g, 'o')
                                  .replace(/ú/g, 'u')
                                  .replace(/ü/g, 'u')
                                  .replace(/û/g, 'u')
                                  .replace(/ç/g, 'c');
        }
        actual = removeAccents(angular.lowercase('' + actual));
        expected = removeAccents(angular.lowercase('' + expected));

        return actual.indexOf(expected) !== -1;
      }

			// This is the function that creates the modal to search members
			function searchMember() {
				scope.searchParams = {phpfilename:'./core/directives/searchmember/searchmember.php',
															exceptionmemberid:scope.member ? scope.member.id : null,
															language:authenticationService.getCurrentLanguage()};
				translationService.getTranslation(scope, 'core/directives/searchmember', authenticationService.getCurrentLanguage());
				// Send the parameters to the modal form
				$uibModal.open({
						animation: false,
						templateUrl: './core/directives/searchmember/searchmembers.template.html',
						controller: 'searcheditor.controller',
						scope: scope,
						size: 'xl',
						backdrop: 'static',
						resolve: {
							searchParams: function() {
								return scope.searchParams;
							}
						}
				})
				.result.then(function(selectedObject) {
					// User clicked OK and everything was valid.
					if (scope.isFullCopy) {
						scope.member = selectedObject;
						scope.formObj.$dirty = true;
						scope.callback({member:selectedObject, forced:true});
					} else {
						if (!scope.member) scope.member = {};
						scope.member.lastname 		= selectedObject.lastname;
						scope.member.address1 		= selectedObject.address1;
						scope.member.address2 		= selectedObject.address2;
						scope.member.town 				= selectedObject.town;
						scope.member.province 		= selectedObject.province;
						scope.member.postalcode 	= selectedObject.postalcode;
						scope.member.country 			= selectedObject.country;
						scope.member.homephone 		= selectedObject.homephone;
						scope.member.cellphone 		= selectedObject.cellphone;
						scope.member.otherphone 	= selectedObject.otherphone;
						scope.member.email 				= selectedObject.email;
						scope.member.email2 			= selectedObject.email2;
						scope.member.copiedfrom 	= selectedObject.id;
						scope.formObj.$dirty 			= true;
					}
				}, function() {
					// User clicked CANCEL.
				});
			};
			translationService.getTranslation(scope, 'core/directives/searchmember', authenticationService.getCurrentLanguage());
		}
	}
}]);
