angular.module('core').service('listsService', ['dialogService', '$http', function(dialogService, $http) {

	this.getAllPrivileges = function ($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllPrivileges'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.privileges = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllPages = function ($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllPages'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.pagelist = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllRoles = function ($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllRoles'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.roles = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllTests = function ($scope, testtype, prop, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'testtype' : testtype, 'type' : 'getAllTests'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope[prop] = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllStarTests = function ($scope, testtype, prop, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'testtype' : testtype, 'type' : 'getAllStarTests'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope[prop] = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllTestsEx = function ($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllTestsEx'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.allTests = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllTestsForMember = function ($scope, testtype, memberid, prop, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'testtype' : testtype, 'memberid' : memberid, 'type' : 'getAllTestsForMember'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
					$scope[prop] = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllStarTestsForMember = function ($scope, testtype, memberid, prop, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'testtype' : testtype, 'memberid' : memberid, 'type' : 'getAllStarTestsForMember'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
					$scope[prop] = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllTestLevelsByType = function ($scope, testtype, prop, preferedlanguage) {
		return $http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'testtype' : testtype, 'type' : 'getAllTestLevelsByType'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
					$scope[prop] = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getCoaches = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getCoaches'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.coaches = data.data;
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllCoaches = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllCoaches'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.allcoaches = data.data;
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getPartners = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getPartners'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.allPartners = data.data;
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllTestDirectors = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllTestDirectors'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.allTestDirectors = data.data;
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllTestSessions = function($scope, preferedlanguage) {
		return ($http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllTestSessions'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.allTestsSessions = data.data;
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    }));
	};

	this.getAllSessionsAndShows = function($scope, preferedlanguage, callback) {
		return ($http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllSessionsAndShows'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.allSessionsAndShows = data.data;
	    		if (callback) callback();
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    }));
	};

	this.getTestPeriodsForSession = function($scope, testsessionid, preferedlanguage) {
		return ($http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'testsessionid' : testsessionid, 'language' : preferedlanguage, 'type' : 'getTestPeriodsForSession'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.testPeriodsForSession = data.data;
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    }));
	};

	this.getAllJudgesForPeriod = function($scope, day, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'day' : day, 'type' : 'getAllJudgesForPeriod'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.allJudgesForPeriod = data.data;
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllJudges = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllJudges'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.allJudges = data.data;
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllProgramAssistants = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllProgramAssistants'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
					if (!angular.isUndefined(data.data)) {
	    			$scope.programassistants = data.data;
					}
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllProgramAssistantHelpers = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllProgramAssistantHelpers'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
					if (!angular.isUndefined(data.data)) {
	    			$scope.programassistanthelpers = data.data;
					}
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllCharges = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllCharges'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
					if (!angular.isUndefined(data.data)) {
	    			$scope.charges = data.data;
					}
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllCourses = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllCourses'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
					if (!angular.isUndefined(data.data)) {
	    			$scope.courses = data.data;
					}
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllCoursesForRules = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllCoursesForRules'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
					if (!angular.isUndefined(data.data)) {
	    			$scope.courses = data.data;
					}
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllCourseLevels = function($scope, preferedlanguage, coursecode) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'coursecode' : coursecode, 'language' : preferedlanguage, 'type' : 'getAllCourseLevels'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.courselevels = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllTestsDefinitions = function($scope, $http, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllTestsDefinitions'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.testdefinitions = data.data;
	    	} else {
	    		if (!data.success) {
	    			dialogService.displayFailure(data);
	    		}
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllCanskateids = function($scope, $http, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllCanskateids'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.canskateids = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllCanskateTests = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllCanskateTests'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.canskateTests = data.data;
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllSessions = function($scope, $http, preferedlanguage, callback) {
		$http({
      method: 'post',
      url: './core/services/lists/lists.php',
      data: $.param({'language' : preferedlanguage, 'type' : 'getAllSessions'}),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.sessions = data.data;
					if (callback) {
						callback();
					}
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getAllSessionsEx = function($scope, preferedlanguage, callback, exception) {
		$http({
      method: 'post',
      url: './core/services/lists/lists.php',
      data: $.param({'language' : preferedlanguage, 'exception' : exception, 'type' : 'getAllSessionsEx'}),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.sessions = data.data;
					if (callback) {
						callback();
					}
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getActiveSession = function($scope, $http, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getActiveSession'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.activeSession = data.data[0];
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getAllActiveCourses = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllActiveCourses'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.activeCourses = data.data;
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getAllSessionCourses = function($scope, sessionid, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'sessionid' : sessionid, 'language' : preferedlanguage, 'type' : 'getAllSessionCourses'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.sessionCourses = data.data;
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getAllActiveCoursesWithSubGroups = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllActiveCoursesWithSubGroups'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.activeCoursesWithSubgroups = data.data;
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	// Get the course date for a range of today - 1 day <> today + 1 day
	this.getRangeCourseDates = function($scope, sessionscoursesid, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'sessionscoursesid' : sessionscoursesid, 'type' : 'getRangeCourseDates'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success /*&& !angular.isUndefined(data.data)*/ ){
    		$scope.AllCourseDates = data.data;
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getDanceMusics = function($scope, testsid, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'testsid' : testsid, 'language' : preferedlanguage, 'type' : 'getDanceMusics'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.danceMusics = data.data;
				} else {
					$scope.danceMusics = [];
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getAllDanceMusics = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllDanceMusics'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success && !angular.isUndefined(data.data)) {
    		$scope.allDanceMusics = data.data;
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getAllClubs = function ($scope, preferedlanguage) {
		return $http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllClubs'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success) {
					if (!angular.isUndefined(data.data)) {
	    			$scope.homeclubs = data.data;
					}
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	this.getAllWsDocuments = function($scope, preferedlanguage, callback) {
		$http({
      method: 'post',
      url: './core/services/lists/lists.php',
      data: $.param({'language' : preferedlanguage, 'type' : 'getAllWsDocuments'}),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.wsdocuments = data.data;
					if (callback) {
						callback();
					}
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getWsSectionsForPage = function($scope, preferedlanguage, pagename, callback) {
		$http({
      method: 'post',
      url: './core/services/lists/lists.php',
      data: $.param({'language' : preferedlanguage, 'pagename' : pagename, 'type' : 'getWsSectionsForPage'}),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.wssections = data.data;
					if (callback) {
						callback();
					}
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getAllWsPages = function($scope, preferedlanguage, callback) {
		$http({
      method: 'post',
      url: './core/services/lists/lists.php',
      data: $.param({'language' : preferedlanguage, 'type' : 'getAllWsPages'}),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.wspages = data.data;
					if (callback) {
						callback();
					}
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	this.getMemberEmails = function($scope, memberid, preferedlanguage, callback) {
		return $http({
				method: 'post',
				url: './core/services/lists/lists.php',
				data: $.param({'language' : preferedlanguage, 'memberid' : memberid, 'type' : 'getMemberEmails'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success && !angular.isUndefined(data.data) ) {
					$scope.memberemails = data.data;
					if (callback) {
						callback();
					}
				} else {
					dialogService.displayFailure(data);
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
			});
	}

	/**
	 * This function gets all the rooms for an ice in a arena. If iceid is null, get all the rooms for the arena
	 */
	this.getAllArenaRooms = function($scope, arenaid, iceid, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'arenaid': arenaid, 'iceid': iceid, 'language' : preferedlanguage, 'type' : 'getAllArenaRooms'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.arenaRooms = data.data;
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

	/**
	 * This function gets all the possible tasks for a show
	 */
	this.getAllShowTasks = function($scope, preferedlanguage) {
		$http({
	      method: 'post',
	      url: './core/services/lists/lists.php',
	      data: $.param({'language' : preferedlanguage, 'type' : 'getAllShowTasks'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
    	if (data.success) {
				if (!angular.isUndefined(data.data)) {
    			$scope.showTasks = data.data;
				}
    	} else {
    		dialogService.displayFailure(data);
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};
}]);
