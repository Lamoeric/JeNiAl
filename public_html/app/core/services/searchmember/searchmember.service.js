/* NOT USED
*  searchmember service let the user select a member and either return the entire member object (isFullCopy==true) or
*  copy a selected number of properties from the selected member to the current member in the main scope.
*  Author: Eric Lamoureux
*/

angular.module('core').service('searchMemberService', ['$uibModal', '$http', 'anycodesService', 'authenticationService', 'translationService', function($uibModal, $http, anycodesService, authenticationService, translationService) {

	// This is the function that creates the modal to search members
	this.searchMember = function(scope, member, formObj, isFullCopy, callback) {
		anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(),'provinces', 'text', 'provinces');
		translationService.getTranslation(scope, 'core/directives/searchmember', authenticationService.getCurrentLanguage());
		var searchParams = {phpfilename:'./core/directives/searchmember/searchmember.php'};
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
						return searchParams;
					}
				}
		})
		.result.then(function(selectedObject) {
			// User clicked OK and everything was valid.
			if (isFullCopy) {
//				if (!member) member = {};
//				scope.member = angular.copy(selectedObject);
				scope.member = selectedObject;
				formObj.$dirty = true;
//				callback();
			} else {
				if (!member) member = {};
				member.address1 		= selectedObject.address1;
				member.address2 		= selectedObject.address2;
				member.town 				= selectedObject.town;
				member.province 		= selectedObject.province;
				member.postalcode 	= selectedObject.postalcode;
				member.country 			= selectedObject.country;
				member.homephone 		= selectedObject.homephone;
				member.cellphone 		= selectedObject.cellphone;
				member.otherphone 	= selectedObject.otherphone;
				member.email 				= selectedObject.email;
				member.email2 			= selectedObject.email2;
				formObj.$dirty 			= true;
			}
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

}]);
