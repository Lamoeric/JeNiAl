'use strict';

angular.module('cpa_admin.showview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/showview', {
		templateUrl: 'showview/showview.html',
		controller: 'showviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.session_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/showview"});
				}
			}
		}
	});
}])

.controller('showviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$window', '$sce', '$timeout', 'Upload', 'dateFilter', 'anycodesService', 'dialogService', 'listsService', 'arenaService', 'authenticationService', 'translationService', 'parseISOdateService', function($rootScope, $scope, $http, $uibModal, $window, $sce, $timeout, Upload, dateFilter, anycodesService, dialogService, listsService, arenaService, authenticationService, translationService, parseISOdateService) {
	$scope.progName = "showView";
	$scope.currentShow = null;
	$scope.newShow = null;
	$scope.selectedLeftObj = null;
	$scope.isFormPristine = true;
	$scope.coursecodefilter = null;
	$scope.filternumbersdate = null;	
	$scope.filternumbersdatestr = null;	
	$scope.filterarena = null;	
	$scope.remarkable = new Remarkable({
			html:         false,        // Enable HTML tags in source
			xhtmlOut:     false,        // Use '/' to close single tags (<br />)
			breaks:       false         // Convert '\n' in paragraphs into <br>
//      langPrefix:   'language-',  // CSS language prefix for fenced blocks
//      typographer:  false,        // Enable some language-neutral replacement + quotes beautification
//      quotes: '����',             // Double + single quotes replacement pairs, when typographer enabled, and smartquotes on. Set doubles to '��' for Russian, '��' for German.
//      highlight: function (/*str, lang*/) { return ''; } // Highlighter function. Should return escaped HTML, or '' if the source string is not changed
		});

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.detailsForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function() {
		$scope.detailsForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.copyShow = function(confirmed) {
		if ($scope.isDirty()) {
			dialogService.alertDlg($scope.translationObj.main.msgerrpleasesavefirst, null);
		} else {
			if ($scope.currentShow != null && !confirmed) {
				dialogService.confirmDlg($scope.translationObj.main.msgconfirmcopy, "YESNO", $scope.copyShow, null, true);
			} else {
				$scope.promise = $http({
						method: 'post',
						url: './showview/shows.php',
						data: $.param({'showid' : $scope.currentShow.id, 'copyicetimes' : true, 'copycourses' : true, 'copycharges' : true, 'copyrules' : true, 'type' : 'copyShow' }),
						headers: {'Content-Type': 'application/x-www-form-urlencoded'}
					}).
					success(function(data, status, headers, config) {
						if (data.success) {
							dialogService.alertDlg($scope.translationObj.main.msgshowcopied, null);
						} else {
							dialogService.displayFailure(data);
						}
					}).
					error(function(data, status, headers, config) {
						dialogService.displayFailure(data);
						return false;
					});
			}
		}
	};

	$scope.activateShow = function() {
		if ($scope.currentShow != null) {
			if ($scope.isDirty()) {
				dialogService.alertDlg($scope.translationObj.main.msgerrpleasesavefirst, null);
			} else {
				$scope.promise = $http({
						method: 'post',
						url: './showview/shows.php',
						data: $.param({'showid' : $scope.currentShow.id, 'type' : 'activateShow' }),
						headers: {'Content-Type': 'application/x-www-form-urlencoded'}
					}).
					success(function(data, status, headers, config) {
						if (data.success) {
							dialogService.alertDlg($scope.translationObj.main.msgshowactivated, null);
							$scope.currentShow.active = "1";	//Do not relead. Set field manually.
						} else {
							dialogService.displayFailure(data);
						}
					}).
					error(function(data, status, headers, config) {
						dialogService.displayFailure(data);
						return false;
					});
			}
		}
	};

	$scope.getAllShows = function () {
		$scope.promise = $http({
				method: 'post',
				url: './showview/shows.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllShows' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
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
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.fixTimeField = function(object, dateFieldName) {
		// Time field not null and is not a date object
		if (object[dateFieldName] != null && object[dateFieldName].getDay == null) {
			if (object[dateFieldName] != '00:00:00') {
				object[dateFieldName + 'str'] = object[dateFieldName];
				object[dateFieldName] = parseISOdateService.parseDate("1970-01-01T" + object[dateFieldName]);
			} else {
				object[dateFieldName] = null;
			}
		// Time field not null and is a date object
		} else if (object[dateFieldName] != null && object[dateFieldName].getDay != null) {
			object[dateFieldName + 'str'] = dateFilter(object[dateFieldName], 'HH:mm:ss');
		} else {
			object[dateFieldName] = null;
		}
	}

	$scope.fixDateField = function(object, dateFieldName) {
		// Date field not null and is not a date object
		if (object[dateFieldName] != null && object[dateFieldName].getDay == null) {
			if (object[dateFieldName] != '0000-00-00') {
				object[dateFieldName + 'str'] = object[dateFieldName];
				object[dateFieldName] = parseISOdateService.parseDate(object[dateFieldName] + "T00:00:00");
			} else {
				object[dateFieldName] = null;
			}
		// Date field not null and is a date object
		} else if (object[dateFieldName] != null && object[dateFieldName].getDay != null) {
			object[dateFieldName + 'str'] = dateFilter(object[dateFieldName], 'yyyy-MM-dd');
		} else {
			object[dateFieldName] = null;
			object[dateFieldName + 'str'] = null;
		}
	}

	$scope.fixNumbers = function() {
		for (var i = 0; i < $scope.currentShow.numbers.length; i++) {
			$scope.fixDateField($scope.currentShow.numbers[i], 'practicesstartdate');
			$scope.fixDateField($scope.currentShow.numbers[i], 'practicesenddate');
			for (var j = 0; j < $scope.currentShow.numbers[i].schedules.length; j++) {
				$scope.fixTimeField($scope.currentShow.numbers[i].schedules[j], 'starttime');
				$scope.fixTimeField($scope.currentShow.numbers[i].schedules[j], 'endtime');
			} 
			for (var j = 0; j < $scope.currentShow.numbers[i].dates.length; j++) {
				$scope.fixTimeField($scope.currentShow.numbers[i].dates[j], 'starttime');
				$scope.fixTimeField($scope.currentShow.numbers[i].dates[j], 'endtime');
				$scope.fixDateField($scope.currentShow.numbers[i].dates[j], 'practicedate');
			} 
		}
	}

	$scope.fixPerformances = function() {
		for (var i = 0; i < $scope.currentShow.performances.length; i++) {
			$scope.fixDateField($scope.currentShow.performances[i], 'perfdate');
			$scope.fixTimeField($scope.currentShow.performances[i], 'starttime');
			$scope.fixTimeField($scope.currentShow.performances[i], 'endtime');
			$scope.fixTimeField($scope.currentShow.performances[i], 'skatersarrivaltime');
			$scope.fixTimeField($scope.currentShow.performances[i], 'skatersdeparturetime');
			$scope.fixTimeField($scope.currentShow.performances[i], 'volunteersarrivaltime');
			$scope.fixTimeField($scope.currentShow.performances[i], 'volunteersdeparturetime');
		}
	}

	$scope.getShowDetails = function(show) {
		$scope.promise = $http({
			method: 'post',
			url: './showview/shows.php',
			data: $.param({'id' : show.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getShowDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentShow = data.data[0];
				$scope.currentShow.practicesstartdate = parseISOdateService.parseDate($scope.currentShow.practicesstartdate + "T00:00:00");
				$scope.currentShow.practicesenddate = 	parseISOdateService.parseDate($scope.currentShow.practicesenddate + "T00:00:00");
				$scope.fixPerformances();
				for (var i = 0; i < $scope.currentShow.paragraphs.length; i++) {
					$scope.convertParagraph($scope.currentShow.paragraphs[i]);
				}
				
				for (var i = 0; i < $scope.currentShow.performances.length; i++) {
					// We need to reconcile the numberlist with the real numbers of the show
					$scope.currentShow.performances[i].numbers = [];
					// First check if id is from the number list
					for (var x = 0; x < $scope.currentShow.performances[i].numberlist.length; x++) {
						var found = false;
						for (var y = 0; y < $scope.currentShow.numbers.length; y++) {
							if ($scope.currentShow.performances[i].numberlist[x].numberid == $scope.currentShow.numbers[y].id) {
								$scope.currentShow.performances[i].numbers.push($scope.currentShow.numbers[y]);
								found = true;
								break;
							}
						}
					// If not found, check if id is from the intervention list
						if (!found) {
							for (var y = 0; y < $scope.currentShow.interventions.length; y++) {
								if ($scope.currentShow.performances[i].numberlist[x].numberid == $scope.currentShow.interventions[y].id) {
									$scope.currentShow.performances[i].numbers.push($scope.currentShow.interventions[y]);
									found = true;
									break;
								}
							}
						}
					}
				}
        $scope.currentShow.displayimagefilename = $scope.currentShow.imagefilename + '?decache=' + Math.random();
        $scope.currentShow.paragraphSelected = null;

				$scope.fixNumbers();
				$scope.filternumbersdate = null;	
				$scope.filternumbersdatestr = null;	
				$scope.filterarena = null;	

				listsService.getAllSessionCourses($scope, $scope.currentShow.sessionid, authenticationService.getCurrentLanguage());

				$scope.manageAllPraticeDates();
			} else {
				dialogService.displayFailure(data);
			}
			$rootScope.repositionLeftColumn();
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (show, index) {
		if (show != null) {
			$scope.selectedLeftObj = show;
			// $scope.selectedShow = show;
			$scope.getShowDetails(show);
			$scope.selectedLeftObj = show;
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentShow = null;
			$scope.selectedLeftObj = null;
		}
	}

	$scope.setCurrent = function (show, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, show, index);
		} else {
			$scope.setCurrentInternal(show, index);
		}
	};

	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentShow != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdeleteshow, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './showview/shows.php',
				data: $.param({'show' : JSON.stringify($scope.currentShow), 'type' : 'delete_show' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedLeftObj),1);
					$scope.setCurrentInternal(null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	}

	$scope.validateAllForms = function() {
		var retVal = true;
		$scope.globalErrorMessage = [];
		$scope.globalWarningMessage = [];

		if ($scope.detailsForm.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}

		if ($scope.globalErrorMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function() {$("#mainglobalerrormessage").hide();});
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function() {$("#mainglobalwarningmessage").hide();});
		}
		return retVal;
	}

	$scope.saveToDB = function() {
		if ($scope.currentShow == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
//			$scope.currentShow.startdatestr = 							dateFilter($scope.currentShow.startdate, 'yyyy-MM-dd');
//			$scope.currentShow.enddatestr = 								dateFilter($scope.currentShow.enddate, 'yyyy-MM-dd');
			$scope.currentShow.practicesstartdatestr = 			dateFilter($scope.currentShow.practicesstartdate, 'yyyy-MM-dd');
			$scope.currentShow.practicesenddatestr = 				dateFilter($scope.currentShow.practicesenddate, 'yyyy-MM-dd');
//			$scope.currentShow.onlineregiststartdatestr = 	dateFilter($scope.currentShow.onlineregiststartdate, 'yyyy-MM-dd');
//			$scope.currentShow.onlineregistenddatestr = 		dateFilter($scope.currentShow.onlineregistenddate, 'yyyy-MM-dd');
//			$scope.currentShow.reimbursementdatestr = 			dateFilter($scope.currentShow.reimbursementdate, 'yyyy-MM-dd');
//			$scope.currentShow.proratastartdatestr = 			dateFilter($scope.currentShow.proratastartdate, 'yyyy-MM-dd');

//			for (var i = 0; i < $scope.currentShow.performances.length; i++) {
//				$scope.currentShow.performances[i].perfdate 							 = dateFilter($scope.currentShow.performances[i].perfdate, 'yyyy-MM-dd');
//				$scope.currentShow.performances[i].starttime 							 = dateFilter($scope.currentShow.performances[i].starttime, 'HH:mm:ss');
//				$scope.currentShow.performances[i].endtime 								 = dateFilter($scope.currentShow.performances[i].endtime, 'HH:mm:ss');
//				$scope.currentShow.performances[i].skatersarrivaltime 		 = dateFilter($scope.currentShow.performances[i].skatersarrivaltime, 'HH:mm:ss');
//				$scope.currentShow.performances[i].skatersdeparturetime 	 = dateFilter($scope.currentShow.performances[i].skatersdeparturetime, 'HH:mm:ss');
//				$scope.currentShow.performances[i].volunteersarrivaltime 	 = dateFilter($scope.currentShow.performances[i].volunteersarrivaltime, 'HH:mm:ss');
//				$scope.currentShow.performances[i].volunteersdeparturetime = dateFilter($scope.currentShow.performances[i].volunteersdeparturetime, 'HH:mm:ss');
//			}

			$scope.fixPerformances();
			$scope.fixNumbers();

//			for (var i = 0; i < $scope.currentShow.numbers.length; i++) {
//				for (var j = 0; j < $scope.currentShow.numbers[i].schedules.length; j++) {
//					$scope.currentShow.numbers[i].schedules[j].startdatestr =	dateFilter($scope.currentShow.numbers[i].schedules[j].startdate, 'yyyy-MM-dd');
//					$scope.currentShow.numbers[i].schedules[j].enddatestr  	=	dateFilter($scope.currentShow.numbers[i].schedules[j].enddate, 'yyyy-MM-dd');
//				}
//			}
//			for (var i = 0; i < $scope.currentShow.showCharges.length; i++) {
//				$scope.currentShow.showCharges[i].startdate = dateFilter($scope.currentShow.showCharges[i].startdate, 'yyyy-MM-dd');
//				$scope.currentShow.showCharges[i].enddate 	= dateFilter($scope.currentShow.showCharges[i].enddate, 'yyyy-MM-dd');
//			}

			$scope.promise = $http({
				method: 'post',
				url: './showview/shows.php',
				data: $.param({'show' : JSON.stringify($scope.currentShow), 'type' : 'updateEntireShow' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this show to reset everything
					$scope.setCurrentInternal($scope.selectedLeftObj, null);
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	$scope.addShowToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './showview/shows.php',
			data: $.param({'show' : $scope.newShow, 'type' : 'insert_show' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newShow = {id:data.id, name:$scope.newShow.name};
				$scope.leftobjs.push(newShow);
				// We could sort the list....
				$scope.setCurrentInternal(newShow);
				return true;
			} else {
				dialogService.displayFailure(data);
				return false;
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		});
	};

	// This is the function that creates the modal to create new show
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newShow = {};
			// Send the newShow to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'showview/newshow.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newShow;
						}
					}
			})
			.result.then(function(newShow) {
					// User clicked OK and everything was valid.
					$scope.newShow = newShow;
					if ($scope.addShowToDB() == true) {
					}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// Creates an array of all courses' dates, ordered by dates/arena/ices
	$scope.manageAllPraticeDates = function() {
		$scope.currentShow.allpracticesdates = [];
		for (var i = 0; i < $scope.currentShow.numbers.length; i++) {
			$scope.currentShow.allpracticesdates = $scope.currentShow.allpracticesdates.concat($scope.currentShow.numbers[i].dates);
		}
		$scope.currentShow.allpracticesdates.sort(
			function(a, b){
				if (a.practicedate < b.practicedate) return -1;
				if (a.practicedate > b.practicedate) return 1;
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

	$scope.onFilterArenaChange = function() {
		$scope.filterarena = $scope.currentShow.filterarena != '' ? $scope.currentShow.filterarena : null;
	}
	
	$scope.onFilterNumbersDateChange = function() {
		$scope.filternumbersdatestr = dateFilter($scope.currentShow.filternumbersdate, 'yyyy-MM-dd');
	}
	
	$scope.onArenaChange = function(newObj) {
		newObj.iceid = null;
		$scope.ices = arenaService.getArenaIces($scope, newObj.arenaid);
	}

	$scope.onCourseChange = function(newObj) {
		newObj.courselevel = null;
		$scope.courselevels = listsService.getAllCourseLevels($scope, authenticationService.getCurrentLanguage(), newObj.coursecode);
	}
	
	$scope.filterByNumbersDate = function(item) {
		var retVal = false;
		if ($scope.filternumbersdatestr == null || item.practicedatestr == $scope.filternumbersdatestr) {
			if ($scope.filterarena == null || item.arenaid == $scope.filterarena) {
				retVal = true;
			}
		}
		return retVal;
	}

	$scope.setStaffList = function(newObj) {
		if (newObj.staffcode == 'COACH') {
			$scope.staffs = $scope.coaches;
		} else if (newObj.staffcode == 'PA') {
			$scope.staffs = $scope.programassistants;
		}
	}

	$scope.onStaffcodeChange = function(newObj) {
		newObj.memberid = null;
		$scope.setStaffList(newObj);
	}

	// This is the function that creates the modal to create/edit courses
	$scope.editShowIntervention = function(newIntervention) {
		$scope.newIntervention = {};
		$scope.currentIntervention = newIntervention;
		angular.copy(newIntervention, $scope.newIntervention);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newintervention.template.html',
				controller: 'childeditorex.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: 	function() {return $scope.newIntervention;},						// The object to edit
					control: 	function() {return $scope.editInterventionsControl;},		// The control object containing all validation functions
					callback: function() {return null;}																// Callback function to overwrite the normal validation
				}
		})
		.result.then(function(newIntervention) {
			// User clicked OK and everything was valid.
			angular.copy(newIntervention, $scope.currentIntervention);
			if ($scope.currentIntervention.id != null) {
				$scope.currentIntervention.status = 'Modified';
			} else {
				$scope.currentIntervention.status = 'New';
				if ($scope.currentShow.interventions == null) $scope.currentShow.interventions = [];
				if ($scope.currentShow.interventions.indexOf($scope.currentIntervention) == -1) {
					$scope.currentShow.interventions.push($scope.currentIntervention);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit numbers
	$scope.editShowNumber = function(newNumber) {
		$scope.newNumber = {};
		$scope.currentNumber = newNumber;
		angular.copy(newNumber, $scope.newNumber);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newnumber.template.html',
				controller: 'childeditorex.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: 	function() {return $scope.newNumber;},						// The object to edit
					control: 	function() {return $scope.editNumbersControl;},		// The control object containing all validation functions
					callback: function() {return null;}													// Callback function to overwrite the normal validation
				}
		})
		.result.then(function(newNumber) {
			// User clicked OK and everything was valid.
//			newNumber.availableonlinelabel = anycodesService.convertCodeToDesc($scope, 'yesnos', newNumber.availableonline);
			if (newNumber.datesgenerated != 1) newNumber.datesgenerated = 0;
			newNumber.datesgeneratedlabel = anycodesService.convertCodeToDesc($scope, 'yesnos', newNumber.datesgenerated);
			if (newNumber.canbeinperformance != 1) newNumber.canbeinperformance = 0;
			newNumber.canbeinperformancelabel = anycodesService.convertCodeToDesc($scope, 'yesnos', newNumber.canbeinperformance);
			if (newNumber.numberlabel == null) newNumber.numberlabel = authenticationService.getCurrentLanguage() == 'fr-ca' ? newNumber.label_fr : newNumber.label_en;
			angular.copy(newNumber, $scope.currentNumber);
			if ($scope.currentNumber.id != null) {
				$scope.currentNumber.status = 'Modified';
			} else {
				$scope.currentNumber.status = 'New';
				$scope.currentNumber.schedules = [];
				$scope.currentNumber.staffs = [];
				$scope.currentNumber.dates = [];
				if ($scope.currentShow.numbers == null) $scope.currentShow.numbers = [];
				if ($scope.currentShow.numbers.indexOf($scope.currentNumber) == -1) {
					$scope.currentShow.numbers.push($scope.currentNumber);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

//	$scope.editShowNumberInvite = function(showNumber, newInvite, type) {
//		if (type == 1) {
//			$scope.editShowNumberInviteGroup(showNumber, newInvite);
//		}
//		if (type == 2) {
//			$scope.editShowNumberInviteSkater(showNumber, newInvite);
//		}
//	}

	// This is the function that creates the modal to create numbers' member invite
	$scope.editShowNumberInviteSkater = function(showNumber) {
		var models = {selected: null, lists: {"skaters": []}};
		$scope.showNumber = showNumber;
		models.lists.skaters = $scope.showNumber.members ? $scope.showNumber.members.slice() : [];
		
		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newnumberinviteskater.template.html',
				controller: 'childeditorex.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: 	function() {return {'showNumber':$scope.showNumber, 'models':models, 'sessionid':$scope.currentShow.sessionid};},				// The object to edit
					control: 	function() {return $scope.editNumbersInviteControl;},		// The control object containing all validation functions
					callback: function() {return null;}													// Callback function to overwrite the normal validation
				}
		})
		.result.then(function(result) {
			// User clicked OK and everything was valid.
			$scope.showNumber.members = result.models.lists.skaters;
			$scope.showNumber.membersdirty = 1;
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};
//	$scope.editNumbersInviteControl = {};
//	$scope.editNumbersInviteControl.remove = function(idx) {
//		var items = $scope.newInvite.skaters.splice(idx, 1);
//		scope.membersFound.push(items[0]);
//		scope.membersFound.sort(function(a, b) {
//				if (a.lastname.toLowerCase() < b.lastname.toLowerCase()) return -1;
//				if (a.lastname.toLowerCase() > b.lastname.toLowerCase()) return 1;
//				if (a.lastname.toLowerCase() == b.lastname.toLowerCase()) {
//					if (a.firstname.toLowerCase() < b.firstname.toLowerCase()) return -1;
//					if (a.firstname.toLowerCase() > b.firstname.toLowerCase()) return 1;
//				} 
//				return 0;
//			})
//	}

	// This is the function that creates the modal to create numbers' group invite
	$scope.editShowNumberInviteGroup = function(showNumber, newInvite) {
		$scope.showNumber = showNumber;
		$scope.newInvite = {};
		$scope.currentInvite = newInvite;
		angular.copy(newInvite, $scope.newInvite);
		$scope.newInvite.type = 1;
		
		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newnumberinvitegroup.template.html',
				controller: 'childeditorex.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: 	function() {return $scope.newInvite;},						// The object to edit
					control: 	function() {return $scope.editNumbersInviteControl;},		// The control object containing all validation functions
					callback: function() {return null;}													// Callback function to overwrite the normal validation
				}
		})
		.result.then(function(newInvite) {
			// User clicked OK and everything was valid.
//			newInvite.grouplabel = anycodesService.convertCodeToDesc($scope, 'yesnos', newNumber.availableonline);
			newInvite.typelabel = anycodesService.convertCodeToDesc($scope, 'numberinvitetypes', newInvite.type);
			newInvite.name = anycodesService.convertIdToDesc($scope, 'sessionCourses', newInvite.groupormemberid);
			angular.copy(newInvite, $scope.currentInvite);
			if ($scope.currentInvite.id != null) {
				$scope.currentInvite.status = 'Modified';
			} else {
				$scope.currentInvite.status = 'New';
				if ($scope.showNumber.invites == null) $scope.showNumber.invites = [];
				if ($scope.showNumber.invites.indexOf($scope.currentInvite) == -1) {
					$scope.showNumber.invites.push($scope.currentInvite);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit courses' sub levels
	$scope.editShowCourseSublevel = function(course, newSublevel) {
		$scope.course = course;
		$scope.newSublevel = {};
		$scope.currentSublevel = newSublevel;
		angular.copy(newSublevel, $scope.newSublevel);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newsublevel.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newSublevel;
					}
				}
		})
		.result.then(function(newSublevel) {
			// User clicked OK and everything was valid.
			angular.copy(newSublevel, $scope.currentSublevel);
			if ($scope.currentSublevel.id != null) {
				$scope.currentSublevel.status = 'Modified';
			} else {
				$scope.currentSublevel.status = 'New';
				if ($scope.course.sublevels == null) $scope.course.sublevels = [];
				// Don't insert twice in list
				if ($scope.course.sublevels.indexOf($scope.currentSublevel) == -1) {
					$scope.course.sublevels.push($scope.currentSublevel);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit courses' staffs
	$scope.editShowNumberStaff = function(showNumber, newStaff) {
		$scope.showNumber = showNumber;
		$scope.newStaff = {};
		$scope.setStaffList(newStaff);
		// Keep a pointer to the current staff
		$scope.currentStaff = newStaff;
		// Copy in another object
		angular.copy(newStaff, $scope.newStaff);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newstaff.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newStaff;
					}
				}
		})
		.result.then(function(newStaff) {
			// User clicked OK and everything was valid.
			if (newStaff.staffcode == 'COACH') {
				newStaff.name = anycodesService.convertIdToDesc($scope, 'coaches', newStaff.memberid);
			} else if (newStaff.staffcode == 'PA') {
				newStaff.name = anycodesService.convertIdToDesc($scope, 'programassistants', newStaff.memberid);
			}
			newStaff.staffcodelabel = anycodesService.convertCodeToDesc($scope, 'numberstaffcodes', newStaff.staffcode);
//			newStaff.statuscodelabel = anycodesService.convertCodeToDesc($scope, 'personnelstatus', newStaff.statuscode);
			angular.copy(newStaff, $scope.currentStaff);
			if ($scope.currentStaff.id != null) {
				$scope.currentStaff.status = 'Modified';
			} else {
				$scope.currentStaff.status = 'New';
				if ($scope.showNumber.staffs == null) $scope.showNumber.staffs = [];
				// Don't insert twice in list
				if ($scope.showNumber.staffs.indexOf($scope.currentStaff) == -1) {
					$scope.showNumber.staffs.push($scope.currentStaff);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit courses' schedules
	$scope.editShowNumberSchedule = function(showNumber, newSchedule) {
		$scope.showNumber = showNumber;
		$scope.newSchedule = {};
		$scope.currentSchedule = newSchedule;
		angular.copy(newSchedule, $scope.newSchedule);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newschedule.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newSchedule;
					}
				}
		})
		.result.then(function(newSchedule) {
			// User clicked OK and everything was valid.
			newSchedule.icelabel = arenaService.convertArenaIceToCurrentDesc($scope, newSchedule.arenaid, newSchedule.iceid);
			newSchedule.starttimestr = dateFilter(newSchedule.starttime, 'HH:mm:ss');
			newSchedule.endtimestr = dateFilter(newSchedule.endtime, 'HH:mm:ss');
			angular.copy(newSchedule, $scope.currentSchedule);
			if ($scope.currentSchedule.id != null) {
				$scope.currentSchedule.status = 'Modified';
			} else {
				$scope.currentSchedule.status = 'New';
				if ($scope.showNumber.schedules == null) $scope.showNumber.schedules = [];
				// Don't insert twice in list
				if ($scope.showNumber.schedules.indexOf($scope.currentSchedule) == -1) {
					$scope.showNumber.schedules.push($scope.currentSchedule);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit courses' dates
	$scope.editShowNumberDate = function(showNumber, newShowNumberDate) {
		$scope.showNumber = showNumber;
		$scope.newShowNumberDate = {};
		$scope.currentShowNumberDate = newShowNumberDate;
		angular.copy(newShowNumberDate, $scope.newShowNumberDate);
		if ($scope.newShowNumberDate.practicedate) $scope.newShowNumberDate.showNumberDate = parseISOdateService.parseDate($scope.newShowNumberDate.showNumberDate + "T00:00:00");
		$scope.ices = arenaService.getArenaIces($scope, $scope.newShowNumberDate.arenaid);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newnumberdate.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newShowNumberDate;
					}
				}
		})
		.result.then(function(newShowNumberDate) {
			// User clicked OK and everything was valid.
			angular.copy(newShowNumberDate, $scope.currentShowNumberDate);
			$scope.currentShowNumberDate.icelabel 			= arenaService.convertArenaIceToCurrentDesc($scope, $scope.currentShowNumberDate.arenaid, $scope.currentShowNumberDate.iceid);
			$scope.currentShowNumberDate.arenalabel 		= arenaService.convertArenaToCurrentDesc($scope, $scope.currentShowNumberDate.arenaid);
			$scope.currentShowNumberDate.daylabel 			= anycodesService.convertCodeToDesc($scope, 'days', $scope.currentShowNumberDate.day);
			if ($scope.currentShowNumberDate.canceled == null || $scope.currentShowNumberDate.canceled == '') {
				$scope.currentShowNumberDate.canceled = 0;
			}
			$scope.currentShowNumberDate.canceledlabel 	= anycodesService.convertCodeToDesc($scope, 'yesnos', $scope.currentShowNumberDate.canceled);
			$scope.fixTimeField($scope.currentShowNumberDate, 'starttime');
			$scope.fixTimeField($scope.currentShowNumberDate, 'endtime');
			$scope.fixDateField($scope.currentShowNumberDate, 'practicedate');
			if (!$scope.currentShowNumberDate.day) {
				$scope.currentShowNumberDate.day = $scope.currentShowNumberDate.practicedate.getDay()/1;
			}
			$scope.currentShowNumberDate.daylabel 	= anycodesService.convertCodeToDesc($scope, 'days', $scope.currentShowNumberDate.day);
			if ($scope.currentShowNumberDate.id != null) {
				$scope.currentShowNumberDate.status = 'Modified';
			} else {
				$scope.currentShowNumberDate.status = 'New';
				if ($scope.showNumber.dates == null) $scope.showNumber.dates = [];
				if ($scope.showNumber.dates.indexOf($scope.currentShowNumberDate) == -1) {
					$scope.showNumber.dates.push($scope.currentShowNumberDate);
				}
			}
			$scope.manageAllPraticeDates();
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit charges
	$scope.editShowCharge = function(newCharge) {
		$scope.newCharge = {};
		$scope.currentCharge = newCharge;
		angular.copy(newCharge, $scope.newCharge);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newcharge.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newCharge;
					}
				}
		})
		.result.then(function(newCharge) {
			// User clicked OK and everything was valid.
			newCharge.startdatestr	= dateFilter(newCharge.startdate, 'yyyy-MM-dd');
			newCharge.enddatestr	= dateFilter(newCharge.enddate, 'yyyy-MM-dd');
			angular.copy(newCharge, $scope.currentCharge);
			if ($scope.currentCharge.id != null) {
				$scope.currentCharge.status = 'Modified';
			} else {
				$scope.currentCharge.status = 'New';
				if ($scope.currentShow.showCharges == null)$scope.currentShow.showCharges = [];
				if ($scope.currentShow.showCharges.indexOf($scope.currentCharge) == -1) {
					$scope.currentShow.showCharges.push($scope.currentCharge);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit ice times
	$scope.editShowIcetime = function(newIcetime) {
		$scope.newIcetime = {};
		$scope.currentIcetime = newIcetime;
		angular.copy(newIcetime, $scope.newIcetime);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newicetime.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newIcetime;
					}
				}
		})
		.result.then(function(newIcetime) {
			// User clicked OK and everything was valid.
			newIcetime.icelabel = arenaService.convertArenaIceToCurrentDesc($scope, newIcetime.arenaid, newIcetime.iceid);//, 'en-ca'/*$scope.context.preferedlanguage*/);
			angular.copy(newIcetime, $scope.currentIcetime);
			if ($scope.currentIcetime.id != null) {
				$scope.currentIcetime.status = 'Modified';
			} else {
				$scope.currentIcetime.status = 'New';
				if ($scope.currentShow.icetimes == null)$scope.currentShow.icetimes = [];
				if ($scope.currentShow.icetimes.indexOf($scope.currentIcetime) == -1) {
					$scope.currentShow.icetimes.push($scope.currentIcetime);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit registrations
	$scope.editShowRegistration = function(newRegistration) {
		$scope.newRegistration = {};
		$scope.currentRegistration = newRegistration;
		angular.copy(newRegistration, $scope.newRegistration);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newregistration.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newRegistration;
					}
				}
			})
			.result.then(function(newRegistration) {
				// User clicked OK and everything was valid.
				newRegistration.registrationdatestr	= dateFilter(newRegistration.registrationdate, 'yyyy-MM-dd');
				newRegistration.starttimestr				= dateFilter(newRegistration.starttime, 'HH:mm:ss');
				newRegistration.endtimestr					= dateFilter(newRegistration.endtime, 'HH:mm:ss');
				angular.copy(newRegistration, $scope.currentRegistration);
				if ($scope.currentRegistration.id != null) {
					$scope.currentRegistration.status = 'Modified';
				} else {
					$scope.currentRegistration.status = 'New';
					if ($scope.currentShow.registrations == null)$scope.currentShow.registrations = [];
					if ($scope.currentShow.registrations.indexOf($scope.currentRegistration) == -1) {
						$scope.currentShow.registrations.push($scope.currentRegistration);
					}
				}
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit events
	$scope.editShowPerformance = function(newPerformance) {
		$scope.newPerformance = {};
		$scope.currentPerformance = newPerformance;
		angular.copy(newPerformance, $scope.newPerformance);
		$scope.ices = arenaService.getArenaIces($scope, newPerformance.arenaid);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newperformance.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newPerformance;
					}
				}
			})
			.result.then(function(newPerformance) {
				// User clicked OK and everything was valid.
				newPerformance.perfdatestr									= dateFilter(newPerformance.perfdate, 'yyyy-MM-dd');
				newPerformance.starttimestr 							 	= dateFilter(newPerformance.starttime, 'HH:mm:ss');
				newPerformance.endtimestr 								 	= dateFilter(newPerformance.endtime, 'HH:mm:ss');
				newPerformance.skatersarrivaltimestr 		 		= dateFilter(newPerformance.skatersarrivaltime, 'HH:mm:ss');
				newPerformance.skatersdeparturetimestr 		 	= dateFilter(newPerformance.skatersdeparturetime, 'HH:mm:ss');
				newPerformance.volunteersarrivaltimestr 	 	= dateFilter(newPerformance.volunteersarrivaltime, 'HH:mm:ss');
				newPerformance.volunteersdeparturetimestr 	= dateFilter(newPerformance.volunteersdeparturetime, 'HH:mm:ss');
				angular.copy(newPerformance, $scope.currentPerformance);
				if (authenticationService.getCurrentLanguage() == 'fr-ca') {
					$scope.currentPerformance.performancelabel = $scope.currentPerformance.label_fr;
				} else {
					$scope.currentPerformance.performancelabel = $scope.currentPerformance.label_en;
				}
				if ($scope.currentPerformance.id != null) {
					$scope.currentPerformance.status = 'Modified';
				} else {
					$scope.currentPerformance.typelabel = anycodesService.convertCodeToDesc($scope, 'performancetypes', $scope.currentPerformance.type);
					$scope.currentPerformance.status = 'New';
					if ($scope.currentShow.performances == null) $scope.currentShow.performances = [];
					if ($scope.currentShow.performances.indexOf($scope.currentPerformance) == -1) {
						$scope.currentShow.performances.push($scope.currentPerformance);
					}
				}
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

// This is the function that creates the modal to create/edit performances's numbers
	$scope.editShowPerformanceNumbers = function(performance) {
		var models = {selected: null, lists: {"performanceNumbers": [], "remainingNumbers": [], "remainingInterventions": []}};
		$scope.performance = performance;
    performance.numbers = performance.numbers ? performance.numbers : [];
		angular.copy(performance.numbers, models.lists.performanceNumbers);
    
    // Create the list of numbers not already associated to the performance
    for (var i = 0; i < $scope.currentShow.numbers.length; i++) {
	    var numberFound = false;
    	for (var j = 0; j < performance.numbers.length; j++) {
    		if (performance.numbers[j].id == $scope.currentShow.numbers[i].id) {
    			numberFound = true;
    			break;
    		}
    	}
    	if (!numberFound && $scope.currentShow.numbers[i].canbeinperformance) {
        models.lists.remainingNumbers.push($scope.currentShow.numbers[i]);
    	}
    }

    // Create the list of interventions not already associated to the performance
    for (var i = 0; i < $scope.currentShow.interventions.length; i++) {
	    var interventionFound = false;
    	for (var j = 0; j < performance.numbers.length; j++) {
    		if (performance.numbers[j].id == $scope.currentShow.interventions[i].id) {
    			interventionFound = true;
    			break;
    		}
    	}
    	if (!interventionFound) {
        models.lists.remainingInterventions.push($scope.currentShow.interventions[i]);
    	}
    }


		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newperformancenumbers.template.html',
				controller: 'childeditorex.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: 	function() {return {'performance':$scope.performance, 'models':models};},				// The object to edit
					control: 	function() {return $scope.editPerfNumbersControl;},															// The control object containing all validation functions
					callback: function() {return null;}																												// Callback function to overwrite the normal validation
				}
		})
		.result.then(function(result) {
			// User clicked OK and everything was valid.
//			$scope.performance.numbers = result.models.lists.performanceNumbers;
			angular.copy(result.models.lists.performanceNumbers, $scope.performance.numbers);
			$scope.performance.status = 'Modified';
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit performance's prices
	$scope.editShowPerformancePrice = function(performance, newPrice) {
		$scope.newPrice = {};
		$scope.currentPerformance = performance;
		$scope.currentPrice = newPrice;
		angular.copy(newPrice, $scope.newPrice);

		$uibModal.open({
				animation: false,
				templateUrl: 'showview/newperformanceprice.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newPrice;
					}
				}
		})
		.result.then(function(newPrice) {
			// User clicked OK and everything was valid.
			angular.copy(newPrice, $scope.currentPrice);
			$scope.currentPrice.pricetypelabel = anycodesService.convertCodeToDesc($scope, 'showpricetypes', $scope.currentPrice.pricetype);

			if ($scope.currentPrice.id != null) {
				$scope.currentPrice.status = 'Modified';
			} else {
				$scope.currentPrice.status = 'New';
				if ($scope.currentPerformance.prices == null)$scope.currentPerformance.prices = [];
				if ($scope.currentPerformance.prices.indexOf($scope.currentPrice) == -1) {
					$scope.currentPerformance.prices.push($scope.currentPrice);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	$scope.generatePracticeDates = function(showNumber, forced) {
		if (!forced) {
			if (showNumber.datesgenerated == 1) {
				// Confirm the deletion of existing dates and creation of new dates
				dialogService.confirmDlg($scope.translationObj.main.msgdatesgenerated, "YESNO", $scope.generatePracticeDates, null, showNumber, true);
			} else {
				// Confirm creation of dates
				dialogService.confirmDlg($scope.translationObj.main.msggeneratedates, "YESNO", $scope.generatePracticeDates, null, showNumber, true);
			}
		} else {
			var dateArr = $scope.generatePracticeDateArray(showNumber);
			$scope.insertPracticeDates(showNumber, dateArr)
//			.then(
//			function(retVal) {
//				if (retVal.data.success) {
//					$scope.setCurrentInternal($scope.selectedLeftObj, null);
//				}
//			});
		}
	}

	$scope.insertPracticeDates = function(showNumber, practicesdatearr) {
		$scope.showNumberForDateInsert = showNumber;
		$scope.promise = $http({
			method: 'post',
			url: './showview/shows.php',
			data: $.param({'practicedates' : practicesdatearr, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'insertPracticeDate' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				angular.copy(data.showNumber[0], $scope.showNumberForDateInsert);
				$scope.fixNumbers();
				$scope.manageAllPraticeDates();
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
		});
	};

	$scope.generatePracticeDateArray = function(showNumber) {
		// We need to generates the date of the course. First, get the course schedule
		// For every schedule, find the first possible date based on the show start date and generate until you reach the enddate of show
		var dateArr = [];
		var tmpPracticeStartDate;
		var tmpPracticeEndDate;
		if (showNumber.practicesstartdate) {
			tmpPracticeStartDate = showNumber.practicesstartdate;
		} else {
			tmpPracticeStartDate = $scope.currentShow.practicesstartdate;
		}
		// If enddate defined for this course, use it. If not take session enddate.
		if (showNumber.practicesenddate) {
			tmpPracticeEndDate = showNumber.practicesenddate;
		} else {
			tmpPracticeEndDate = $scope.currentShow.practicesenddate;
		}
		tmpPracticeEndDate.setHours("23","59","00");
		for (var i = 0; i < showNumber.schedules.length; i++) {
			var schedule = showNumber.schedules[i];
			var day = schedule.day/1;
			// Find first date of course for this schedule
			var startday = tmpPracticeStartDate.getDay()/1; // This is the start day of the session
			var diff = (startday <= day) ? day-startday : day + 7 - (startday); // This is the difference in days
			var firstDate = new Date(new Date(tmpPracticeStartDate).setDate(tmpPracticeStartDate.getDate() + diff)); // First course date.
			var scheduleTime = schedule.starttimestr.split(":");
			firstDate.setHours(scheduleTime[0],scheduleTime[1],scheduleTime[2]);
			do  {
				var practicedatestr = dateFilter(firstDate, 'yyyy-MM-dd');
				dateArr.push({numberid : showNumber.id, showid : $scope.currentShow.id, arenaid: showNumber.schedules[i].arenaid, iceid : showNumber.schedules[i].iceid, practicedatestr : practicedatestr, starttime : schedule.starttimestr, endtime : schedule.endtimestr, duration : schedule.duration, day : schedule.day/1});
				firstDate = new Date(new Date(firstDate).setDate(firstDate.getDate() + 7));
			} while (firstDate <= tmpPracticeEndDate)
		}
		return dateArr;
	}

  // This is the function that displays the upload error messages
  $scope.displayUploadError = function(errFile) {
    // dialogService.alertDlg($scope.translationObj.details.msgerrinvalidfile);
    if (errFile.$error == 'maxSize') {
      dialogService.alertDlg($scope.translationObj.details.msgerrinvalidfilesize + errFile.$errorParam);
    } else if (errFile.$error == 'maxWidth') {
      dialogService.alertDlg($scope.translationObj.details.msgerrinvalidmaxwidth + errFile.$errorParam);
    } else if (errFile.$error == 'maxHeight') {
      dialogService.alertDlg($scope.translationObj.details.msgerrinvalidmaxheight + errFile.$errorParam);
    }
  }

  // This is the function that uploads the image for the current event
  $scope.uploadMainImage = function(file, errFiles) {
    $scope.f = file;
    if (errFiles && errFiles[0]) {
      $scope.displayUploadError(errFiles[0]);
    }
    if (file) {
      if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
        dialogService.alertDlg('only jpg files are allowed.');
        return;
      }
      file.upload = Upload.upload({
        url: './showview/uploadmainimage.php',
        method: 'POST',
        file: file,
        data: {
          'mainobj': $scope.currentShow
        }
      });
      file.upload.then(function (data) {
        $timeout(function () {
          if (data.data.success) {
            dialogService.alertDlg($scope.translationObj.websitedesc.msguploadcompleted);
            // Select this event to reset everything
            $scope.setCurrentInternal($scope.selectedShow, null);
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


	// This is the function that displays the upload error messages
//	$scope.displayUploadError = function(errFile) {
//		if (errFile.$error == 'maxSize') {
//			dialogService.alertDlg($scope.translationObj.main.msgerrinvalidfilesize + errFile.$errorParam);
//		} else if (errFile.$error == 'maxWidth') {
//			dialogService.alertDlg($scope.translationObj.main.msgerrinvalidmaxwidth + errFile.$errorParam);
//		} else if (errFile.$error == 'maxHeight') {
//			dialogService.alertDlg($scope.translationObj.main.msgerrinvalidmaxheight + errFile.$errorParam);
//		}
//	}

	// This is the function that uploads the rules file for the show
	$scope.uploadRulesFile = function(file, errFiles, language) {
		// $scope.f = file;
		// $scope.language = language;
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
		}
		if (file) {
			file.upload = Upload.upload({
					url: './showview/uploadrulesfile.php',
					method: 'POST',
					file: file,
					data: {
									'language': language,
									'mainobj': $scope.currentShow
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

	$scope.convertParagraph = function(paragraph) {
		if (paragraph) {
			paragraph.msgfr =  "<H3>" + (paragraph.title_fr!=null && paragraph.title_fr!='' ? paragraph.title_fr : '') + "</H3>" +
							"<H4>" + (paragraph.subtitle_fr!=null && paragraph.subtitle_fr!='' ? paragraph.subtitle_fr : '') + "</H4>" +
							"<p>" + (paragraph.paragraphtext_fr!=null && paragraph.paragraphtext_fr!='' ? $scope.remarkable.render(paragraph.paragraphtext_fr) : '') + "</p>";
			paragraph.msgfr =  $sce.trustAsHtml(paragraph.msgfr);
			paragraph.msgen =  "<H3>" + (paragraph.title_en!=null && paragraph.title_en!='' ? paragraph.title_en : '') + "</H3>" +
							"<H4>" + (paragraph.subtitle_en!=null && paragraph.subtitle_en!='' ? paragraph.subtitle_en : '') + "</H4>" +
							"<p>" + (paragraph.paragraphtext_en!=null && paragraph.paragraphtext_en!='' ? $scope.remarkable.render(paragraph.paragraphtext_en) : '') + "</p>";
			paragraph.msgen =  $sce.trustAsHtml(paragraph.msgen);
		}
	}

	// Opens the show calendar in another window. Pass the showid as a parameter using ?
	$scope.viewShowCalendar = function () {
		$window.open('./showscheduleview/showscheduleview.html?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=200,left=200,width=1400,height=700");
	}

	// Opens the show schedule in another window. Pass the showid as a parameter using ?
	$scope.viewShowSchedule = function () {
		$window.open('./showcoursesscheduleview/showcoursesscheduleview.html?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=200,left=200,width=1400,height=700");
	}

	// REPORTS
	$scope.printReport = function(reportName) {
		if (reportName == 'showSchedule') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id);
		}
		if (reportName == 'showCoursesSummary') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id);
		}
		if (reportName == 'showCoursesList') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id);
		}
		if (reportName == 'showCourseAttendance') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id);
		}
		if (reportName == 'showBillingList') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id);
		}
		if (reportName == 'showCoachesSchedule') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id);
		}
		if (reportName == 'showTaxReceipt') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id);
		}
		if (reportName == 'showCoursesListActive') {
			$window.open('./reports/showCoursesList.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id+'&activeonly=true');
		}
		if (reportName == 'showCoursesCount') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id+'&activeonly=true');
		}
		if (reportName == 'showSCRegistrations') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&showid='+$scope.currentShow.id);
		}
	}

	$scope.refreshAll = function() {
		$scope.getAllShows();
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'days', 'sequence', 'days');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'numberstaffcodes', 'sequence', 'numberstaffcodes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'performancetypes', 'sequence', 'performancetypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'numberregistrationtypes', 'sequence', 'numberregistrationtypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'numberinvitetypes', 'sequence', 'numberinvitetypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'showpricetypes', 'sequence', 'showpricetypes');
		arenaService.getAllArenas($scope, authenticationService.getCurrentLanguage());
		listsService.getAllCharges($scope, authenticationService.getCurrentLanguage());
		listsService.getCoaches($scope, authenticationService.getCurrentLanguage());
		listsService.getAllProgramAssistants($scope, authenticationService.getCurrentLanguage());
		// Get all sessions to relate this show to a session
		listsService.getAllSessionsEx($scope, authenticationService.getCurrentLanguage(), null, null);
		translationService.getTranslation($scope, 'showview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
	
	
	
	
	
//	$scope.models = {
//		selected: null,
//		lists: {"A": [], "B": []}
//	};
//
//	// Generate initial model
//	for (var i = 1; i <= 3; ++i) {
//	  $scope.models.lists.A.push({label: "Item A" + i});
//	  $scope.models.lists.B.push({label: "Item B" + i});
//	}

	// Model to JSON for demo purpose
//	$scope.$watch('models', function(model) {
//	  $scope.modelAsJson = angular.toJson(model, true);
//	}, true);
//


}]);
