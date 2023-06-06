// Adds the $scope.courseSublevels array with all the possible sub levels for the course. 
// Also adds a empty choice.
angular.module('core').service('sessionCourseSublevelService', ['dialogService', function(dialogService) {
	
	this.getCourseSublevelCodes = function ($scope, $http, sessionscoursesid, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/coursesublevel/coursesublevel.php',
	      data: $.param({'sessionscoursesid' : sessionscoursesid, 'language' : preferedlanguage}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if(data.success && !angular.isUndefined(data.data) ){
	    		$scope.courseSublevels = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

}]);