angular.module('core').service('listsService', ['dialogService', '$http', function(dialogService, $http) {

	/**
	 * This creates a list from the query and puts it in the property $scope['destinationVariable']
	 * @param {*} $scope 				$scope of the destination
	 * @param {*} destinationVariable 	Name of the property in $scope where to put the list
	 * @param {*} preferedlanguage 		The language in which the list is to be created
	 * @param {*} query 				The SQL query used to create the list
	 */
	this.getSimpleListPattern1 = function ($scope, destinationVariable, preferedlanguage, query) {
		query = query.replace(/[$]language/g, preferedlanguage);
		return $http({
			method: 'post',
			url: './core/services/lists/lists.php',
			data: $.param({'query': query, 'type': 'getSimpleListPattern1' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).
		success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope[destinationVariable] = data.data;
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	}

	this.getAllPrivileges = function ($scope, preferedlanguage) {
		var query = "SELECT id, concat(code, ' - ', description) text FROM cpa_privileges order by id";
		this.getSimpleListPattern1($scope, 'privileges', preferedlanguage, query);
	};

	this.getAllPages = function ($scope, preferedlanguage) {
		var query = "SELECT *, getWSTextLabel(cws.navbarlabel, 'fr-ca') navbarlabel_fr, getWSTextLabel(cws.navbarlabel, 'en-ca') navbarlabel_en, getWSTextLabel(navbarlabel, '$language') navbarlabeltext, getWSTextLabel(label, '$language') labeltext FROM cpa_ws_pages cws ORDER BY pageindex";
		this.getSimpleListPattern1($scope, 'pagelist', preferedlanguage, query);
	};

	this.getAllRoles = function ($scope, preferedlanguage) {
		var query = "SELECT id, concat(roleid, ' - ', rolename) text FROM cpa_roles order by id";
		this.getSimpleListPattern1($scope, 'roles', preferedlanguage, query);
	};

	this.getAllTests = function ($scope, testtype, prop, preferedlanguage) {
		var query = "SELECT ct.id, getTextLabel(label, '$language') text FROM cpa_tests ct JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid WHERE ctd.type = '$testtype' AND ctd.version = 1 order by ct.sequence";
		query = query.replace("$testtype", testtype);
		this.getSimpleListPattern1($scope, prop, preferedlanguage, query);
	};

	this.getAllStarTests = function ($scope, testtype, prop, preferedlanguage) {
		var query = "SELECT ct.id, getTextLabel(label, '$language') text FROM cpa_tests ct JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid WHERE ctd.type = '$testtype' AND ctd.version = 2 order by ct.sequence";
		query = query.replace("$testtype", testtype);
		this.getSimpleListPattern1($scope, prop, preferedlanguage, query);
	};

	this.getAllTestsEx = function ($scope, preferedlanguage) {
		var query = "SELECT ct.id, getTextLabel(label, '$language') text FROM cpa_tests ct order by ct.sequence";
		this.getSimpleListPattern1($scope, 'allTests', preferedlanguage, query);
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
		var query = "SELECT code, getTextLabel(description, '$language') text FROM cpa_codetable cc WHERE cc.ctname = 'testlevels' AND cc.code IN (SELECT level FROM cpa_tests_definitions WHERE type = '$testtype' AND version = 1) ORDER BY cc.sequence";
		query = query.replace("$testtype", testtype);
		return this.getSimpleListPattern1($scope, prop, preferedlanguage, query);
	};

	this.getCoaches = function($scope, preferedlanguage) {
		var query = "SELECT id, concat(lastname, ', ', firstname) text FROM cpa_members where qualifications like '%COACH%' order by concat(lastname, ', ', firstname)";
		this.getSimpleListPattern1($scope, 'coaches', preferedlanguage, query);
	};

	this.getAllCoaches = function($scope, preferedlanguage) {
		var query = "SELECT id, concat(lastname, ', ', firstname) text FROM cpa_members where qualifications like '%COACH%' or qualifications like '%PARTNER%' or qualifications like '%CHOR%' order by concat(lastname, ', ', firstname)";
		this.getSimpleListPattern1($scope, 'allcoaches', preferedlanguage, query);
	};

	this.getPartners = function($scope, preferedlanguage) {
		var query = "SELECT id, concat(lastname, ', ', firstname) text FROM cpa_members where qualifications like '%PARTNER%' order by concat(lastname, ', ', firstname)";
		this.getSimpleListPattern1($scope, 'allPartners', preferedlanguage, query);
	};

	this.getAllTestDirectors = function($scope, preferedlanguage) {
		var query = "SELECT id, concat(lastname, ', ', firstname) text FROM cpa_members where qualifications like '%dir%' order by concat(lastname, ', ', firstname)";
		this.getSimpleListPattern1($scope, 'allTestDirectors', preferedlanguage, query);
	};

	this.getAllTestSessions = function($scope, preferedlanguage) {
		var query = "SELECT id,  getTextLabel(label, '$language') text FROM cpa_tests_sessions order by registrationstartdate DESC";
		this.getSimpleListPattern1($scope, 'allTestsSessions', preferedlanguage, query);
	};

	this.getAllSessionsAndShows = function($scope, preferedlanguage) {
		return ($http({
			method: 'post',
			url: './core/services/lists/lists.php',
			data: $.param({'language' : preferedlanguage, 'type' : 'getAllSessionsAndShows'}),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if (data.success && !angular.isUndefined(data.data)) {
	    		$scope.allSessionsAndShows = data.data;
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
		var query = "SELECT ctsdp.id, concat(ctsd.testdate, ' ', getTextLabel(ca.label, '$language'), ' ', if(ctsdp.iceid != 0, getTextLabel(cai.label, '$language'), ''), ' ', ctsdp.starttime, ' - ', ctsdp.endtime) text FROM cpa_tests_sessions_days_periods ctsdp JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctsdp.testsdaysid JOIN cpa_arenas ca ON ca.id = ctsdp.arenaid LEFT JOIN cpa_arenas_ices cai ON cai.id = ctsdp.iceid WHERE ctsd.testssessionsid = $testsessionid ORDER BY starttime";
		query = query.replace("$testsessionid", testsessionid);
		this.getSimpleListPattern1($scope, 'testPeriodsForSession', preferedlanguage, query);
	};

	this.getAllJudges = function($scope, preferedlanguage) {
		var query = "SELECT id, concat(lastname, ', ', firstname) text FROM cpa_members WHERE qualifications LIKE '%jud%' ORDER BY concat(lastname, ', ', firstname)";
		this.getSimpleListPattern1($scope, 'allJudges', preferedlanguage, query);
	};

	this.getAllProgramAssistants = function($scope, preferedlanguage) {
		var query = "SELECT id, concat(lastname, ', ', firstname) text FROM cpa_members where qualifications like '%pa,%' order by concat(lastname, ', ', firstname)";
		this.getSimpleListPattern1($scope, 'programassistants', preferedlanguage, query);
	};

	this.getAllProgramAssistantHelpers = function($scope, preferedlanguage) {
		var query = "SELECT id, concat(lastname, ', ', firstname) text FROM cpa_members where qualifications like '%pah,%' order by concat(lastname, ', ', firstname)";
		this.getSimpleListPattern1($scope, 'programassistanthelpers', preferedlanguage, query);
	};

	/**
	 * Get all charges. By default, do not get the system charges, like specialcharge and specialdiscount. By default, also include non active charges.
	 * @param {*} $scope 
	 * @param {*} preferedlanguage 
	 * @param {*} includesystem if true, include system charges. Default is false.
	 * @param {*} includenonactive if true, include non active charges. Default is true.
	 */
	this.getAllCharges = function($scope, preferedlanguage, includesystem, includenonactive) {
		// Transforms boolean to numeric
		includesystem =  (!includesystem) ? 0 : 1;
		includenonactive =  (includenonactive==undefined) ? 1 : (includenonactive==true) ? 1 : 0;
		$http({
			method: 'post',
			url: './core/services/lists/lists.php',
			data: $.param({'language' : preferedlanguage, 'includesystem' : includesystem, 'includenonactive' : includenonactive, 'type' : 'getAllCharges'}),
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
		var query = "SELECT code, getTextLabel(label, '$language') label FROM cpa_courses order by label";
		this.getSimpleListPattern1($scope, 'courses', preferedlanguage, query);
	};

	this.getAllCoursesForRules = function($scope, preferedlanguage) {
		var query = "SELECT code, getTextLabel(label, '$language') label FROM cpa_courses UNION SELECT 'SHOWNUMBER' as code, if ('$language' = 'fr-ca',  'Numero de spectacle (interne)',  'Show number (Internal)') as label FROM cpa_courses order by label";
		this.getSimpleListPattern1($scope, 'courses', preferedlanguage, query);
	};

	this.getAllCourseLevels = function($scope, preferedlanguage, coursecode) {
		var query = "SELECT code, getTextLabel(label, '$language') label FROM cpa_courses_levels WHERE coursecode = '$coursecode'";
		query = query.replace("$coursecode", coursecode);
		this.getSimpleListPattern1($scope, 'courselevels', preferedlanguage, query);
	};

	this.getAllTestsDefinitions = function($scope, preferedlanguage) {
		var query = "SELECT id, convert(concat(getCodeDescription('testtypes', type, '$language'), '/', getCodeDescription('testlevels', level, '$language'), if(subtype != '', concat('/',  getCodeDescription('testsubtypes', subtype, '$language')), '')) using utf8) description, type, subtype, level, version FROM cpa_tests_definitions WHERE version = 1 UNION SELECT id, concat(getCodeDescription('testtypes', type, '$language'), '/STAR ', level) description, type, subtype, level, version FROM cpa_tests_definitions WHERE version = 2 ORDER BY version, type, subtype, cast(level as DECIMAL)";
		this.getSimpleListPattern1($scope, 'testdefinitions', preferedlanguage, query);
	};

	this.getAllCanskateids = function($scope, preferedlanguage) {
		var query = "SELECT id, category, stage FROM cpa_canskate order by category, stage";
		this.getSimpleListPattern1($scope, 'canskateids', preferedlanguage, query);
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

	this.getAllSessions = function($scope, preferedlanguage) {
		var query = "SELECT id, concat(getTextLabel(label, '$language'), if(active=1, ' (active)', '')) label, coursesstartdate, coursesenddate, active, getTextLabel(label, '$language') origlabel FROM cpa_sessions cs order by active DESC, startdate DESC";
		return this.getSimpleListPattern1($scope, 'sessions', preferedlanguage, query);
	};

	this.getAllSessionsEx = function($scope, preferedlanguage, exception) {
		var query = "SELECT id, getTextLabel(label, '$language') label FROM cpa_sessions cs $where order by startdate DESC";
		if (exception != null) {
			var whereClause = " WHERE id != " + exception;
			query = query.replace("$where", whereClause);
		} else {
			query = query.replace("$where", "");
		}
		return this.getSimpleListPattern1($scope, 'sessions', preferedlanguage, query);
	};

	this.getAllActiveCourses = function($scope, preferedlanguage) {
		var query = "SELECT csc.id, csc.name, getTextLabel(csc.label, '$language') label FROM cpa_sessions_courses csc JOIN cpa_sessions cs ON cs.id = csc.sessionid WHERE cs.active = 1";
		this.getSimpleListPattern1($scope, 'activeCourses', preferedlanguage, query);
	};

	this.getAllSessionCourses = function($scope, sessionid, preferedlanguage) {
		var query = "SELECT csc.id, concat(concat(csc.name, ' - '), getTextLabel(csc.label, '$language')) text FROM cpa_sessions_courses csc WHERE csc.sessionid = $sessionid";
		query = query.replace("$sessionid", sessionid);
		this.getSimpleListPattern1($scope, 'sessionCourses', preferedlanguage, query);
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
		var query = "SELECT cm.id, concat(cm.song, ' - ', cm.author) label FROM cpa_musics cm";
		this.getSimpleListPattern1($scope, 'allDanceMusics', preferedlanguage, query);
	};

	this.getAllClubs = function ($scope, preferedlanguage) {
		var query = "SELECT cc.code, getTextLabel(label, '$language') text FROM cpa_clubs cc ORDER BY cc.code";
		this.getSimpleListPattern1($scope, 'homeclubs', preferedlanguage, query);
	};

	this.getAllWsDocuments = function($scope, preferedlanguage) {
		var query = "SELECT id, documentname FROM cpa_ws_documents cwd WHERE getWsTextLabel(filename, '$language') != '' AND publish = 1 ORDER BY cwd.publishon DESC";
		this.getSimpleListPattern1($scope, 'wsdocuments', preferedlanguage, query);
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
		var query = "SELECT cwp.name FROM cpa_ws_pages cwp ORDER BY cwp.name";
		this.getSimpleListPattern1($scope, 'wspages', preferedlanguage, query);
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
		var query = "SELECT cst.*, getTextLabel(label, '$language') text FROM cpa_shows_tasks cst JOIN cpa_codetable cct on cct.ctname = 'taskcategories' and cct.code = cst.category WHERE cst.active = 1 ORDER BY cct.sequence, cst.id";
		this.getSimpleListPattern1($scope, 'showTasks', preferedlanguage, query);
	};

	this.getAllEmailTemplates = function($scope, preferedlanguage) {
		var query = "SELECT id, templatename FROM cpa_emails_templates where active = 1 order by id DESC";
		this.getSimpleListPattern1($scope, 'allEmailTemplates', preferedlanguage, query);
	};

	this.getAllUsers = function($scope, preferedlanguage) {
		var query = "SELECT userid, fullname FROM cpa_users order by userid";
		return this.getSimpleListPattern1($scope, 'allUsers', preferedlanguage, query);
	};

	this.getAllPrograms = function($scope, preferedlanguage) {
		var query = "SELECT progname FROM cpa_programs_privileges order by progname";
		return this.getSimpleListPattern1($scope, 'allPrograms', preferedlanguage, query);
	};

	this.getAllAuditActions = function($scope, preferedlanguage) {
		var query = "SELECT DISTINCT action FROM cpa_audit_trail order by action";
		return this.getSimpleListPattern1($scope, 'allAuditActions', preferedlanguage, query);
	};
}]);
