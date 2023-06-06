/* Directive to display errors and warning on top of the page
*  Adds the globalErrorMessage and globalWarningMessage to the scope
*  Author Eric Lamoureux, 2018-10-02
*/
angular.module('core').directive( "displayerror", [function() {
	return {
		template:'<div class="alert alert-danger" id="mainglobalerrormessage" style="display:none;"><li ng-repeat="error in globalErrorMessage">{{error}}</li></div><div class="alert alert-warning" id="mainglobalwarningmessage" style="display:none;"><li ng-repeat="warning in globalWarningMessage">{{warning}}</li></div>',

		link: function(scope, element, attrs) {
			scope.globalErrorMessage = [];
      scope.globalWarningMessage = [];
		}
	}
}]);
