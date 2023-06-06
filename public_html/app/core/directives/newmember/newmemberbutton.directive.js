angular.module('core').directive( "newMemberButton", ['authenticationService', 'translationService', function(authenticationService, translationService) {
	return {
//		restrict: "A",
//		transclude: true,
//		scope: false,
		template:'<button class="btn btn-primary" ng-disabled="isFormPristine">{{translationObj.newmember.buttontitlenew}}</button>',
    scope: {
      member: '=member',
      isFormPristine: '=isFormPristine',
      callback: '&callback'
    },

		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
				initMember();
  		});

  		function initMember() {
				scope.member = {};
				scope.member.id 					= null;
				scope.member.language 		= 'F';
				scope.member.gender 			= 'F';
				scope.member.town 				= 'Montréal';
				scope.member.province 		= 'QC';
				scope.member.country 			= 'CAN';
				scope.member.homephone 		= '(514) ';
				scope.memberid = null;
				scope.$apply();
  		}

			translationService.getTranslation(scope, 'core/directives/newmember', authenticationService.getCurrentLanguage());

		}
	}
}]);
