'use strict';

angular.module('cpa_admin.sessionview', ['ngRoute'])

.config(['$routeProvider', function ($routeProvider) {
	$routeProvider.when('/sessionview', {
		templateUrl: 'sessionview/sessionview.html',
		controller: 'sessionviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
			}
		}
	});
}])

.controller('sessionviewCtrl', ['$q', '$rootScope', '$scope', '$http', '$uibModal', '$window', '$sce', '$timeout', 'Upload', 'dateFilter', 'anycodesService', 'dialogService', 'listsService', 'arenaService', 'authenticationService', 'translationService', 'parseISOdateService', function ($q, $rootScope, $scope, $http, $uibModal, $window, $sce, $timeout, Upload, dateFilter, anycodesService, dialogService, listsService, arenaService, authenticationService, translationService, parseISOdateService) {
	$scope.progName = "sessionView";
	$scope.currentSession = null;
	$scope.newSession = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.coursecodefilter = null;
	$scope.filtercoursesdate = null;
	$scope.filterarena = null;
	$scope.remarkable = new Remarkable({
		html: false,        // Enable HTML tags in source
		xhtmlOut: false,        // Use '/' to close single tags (<br />)
		breaks: false         // Convert '\n' in paragraphs into <br>
		//      langPrefix:   'language-',  // CSS language prefix for fenced blocks
		//      typographer:  false,        // Enable some language-neutral replacement + quotes beautification
		//      quotes: '����',             // Double + single quotes replacement pairs, when typographer enabled, and smartquotes on. Set doubles to '��' for Russian, '��' for German.
		//      highlight: function (/*str, lang*/) { return ''; } // Highlighter function. Should return escaped HTML, or '' if the source string is not changed
	});

	$scope.isDirty = function () {
		if ($scope.detailsForm.$dirty || $scope.onlineregistrationForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function () {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function () {
		$scope.detailsForm.$setPristine();
		$scope.onlineregistrationForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.copySession = function (confirmed) {
		if ($scope.isDirty()) {
			dialogService.alertDlg($scope.translationObj.main.msgerrpleasesavefirst, null);
		} else {
			if ($scope.currentSession != null && !confirmed) {
				dialogService.confirmDlg($scope.translationObj.main.msgconfirmcopy, "YESNO", $scope.copySession, null, true);
			} else {
				$scope.promise = $http({
					method: 'post',
					url: './sessionview/sessions.php',
					data: $.param({ 'sessionid': $scope.currentSession.id, 'copyicetimes': true, 'copycourses': true, 'copycharges': true, 'copyrules': false, 'type': 'copySession' }),
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
				}).success(function (data, status, headers, config) {
					if (data.success) {
						dialogService.alertDlg($scope.translationObj.main.msgsessioncopied, null);
					} else {
						dialogService.displayFailure(data);
					}
				}).error(function (data, status, headers, config) {
					dialogService.displayFailure(data);
					return false;
				});
			}
		}
	};

	$scope.activateSession = function () {
		if ($scope.currentSession != null) {
			if ($scope.isDirty()) {
				dialogService.alertDlg($scope.translationObj.main.msgerrpleasesavefirst, null);
			} else {
				$scope.promise = $http({
					method: 'post',
					url: './sessionview/sessions.php',
					data: $.param({ 'sessionid': $scope.currentSession.id, 'type': 'activateSession' }),
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
				}).success(function (data, status, headers, config) {
					if (data.success) {
						dialogService.alertDlg($scope.translationObj.main.msgsessionactivated, null);
						$scope.currentSession.active = "1";	//Do not relead. Set field manually.
					} else {
						dialogService.displayFailure(data);
					}
				}).error(function (data, status, headers, config) {
					dialogService.displayFailure(data);
					return false;
				});
			}
		}
	};

	$scope.getAllSessions = function () {
		$scope.promise = $http({
			method: 'post',
			url: './sessionview/sessions.php',
			data: $.param({ 'language': authenticationService.getCurrentLanguage(), 'type': 'getAllSessions' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.leftobjs = data.data;
				} else {
					$scope.leftobjs = [];
				}
				$rootScope.repositionLeftColumn();
			} else {
				if (!data.success) {
					dialogService.displayFailure(data);
				}
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.convertParagraph = function (paragraph) {
		if (paragraph) {
			paragraph.msgfr = "<H3>" + (paragraph.title_fr != null && paragraph.title_fr != '' ? paragraph.title_fr : '') + "</H3>" +
				"<H4>" + (paragraph.subtitle_fr != null && paragraph.subtitle_fr != '' ? paragraph.subtitle_fr : '') + "</H4>" +
				"<p>" + (paragraph.paragraphtext_fr != null && paragraph.paragraphtext_fr != '' ? $scope.remarkable.render(paragraph.paragraphtext_fr) : '') + "</p>";
			paragraph.msgfr = $sce.trustAsHtml(paragraph.msgfr);
			paragraph.msgen = "<H3>" + (paragraph.title_en != null && paragraph.title_en != '' ? paragraph.title_en : '') + "</H3>" +
				"<H4>" + (paragraph.subtitle_en != null && paragraph.subtitle_en != '' ? paragraph.subtitle_en : '') + "</H4>" +
				"<p>" + (paragraph.paragraphtext_en != null && paragraph.paragraphtext_en != '' ? $scope.remarkable.render(paragraph.paragraphtext_en) : '') + "</p>";
			paragraph.msgen = $sce.trustAsHtml(paragraph.msgen);
		}
	}

	// Check if date is a valid value
	$scope.isDateValid = function (datetovalidate) {
		var retVal = true;
		if (datetovalidate == "0000-00-00" || datetovalidate == "0000-01-01") {
			retVal = false;
		}
		return retVal;
	}

	$scope.getSessionDetails = function (session) {
		$scope.promise = $http({
			method: 'post',
			url: './sessionview/sessions.php',
			data: $.param({ 'id': session.id, 'language': authenticationService.getCurrentLanguage(), 'type': 'getSessionDetails' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.coursecodefilter = null;
				$scope.filtercoursesdate = null;
				$scope.currentSession = data.data[0];
				$scope.currentSession.onlinepreregistemailtpl = $scope.currentSession.onlinepreregistemailtpl != 0 ? $scope.currentSession.onlinepreregistemailtpl : null;
				// $scope.currentSession.onlineregistemailtpl.id = $scope.currentSession.onlineregistemailtpl;
				$scope.currentSession.rulesen = $sce.trustAsHtml($scope.currentSession.rulesen);
				$scope.currentSession.rulesfr = $sce.trustAsHtml($scope.currentSession.rulesfr);
				$scope.currentSession.startdate = parseISOdateService.parseDate($scope.currentSession.startdate + "T00:00:00");
				$scope.currentSession.enddate = parseISOdateService.parseDate($scope.currentSession.enddate + "T00:00:00");
				$scope.currentSession.coursesstartdate = parseISOdateService.parseDate($scope.currentSession.coursesstartdate + "T00:00:00");
				$scope.currentSession.coursesenddate = parseISOdateService.parseDate($scope.currentSession.coursesenddate + "T00:00:00");
				$scope.currentSession.lastupdateddate = parseISOdateService.parseDate($scope.currentSession.lastupdateddate);
				$scope.currentSession.reimbursementdate = parseISOdateService.parseDate($scope.currentSession.reimbursementdate + "T00:00:00");
				$scope.currentSession.agereferencedate = parseISOdateService.parseDate($scope.currentSession.agereferencedate + "T00:00:00");
				$scope.currentSession.proratastartdate = parseISOdateService.parseDate($scope.currentSession.proratastartdate + "T00:00:00");
				if ($scope.currentSession.onlineregiststartdate) {
					if ($scope.isDateValid($scope.currentSession.onlineregiststartdate)) {
						$scope.currentSession.onlineregiststartdate = parseISOdateService.parseDate($scope.currentSession.onlineregiststartdate + "T00:00:00");
					} else {
						$scope.currentSession.onlineregiststartdate = null;
					}
				}
				if ($scope.currentSession.onlineregistenddate) {
					if ($scope.isDateValid($scope.currentSession.onlineregistenddate)) {
						$scope.currentSession.onlineregistenddate = parseISOdateService.parseDate($scope.currentSession.onlineregistenddate + "T00:00:00");
					} else {
						$scope.currentSession.onlineregistenddate = null;
					}
				}
				if ($scope.currentSession.onlinepreregiststartdate) {
					if ($scope.isDateValid($scope.currentSession.onlinepreregiststartdate)) {
						$scope.currentSession.onlinepreregiststartdate = parseISOdateService.parseDate($scope.currentSession.onlinepreregiststartdate + "T00:00:00");
					} else {
						$scope.currentSession.onlinepreregiststartdate = null;
					}
				}
				if ($scope.currentSession.onlinepreregistenddate) {
					if ($scope.isDateValid($scope.currentSession.onlinepreregistenddate)) {
						$scope.currentSession.onlinepreregistenddate = parseISOdateService.parseDate($scope.currentSession.onlinepreregistenddate + "T00:00:00");
					} else {
						$scope.currentSession.onlinepreregistenddate = null;
					}
				}
				for (var i = 0; i < $scope.currentSession.registrations.length; i++) {
					$scope.currentSession.registrations[i].registrationdatestr = $scope.currentSession.registrations[i].registrationdate;
					$scope.currentSession.registrations[i].starttimestr = $scope.currentSession.registrations[i].starttime;
					$scope.currentSession.registrations[i].endtimestr = $scope.currentSession.registrations[i].endtime;
					$scope.currentSession.registrations[i].registrationdate = parseISOdateService.parseDate($scope.currentSession.registrations[i].registrationdate + "T00:00:00");
					$scope.currentSession.registrations[i].starttime = parseISOdateService.parseDate("1970-01-01T" + $scope.currentSession.registrations[i].starttime);
					$scope.currentSession.registrations[i].endtime = parseISOdateService.parseDate("1970-01-01T" + $scope.currentSession.registrations[i].endtime);
				}
				for (var i = 0; i < $scope.currentSession.events.length; i++) {
					$scope.currentSession.events[i].eventdatestr = $scope.currentSession.events[i].eventdate;
					$scope.currentSession.events[i].eventdate = parseISOdateService.parseDate($scope.currentSession.events[i].eventdate + "T00:00:00");
				}
				for (var i = 0; i < $scope.currentSession.sessionCharges.length; i++) {
					$scope.currentSession.sessionCharges[i].startdatestr = $scope.currentSession.sessionCharges[i].startdate;
					$scope.currentSession.sessionCharges[i].enddatestr = $scope.currentSession.sessionCharges[i].enddate;
					if ($scope.currentSession.sessionCharges[i].startdate) {
						$scope.currentSession.sessionCharges[i].startdate = parseISOdateService.parseDate($scope.currentSession.sessionCharges[i].startdate + "T00:00:00");
					}
					if ($scope.currentSession.sessionCharges[i].enddate) {
						$scope.currentSession.sessionCharges[i].enddate = parseISOdateService.parseDate($scope.currentSession.sessionCharges[i].enddate + "T00:00:00");
					}
				}
				for (var i = 0; i < $scope.currentSession.sessionCourses.length; i++) {
					var course = $scope.currentSession.sessionCourses[i];
					if (course.startdate) {
						course.startdate = parseISOdateService.parseDate(course.startdate + "T00:00:00");
					}
					if (course.enddate) {
						course.enddate = parseISOdateService.parseDate(course.enddate + "T00:00:00");
					}
					course.isprereqdefined = (course.prereqagemax || course.prereqagemin || course.prereqcanskatebadgemax > 0 || course.prereqcanskatebadgemin > 0) ? true : false;
					course.isprereqdefinedlabel = anycodesService.convertCodeToDesc($scope, 'yesnos', course.isprereqdefined);
					course.namenospace = course.id;
					for (var x = 0; course.schedules && x < course.schedules.length; x++) {
						if (course.schedules[x].arenaactive == 0) {
							course.hasainactivearena = true;
							break;
						}
					}
				}
				for (var i = 0; i < $scope.currentSession.rules2.length; i++) {
					$scope.convertParagraph($scope.currentSession.rules2[i]);
				}
				listsService.getAllSessionsEx($scope, authenticationService.getCurrentLanguage(), $scope.currentSession.id);
				$scope.manageAllCoursesDates();
			} else {
				dialogService.displayFailure(data);
			}
			$rootScope.repositionLeftColumn();
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (session, index) {
		if (session != null) {
			$scope.selectedLeftObj = session;
			// $scope.selectedSession = session;
			$scope.getSessionDetails(session);
			$scope.selectedLeftObj = session;
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentSession = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (session, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, session, index);
		} else {
			$scope.setCurrentInternal(session, index);
		}
	};

	$scope.displayCanceledDeleteMsg = function () {
		dialogService.alertDlg($scope.translationObj.main.msgdeletesessioncanceled);
	}

	$scope.deleteFromDB = function (confirmed, confirmed2, confirmValue) {
		if ($scope.currentSession != null) {
			if (confirmed == null || confirmed == false) {
				dialogService.confirmDlg($scope.translationObj.main.msgdeletesession, "YESNO", $scope.deleteFromDB, $scope.displayCanceledDeleteMsg, true, null);
			} else if (confirmed == true && (confirmed2 == null || confirmed2 == false)) {
				dialogService.promptDlg($scope.translationObj.main.msgdeletesession2 + $scope.currentSession.name, "OKCANCEL", $scope.deleteFromDB, $scope.displayCanceledDeleteMsg, true, true);
			}

			if (confirmed == true && confirmed2 == true && confirmValue == $scope.currentSession.name) {
				$scope.promise = $http({
					method: 'post',
					url: './sessionview/sessions.php',
					data: $.param({ 'session': JSON.stringify($scope.currentSession), 'type': 'delete_session' }),
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
				}).success(function (data, status, headers, config) {
					if (data.success) {
						$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedLeftObj), 1);
						$scope.setCurrentInternal(null);
						return true;
					} else {
						dialogService.displayFailure(data);
						return false;
					}
				}).error(function (data, status, headers, config) {
					dialogService.displayFailure(data);
					return false;
				});
			} else if (confirmed == true && confirmed2 == true && confirmValue != $scope.currentSession.name) {
				$scope.displayCanceledDeleteMsg();
			}
		}
	}

	$scope.validateAllForms = function () {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}

		if ($scope.onlineregistrationForm.$invalid) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerronlineregistrationallmandatory);
		}
		if ($scope.globalErrorMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function () { $("#mainglobalerrormessage").hide(); });
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function () { $("#mainglobalwarningmessage").hide(); });
		}
		return retVal;
	}

	$scope.myDateFilter = function(dateToChange) {
		return dateFilter(dateToChange, 'yyyy-MM-dd');
	}

	$scope.saveToDB = function () {
		if ($scope.currentSession == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			// $scope.currentSession.onlineregistemailtpl = $scope.currentSession.onlineregistemailtpl.id;
			$scope.currentSession.startdatestr = dateFilter($scope.currentSession.startdate, 'yyyy-MM-dd');
			$scope.currentSession.enddatestr = dateFilter($scope.currentSession.enddate, 'yyyy-MM-dd');
			$scope.currentSession.coursesstartdatestr = dateFilter($scope.currentSession.coursesstartdate, 'yyyy-MM-dd');
			$scope.currentSession.coursesenddatestr = dateFilter($scope.currentSession.coursesenddate, 'yyyy-MM-dd');
			$scope.currentSession.onlineregiststartdatestr = dateFilter($scope.currentSession.onlineregiststartdate, 'yyyy-MM-dd');
			$scope.currentSession.onlineregistenddatestr = dateFilter($scope.currentSession.onlineregistenddate, 'yyyy-MM-dd');
			$scope.currentSession.onlinepreregiststartdatestr = dateFilter($scope.currentSession.onlinepreregiststartdate, 'yyyy-MM-dd');
			$scope.currentSession.onlinepreregistenddatestr = dateFilter($scope.currentSession.onlinepreregistenddate, 'yyyy-MM-dd');
			$scope.currentSession.reimbursementdatestr = dateFilter($scope.currentSession.reimbursementdate, 'yyyy-MM-dd');
			$scope.currentSession.agereferencedatestr = dateFilter($scope.currentSession.agereferencedate, 'yyyy-MM-dd');
			$scope.currentSession.proratastartdatestr = dateFilter($scope.currentSession.proratastartdate, 'yyyy-MM-dd');
			for (var i = 0; i < $scope.currentSession.registrations.length; i++) {
				$scope.currentSession.registrations[i].registrationdate = dateFilter($scope.currentSession.registrations[i].registrationdate, 'yyyy-MM-dd');
				$scope.currentSession.registrations[i].starttime = dateFilter($scope.currentSession.registrations[i].starttime, 'HH:mm:ss');
				$scope.currentSession.registrations[i].endtime = dateFilter($scope.currentSession.registrations[i].endtime, 'HH:mm:ss');
			}
			for (var i = 0; i < $scope.currentSession.events.length; i++) {
				$scope.currentSession.events[i].eventdate = dateFilter($scope.currentSession.events[i].eventdate, 'yyyy-MM-dd');
			}
			for (var i = 0; i < $scope.currentSession.sessionCharges.length; i++) {
				$scope.currentSession.sessionCharges[i].startdate = dateFilter($scope.currentSession.sessionCharges[i].startdate, 'yyyy-MM-dd');
				$scope.currentSession.sessionCharges[i].enddate = dateFilter($scope.currentSession.sessionCharges[i].enddate, 'yyyy-MM-dd');
			}
			for (var i = 0; i < $scope.currentSession.sessionCourses.length; i++) {
				$scope.currentSession.sessionCourses[i].startdate = dateFilter($scope.currentSession.sessionCourses[i].startdate, 'yyyy-MM-dd');
				$scope.currentSession.sessionCourses[i].enddate = dateFilter($scope.currentSession.sessionCourses[i].enddate, 'yyyy-MM-dd');
				if ($scope.currentSession.sessionCourses[i].isschedule == "0") {
					$scope.currentSession.sessionCourses[i].datesgenerated = 1;
				}
			}
			$scope.promise = $http({
				method: 'post',
				url: './sessionview/sessions.php',
				data: $.param({ 'session': JSON.stringify($scope.currentSession), 'type': 'updateEntireSession' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Select this session to reset everything
					$scope.setCurrentInternal($scope.selectedLeftObj, null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).error(function (data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	$scope.addSessionToDB = function () {
		$scope.promise = $http({
			method: 'post',
			url: './sessionview/sessions.php',
			data: $.param({ 'session': $scope.newSession, 'type': 'insert_session' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				var newSession = { id: data.id, name: $scope.newSession.name };
				$scope.leftobjs.push(newSession);
				// We could sort the list....
				$scope.setCurrentInternal(newSession);
				return true;
			} else {
				dialogService.displayFailure(data);
				return false;
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	// This is the function that creates the modal to create new session
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newSession = {};
			// Send the newSession to the modal form
			$uibModal.open({
				animation: false,
				templateUrl: 'sessionview/newsession.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'md',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newSession;
					}
				}
			}).result.then(function (newSession) {
				// User clicked OK and everything was valid.
				$scope.newSession = newSession;
				if ($scope.addSessionToDB() == true) {
				}
			}, function () {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// Creates an array of all courses' dates, ordered by dates/arena/ices
	$scope.manageAllCoursesDates = function () {
		$scope.currentSession.allcoursesdates = [];
		for (var i = 0; i < $scope.currentSession.sessionCourses.length; i++) {
			$scope.currentSession.allcoursesdates = $scope.currentSession.allcoursesdates.concat($scope.currentSession.sessionCourses[i].dates);
		}
		$scope.currentSession.allcoursesdates.sort(
			function (a, b) {
				if (a.coursedate < b.coursedate) return -1;
				if (a.coursedate > b.coursedate) return 1;
				// If dates are equal, check arena
				if (a.arenaid < b.arenaid) return -1;
				if (a.arenaid > b.arenaid) return 1;
				// If arenas are equal, check ice
				if (a.iceid < b.iceid) return -1;
				if (a.iceid > b.iceid) return 1;
				// If ices are equal, check starttime
				if (a.starttime < b.starttime) return -1;
				if (a.starttime > b.starttime) return 1;
				return 0;
			});

		// Next, create the array of all the possible dates for filtering
		return;
	}

	$scope.onFilterArenaChange = function () {
		$scope.filterarena = $scope.currentSession.filterarena != '' ? $scope.currentSession.filterarena : null;
	}

	$scope.onFilterCoursesDateChange = function () {
		$scope.filtercoursesdate = dateFilter($scope.currentSession.filtercoursesdate, 'yyyy-MM-dd');
	}

	$scope.onArenaChange = function (newObj) {
		newObj.iceid = null;
		$scope.ices = arenaService.getArenaIces($scope, newObj.arenaid);
	}

	$scope.onCourseChange = function (newObj) {
		newObj.courselevel = null;
		$scope.courselevels = listsService.getAllCourseLevels($scope, authenticationService.getCurrentLanguage(), newObj.coursecode);
	}

	// When a coursecode is selected (or de-selected)
	$scope.onCourseCodeSelected = function (coursecode) {
		for (var i = 0; i < $scope.currentSession.coursecodes.length; i++) {
			if ($scope.currentSession.coursecodes[i].selected == '1') {
				$scope.coursecodefilter = 1;
				return;
			}
		}
		$scope.coursecodefilter = null;
	}

	$scope.filterCourses = function (item) {
		if ($scope.coursecodefilter == 1) {
			for (var i = 0; i < $scope.currentSession.coursecodes.length; i++) {
				if ($scope.currentSession.coursecodes[i].selected == '1' && item.coursecode == $scope.currentSession.coursecodes[i].code) {
					return true;
				}
			}
		} else {
			return true;
		}
		return false;
	}

	$scope.filterByCoursesDate = function (item) {
		var retVal = false;
		if ($scope.filtercoursesdate == null || item.coursedate == $scope.filtercoursesdate) {
			if ($scope.filterarena == null || item.arenaid == $scope.filterarena) {
				retVal = true;
			}
		}
		return retVal;
	}

	$scope.clearCourseCodesFilter = function () {
		for (var i = 0; i < $scope.currentSession.coursecodes.length; i++) {
			$scope.currentSession.coursecodes[i].selected = '0';
		}
		$scope.coursecodefilter = null;
	}

	$scope.setStaffList = function (newObj) {
		if (newObj.staffcode == 'COACH') {
			$scope.staffs = $scope.coaches;
		} else if (newObj.staffcode == 'PA') {
			$scope.staffs = $scope.programassistants;
		} else if (newObj.staffcode == 'pah') {
			$scope.staffs = $scope.programassistanthelpers;
		}
	}

	$scope.onStaffcodeChange = function (newObj) {
		newObj.memberid = null;
		$scope.setStaffList(newObj);
	}

	/**
	 * This function is used in the validation of the new course dialog
	 * @param {*} editObjForm 
	 * @param {*} newObj 
	 * @returns 
	 */
	$scope.validateNewCourseForm = function (editObjForm, newObj) {
		if (editObjForm.$invalid) {
			if (newObj.prereqagemin < 1 || newObj.prereqagemax < 1) {
				return "#editObjFieldAgeMustBePositive";
			}
			return "#editObjFieldMandatory";
		} else {
			return null;
		}
		return "#editObjFieldMandatory";
	}

	// This is the function that creates the modal to create/edit courses
	$scope.editSessionCourse = function (newCourse) {
		$scope.newCourse = {};
		$scope.currentCourse = newCourse;
		angular.copy(newCourse, $scope.newCourse);
		$scope.courselevels = listsService.getAllCourseLevels($scope, authenticationService.getCurrentLanguage(), newCourse.coursecode);

		$uibModal.open({
			animation: false,
			templateUrl: 'sessionview/newcourse.template.html',
			controller: 'childeditorex.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () { return $scope.newCourse; },
				control: function () { return null; },		// The control object containing all validation functions
				callback: function () { return $scope.validateNewCourseForm; }										// Callback function to overwrite the normal validation
			}
		}).result.then(function (newCourse) {
			// User clicked OK and everything was valid.
			newCourse.availableonlinelabel = anycodesService.convertCodeToDesc($scope, 'yesnos', newCourse.availableonline);
			newCourse.isschedulelabel = anycodesService.convertCodeToDesc($scope, 'yesnos', newCourse.isschedule);
			newCourse.courselabel = (authenticationService.getCurrentLanguage() == "fr-ca") ? newCourse.label_fr : newCourse.label_en;
			newCourse.isprereqdefined = (newCourse.prereqagemax || newCourse.prereqagemin || newCourse.prereqcanskatebadgemax > 0 || newCourse.prereqcanskatebadgemin > 0) ? true : false;
			newCourse.isprereqdefinedlabel = anycodesService.convertCodeToDesc($scope, 'yesnos', newCourse.isprereqdefined);
			angular.copy(newCourse, $scope.currentCourse);
			// If course doesn't allow for schedules, removed all existing schedules
			if ($scope.currentCourse.isschedule == 0) {
				if ($scope.currentCourse.schedules) {
					for (var i = 0; i < $scope.currentCourse.schedules.length; i++) {
						$scope.currentCourse.schedules[i].status = 'Deleted';
					}
				}
			}
			if (!$scope.currentCourse.staffs) $scope.currentCourse.staffs = [];
			if (!$scope.currentCourse.sublevels) $scope.currentCourse.sublevels = [];
			if (!$scope.currentCourse.schedules) $scope.currentCourse.schedules = [];
			if (!$scope.currentCourse.datesgenerated) $scope.currentCourse.datesgenerated = 0;
			$scope.currentCourse.datesgeneratedlabel = anycodesService.convertCodeToDesc($scope, 'yesnos', $scope.currentCourse.datesgenerated);
			if ($scope.currentCourse.id != null) {
				$scope.currentCourse.status = 'Modified';
			} else {
				$scope.currentCourse.status = 'New';
				if ($scope.currentSession.sessionCourses == null) $scope.currentSession.sessionCourses = [];
				if ($scope.currentSession.sessionCourses.indexOf($scope.currentCourse) == -1) {
					$scope.currentSession.sessionCourses.push($scope.currentCourse);
				}
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit courses' sub levels
	$scope.editSessionCourseSublevel = function (course, newSublevel) {
		$scope.course = course;
		$scope.newSublevel = {};
		$scope.currentSublevel = newSublevel;
		angular.copy(newSublevel, $scope.newSublevel);

		$uibModal.open({
			animation: false,
			templateUrl: 'sessionview/newsublevel.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newSublevel;
				}
			}
		}).result.then(function (newSublevel) {
			// User clicked OK and everything was valid.
			angular.copy(newSublevel, $scope.currentSublevel);
			if ($scope.currentSublevel.id != null) {
				$scope.currentSublevel.status = 'Modified';
			} else {
				$scope.currentSublevel.status = 'New';
				if ($scope.course.sublevels == null) $scope.course.sublevels = [];
				// Don't insert twice in list
				var insertInList = true;
				if ($scope.course.sublevels.indexOf($scope.currentSublevel) == -1) {
					// We need to validate that this code doesn't exists already in the list for this course.
					for (var i = 0; i < $scope.course.sublevels.length; i++) {
						if ($scope.course.sublevels[i].code == $scope.currentSublevel.code) {
							// Display an error message
							insertInList = false;
							dialogService.alertDlg($scope.translationObj.main.msgduplicatedsublevelcode, null);
							break;
						}
					}
					if (insertInList) {
						$scope.course.sublevels.push($scope.currentSublevel);
					}
				}
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	/**
	 * This function is used by the new staff dialog box to validate that the new staff is valid
	 * All fields must be filled and staff member must not already be in the staff list
	 * @param {*} newStaff Staff object being added
	 */
	$scope.validateNewStaff = function (formObj, newStaff) {
		// Validate the form using the field's rules
		if (formObj.$invalid) {
			return "#editObjFieldMandatory";
		}
		// Make sure that the new staff is not already in one of the list
		if (!newStaff.id) {
			for (var x = 0; $scope.course.staffs && x < $scope.course.staffs.length; x++) {
				if ($scope.course.staffs[x].memberid == newStaff.memberid) {
					return "#editObjStaffAlreadyExists";
				}
			}
		}
	}

	// This is the function that creates the modal to create/edit courses' staffs
	$scope.editSessionCourseStaff = function (course, newStaff) {
		$scope.course = course;
		$scope.newStaff = {};
		$scope.setStaffList(newStaff);
		// Keep a pointer to the current staff
		$scope.currentStaff = newStaff;
		// Copy in another object
		angular.copy(newStaff, $scope.newStaff);
		$scope.newStaff.callback = $scope.validateNewStaff;

		$uibModal.open({
			animation: false,
			templateUrl: 'sessionview/newstaff.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newStaff;
				}
			}
		}).result.then(function (newStaff) {
			// User clicked OK and everything was valid.
			if (newStaff.staffcode == 'COACH') {
				newStaff.name = anycodesService.convertIdToDesc($scope, 'coaches', newStaff.memberid);
			} else if (newStaff.staffcode == 'PA') {
				newStaff.name = anycodesService.convertIdToDesc($scope, 'programassistants', newStaff.memberid);
			} else if (newStaff.staffcode == 'pah') {
				newStaff.name = anycodesService.convertIdToDesc($scope, 'programassistanthelpers', newStaff.memberid);
			}
			newStaff.staffcodelabel = anycodesService.convertCodeToDesc($scope, 'staffcodes', newStaff.staffcode);
			newStaff.statuscodelabel = anycodesService.convertCodeToDesc($scope, 'personnelstatus', newStaff.statuscode);
			angular.copy(newStaff, $scope.currentStaff);
			if ($scope.currentStaff.id != null) {
				$scope.currentStaff.status = 'Modified';
			} else {
				$scope.currentStaff.status = 'New';
				if ($scope.course.staffs == null) $scope.course.staffs = [];
				// Don't insert twice in list
				if ($scope.course.staffs.indexOf($scope.currentStaff) == -1) {
					$scope.course.staffs.push($scope.currentStaff);
				}
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit courses' schedules
	$scope.editSessionCourseSchedule = function (course, newSchedule) {
		$scope.course = course;
		$scope.newSchedule = {};
		$scope.currentSchedule = newSchedule;
		angular.copy(newSchedule, $scope.newSchedule);

		$uibModal.open({
			animation: false,
			templateUrl: 'sessionview/newschedule.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newSchedule;
				}
			}
		}).result.then(function (newSchedule) {
			// User clicked OK and everything was valid.
			newSchedule.icelabel = arenaService.convertArenaIceToCurrentDesc($scope, newSchedule.arenaid, newSchedule.iceid);//, 'en-ca'/*$scope.context.preferedlanguage*/);
			angular.copy(newSchedule, $scope.currentSchedule);
			if ($scope.currentSchedule.id != null) {
				$scope.currentSchedule.status = 'Modified';
			} else {
				$scope.currentSchedule.status = 'New';
				if ($scope.course.schedules == null) $scope.course.schedules = [];
				// Don't insert twice in list
				if ($scope.course.schedules.indexOf($scope.currentSchedule) == -1) {
					$scope.course.schedules.push($scope.currentSchedule);
				}
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit courses' dates
	$scope.editSessionCourseDate = function (course, newCoursedate) {
		$scope.course = course;
		$scope.newCoursedate = {};
		if (newCoursedate == null) newCoursedate = { manual: 1 };
		$scope.currentCoursedate = newCoursedate;
		angular.copy(newCoursedate, $scope.newCoursedate);
		if ($scope.newCoursedate.coursedate) $scope.newCoursedate.coursedate = parseISOdateService.parseDate($scope.newCoursedate.coursedate + "T00:00:00");
		$scope.ices = arenaService.getArenaIces($scope, $scope.newCoursedate.arenaid);

		$uibModal.open({
			animation: false,
			templateUrl: 'sessionview/newcoursedate.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newCoursedate;
				}
			}
		}).result.then(function (newCoursedate) {
			// User clicked OK and everything was valid.
			newCoursedate.icelabel = arenaService.convertArenaIceToCurrentDesc($scope, newCoursedate.arenaid, newCoursedate.iceid);
			newCoursedate.arenalabel = arenaService.convertArenaToCurrentDesc($scope, newCoursedate.arenaid);
			angular.copy(newCoursedate, $scope.currentCoursedate);
			if (!$scope.currentCoursedate.day) {
				// var coursedate = new Date(newCoursedate.coursedate + "T00:00:00");
				$scope.currentCoursedate.day = $scope.currentCoursedate.coursedate.getDay() / 1;
			}
			$scope.currentCoursedate.daylabel = anycodesService.convertCodeToDesc($scope, 'days', $scope.currentCoursedate.day);
			$scope.currentCoursedate.canceledlabel = anycodesService.convertCodeToDesc($scope, 'yesnos', $scope.currentCoursedate.canceled);
			$scope.currentCoursedate.coursedate = dateFilter(newCoursedate.coursedate, 'yyyy-MM-dd');
			if ($scope.currentCoursedate.id != null) {
				$scope.currentCoursedate.status = 'Modified';
			} else {
				$scope.currentCoursedate.status = 'New';
				$scope.currentCoursedate.coursename = $scope.course.name;
				if ($scope.course.dates == null) $scope.course.dates = [];
				if ($scope.course.dates.indexOf($scope.currentCoursedate) == -1) {
					$scope.course.dates.push($scope.currentCoursedate);
				}
			}
			$scope.manageAllCoursesDates();
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit charges
	$scope.editSessionCharge = function (newCharge) {
		$scope.newCharge = {};
		$scope.currentCharge = newCharge;
		angular.copy(newCharge, $scope.newCharge);

		var modal = $uibModal.open({
			animation: false,
			templateUrl: 'sessionview/newcharge.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newCharge;
				}
			}
		}).result.then(function (newCharge) {
			// User clicked OK and everything was valid.
			newCharge.startdatestr = dateFilter(newCharge.startdate, 'yyyy-MM-dd');
			newCharge.enddatestr = dateFilter(newCharge.enddate, 'yyyy-MM-dd');
			angular.copy(newCharge, $scope.currentCharge);
			$scope.currentCharge.chargelabel = anycodesService.convertCodeToLabel($scope, 'charges', $scope.currentCharge.chargecode);
			if ($scope.currentCharge.id != null) {
				$scope.currentCharge.status = 'Modified';
			} else {
				$scope.currentCharge.status = 'New';
				if ($scope.currentSession.sessionCharges == null) $scope.currentSession.sessionCharges = [];
				// Let's make sure there is not another charge with the same code and conflicting dates
				for (var x = 0; x < $scope.currentSession.sessionCharges.length; x++) {
					var charge = $scope.currentSession.sessionCharges[x];
					if (charge.chargecode == $scope.currentCharge.chargecode) {
						// Possible conflicting dates
						dialogService.alertDlg($scope.translationObj.charges.msgerrconflictingcharges);
					}
				}
				if ($scope.currentSession.sessionCharges.indexOf($scope.currentCharge) == -1) {
					$scope.currentSession.sessionCharges.push($scope.currentCharge);
				}
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit ice times
	$scope.editSessionIcetime = function (newIcetime) {
		$scope.newIcetime = {};
		$scope.currentIcetime = newIcetime;
		angular.copy(newIcetime, $scope.newIcetime);

		$uibModal.open({
			animation: false,
			templateUrl: 'sessionview/newicetime.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newIcetime;
				}
			}
		}).result.then(function (newIcetime) {
			// User clicked OK and everything was valid.
			newIcetime.icelabel = arenaService.convertArenaIceToCurrentDesc($scope, newIcetime.arenaid, newIcetime.iceid);
			newIcetime.arenalabel = arenaService.convertArenaToCurrentDesc($scope, newIcetime.arenaid);
			newIcetime.daylabel = anycodesService.convertCodeToDesc($scope, 'days', newIcetime.day);
			angular.copy(newIcetime, $scope.currentIcetime);
			if ($scope.currentIcetime.id != null) {
				$scope.currentIcetime.status = 'Modified';
			} else {
				$scope.currentIcetime.status = 'New';
				if ($scope.currentSession.icetimes == null) $scope.currentSession.icetimes = [];
				if ($scope.currentSession.icetimes.indexOf($scope.currentIcetime) == -1) {
					$scope.currentSession.icetimes.push($scope.currentIcetime);
				}
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit registrations
	$scope.editSessionRegistration = function (newRegistration) {
		$scope.newRegistration = {};
		$scope.currentRegistration = newRegistration;
		angular.copy(newRegistration, $scope.newRegistration);

		$uibModal.open({
			animation: false,
			templateUrl: 'sessionview/newregistration.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newRegistration;
				}
			}
		}).result.then(function (newRegistration) {
			// User clicked OK and everything was valid.
			newRegistration.registrationdatestr = dateFilter(newRegistration.registrationdate, 'yyyy-MM-dd');
			newRegistration.starttimestr = dateFilter(newRegistration.starttime, 'HH:mm:ss');
			newRegistration.endtimestr = dateFilter(newRegistration.endtime, 'HH:mm:ss');
			angular.copy(newRegistration, $scope.currentRegistration);
			if ($scope.currentRegistration.id != null) {
				$scope.currentRegistration.status = 'Modified';
			} else {
				$scope.currentRegistration.status = 'New';
				if ($scope.currentSession.registrations == null) $scope.currentSession.registrations = [];
				if ($scope.currentSession.registrations.indexOf($scope.currentRegistration) == -1) {
					$scope.currentSession.registrations.push($scope.currentRegistration);
				}
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit events
	$scope.editSessionEvent = function (newEvent) {
		$scope.newEvent = {};
		$scope.currentEvent = newEvent;
		angular.copy(newEvent, $scope.newEvent);

		$uibModal.open({
			animation: false,
			templateUrl: 'sessionview/newevent.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newEvent;
				}
			}
		}).result.then(function (newEvent) {
			// User clicked OK and everything was valid.
			newEvent.eventdatestr = dateFilter(newEvent.eventdate, 'yyyy-MM-dd');
			angular.copy(newEvent, $scope.currentEvent);
			if ($scope.currentEvent.id != null) {
				$scope.currentEvent.status = 'Modified';
			} else {
				$scope.currentEvent.status = 'New';
				if ($scope.currentSession.events == null) $scope.currentSession.events = [];
				if ($scope.currentSession.events.indexOf($scope.currentEvent) == -1) {
					$scope.currentSession.events.push($scope.currentEvent);
				}
			}
			$scope.setDirty();
		}, function () {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	$scope.generateCourseDates = function (course, forced) {
		if (!forced) {
			if (course.datesgenerated == 1) {
				// Confirm the deletion of existing dates and creation of new dates
				dialogService.confirmDlg($scope.translationObj.main.msgdatesgenerated, "YESNO", $scope.generateCourseDates, null, course, true);
			} else {
				// Confirm creation of dates
				dialogService.confirmDlg($scope.translationObj.main.msggeneratedates, "YESNO", $scope.generateCourseDates, null, course, true);
			}
		} else {
			var dateArr = $scope.generateCourseDateArray(course);
			$scope.insertCourseDates(course, dateArr)
		}
	}

	$scope.insertCourseDates = function (course, coursedatearr) {
		$scope.courseForDateInsert = course;
		if (coursedatearr != null && coursedatearr.length != 0) {
			$scope.promise = $http({
				method: 'post',
				url: './sessionview/sessions.php',
				data: $.param({ 'coursedate': coursedatearr, 'language': authenticationService.getCurrentLanguage(), 'type': 'insertCourseDate' }),
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
			}).success(function (data, status, headers, config) {
				if (data.success) {
					// Because this function also re-read the entire course from the DB (instead of re-reading the entire session)
					// we need to reformat certain information just like in the getSessionDetails function
					if (data.course[0].startdate) {
						data.course[0].startdate = parseISOdateService.parseDate(data.course[0].startdate + "T00:00:00");
					}
					if (data.course[0].enddate) {
						data.course[0].enddate = parseISOdateService.parseDate(data.course[0].enddate + "T00:00:00");
					}
					angular.copy(data.course[0], $scope.courseForDateInsert);
					$scope.manageAllCoursesDates();
					dialogService.alertDlg($scope.translationObj.main.msgdatesregenerated + '<br>' + $scope.translationObj.main.msgdatesdeleted + data.deletedates.deleted + '<br>' + $scope.translationObj.main.msgdatesinserted + data.inserted + '/' + data.count);
				} else {
					dialogService.displayFailure(data);
				}
			}).error(function (data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		} else {
			dialogService.alertDlg($scope.translationObj.main.msgdatesnotgenerated);
		}
	};

	$scope.generateCourseDateArray = function (course) {
		// We need to generates the date of the course. First, get the course schedule
		// For every schedule, find the first possible date based on the session start date and generate until you reach the enddate of session
		var dateArr = [];
		var tmpCourseStartDate;
		var tmpCourseEndDate;
		// If startdate defined for this course, use it. If not take session startdate.
		if (course.startdate) {
			tmpCourseStartDate = course.startdate;
		} else {
			tmpCourseStartDate = $scope.currentSession.coursesstartdate;
		}
		// If enddate defined for this course, use it. If not take session enddate.
		if (course.enddate) {
			tmpCourseEndDate = course.enddate;
		} else {
			tmpCourseEndDate = $scope.currentSession.coursesenddate;
		}
		// Make sure the end date is included in the time period
		tmpCourseEndDate.setHours("23", "59", "00");
		for (var i = 0; i < course.schedules.length; i++) {
			var schedule = course.schedules[i];
			var day = schedule.day / 1;
			// Find first date of course for this schedule
			var startday = tmpCourseStartDate.getDay() / 1; // This is the start day of the session
			var diff = (startday <= day) ? day - startday : day + 7 - (startday); // This is the difference in days
			var firstDate = new Date(new Date(tmpCourseStartDate).setDate(tmpCourseStartDate.getDate() + diff)); // First course date.
			var scheduleTime = schedule.starttime.split(":");
			firstDate.setHours(scheduleTime[0], scheduleTime[1], scheduleTime[2]);
			do {
				var coursedatestr = dateFilter(firstDate, 'yyyy-MM-dd');
				dateArr.push({ sessionscoursesid: course.id, arenaid: course.schedules[i].arenaid, iceid: course.schedules[i].iceid, coursedatestr: coursedatestr, starttime: schedule.starttime, endtime: schedule.endtime, duration: schedule.duration, day: schedule.day / 1 });
				firstDate = new Date(new Date(firstDate).setDate(firstDate.getDate() + 7));
			} while (firstDate <= tmpCourseEndDate)
		}
		return dateArr;
	}

	// This is the function that displays the upload error messages
	$scope.displayUploadError = function (errFile) {
		if (errFile.$error == 'maxSize') {
			dialogService.alertDlg($scope.translationObj.main.msgerrinvalidfilesize + errFile.$errorParam);
		} else if (errFile.$error == 'maxWidth') {
			dialogService.alertDlg($scope.translationObj.main.msgerrinvalidmaxwidth + errFile.$errorParam);
		} else if (errFile.$error == 'maxHeight') {
			dialogService.alertDlg($scope.translationObj.main.msgerrinvalidmaxheight + errFile.$errorParam);
		}
	}

	// This is the function that uploads the rules file for the session
	$scope.uploadRulesFile = function (file, errFiles, language) {
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
		}
		if (file) {
			file.upload = Upload.upload({
				url: './sessionview/uploadrulesfile.php',
				method: 'POST',
				file: file,
				data: {
					'language': language,
					'mainobj': $scope.currentSession
				}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.main.msguploadcompleted);
						// Select this document to reset everything
						$scope.setCurrentInternal($scope.selectedLeftObj, null);
					} else {
						dialogService.displayFailure(data.data);
					}
				});
			}, function (data) {
				if (!data.success) {
					dialogService.displayFailure(data.data);
				}
			}, function (evt) {
				file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
			});
		}
	}

	// Opens the session calendar in another window. Pass the sessionid as a parameter using ?
	$scope.viewSessionCalendar = function () {
		$window.open('./#!/sessionscheduleview?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=200,left=200,width=1400,height=700");
	}

	// Opens the session schedule in another window. Pass the sessionid as a parameter using ?
	$scope.viewSessionSchedule = function () {
		$window.open('./sessioncoursesscheduleview/sessioncoursesscheduleview.html?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=200,left=200,width=1400,height=700");
	}

	// REPORTS
	$scope.printReport = function (reportName) {
		if (reportName == 'sessionSchedule') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id);
		}
		if (reportName == 'sessionCoursesSummary') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id);
		}
		if (reportName == 'sessionCoursesList') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id);
		}
		if (reportName == 'sessionCourseAttendance') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id);
		}
		if (reportName == 'sessionBillingList') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id);
		}
		if (reportName == 'sessionCoachesSchedule') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id);
		}
		if (reportName == 'sessionTaxReceipt') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id);
		}
		if (reportName == 'sessionCoursesListActive') {
			$window.open('./reports/sessionCoursesList.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id + '&activeonly=true');
		}
		if (reportName == 'sessionCoursesCount') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id + '&activeonly=true');
		}
		if (reportName == 'sessionSCRegistrations') {
			$window.open('./reports/' + reportName + '.php?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id);
		}
	}

	// This is the function that exports the bills
	$scope.exportBills = function () {
		$window.open('./reports/exportsessionbillstocsv.php' + '?language=' + authenticationService.getCurrentLanguage() + '&sessionid=' + $scope.currentSession.id);
	};

	$scope.refreshAll = function () {
		/*		
				var promises = [];
				var results = [];
				promises.push(anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos'));
				promises.push(anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'days', 'sequence', 'days'));
				$q.all(promises).then(function(results) {
					console.log(results[0]);
					console.log(results[1]);
					$scope.getAllSessions();
				});
		*/
		$scope.getAllSessions();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'days', 'sequence', 'days');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'staffcodes', 'sequence', 'staffcodes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'eventtypes', 'sequence', 'eventtypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'personnelstatus', 'sequence', 'personnelstatus');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'prorataoptions', 'sequence', 'prorataoptions');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'onlinepaymentoptions', 'sequence', 'onlinepaymentoptions');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'canskatebadges', 'sequence', 'canskatebadges');
		arenaService.getAllArenas($scope, authenticationService.getCurrentLanguage(), false);
		listsService.getAllCharges($scope, authenticationService.getCurrentLanguage(), false, false);
		listsService.getAllCourses($scope, authenticationService.getCurrentLanguage());
		listsService.getCoaches($scope, authenticationService.getCurrentLanguage());
		listsService.getAllProgramAssistants($scope, authenticationService.getCurrentLanguage());
		listsService.getAllProgramAssistantHelpers($scope, authenticationService.getCurrentLanguage());
		listsService.getAllEmailTemplates($scope, authenticationService.getCurrentLanguage());
		translationService.getTranslation($scope, 'sessionview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
