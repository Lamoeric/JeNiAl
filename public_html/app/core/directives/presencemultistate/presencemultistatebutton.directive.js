angular.module('core').directive( "presenceMultistateButton", [function() {
	return {
		template: '<button class="btn" ng-class="{\'btn-primary\':state==\'1\',\'btn-danger\':state!=\'1\'}" ng-if="state!=\'---\'">{{newStatelist[state]}}</button><h4 ng-if="state==\'---\'">---</h4>'
							// '<button class="btn btn-primary" ng-show="obj==statelist[0].code">{{statelist[0].text}}</button>' +
							// '<button class="btn btn-primary" ng-show="obj==statelist[1].code">{{statelist[1].text}}</button>' +
							// '<button class="btn btn-primary" ng-show="obj==statelist[2].code">{{statelist[2].text}}</button>' +
							// '<button class="btn btn-primary" ng-show="obj==statelist[3].code">{{statelist[3].text}}</button>' +
							// '<button class="btn btn-primary" ng-show="obj==statelist[4].code">{{statelist[4].text}}</button>' +
							// '<button class="btn btn-primary" ng-show="obj==statelist[5].code">{{statelist[5].text}}</button>'

							,
    scope: {
			obj: '=',
      state: '=',
      statelist: '=',
      callback: '&callback'
    },

		link: function( scope, element, attrs ) {
			scope.newStatelist = [];
			for (var i = 0; i < scope.statelist.length; i++) {
				scope.newStatelist[scope.statelist[i].code] = scope.statelist[i].text;
			}
			element.bind( "click", function() {
				var finalIndex = 0;
				for (var i = 0; i < scope.statelist.length; i++) {
					if (scope.state == scope.statelist[i].code) {
						if (i < scope.statelist.length -1) {
							finalIndex = i+1;
							break;
						}
					}
				}
				scope.state = scope.statelist[finalIndex].code;
				scope.callback({newPresence:scope.state});
  		});
		}
	}
}]);
