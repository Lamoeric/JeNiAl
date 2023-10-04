'use strict';

angular.module('cpa_admin.registrationview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/registrationview', {
		templateUrl: 'registrationview/registrationview.html',
		controller: 'registrationviewCtrl',
		resolve: {
			auth: function($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.registration_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/registrationview"});
				}
			}
		}
	});
}])

.controller('registrationviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$q', 'anycodesService', 'dialogService', 'listsService', 'dateFilter', 'billingService', 'authenticationService', 'translationService', 'pricingService', 'parseISOdateService', function($rootScope, $scope, $http, $uibModal, $q, anycodesService, dialogService, listsService, dateFilter, billingService, authenticationService, translationService, pricingService, parseISOdateService) {
	$scope.currentLanguage = authenticationService.getCurrentLanguage();
	$scope.progName = "registrationView";
	$scope.currentRegistration = null;
	$scope.selectedLeftObj = null;
	$scope.newRegistration = null;
	$scope.isFormPristine = true;
	$scope.today = new Date();
	$scope.startSearchMember = false;
	$scope.selectedTab = '#member';
//	$scope.newFilter = {activeOnly:"1"};
//	$scope.newFilter.filterApplied = true;
	$scope.activeSession = null;
	$scope.coursecodefilter = null;
	$scope.leftobjs = [];

	// Filter function to display selected charges or courses in the summary
	$scope.filterSelected = function(obj) {
		return obj.selected == 1 || obj.selected_old == 1;
	}

	$scope.isDirty = function() {
		if ($scope.memberForm.$dirty || $scope.contactForm.$dirty || ($scope.summaryForm && $scope.summaryForm.$dirty)) {
			return true;
		}
		return false;
	};

	$scope.setDirty = function() {
		$scope.memberForm.$dirty = true;
		$scope.isFormPristine = false;
	};

	$scope.setPristine = function() {
		$scope.memberForm.$setPristine();
		$scope.contactForm.$setPristine();
		$scope.summaryForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllRegistrations = function(newFilter) {
//		if (newFilter) {
//			$scope.newFilter.filterApplied = true;
//		} else {
//			$scope.newFilter.filterApplied = false;
//		}
		$scope.promise = $http({
				method: 'post',
				url: './registrationview/manageregistrations.php',
				data: $.param({'eventType' : $scope.currentEvent.type, 'eventId' : $scope.currentEvent.id, 'filter' : newFilter, 'type' : 'getAllRegistrations'}),
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

	$scope.getRegistrationDetails = function(registration) {
		return ($scope.promise = $http({
			method: 'post',
			url: './registrationview/manageregistrations.php',
			data: $.param({'eventType' : registration.eventtype, 'eventId' : registration.eventid, 'id' : registration.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getRegistrationDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data)) {
				$scope.currentRegistration = data.data[0];
				$scope.currentRegistration.registrationdatestr = $scope.currentRegistration.registrationdate;
				$scope.currentRegistration.registrationdate = parseISOdateService.parseDateWithoutTime($scope.currentRegistration.registrationdate);
				$scope.currentRegistration.use_prorata = $scope.currentRegistration.use_prorata == "0" ? false : true;
				$scope.currentRegistration.prorataoptions = $scope.currentRegistration.prorataoptions/1;
				// Clear course codes filter
				$scope.coursecodefilter = null;
				if (!$scope.currentRegistration.member) {
					$scope.currentMember = $scope.currentRegistration.member = {};
				} else {
					for (var x = 0; x < $scope.currentRegistration.member.csbalanceribbons.length; x++) {
						$scope.currentRegistration.member.csbalanceribbons[x].ribbondate = parseISOdateService.parseDateWithoutTime($scope.currentRegistration.member.csbalanceribbons[x].ribbondate);
					}
					for (var x = 0; x < $scope.currentRegistration.member.csagilityribbons.length; x++) {
						$scope.currentRegistration.member.csagilityribbons[x].ribbondate = parseISOdateService.parseDateWithoutTime($scope.currentRegistration.member.csagilityribbons[x].ribbondate);
					}
					for (var x = 0; x < $scope.currentRegistration.member.cscontrolribbons.length; x++) {
						$scope.currentRegistration.member.cscontrolribbons[x].ribbondate = parseISOdateService.parseDateWithoutTime($scope.currentRegistration.member.cscontrolribbons[x].ribbondate);
					}
					for (var x = 0; x < $scope.currentRegistration.member.csstagebadges.length; x++) {
						$scope.currentRegistration.member.csstagebadges[x].badgedate = parseISOdateService.parseDateWithoutTime($scope.currentRegistration.member.csstagebadges[x].badgedate);
					}
				}
				if ($scope.startSearchMember == true) {
					$scope.startSearchMember = false;
					angular.element('#searchmember').trigger('click');
				}
				pricingService.applyPricingRules($scope.currentRegistration, $scope.currentRegistration.use_prorata, $scope.currentRegistration.prorataoptions);
				billingService.calculateBillAmounts($scope.currentRegistration.bill);
				$rootScope.repositionLeftColumn();
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		}));
	};

	// Used in the course.template.html. Switch to a directive.
	$scope.getCourseDelta = function(course) {
		var delta = 0;
		if (course.selected_old/1 == 0 && course.selected/1 == 0 && course.fees_old/1 > 0) {
			// Course was removed in a previous revision
			course.deltacode = 'REMOVED_CLOSED';
		} else {
			delta = course.selected_old/1 - course.selected/1;
			if (delta == 0) {
				course.deltacode = 'UNTOUCHED';
			} else if (delta == 1) {
				course.deltacode = 'REMOVED';
			} else if (delta == -1) {
				course.deltacode = 'ADDED';
			}
		}
		if (course.deltacode != 'UNTOUCHED') {
			return anycodesService.convertCodeToDesc($scope, "coursedeltatypes", course.deltacode);
		} else {
			return null;
		}
	}

	// Used in the course.template.html. Switch to a directive.
	$scope.getChargeDelta = function(charge) {
		var delta = charge.selected_old/1 - charge.selected/1;
		if (delta == 0) {
		} else if (delta == 1) {
			return anycodesService.convertCodeToDesc($scope, "coursedeltatypes", 'REMOVED');
		} else if (delta == -1) {
			return anycodesService.convertCodeToDesc($scope, "coursedeltatypes", 'ADDED');
		}
	}

	// This function uses the current registration data to update the one in the list on the left
	// TODO : When the registration is copied, the pane on the left never gets updated properly because the id of the registration changes.
	//        We need to check if the selectedLeftObj.id = registration.relatedolregistrationid
	$scope.changeselectedLeftObj = function(registration) {
		$scope.selectedLeftObj = registration != null ? registration : $scope.selectedLeftObj;
		if ($scope.selectedLeftObj != null && $scope.currentRegistration != null && $scope.selectedLeftObj.id == $scope.currentRegistration.id) {
			if ($scope.currentRegistration.member != null && $scope.currentRegistration.member.id != null) {
				if ($scope.currentRegistration.member.lastname != $scope.selectedLeftObj.memberlastname) {
					$scope.selectedLeftObj.memberlastname = $scope.currentRegistration.member.lastname;
				}
				if ($scope.currentRegistration.member.firstname != $scope.selectedLeftObj.memberfirstname) {
					$scope.selectedLeftObj.memberfirstname = $scope.currentRegistration.member.firstname;
				}
			}
			if ($scope.selectedLeftObj.status != $scope.currentRegistration.status) {
				$scope.selectedLeftObj.status = $scope.currentRegistration.status;
			}
			if ($scope.selectedLeftObj.sessionname != $scope.currentRegistration.sessionname) {
				$scope.selectedLeftObj.sessionname = $scope.currentRegistration.sessionname;
			}
			if ($scope.selectedLeftObj.registrationdate != dateFilter($scope.currentRegistration.registrationdate, 'yyyy-MM-dd')) {
				$scope.selectedLeftObj.registrationdate = dateFilter($scope.currentRegistration.registrationdate, 'yyyy-MM-dd');
			}
		}
	}

	$scope.setCurrentInternal = function(registration, index) {
		if (registration != null) {
			return $scope.getRegistrationDetails(registration).then(
			function(retVal) {
				$scope.setPristine();
				if ($scope.isnew) {
					$('.nav-tabs a[data-target="#member"]').tab('show');
					$scope.isnew = false;
				} else if (($scope.currentRegistration.status.indexOf('PRESENTED') == -1 && $scope.currentRegistration.status.indexOf('ACCEPTED') == -1) && ($scope.selectedTab == '#summary'|| $scope.selectedTab == null)) {
					// Select tab by name
					$('.nav-tabs a[data-target="#courses"]').tab('show');
				} else if ($scope.currentRegistration.status.indexOf('ACCEPTED') == -1 && ($scope.selectedTab == '#bill' || $scope.selectedTab == null)) {
					// Select tab by name
					$('.nav-tabs a[data-target="#courses"]').tab('show');
				}
				$scope.changeselectedLeftObj(registration);
			});
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentRegistration = null;
			$scope.setPristine();
		}
	}

	$scope.setCurrent = function(registration, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, registration, index);
		} else {
			$scope.setCurrentInternal(registration, index);
		}
	};

	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentRegistration != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdeleteregistration, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './registrationview/manageregistrations.php',
				data: $.param({'registration' : $scope.currentRegistration, 'type' : 'delete_registration' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					if ($scope.currentRegistration.status == 'DRAFT-R') {
						// If registration is in revised draft mode and is being deleted, we need to change the left object back to the original registration
						$scope.selectedLeftObj.id								= $scope.currentRegistration.relatedoldregistrationid;
						$scope.selectedLeftObj.registrationdate = null;
						$scope.selectedLeftObj.status 					= "ACCEPTED";
						$scope.setCurrentInternal($scope.selectedLeftObj, null);
					} else {
						$scope.leftobjs.splice($scope.leftobjs.indexOf($scope.selectedLeftObj), 1);
						$scope.setCurrentInternal(null);
					}
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

	$scope.saveToDB = function(member, forced, newStatus) {
		if (($scope.currentRegistration == null || !$scope.isDirty()) && !forced) {
			dialogService.alertDlg("Nothing to save!", null);
		} else if (!$scope.memberForm.$valid && !forced) {
			dialogService.alertDlg($scope.translationObj.main.msgmembermissinginfo, null, null, null, null);
		} else {
			$scope.currentRegistration.registrationdatestr = dateFilter($scope.currentRegistration.registrationdate, 'yyyy-MM-dd');
			if (member != null) {
				$scope.currentRegistration.member = member;
			}
			$scope.promise = $http({
				method: 'post',
				url: './registrationview/manageregistrations.php',
				data: $.param({'registration' : $scope.currentRegistration, 'newstatus': newStatus, 'type' : 'updateEntireRegistration' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this registration to reset everything
					$scope.setCurrentInternal($scope.selectedLeftObj, null);
					return true;
				} else {
					if (data.errno == 9999) {
						dialogService.displayFailure($scope.translationObj.main.msgregistrationalreadyexists);
					} else {
						dialogService.displayFailure(data);
					}
//					dialogService.displayFailure(data);
//					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	$scope.presentRegistrationEx = function() {
		var isValid = true;

		if ($scope.currentRegistration.status.indexOf("DRAFT") != -1) {  // Check for one of the draft status (DRAFT or DRAFT-R)
			if ($scope.currentRegistration.status == "DRAFT") {
				var isValid = false;
				if ($scope.currentRegistration.courses) {
					// Validate that one course has been added or removed
					for (var i = 0; i < $scope.currentRegistration.courses.length; i++) {
						if ($scope.currentRegistration.courses[i].selected == "1") {
							isValid = true;
							break;
						}
					}
				}
				if ($scope.currentRegistration.shownumbers) {
					for (var i = 0; i < $scope.currentRegistration.shownumbers.length; i++) {
						if ($scope.currentRegistration.shownumbers[i].selected == "1") {
							isValid = true;
							break;
						}
					}
				}
			}
			if ($scope.currentRegistration.status == "DRAFT-R") {
				var isValid = false;
				if ($scope.currentRegistration.courses) {
					for (var i = 0; i < $scope.currentRegistration.courses.length; i++) {
						var tmpCourse = $scope.currentRegistration.courses[i];
						if ((tmpCourse.selected == "1" && tmpCourse.selected_old != "1") || (tmpCourse.selected == "0" && tmpCourse.selected_old == "1")) {
							isValid = true;
							break;
						}
					}
				}
				if ($scope.currentRegistration.shownumbers) {
					for (var i = 0; i < $scope.currentRegistration.shownumbers.length; i++) {
						var tmpNumber = $scope.currentRegistration.shownumbers[i];
						if ((tmpNumber.selected == "1" && tmpNumber.selected_old != "1") || (tmpNumber.selected == "0" && tmpNumber.selected_old == "1")) {
							isValid = true;
							break;
						}
					}
				}
				for (var i = 0; i < $scope.currentRegistration.charges.length; i++) {
					var tmpCharge = $scope.currentRegistration.charges[i];
					if ((tmpCharge.selected == "1" && tmpCharge.selected_old != "1") || (tmpCharge.selected == "0" && tmpCharge.selected_old == "1")) {
						isValid = true;
						break;
					}
				}
			}
			if (!isValid) {
				dialogService.alertDlg($scope.translationObj.main.msgmustselectonecourse, null, null, null, null);
			}
			if (isValid) {
				if ($scope.memberForm.$valid == true) {
					if ($scope.currentRegistration.member.contacts.length >= 1) {
						// Revalidate the status, make sure this is not a double click of the presented button
						if ($scope.currentRegistration.status.indexOf("DRAFT") != -1) {  // Check for one of the draft status (DRAFT or DRAFT-R)
							$scope.saveToDB(null, true, $scope.currentRegistration.status.replace("DRAFT","PRESENTED")); // set new status
							$('.nav-tabs a[data-target="#summary"]').tab('show');
						}
					} else {
						dialogService.alertDlg($scope.translationObj.main.msgatleastonecontact, null, null, null, null);
					}
				} else {
					dialogService.alertDlg($scope.translationObj.main.msgmembermissinginfo, null, null, null, null);
				}
			}
		}
	}

	// Changes registration status from PRESENTED back to DRAFT.
	$scope.draftRegistration = function() {
		if ($scope.currentRegistration.status.indexOf("PRESENTED") != -1) {  // Check for one of the draft status (PRESENTED or PRESENTED-R)
			$scope.saveToDB(null, true, $scope.currentRegistration.status.replace("PRESENTED", "DRAFT")); // set new status
			if ($scope.currentRegistration.sessionid) {
				$('.nav-tabs a[data-target="#courses"]').tab('show');
			} else if ($scope.currentRegistration.showid) {
				$('.nav-tabs a[data-target="#numbers"]').tab('show');
			}
		}
	}

	/*
		Check, for each courses, if the maximum number of skater has been respected, and if not, display a message.
		@param {sessioncoursemembers}	array 				Array of courses (sessionscoursesid, nbofskaters, maxnumberskater, label, courselevellabel, name)
	*/
	$scope.checkCoursesMaxNumberOfSkaters = function(sessioncoursemembers) {
		var msg = "";
		for (var i = 0; sessioncoursemembers && i < sessioncoursemembers.length; i++) {
			if (sessioncoursemembers[i].nbofskaters/1 > sessioncoursemembers[i].maxnumberskater/1) {
				if (msg != "") {
					msg += "<br>";
				}
				msg += sessioncoursemembers[i].label + " " + sessioncoursemembers[i].courselevellabel + " (" + sessioncoursemembers[i].name + "): "+ $scope.translationObj.main.msgovertheskaterlimit +" (" + sessioncoursemembers[i].nbofskaters + "/" + sessioncoursemembers[i].maxnumberskater + ")";
			}
		}
		if (msg != "") {
			dialogService.alertDlg(msg);
		}
	}

	$scope.saveAcceptedRegistration = function(billid) {
		$scope.currentRegistration.registrationdatestr = dateFilter($scope.currentRegistration.registrationdate, 'yyyy-MM-dd');
		$scope.promise = $http({
			method: 'post',
			url: './registrationview/manageregistrations.php',
			data: $.param({'registration' : $scope.currentRegistration, 'billid' : billid, 'language' : authenticationService.getCurrentLanguage(), 'validcount' : false, 'type' : 'acceptRegistration' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				$scope.checkCoursesMaxNumberOfSkaters((data.sessioncoursemember) ? data.sessioncoursemember.data : null);
				$scope.setCurrentInternal($scope.selectedLeftObj, null);
				$('.nav-tabs a[data-target="#bill"]').tab('show');
			} else {
					dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	}

	$scope.countFamilyMembersRegistrations = function() {
		return $http({
				method: 'post',
				url: './registrationview/manageregistrations.php',
				data: $.param({'eventtype' : ($scope.currentRegistration.sessionid ? 1 : 2), 'eventid' : ($scope.currentRegistration.sessionid ? $scope.currentRegistration.sessionid : $scope.currentRegistration.showid), 'memberid' : $scope.currentRegistration.memberid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'countFamilyMembersRegistrations' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
			});
	}

	$scope.acceptRegistration = function() {
		if ($scope.currentRegistration.status == 'PRESENTED') {

			// Let's ask if we want a new bill or an existing bill
			dialogService.setDlgCustomButtonLabels($scope.translationObj.main.buttontitlecreatenewbill, $scope.translationObj.main.buttontitleselectbill);
			dialogService.customDialog($scope.translationObj.main.msgcreatebill,
				function(e) {
					if (e) {
						// Validate one last time if family members count is ok or not
						$scope.countFamilyMembersRegistrations().then(function(data) {
							// dialogService.alertDlg(data.data.count);
							if ($scope.currentRegistration.familyMemberCount == data.data.count/1) {
								// user clicked for a new bill
								billingService.selectBillingName($scope, $scope.currentRegistration.member.id, authenticationService.getCurrentLanguage())
								.then(function(selectBillingName) {
									$scope.currentRegistration.billingname = selectBillingName.billingname;
									$scope.saveAcceptedRegistration(null);
								});
							} else {
								dialogService.alertDlg($scope.translationObj.main.msgerrfamilycountinvalid);
							}
						});

					} else {
						// user clicked "no"
						// We need to select an existing bill
						// Validate one last time if family members count is ok or not
						$scope.countFamilyMembersRegistrations().then(function(data) {
							if ($scope.currentRegistration.familyMemberCount == data.data.count/1) {
								billingService.selectExistingBill($scope, $scope.currentRegistration.sessionid).then(function(selectedBill) {
									if (selectedBill && selectedBill.id) {
										$scope.saveAcceptedRegistration(selectedBill.id);
									}
								});
							} else {
								dialogService.alertDlg($scope.translationObj.main.msgerrfamilycountinvalid);
							}
						});

					}
				}
			);
		} else if ($scope.currentRegistration.status == 'PRESENTED-R') {
			// No need to ask for bill, we will automatically connect this registration to the same bill the old registration was connected to.
			$scope.saveAcceptedRegistration(-1);
		}
	}

	$scope.deleteRevisedRegistration = function() {
		if ($scope.currentRegistration != null) {
			dialogService.confirmDlg($scope.translationObj.main.msgdeleterevisedregistration, "YESNO", $scope.deleteFromDB, null, true, null);
		}		
	}

	$scope.reviseRegistration = function() {
		// dialogService.confirmYesNo($scope.translationObj.main.msgconfirmrevise,
		// 	function(e) {
		// 		if (e) {
					// user clicked "yes"
					$scope.newRegistration = $scope.currentRegistration;
					$scope.newRegistration.callback = $scope.validateNewRegistration;
					// Send the newRegistration to the modal form
					$uibModal.open({
							animation: false,
							templateUrl: 'registrationview/reviseregistration.template.html',
							controller: 'childeditor.controller',
							scope: $scope,
							size: null,
							backdrop: 'static',
							resolve: {
								newObj: function() {
									return $scope.newRegistration;
								}
							}
					}).
					result.then(function(newRegistration) {
						// User clicked OK and everything was valid.
						$scope.currentRegistration.registrationdatestr = dateFilter($scope.currentRegistration.registrationdate, 'yyyy-MM-dd');
						$scope.promise = $http({
							method: 'post',
							url: './registrationview/manageregistrations.php',
							data: $.param({'registrationid' : $scope.currentRegistration.id, 'registrationdatestr' : $scope.currentRegistration.registrationdatestr, 'newstatus' : 'DRAFT-R', 'type' : 'copyRegistration' }),
							headers: {'Content-Type': 'application/x-www-form-urlencoded'}
						}).
						success(function(data, status, headers, config) {
							if (data.success) {
								// We need to reload the new version of the registration and change the left pane
								$scope.selectedLeftObj.id = data.newregistrationid;
								$scope.setCurrentInternal($scope.selectedLeftObj, null);
								if ($scope.currentRegistration.courses) {
									$('.nav-tabs a[href="#courses"]').tab('show');
								} else {
									$('.nav-tabs a[href="#numbers"]').tab('show');
								}
							} else {
								if (!data.success) {
									dialogService.displayFailure(data);
								}
							}
						}).
						error(function(data, status, headers, config) {
							dialogService.displayFailure(data);
						});
					},
					function() {
						// User clicked CANCEL.
						// alert('canceled');
					});

				// } else {
				// 	// user clicked "no"
				// }
			// }
		// );
	}

	$scope.addRegistrationToDB = function() {
		$scope.newRegistration.registrationdatestr = dateFilter($scope.newRegistration.registrationdate, 'yyyy-MM-dd');
		$scope.newRegistration.callback = null;
		return ($scope.promise = $http({
			method: 'post',
			url: './registrationview/manageregistrations.php',
			data: $.param({'registration' : $scope.newRegistration, 'type' : 'insert_registration' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				// $('.nav-tabs a[data-target="#member"]').tab('show');
				// $scope.selectedTab == '#member';
				$scope.isnew = true;
				var newRegistration = {id:data.id,eventtype:data.eventtype,eventid:data.eventid};
				// $scope.leftobjs.push(newRegistration);
				$scope.leftobjs.splice(0, 0, newRegistration);
				$scope.setCurrentInternal(newRegistration);
				return true;
			} else {
				dialogService.displayFailure(data);
				return false;
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return false;
		}));
	};

	$scope.validateNewRegistration = function(editObjForm, newRegistration) {
		if (editObjForm.$invalid) {
			// $("#editObjFieldMandatory").fadeTo(2000, 500).slideUp(500, function() {$("#editObjFieldMandatory").hide();});
			return "#editObjFieldMandatory";
		} else {
			if (newRegistration.session && newRegistration.session.type == 1) {	// Registration is for a session and not a show
				if (newRegistration.registrationdate > parseISOdateService.parseDateWithoutTime(newRegistration.session.coursesenddate)) {
					// $("#editObjFieldInvalidDate").fadeTo(2000, 500).slideUp(500, function() {$("#editObjFieldInvalidDate").hide();});
					return "#editObjFieldInvalidDate";
				}
			}
		}
		return null;
	}

	// This is the function that creates the modal to create new registration
	$scope.createNew = function(confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newRegistration = {};
			$scope.newRegistration.session = $scope.currentEvent;
			$scope.newRegistration.registrationdate = $scope.today;
			$scope.newRegistration.callback = $scope.validateNewRegistration;
			$uibModal.open({
					animation: false,
					templateUrl: 'registrationview/newregistration.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: null,
					backdrop: 'static',
					resolve: {
						newObj: function() {
							return $scope.newRegistration;
						}
					}
			})
			.result.then(function(newRegistration) {
				// User clicked OK and everything was valid.
				$scope.newRegistration = newRegistration;
				if (newRegistration.session.type == 1) {
					$scope.newRegistration.sessionid = newRegistration.session.id;
				} else if (newRegistration.session.type == 2) {
					$scope.newRegistration.showid = newRegistration.session.id;
				}
				$scope.startSearchMember = true;
				$scope.addRegistrationToDB();
			}, function() {
				$scope.newRegistration = null;
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// Calculates the total amount ($) for the courses, based on current and previous selected state.
	// Also calculates the charges and sets the total amount for the order.
	$scope.calculateTotal_old = function() {
		var total = 0.0;
		var prorata = 0.0;
		var courseTmp = null;
		for (var i = 0; $scope.currentRegistration.courses && i < $scope.currentRegistration.courses.length; i++) {
			courseTmp = $scope.currentRegistration.courses[i];
			prorata = ((courseTmp.fees/1)/(courseTmp.nbofcourses/1)  * (courseTmp.nbofcoursesleft/1))/1;
			if (courseTmp.selected == '1') {
				// New course for the registration
				if (courseTmp.selected_old == '0') {
					if (courseTmp.fees_old != null && courseTmp.fees_old > 0) {
						// This course was  removed in a previous registration, but it still need to be paid. We then add the new value of this registration
						courseTmp.fees_billing = courseTmp.fees_old + prorata;
					} else {
						// This is a totaly new course for this registration
						courseTmp.fees_billing = prorata;
					}
					total += courseTmp.fees_billing;
				}
				// Existing course from old registration
				if (courseTmp.selected_old == '1') {
					courseTmp.fees_billing = courseTmp.fees_old/1;
					total += courseTmp.fees_billing;
				}

			}
			if (courseTmp.selected == '0') {
				if (courseTmp.selected_old == '1') {
					// Course is being removed, we need to calculate the difference between the paid value and the residual value (prorata)
					courseTmp.fees_billing = courseTmp.fees_old/1 - prorata/1;
					total += courseTmp.fees_billing;
				}
				if (courseTmp.selected_old == '0' && courseTmp.fees_old/1 > 0) {
					// Course was removed in another revision, no calculation needed.
					courseTmp.fees_billing = courseTmp.fees_old/1;
					total += courseTmp.fees_billing;
				}
				if (courseTmp.selected_old == '0' && courseTmp.fees_old/1 == 0) {
					// untouched
					courseTmp.fees_billing = null;
					total += courseTmp.fees_billing;
				}
			}
		}
		for (var i = 0; i < $scope.currentRegistration.charges.length; i++) {
			if ($scope.currentRegistration.charges[i].selected == '1') {
				if ($scope.currentRegistration.charges[i].type == 'CHARGE') {
					total += $scope.currentRegistration.charges[i].amount/1;
				} else if ($scope.currentRegistration.charges[i].type == 'DISCOUNT') {
					total -= $scope.currentRegistration.charges[i].amount/1;
				}
			}
		}
		$scope.currentRegistration.totalamount = total/1;
	}

//	$scope.mainFilter = function() {
//		// Send the newFilter to the modal form
//		$uibModal.open({
//				animation: false,
//				templateUrl: 'registrationview/filter.template.html',
//				controller: 'childeditor.controller',
//				scope: $scope,
//				size: 'lg',
//				backdrop: 'static',
//				resolve: {
//					newObj: function() {
//						return $scope.newFilter;
//					}
//				}
//		})
//		.result.then(function(newFilter) {
//				// User clicked OK
//				if (newFilter.activeOnly || newFilter.sessionid) {
//					$scope.newFilter = newFilter;
//					$scope.getAllRegistrations(newFilter);
//				} else {
//					dialogService.alertDlg($scope.translationObj.main.msgnofilter, null);
//					$scope.newFilter = {};
//					$scope.getAllRegistrations(null);
//				}
//		}, function(dismiss) {
//			if (dismiss == true) {
//				$scope.getAllRegistrations(null);
//			}
//			// User clicked CANCEL.
//			// alert('canceled');
//		});
//	}

	// This is the function that creates the modal to add charges or discount
	$scope.addChargeOrDiscount = function() {
		$uibModal.open({
				animation: false,
				templateUrl: 'registrationview/newcharge.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function() {
						return $scope.currentRegistration;
					}
				}
		})
		.result.then(function(currentRegistration) {
			// User clicked OK and everything was valid.
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	$scope.validateNewSpecialCharge = function(editObjForm, newCharge) {
		if (editObjForm.$invalid) {
			return "#editObjFieldMandatory";
		} else {
			if (newCharge.amount <= 0) {
				return "#editObjAmountOverZero";
			}
		}
		return null;
	}

	// This is the function that creates the modal to add special charges
	$scope.addSpecialCharge = function() {
		$scope.newCharge = null;
		for (var i = 0; i < $scope.currentRegistration.charges.length; i++) {
			if ($scope.currentRegistration.charges[i].code == 'SPECCHARGE') {
				$scope.newCharge = {};
				angular.copy($scope.currentRegistration.charges[i], $scope.newCharge);
				$scope.newCharge.amount = null;
				$scope.newCharge.comments = null;
				$scope.newCharge.oldchargeid = null;
				$scope.newCharge.selected = null;
				$scope.newCharge.selected_old = null;
				break;
			}
		}
		if ($scope.newCharge) {
			$scope.newCharge.callback = $scope.validateNewSpecialCharge;
			$uibModal.open({
					animation: false,
					templateUrl: 'registrationview/newspecialcharge.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function() {
							return $scope.newCharge;
						}
					}
			})
			.result.then(function(newCharge) {
				// User clicked OK and everything was valid.
				// Add the special charge to the array of charges
				newCharge.selected = '1';
				$scope.currentRegistration.charges.push(newCharge);
				newCharge.callback = null;
				$scope.newCharge = null;
				$scope.setDirty();
				pricingService.applyPricingRules($scope.currentRegistration, $scope.currentRegistration.use_prorata, $scope.currentRegistration.prorataoptions);
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	$scope.validateNewSpecialDiscount = function(editObjForm, newDiscount) {
		if (editObjForm.$invalid) {
			return "#editObjFieldMandatory";
		} else {
			if (newDiscount.amount <= 0) {
				return "#editObjAmountOverZero";
			}
		}
		return null;
	}

	// This is the function that creates the modal to add special discounts
	$scope.addSpecialDiscount = function() {
		$scope.newDiscount = null;
		for (var i = 0; i < $scope.currentRegistration.charges.length; i++) {
			if ($scope.currentRegistration.charges[i].code == 'SPECDISCNT') {
				$scope.newDiscount = {};
				angular.copy($scope.currentRegistration.charges[i], $scope.newDiscount);
				$scope.newDiscount.amount = null;
				$scope.newDiscount.comments = null;
				$scope.newDiscount.oldchargeid = null;
				$scope.newDiscount.selected = null;
				$scope.newDiscount.selected_old = null;
				break;
			}
		}
		if ($scope.newDiscount) {
			$scope.newDiscount.callback = $scope.validateNewSpecialDiscount;
			$uibModal.open({
					animation: false,
					templateUrl: 'registrationview/newspecialdiscount.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'md',
					backdrop: 'static',
					resolve: {
						newObj: function() {
							return $scope.newDiscount;
						}
					}
			})
			.result.then(function(newDiscount) {
				// User clicked OK and everything was valid.
				// Add the special charge to the array of charges
				newDiscount.selected = '1';
				$scope.currentRegistration.charges.push(newDiscount);
				newDiscount.callback = null;
				$scope.newDiscount = null;
				$scope.setDirty();
				pricingService.applyPricingRules($scope.currentRegistration, $scope.currentRegistration.use_prorata, $scope.currentRegistration.prorataoptions);
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// When a course is selected (or de-selected)
	$scope.onCourseSelected = function(course, confirmed) {
		if (course != null) {
			if (($scope.currentRegistration.status=='DRAFT' || $scope.currentRegistration.status=='DRAFT-R') && course.deltacode != 'REMOVED_CLOSED') {
				if (course.selected == "1") {
					if (course.mandatory != 1) {
						course.selected = "0";
					} else {
						if (!confirmed) {
							dialogService.confirmDlg($scope.translationObj.main.msgremovemandatorynumber, "YESNO", $scope.onCourseSelected, null, course, true);
//							dialogService.confirmDlg($scope.translationObj.main.msgremovemandatorynumber, "YESNO", $scope.deselectCourse, null, course, true);
						} else {
							course.selected = "0";
						}
					}
				} else if (course.selected == "0") {
					course.selected = "1";
				}
				$scope.setDirty();
				pricingService.applyPricingRules($scope.currentRegistration, $scope.currentRegistration.use_prorata, $scope.currentRegistration.prorataoptions);
				if (confirmed) {
					// For some reason, when we displayed the warning to the user, the form wasn't refreshing so we added a $apply
					$scope.$apply();
				}
			}
		}
	}

	// When a coursecode is selected (or de-selected)
	$scope.onCourseCodeSelected = function(coursecode) {
		for (var i = 0; i < $scope.currentRegistration.coursecodes.length; i++) {
			if ($scope.currentRegistration.coursecodes[i].selected == '1') {
				$scope.coursecodefilter = 1;
				return;
			}
		}
		$scope.coursecodefilter = null;
	}

	// When a charge is selected (or de-selected)
	$scope.onChargeSelected = function(charge) {
		// if (charge != null && (charge.alwaysselected != '1' && ($scope.currentRegistration.status == 'DRAFT' || $scope.currentRegistration.status == 'DRAFT-R'))) {
		if (charge != null && ($scope.currentRegistration.status == 'DRAFT' || $scope.currentRegistration.status == 'DRAFT-R')) {
			if (charge.alwaysselected != '1') {
				if (charge.selected == "1") {
					charge.selected = "0";
				} else {
					charge.selected = "1";
				}
			} else { //charge.alwaysselected == '1'
				// Charge is always selected for a new registration, but what if charge was defined after the start of registration and some registrations don't have it selected.
				// We need to allow user to add this charge manually to the registration.
				if (charge.selected == '0' && charge.selected_old == '0') {
					charge.selected = "1";
					charge.selectedSpecial = true;
				} else if (charge.selectedSpecial) {
					charge.selected = "0";
					charge.selectedSpecial = false;
				}
			}
			$scope.setDirty();
			pricingService.applyPricingRules($scope.currentRegistration, $scope.currentRegistration.use_prorata, $scope.currentRegistration.prorataoptions);
		}
	}

	// Event generated everytime the user clicks on a tab.
	$('.nav-tabs a').on('shown.bs.tab', function(event) {
		$scope.selectedTab = event.target.attributes['DATA-TARGET'].value;         // active tab
	});

	$scope.getActiveSession = function() {
		$scope.activeSession = null;
		for (var i = 0; i < $scope.sessions.length; i++) {
			if ($scope.sessions[i].active == 1) {
				$scope.activeSession = $scope.sessions[i];
				return;
			}
		}
	}

	// Filters the charges and discounts to display in the courses.template form
	$scope.filterCharges = function(item) {
		if (item.type == 'DISCOUNT') {
			if (item.selected == '0' && item.selected_old == '0') {
				if (item.rules && item.rules.length != 0) {
					return false;
				} else {
					if (item.alwaysdisplay == '0') {
						return false;
					}
				}
			}
			if (item.active/1 == 0) {
				if (item.selected == '1' || item.selected_old == '1') {
					return true;
				} else {
					return false;
				}
			}
		}
		if (item.type == 'CHARGE') {
			if (item.selected == '0' && item.selected_old == '0') {
				if (item.alwaysdisplay == '0') {
					return false;
				}
			}
			if (item.active/1 == 0) {
				if (item.selected == '1' || item.selected_old == '1') {
					return true;
				} else {
					return false;
				}
			}
		}
		return true;
	}

	$scope.filterAdditionnalCharges = function(item) {

		if (item.alwaysdisplay == '1' || item.issystem == '1' || (item.rules && item.rules.length != 0)) {
			return false;
		}
		if (item.alwaysselected == '1' && (item.selected == '1' || item.selected_old == '1') && item.selectedSpecial == null) {
			return false;
		}
		if (item.active/1 == 0) {
			if (item.selected == '1' || item.selected_old == '1') {
				return true;
			} else {
				return false;
			}
		}
		return true;
	}

	$scope.filterCourses = function(item) {
		if ($scope.coursecodefilter == 1) {
			for (var i = 0; i < $scope.currentRegistration.coursecodes.length; i++) {
				if ($scope.currentRegistration.coursecodes[i].selected == '1' && item.coursecode == $scope.currentRegistration.coursecodes[i].code) {
					return true;
				}
			}
		} else {
			return true;
		}
		return false;
	}

	$scope.clearCourseCodesFilter = function() {
		for (var i = 0; i < $scope.currentRegistration.coursecodes.length; i++) {
			$scope.currentRegistration.coursecodes[i].selected = '0';
		}
		$scope.coursecodefilter = null;
	}

	$scope.onCurrentEventChange = function() {
		$scope.getAllRegistrations(/*$scope.newFilter*/null);
		$scope.currentRegistration = null;
		$scope.selectedLeftObj = null;
	}

	/**
	*			Callback from listsService.getAllSessionsAndShows to force the selection of the first element in the list
	*/	
	$scope.callback = function() {
		$scope.currentEvent = $scope.allSessionsAndShows[0];
		$scope.onCurrentEventChange();
	}

	$scope.refreshAll = function() {
		if (!$scope.currentEvent) {
			listsService.getAllSessionsAndShows($scope, authenticationService.getCurrentLanguage(), $scope.callback)
		} else {
			$scope.getAllRegistrations(null);
		}
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'countries', 				'text', 'countries');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'contacttypes', 			'text', 'contacttypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'provinces', 				'text', 'provinces');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'genders', 					'text', 'genders');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'languages', 				'sequence', 'languages');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'familyranks', 			'text', 'familyranks');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'coursedeltatypes',	'text', 'coursedeltatypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 							'text', 'yesnos');

		listsService.getAllSessions($scope, $http, authenticationService.getCurrentLanguage(), $scope.getActiveSession);
		translationService.getTranslation($scope, 'registrationview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
