angular.module('core').directive('contactCopyFromMember', ['authenticationService', 'translationService', function(authenticationService, translationService) {
  return {
    restrict: 'E',
		scope:false,
//    scope: {
//      contact: '=',
//      member: '=',
//    },
		template:'<button class="btn btn-primary">{{translationObj.main.buttontitlecopyfrommember}}</button>',
		link: function( scope, element, attrs ) {
			element.bind("click", function() {
		    scope.newObj.lastname  = (!scope.newObj.lastname  ? scope.$resolve.member.lastname  : scope.newObj.lastname);
		    scope.newObj.homephone = (!scope.newObj.homephone ? scope.$resolve.member.homephone : scope.newObj.homephone);
		    scope.newObj.cellphone = (!scope.newObj.cellphone ? scope.$resolve.member.cellphone : scope.newObj.cellphone);
		    scope.newObj.email     = (!scope.newObj.email 		? scope.$resolve.member.email     : scope.newObj.email);
		    scope.$apply();
			});
      translationService.getTranslation(scope, 'core/directives/contacts', authenticationService.getCurrentLanguage());
		}
  };
}]);
