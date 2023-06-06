// This service handles the showing of an agreement text. User must accepts for this services to return true.
angular.module('core').service('agreementdialog', ['$uibModal', '$http', '$sce', 'anycodesService', 'dialogService', 'translationService', 'authenticationService', function($uibModal, $http, $sce, anycodesService, dialogService, translationService, authenticationService) {
	thisAgreementDialog = this;
	// This is the function that creates the modal to display the agreement text.
	this.showAgreement = function(scope, agreement) {
		scope.scrolldown = false;
		var newAgreement = {};
		if (agreement.paragraphs != null) {
			newAgreement.agreement = agreement;
		} else {
			newAgreement.agreement =  $sce.trustAsHtml(agreement);
		}
		translationService.getTranslation(scope, 'core/services/agreementdialog', authenticationService.getCurrentLanguage());
		// Set the callback function for the scrolldetector directive. When user scrolls all the way down, this function is called
		scope.test = function() {
			scope.scrolldown = true;
		};
		// Send the newAgreement to the modal form
		return $uibModal.open({
				animation: false,
				templateUrl: './core/services/agreementdialog/showagreement.template.html',
				controller: 'childeditor.controller',
				scope: scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return newAgreement;
					}
				}
			})
			.result.then(function(newAgreement) {
				// User clicked OK and everything was valid.
				return true;
			}, function() {
				// User clicked CANCEL.
				return false;
			});
	};


}]);
