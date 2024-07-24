'use strict';

angular.module('cpa_admin.memberview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/memberview', {
		templateUrl: 'memberview/memberview.html',
		controller: 'memberviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.member_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/memberview"});
				}
			}
		}
	});
}])

.controller('memberviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$window', 'dateFilter', 'anycodesService', 'dialogService', 'listsService', 'billingService', 'translationService', 'authenticationService', 'parseISOdateService', 'selectMembersService', 'reportingService',function($rootScope, $scope, $http, $uibModal, $window, dateFilter, anycodesService, dialogService, listsService, billingService, translationService, authenticationService, parseISOdateService, selectMembersService, reportingService) {

	$scope.progName = "memberView";
	$scope.currentMember = null;
	$scope.selectedLeftObj = null;
	$scope.newMember = null;
	$scope.isFormPristine = true;
	$scope.currentLanguage = authenticationService.getCurrentLanguage();
	$scope.newFilter = {};
	$scope.newFilter.filterApplied = false;

	$scope.isDirty = function() {
		if ($scope.detailsForm.$dirty || $scope.addressForm.$dirty || $scope.skateForm.$dirty || $scope.contactForm.$dirty) {
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
		$scope.addressForm.$setPristine();
		$scope.skateForm.$setPristine();
		$scope.contactForm.$setPristine();
		$scope.isFormPristine = true;
	};

	$scope.getAllMembers = function (newFilter) {
		if (newFilter) {
			$scope.newFilter.filterApplied = true;
		} else {
			$scope.newFilter.filterApplied = false;
		}
		$scope.promise = $http({
				method: 'post',
				url: './memberview/manageMembers.php',
				data: $.param({'language' : authenticationService.getCurrentLanguage(), 'filter' : newFilter, 'type' : 'getAllMembers' }),
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

	// This is done purely in case the number of qualifications changed since the last time the record was saved
	// Return the converted array of qualifications;
	$scope.convertQualifications = function($scope, currentQualifs, globalQualifs) {
		var qualifications = [];
		for (var i = 0; globalQualifs && i < globalQualifs.length; i++) {
			if (currentQualifs.indexOf(globalQualifs[i].code) != -1) {
				qualifications.push(globalQualifs[i].code);
			} else {
				qualifications.push(null);
			}
		}
		return qualifications;
	};

	$scope.getMemberDetails = function(member) {
		$scope.promise = $http({
			method: 'post',
			url: './memberview/manageMembers.php',
			data: $.param({'id' : member.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getMemberDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data) ) {
				$scope.currentMember = data.data[0];
				// This is done to validate that all the options that were saved are in the right index vs the complete list of qualifications (that may have changed since the last save)
				$scope.currentMember.qualifications	= $scope.convertQualifications($scope, $scope.currentMember.qualifications.split(","), $scope.qualifications);
				for (var x = 0; x < $scope.currentMember.csbalanceribbons.length; x++) {
					$scope.currentMember.csbalanceribbons[x].ribbondate = parseISOdateService.parseDateWithoutTime($scope.currentMember.csbalanceribbons[x].ribbondate);
				}
				for (var x = 0; x < $scope.currentMember.csagilityribbons.length; x++) {
					$scope.currentMember.csagilityribbons[x].ribbondate = parseISOdateService.parseDateWithoutTime($scope.currentMember.csagilityribbons[x].ribbondate);
				}
				for (var x = 0; x < $scope.currentMember.cscontrolribbons.length; x++) {
					$scope.currentMember.cscontrolribbons[x].ribbondate = parseISOdateService.parseDateWithoutTime($scope.currentMember.cscontrolribbons[x].ribbondate);
				}
				for (var x = 0; x < $scope.currentMember.precsribbons.length; x++) {
					$scope.currentMember.precsribbons[x].ribbondate = parseISOdateService.parseDateWithoutTime($scope.currentMember.precsribbons[x].ribbondate);
				}
				for (var x = 0; x < $scope.currentMember.csstagebadges.length; x++) {
					$scope.currentMember.csstagebadges[x].badgedate = parseISOdateService.parseDateWithoutTime($scope.currentMember.csstagebadges[x].badgedate);
				}
				/* Let's create the list of email for the "send email template" directive */
				$scope.currentMember.contactsforemail = [];
				if ($scope.currentMember.email != null && $scope.currentMember.email != '') {
					$scope.currentMember.contactsforemail.push({'firstname':$scope.currentMember.firstname, 'lastname':$scope.currentMember.lastname,'email':$scope.currentMember.email});
				}
				if ($scope.currentMember.email2 != null && $scope.currentMember.email2 != '') {
					$scope.currentMember.contactsforemail.push({'firstname':$scope.currentMember.firstname, 'lastname':$scope.currentMember.lastname,'email':$scope.currentMember.email2});
				}
				for (var x = 0; x < $scope.currentMember.contacts.length; x++) {
					$scope.currentMember.contactsforemail.push({'firstname':$scope.currentMember.contacts[x].firstname, 'lastname':$scope.currentMember.contacts[x].lastname,'email':$scope.currentMember.contacts[x].email});
				}
			} else {
				dialogService.displayFailure(data);
			}
			$rootScope.repositionLeftColumn();
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (member, index) {
		if (member != null) {
			$scope.selectedLeftObj = member;
			$scope.getMemberDetails(member);
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currentMember = null;
		}
	}

	$scope.setCurrent = function (member, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, member, index);
		} else {
			$scope.setCurrentInternal(member, index);
		}
	};

	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currentMember != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgdeletemember, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './memberview/manageMembers.php',
				data: $.param({'id' : $scope.currentMember.id, 'type' : 'delete_member' }),
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
			if ($scope.detailsForm.firstname.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrfirstnamemandatory);
			}
			if ($scope.detailsForm.lastname.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrlastnamemandatory);
			}
		}
		if ($scope.currentMember.healthcareno == "") {
			$scope.globalWarningMessage.push($scope.translationObj.main.msgwarnhealthcarenonice);
		}

		if ($scope.globalErrorMessage.length != 0) {
			// $scope.globalErrorMessage = errorMsg.join('</br>');
			$scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function() {$("#mainglobalerrormessage").hide();});
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			// $scope.globalErrorMessage = errorMsg.join('</br>');
			// $scope.globalErrorMessage = errorMsg;
			$scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function() {$("#mainglobalwarningmessage").hide();});
			// retVal = false;
		}
		return retVal;
	}

	$scope.saveToDB = function() {
		if ($scope.currentMember == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.currentMember.qualifications = $scope.currentMember.qualifications.join();
			for (var x = 0; x < $scope.currentMember.csbalanceribbons.length; x++) {
				$scope.currentMember.csbalanceribbons[x].ribbondatestr = dateFilter($scope.currentMember.csbalanceribbons[x].ribbondate, 'yyyy-MM-dd');
			}
			for (var x = 0; x < $scope.currentMember.csagilityribbons.length; x++) {
				$scope.currentMember.csagilityribbons[x].ribbondatestr = dateFilter($scope.currentMember.csagilityribbons[x].ribbondate, 'yyyy-MM-dd');
			}
			for (var x = 0; x < $scope.currentMember.cscontrolribbons.length; x++) {
				$scope.currentMember.cscontrolribbons[x].ribbondatestr = dateFilter($scope.currentMember.cscontrolribbons[x].ribbondate, 'yyyy-MM-dd');
			}
			for (var x = 0; x < $scope.currentMember.precsribbons.length; x++) {
				$scope.currentMember.precsribbons[x].ribbondatestr = dateFilter($scope.currentMember.precsribbons[x].ribbondate, 'yyyy-MM-dd');
			}
			for (var x = 0; x < $scope.currentMember.csstagebadges.length; x++) {
				$scope.currentMember.csstagebadges[x].badgedatestr = dateFilter($scope.currentMember.csstagebadges[x].badgedate, 'yyyy-MM-dd');
			}
			$scope.promise = $http({
				method: 'post',
				url: './memberview/manageMembers.php',
				data: $.param({'member' : JSON.stringify($scope.currentMember), 'type' : 'updateEntireMember' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this member to reset everything
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

	$scope.addMemberToDB = function() {
		$scope.promise = $http({
			method: 'post',
			url: './memberview/manageMembers.php',
			data: $.param({'member' : $scope.newMember, 'type' : 'insert_member' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newMember = {id:data.id, firstname:$scope.newMember.firstname, lastname:$scope.newMember.lastname};
				$scope.leftobjs.push(newMember);
				// $scope.selectedLeftObj = newMember;
				// We could sort the list....
				$scope.setCurrentInternal(newMember);
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

	// This is the function that creates the modal to create new member
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.newMember = {};
			// Send the newMember to the modal form
			$uibModal.open({
					animation: false,
					templateUrl: 'memberview/newmember.template.html',
					controller: 'childeditor.controller',
					scope: $scope,
					size: 'lg',
					backdrop: 'static',
					resolve: {
						newObj: function () {
							return $scope.newMember;
						}
					}
			})
			.result.then(function(newMember) {
					// User clicked OK and everything was valid.
					$scope.newMember = newMember;
					if ($scope.addMemberToDB() == true) {
					}
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
			});
		}
	};

	// This is the function that creates the modal to create/edit courses' coachs
	$scope.editCoach = function(newCoach) {
		$scope.newCoach = {};
		// Keep a pointer to the current test
		$scope.currentCoach = newCoach;
		// Copy in another object
		angular.copy(newCoach, $scope.newCoach);
		// Send the newContact to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'memberview/newcoach.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newCoach;
					}
				}
		})
		.result.then(function(newCoach) {
			// User clicked OK and everything was valid.
			angular.copy(newCoach, $scope.currentCoach);
			// If already saved in DB
			if ($scope.currentCoach.id != null) {
				$scope.currentCoach.status = 'Modified';
			} else {
				$scope.currentCoach.status = 'New';
				if ($scope.currentMember.coaches == null) $scope.currentMember.coaches = [];
				// Don't insert twice in list
				if ($scope.currentMember.coaches.indexOf($scope.currentCoach) == -1) {
					$scope.currentMember.coaches.push($scope.currentCoach);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit skill tests
	$scope.editSkillTest = function(newSkill, version) {
		if (version == 1) {
			$scope.editTestEx(newSkill, 'abilities', $scope.currentMember.abilities, version, $scope.abilities);
		} else if (version == 2) {
			$scope.editTestEx(newSkill, 'starabilities', $scope.currentMember.starabilities, version, $scope.starabilities);
		}
	}

	// This is the function that creates the modal to create/edit dance tests
	$scope.editDanceTest = function(newDance, version) {
		if (version == 1) {
			$scope.editTestEx(newDance, 'dances', $scope.currentMember.dances, version, $scope.dances);
		} else if (version == 2) {
			$scope.editTestEx(newDance, 'stardances', $scope.currentMember.stardances, version, $scope.stardances);
		}
	}

	// This is the function that creates the modal to create/edit free style tests
	$scope.editFreestyleTest = function(newFreestyle, version) {
		if (version == 1) {
			$scope.editTestEx(newFreestyle, 'freestyles', $scope.currentMember.freestyles, version, $scope.freestyles);
		} else if (version == 2) {
			$scope.editTestEx(newFreestyle, 'starfreestyles', $scope.currentMember.starfreestyles, version, $scope.starfreestyles);
		}
	}

	// This is the function that creates the modal to create/edit artistic tests
	$scope.editArtisticTest = function(test, version) {
		if (version == 1) {
			$scope.editTestEx(test, 'artistics', $scope.currentMember.freestyles, version, $scope.freestyles);
		} else if (version == 2) {
			$scope.editTestEx(test, 'starartistics', $scope.currentMember.starartistics, version, $scope.starartistics);
		}
	}

	// This is the function that creates the modal to create/edit synchro tests
	$scope.editSynchroTest = function(test, version) {
		if (version == 1) {
			$scope.editTestEx(test, 'synchros', $scope.currentMember.synchros, version, $scope.synchros);
		} else if (version == 2) {
			$scope.editTestEx(test, 'starsynchros', $scope.currentMember.starsynchros, version, $scope.starsynchros);
		}
	}
	
	// This is the function that creates the modal to create/edit interpretive tests
	$scope.editInterpretiveTest = function(newInterpretive) {
		$scope.editTestEx(newInterpretive, 'interpretives', $scope.currentMember.interpretives, 1, $scope.interpretives);
	}

	// This is the function that creates the modal to create/edit interpretive tests
	$scope.editCompetitiveTest = function(newCompetitive) {
		$scope.editTestEx(newCompetitive, 'competitives', $scope.currentMember.competitives, 1, $scope.competitives);
	}

	// This is the function that creates the modal to create/edit test
	// TODO : this could be done with a single template, because they are all the same, the only thing that changes is the list of test.
// 	$scope.editTest = function(newTest, testType, testList, version, templateName) {
// 		$scope.newTest = {};
// 		// Keep a pointer to the current test
// 		$scope.currentTest = newTest;
// 		// Copy in another object
// 		angular.copy(newTest, $scope.newTest);
// 		// Convert the date
// 		$scope.newTest.testdate = parseISOdateService.parseDateWithoutTime($scope.newTest.testdatestr);
// 		$uibModal.open({
// 				animation: false,
// 				templateUrl: templateName,
// 				controller: 'childeditor.controller',
// 				scope: $scope,
// 				size: 'lg',
// 				backdrop: 'static',
// 				resolve: {
// 					newObj: function () {
// 						return $scope.newTest;
// 					}
// 				}
// 		})
// 		.result.then(function(newTest) {
// 			// User clicked OK and everything was valid.
// 			newTest.testdatestr = dateFilter(newTest.testdate, 'yyyy-MM-dd');
// 			newTest.testlabel = anycodesService.convertIdToDesc($scope, testType, newTest.testid);
// 			angular.copy(newTest, $scope.currentTest);
// 			// If already saved in DB
// 			if ($scope.currentTest.id != null) {
// 				$scope.currentTest.status = 'Modified';
// 			} else {
// 				$scope.currentTest.status = 'New';
// 				if (testList == null) testList = [];
// 				// Don't insert twice in list
// 				if (testList.indexOf($scope.currentTest) == -1) {
// 					testList.push($scope.currentTest);
// 				}
// 			}
// 			$scope.setDirty();
// 		}, function() {
// 				// User clicked CANCEL.
// //					alert('canceled');
// 		});
// 	};

	// This is the function that creates the modal to create/edit test - new version
	$scope.editTestEx = function(newTest, testType, testList, version, listoftestfortype) {
		$scope.listoftestfortype = listoftestfortype;
		if (version == 1) {
			$scope.testresultcodes = $scope.yesnos
		} else if (version == 2) {
			$scope.testresultcodes = $scope.testresults
		}
		$scope.newTest = {};
		$scope.currentTestList = testList;
		// Keep a pointer to the current test
		$scope.currentTest = newTest;
		// Copy in another object
		angular.copy(newTest, $scope.newTest);
		// Convert the date
		$scope.newTest.testdate = parseISOdateService.parseDateWithoutTime($scope.newTest.testdatestr);
		$uibModal.open({
				animation: false,
				templateUrl: 'memberview/newtest.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newTest;
					}
				}
		})
		.result.then(function(newTest) {
			// User clicked OK and everything was valid.
			newTest.testdatestr = dateFilter(newTest.testdate, 'yyyy-MM-dd');
			newTest.testlabel = anycodesService.convertIdToDesc($scope, testType, newTest.testid);
			angular.copy(newTest, $scope.currentTest);
			// If already saved in DB
			if ($scope.currentTest.id != null) {
				$scope.currentTest.status = 'Modified';
			} else {
				$scope.currentTest.status = 'New';
				// Last chance to find a suitable label for the test
				if (newTest.testlabel == null || newTest.testlabel == '') {
					for (var i = 0; i < $scope.currentTestList.length; i++) {
						if ($scope.currentTestList[i].testid == $scope.currentTest.testid) {
							$scope.currentTest.testlabel = $scope.currentTestList[i].testlabel;
						}
					}
				}
				if (testList == null) testList = [];
				// Don't insert twice in list
				if (testList.indexOf($scope.currentTest) == -1) {
					testList.push($scope.currentTest);
				}
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	$scope.mainFilter = function() {
		// Send the newFilter to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'memberview/filter.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newFilter;
					}
				}
		})
		.result.then(function(newFilter) {
				// User clicked OK
				if (newFilter.firstname || newFilter.lastname || newFilter.course || newFilter.registration|| newFilter.qualification) {
					$scope.newFilter = newFilter;
					$scope.getAllMembers(newFilter);
				} else {
					dialogService.alertDlg($scope.translationObj.main.msgnofilter, null);
					$scope.newFilter = {};
					$scope.getAllMembers(null);
				}
		}, function(dismiss) {
			if (dismiss == true) {
				$scope.getAllMembers(null);
			}
			// User clicked CANCEL.
			// alert('canceled');
		});
	}

	// This is the function that creates the modal to get the members email addresses
	$scope.getMembersEmail = function() {
		selectMembersService.selectMembers($scope)
		.then(function(selectedMembers) {
			reportingService.createAndDisplayReport("sessionMemberEmailList.php", selectedMembers);
				return;
		}) ;
	};

	$scope.checkAll = function(list) {
		for (var i = 0; list && i < list.length; i++) {
			if (list[i] != undefined) {
				list[i].success = '1';
			}
		}
		$scope.setDirty();
	}

	// This is the function that creates the modal to export the members
	$scope.exportMembers = function() {
		$window.open('./reports/exportmemberstocsv.php' + '?language='+authenticationService.getCurrentLanguage());
		// $window.open("http://localhost/cpa_admin/app/reports/exportmemberstocsv.php");
		// var parameters = null;
		// $http({
    //   method: 'post',
    //   url: './reports/exportmemberstocsv.php',
    //   data: $.param({'parameters' : parameters}),
    //   headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    // }).
    // success(function(filename) {
    //   return filename;
    // }).
    // error(function(filename) {
    //   dialogService.displayFailure(filename);
    //   return false;
    // });



		// selectMembersService.selectMembers($scope)
		// .then(function(selectedMembers) {
		// 	$window.open("http://localhost/cpa_admin/app/reports/exportmemberstocsv.php")
		// 	// reportingService.createAndDisplayReport("exportmemberstocsv.php", selectedMembers)
		// 		return;
		// }) ;
	};

	// This is to view the bill from the bill list
	$scope.viewBill = function(billid) {
		$window.open('./reports/memberBill.php?language='+authenticationService.getCurrentLanguage()+'&billid='+billid);
	}

	$rootScope.$on("authentication.language.changed", function (event, current, previous, eventObj) {
		$scope.refreshAll();
	});

	$scope.refreshAll = function() {
		$scope.getAllMembers(null);
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 							'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'qualifications',			'text', 'qualifications');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'provinces', 					'text', 'provinces');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'countries', 					'text', 'countries');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'languages', 					'sequence', 'languages');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'genders', 						'text', 'genders');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'contacttypes', 				'text', 'contacttypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'skaterlevels', 				'text', 'skaterlevels');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'coachtypes', 					'text', 'coachtypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'programs', 						'text', 'programs');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'yesno', 							'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'testresults', 				 'sequence', 'testresults');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'familyranks', 				'text', 'familyranks');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'testlevels',					'text', 'testlevels');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'testtypes',						'text', 'testtypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'registrationfilters',	'sequence', 'registrationfilters');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'membermailfilters',	  'sequence', 'membermailfilters');

		listsService.getAllCoaches($scope, authenticationService.getCurrentLanguage());
		listsService.getAllActiveCourses($scope, authenticationService.getCurrentLanguage());
		listsService.getAllClubs($scope, authenticationService.getCurrentLanguage());

		listsService.getAllTests($scope, 'DANCE', 	'dances', 			authenticationService.getCurrentLanguage());
		listsService.getAllTests($scope, 'SKILLS',	'abilities', 		authenticationService.getCurrentLanguage());
		listsService.getAllTests($scope, 'FREE', 	'freestyles', 		authenticationService.getCurrentLanguage());
		listsService.getAllTests($scope, 'INTER', 	'interpretives', 	authenticationService.getCurrentLanguage());
		listsService.getAllTests($scope, 'COMP', 	'competitives', 	authenticationService.getCurrentLanguage());

		listsService.getAllStarTests($scope, 'DANCE', 		'stardances', 		authenticationService.getCurrentLanguage());
		listsService.getAllStarTests($scope, 'SKILLS',		'starabilities', 	authenticationService.getCurrentLanguage());
		listsService.getAllStarTests($scope, 'FREE', 		'starfreestyles',	authenticationService.getCurrentLanguage());
		listsService.getAllStarTests($scope, 'ARTISTIC',	'starartistics', 	authenticationService.getCurrentLanguage());
		listsService.getAllStarTests($scope, 'SYNCHRO', 	'starsynchros', 	authenticationService.getCurrentLanguage());

		translationService.getTranslation($scope, 'memberview', authenticationService.getCurrentLanguage());
		$rootScope.repositionLeftColumn();
		return;
	}

	// REPORTS
	$scope.printReport = function(reportName) {
		if (reportName == 'sessionCourseCSReportCard') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&memberid='+$scope.currentMember.id);
		}
		if (reportName == 'sessionCoursePreCSReportCard') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&memberid='+$scope.currentMember.id);
		}
		if (reportName == 'memberCSProgress') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&memberid='+$scope.currentMember.id);
		}
	}

	$scope.refreshAll();
}]);

angular.module('cpa_admin.memberview').directive('qualif', function() {
		return {
				scope: true,
				replace: true,
				templateUrl: './memberview/qualifications.template.html'
		}
});
