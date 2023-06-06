angular.module('core').directive( "genHealthCareNo", function() {
	return {
//		restrict: "A",
//		transclude: true,
		scope: {
			member: '=member'
		},
		link: function( scope, element, attrs ) {
			element.bind( "click", function() {
				var healthCareNumber = '';
				if (scope.member && (!scope.member.healthcareno || scope.member.healthcareno == "")) {
					if (scope.member.lastname && scope.member.lastname != '') {
						healthCareNumber = scope.member.lastname.substr(0, 3).toUpperCase()
						if (scope.member.firstname && scope.member.firstname != '') {
							healthCareNumber += scope.member.firstname.substr(0, 1).toUpperCase();
							if (scope.member.birthday && scope.member.birthday != '') {
								healthCareNumber += scope.member.birthday.substr(2, 2); // Year
								healthCareNumber += (scope.member.gender == 'F' ? ((scope.member.birthday.substr(5, 2)/1)+50).toString() : scope.member.birthday.substr(5, 2));	// Month
								healthCareNumber += scope.member.birthday.substr(8, 2);	// Day
								scope.member.healthcareno = healthCareNumber;
								scope.$apply();
							}
						}
					}
				}
  		});
		}
	}
});
 