'use strict';

angular.module('cpa_admin.testexternalapprobview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/testexternalapprobview', {
		templateUrl: 'testexternalapprobview/testexternalapprobview.html',
		controller: 'testexternalapprobviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				return authenticationService.validateUserRoutingPrivilege();
      		}
		}
	});
}])

.controller('testexternalapprobviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$window', '$timeout', 'Upload', 'dateFilter', 'parseISOdateService','anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $window, $timeout, Upload, dateFilter, parseISOdateService, anycodesService, dialogService, listsService, authenticationService, translationService) {
	$scope.progName = "testexternalapprobview";
	$scope.currenttestexternalapprob = null;
	$scope.selectedLeftObj = null;
	$scope.newtestexternalapprob = null;
	$scope.currentTestsession = {};
	$scope.selectedIndex = null;
	$scope.isFormPristine = true;
	$scope.userinfo = authenticationService.getUserInfo();

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

	$scope.getAlltestexternalapprobs = function () {
		// var userInfo = authenticationService.getUserInfo();
		$scope.promise = $http({
				method: 'post',
				url: './testexternalapprobview/testexternalapprob.php',
				data: $.param({'userinfo' : $scope.userinfo, 'type' : 'getAlltestexternalapprobs' }),
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
				} else {
					$scope.leftobjs = [];
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// Sents the email to the test director
	$scope.sendEmailToTestDirector = function(testexternalapprobsid) {
		$scope.promise = $http({
			method: 'post',
			url: './testexternalapprobview/testexternalapprob.php',
			data: $.param({'id' : testexternalapprobsid, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'sendEmailToTestDirector' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				dialogService.alertDlg($scope.translationObj.main.msgerremailsent);
				return;
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// Sents the email to the coach
	$scope.sendEmailToCoachExt = function(filename, ccToTestDirector) {
		// If sheet exists, add it to the email generation. If not, leave null.
		$scope.promise = $http({
			method: 'post',
			url: './testexternalapprobview/testexternalapprob.php',
			data: $.param({'id' : $scope.currenttestexternalapprob.id, 'filename' : filename, 'ccToTestDirector' : ccToTestDirector, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'sendEmailToCoach' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				dialogService.alertDlg($scope.translationObj.main.msgerremailsent);
				return;
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	// Creates the PDF file if a signed sheet exists for the test director and sends it to the coach, with the confirmation.
	$scope.sendEmailToCoach = function(ccToTestDirector) {
		// First, try to generate the signed approbation sheet.
		$scope.ccToTestDirector = ccToTestDirector;
		$scope.promise = $http({
			method: 'post',
			url: './reports/testExternalPermission.php',
			data: $.param({'testpermissionid' : $scope.currenttestexternalapprob.id, 'type': 'signed', 'output' : 'F', 'language' : 'fr-ca'/* todo : authenticationService.getCurrentLanguage()*/}),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && data.filename) {
				// filename = filename.replace("C:\\wamp\\www\\", "http://localhost/");
				return $scope.sendEmailToCoachExt(data.filename, $scope.ccToTestDirector);
			} else {
				if (data.message.indexOf('JeNiAlError1') != -1) {
				// if (filename.indexOf('JeNiAlError1') != -1) {
					dialogService.alertDlg($scope.translationObj.main.msgerrtestdirectornotdefined);
				// } else if (filename.indexOf('JeNiAlError2') != -1) {
				} else if (data.message.indexOf('JeNiAlError2') != -1) {
					// Ask if user wants to send the email without the signed approbation sheet.
					dialogService.confirmYesNo($scope.translationObj.main.msgerrsignedsheetdoesnotexist,
						function(e) {
							if (e) {
								return $scope.sendEmailToCoachExt(null, $scope.ccToTestDirector);
							} else {
								// user clicked "no"
							}
						});
				} else {
					dialogService.displayFailure(data);
					return null;
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
			return null;
		});
	};

	$scope.gettestexternalapprobDetails = function(testexternalapprob) {
		$scope.promise = $http({
			method: 'post',
			url: './testexternalapprobview/testexternalapprob.php',
			data: $.param({'id' : testexternalapprob.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'gettestexternalapprobDetails' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success && !angular.isUndefined(data.data) ) {
				$scope.currenttestexternalapprob = data.data[0];
				$scope.currenttestexternalapprob.testdate  = parseISOdateService.parseDateWithoutTime($scope.currenttestexternalapprob.testdate);
			} else {
				dialogService.displayFailure(data);
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.setCurrentInternal = function (testexternalapprob, index) {
		if (testexternalapprob != null) {
			$scope.selectedLeftObj = testexternalapprob;
			$scope.gettestexternalapprobDetails(testexternalapprob);
			$scope.selectedIndex = index;
			$scope.setPristine();
		} else {
			$scope.selectedLeftObj = null;
			$scope.currenttestexternalapprob = null;
			$scope.selectedIndex = null;
			$scope.setPristine();
		}
	}

	$scope.setCurrent = function (testexternalapprob, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, testexternalapprob, index);
		} else {
			$scope.setCurrentInternal(testexternalapprob, index);
		}
	};

	$scope.deleteFromDB = function(confirmed) {
		if ($scope.currenttestexternalapprob != null && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgconfirmdelete, "YESNO", $scope.deleteFromDB, null, true, null);
		} else {
			$scope.promise = $http({
				method: 'post',
				url: './testexternalapprobview/testexternalapprob.php',
				data: $.param({'testexternalapprob' : $scope.currenttestexternalapprob, 'type' : 'delete_testexternalapprob' }),
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

		// List of validations
		/*
					 TODO : Program should declare an error if no Test Director has been set in the configuration
		*/

		if ($scope.detailsForm.$invalid) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrallmandatory);
		}

		// Member must have a Skate Canada no and a home club
		if ($scope.currenttestexternalapprob.member != null && (($scope.currenttestexternalapprob.member.skatecanadano == null || $scope.currenttestexternalapprob.member.skatecanadano == '') || ($scope.currenttestexternalapprob.member.homeclub == null || $scope.currenttestexternalapprob.member.homeclub == ''))) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerrhomecludandskatecanadanomandatory);
		}

		// If host club is OTHER, the ckub name has to be provided
		if ($scope.currenttestexternalapprob.clubcode != null && $scope.currenttestexternalapprob.clubcode == 'OTHER' && $scope.currenttestexternalapprob.clubname == '') {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerrclubmandatory);
		}

		// Clear the clubname field if clubcode is != OTHER
		if ($scope.currenttestexternalapprob.clubcode != null && $scope.currenttestexternalapprob.clubcode != 'OTHER') {
			$scope.currenttestexternalapprob.clubname = '';
		}

		// Test date must be in the future, unless testregistration_revise privilege is true
		var today = new Date();
		today.setHours(0,0,0,0);
		if (!$scope.currenttestexternalapprob.testdate || $scope.currenttestexternalapprob.testdate < today) {
			if ($scope.userinfo.privileges.testregistration_revise == true) {
				$scope.globalWarningMessage.push($scope.translationObj.main.msgerrdateinpast);
			} else {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerrdateinpast);
			}
		}

		// At least one test must be present
		if (!$scope.currenttestexternalapprob.tests || $scope.currenttestexternalapprob.tests.length == 0) {
			$scope.globalErrorMessage.push($scope.translationObj.main.msgerratleastonetest);
		} else {
			var atleastonetest = false;
			for (var x = 0; $scope.currenttestexternalapprob.tests && x < $scope.currenttestexternalapprob.tests.length; x++) {
				if ($scope.currenttestexternalapprob.tests[x].status != 'Deleted') {
					atleastonetest = true;
					break;
				}
			}
			if (!atleastonetest) {
				$scope.globalErrorMessage.push($scope.translationObj.main.msgerratleastonetest);
			}
		}

		// if clubcode is OTHER and clubname is given and approbation is old (id != null) then advice to create the clubcode
		if ($scope.currenttestexternalapprob.id != null && $scope.currenttestexternalapprob.clubcode == 'OTHER') {
			$scope.globalWarningMessage.push($scope.translationObj.main.msgerrshouldcreateclub);
		}

		// In case user changed member midway, double check that all test were not passed previoulsy by the member
		if ($scope.globalErrorMessage.length == 0) {
			return $http({
				method: 'post',
				url: './testexternalapprobview/testexternalapprob.php',
				data: $.param({'memberid' : $scope.currenttestexternalapprob.member.id, 'tests' : $scope.currenttestexternalapprob.tests, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'validateSkatersTests' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					if (data.data) {
						// $scope.globalErrorMessage.push($scope.translationObj.main.msgerratleastonetest);
						$scope.globalErrorMessage.push($scope.translationObj.main.msgerrtestalreadypassed + data.data[0].testtypelabel + " - " + data.data[0].testlabel);
					}
				} else {
					dialogService.displayFailure(data);
					return false;
				}
				if ($scope.globalErrorMessage.length != 0) {
					// $scope.$apply();
					$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function() {$("#mainglobalerrormessage").hide();});
					retVal = false;
				}
				if ($scope.globalWarningMessage.length != 0) {
					// $scope.$apply();
					$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function() {$("#mainglobalwarningmessage").hide();});
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
		if ($scope.globalErrorMessage.length != 0) {
			// $scope.$apply();
			$("#mainglobalerrormessage").fadeTo(2000, 500).slideUp(500, function() {$("#mainglobalerrormessage").hide();});
			retVal = false;
		}
		if ($scope.globalWarningMessage.length != 0) {
			// $scope.$apply();
			$("#mainglobalwarningmessage").fadeTo(2000, 500).slideUp(500, function() {$("#mainglobalwarningmessage").hide();});
		}
		return retVal;
	}

	$scope.approveAprobation = function() {
		if ($scope.currenttestexternalapprob == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			$scope.promise = $scope.validateAllForms().
				then(function() {
					$scope.saveToDB().
					then(function() {
							// Here, we need to send an email to the coach?
							// dialogService.alertDlg("Done");
					})
				});
		}
	}

	$scope.submitAprobation = function() {
		$scope.promise = $scope.validateAllForms().
			then(function() {
				$scope.addtestexternalapprobToDB().
				then(function(data) {
					// Here, we need to send an email to the test director
					$scope.sendEmailToTestDirector(data.data.id);
				})
			});
	}

	$scope.saveToDB = function() {
		$scope.currenttestexternalapprob.testdatestr = dateFilter($scope.currenttestexternalapprob.testdate, 'yyyy-MM-dd');
		return $http({
				method: 'post',
				url: './testexternalapprobview/testexternalapprob.php',
				data: $.param({'testexternalapprob' : $scope.currenttestexternalapprob, 'userinfo' : $scope.userinfo, 'type' : 'updatetestexternalapprob'}),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this testexternalapprob to reset everything
					$scope.selectedLeftObj.approbationstatus = $scope.currenttestexternalapprob.approbationstatus;
					$scope.selectedLeftObj.testdate = dateFilter($scope.currenttestexternalapprob.testdate, 'yyyy-MM-dd');
					$scope.setCurrentInternal($scope.selectedLeftObj, $scope.selectedIndex);
				} else {
					dialogService.displayFailure(data);
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
			});
	};

	$scope.addtestexternalapprobToDB = function() {
		var userInfo = authenticationService.getUserInfo();
		$scope.currenttestexternalapprob.testdatestr = dateFilter($scope.currenttestexternalapprob.testdate, 'yyyy-MM-dd');
		return $http({
			method: 'post',
			url: './testexternalapprobview/testexternalapprob.php',
			data: $.param({'testexternalapprob' : $scope.currenttestexternalapprob, 'userid' : userInfo.userid, 'type' : 'insert_testexternalapprob' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				var newtestexternalapprob = {id:data.id, approbationstatus:$scope.currenttestexternalapprob.approbationstatus, testdate:dateFilter($scope.currenttestexternalapprob.testdate, 'yyyy-MM-dd'), firstname:$scope.currenttestexternalapprob.member.firstname, lastname:$scope.currenttestexternalapprob.member.lastname};
				$scope.leftobjs.push(newtestexternalapprob);
				// We could sort the list....
				$scope.setCurrentInternal(newtestexternalapprob);
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

	// This is the function that creates the modal to create new testexternalapprob
	$scope.createNew = function (confirmed) {
		if ($scope.isDirty() && !confirmed) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.createNew, null, true, null);
		} else {
			$scope.currenttestexternalapprob = {id:null};
			$scope.currenttestexternalapprob.approbationstatus = 2;
			$scope.$apply();
		}
	};

	// This is the function that creates the modal to create/edit test
	$scope.editTest = function(newTest) {
		$scope.newTest = {};
		// Keep a pointer to the current test
		$scope.currentTest = newTest;
		// Copy in another object
		angular.copy(newTest, $scope.newTest);
		// Get the values for the testsid drop down list
		if (newTest.testtype) {
			listsService.getAllTests($scope, newTest.testtype, "allTestsByType", authenticationService.getCurrentLanguage());
		} else {
			$scope.allTestsByType = null;
		}
		// Send the newTest to the modal form
		$uibModal.open({
				animation: false,
				templateUrl: 'testexternalapprobview/newtest.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return $scope.newTest;
					}
				}
			})
			.result.then(function(newTest) {
				// User clicked OK and everything was valid.
				angular.copy(newTest, $scope.currentTest);
				// If already saved in DB
				if ($scope.currentTest.id != null) {
					$scope.currentTest.status = 'Modified';
				} else {
					$scope.currentTest.status = 'New';
					if ($scope.currenttestexternalapprob.tests == null) $scope.currenttestexternalapprob.tests = [];
					// Don't insert twice in list
					if ($scope.currenttestexternalapprob.tests.indexOf($scope.currentTest) == -1) {
						$scope.currenttestexternalapprob.tests.push($scope.currentTest);
					}
				}
				$scope.setDirty();
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	// This is the function that creates the modal to import signed test sheets
	$scope.editSignedApprobationSheet = function() {
		$uibModal.open({
				animation: false,
				templateUrl: 'testexternalapprobview/editSignedTestSheet.template.html',
				controller: 'childeditor.controller',
				scope: $scope,
				size: null,
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return {};
					}
				}
			})
			.result.then(function(sheet) {
			}, function() {
				// User clicked CANCEL.
				// alert('canceled');
		});
	};

	$scope.uploadSignedApprobationSheet = function(file, errFiles, newObj) {
      $scope.f = file;
      $scope.errFile = errFiles && errFiles[0];
      if (file) {
        if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
          dialogService.alertDlg('only jpg files are allowed.');
          return;
        }
          // file.upload = Upload.upload({
          //     url: 'https://angular-file-upload-cors-srv.appspot.com/upload',
          //     data: {file: file}
          // });
          file.upload = Upload.upload({
              url: 'testexternalapprobview/uploadSignedApprobationSheet.php',
              method: 'POST',
              file: file,
               data: {
                   'testdirectorid': newObj.testdirectorid,
									 'language' : authenticationService.getCurrentLanguage()
              //     'targetPath' : '/media/'
               }
          });
          file.upload.then(function (data) {
              $timeout(function () {
                if (data.data.success) {
									dialogService.alertDlg($scope.translationObj.main.msgerrimportdone);
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


	$scope.onTestTypeChange = function(newObj) {
		listsService.getAllTestsForMember($scope, newObj.testtype, $scope.currenttestexternalapprob.member.id, "allTestsByType", authenticationService.getCurrentLanguage());
		newObj.testsid = null;
		if (newObj.testtype != 'DANCE') {
			newObj.partnerid = null;
			newObj.musicid = null;
			newObj.partnersteps = null;
		}
	}

	// REPORTS
	$scope.printReport = function(reportName) {
		if (reportName == 'testExternalPermissionBlank') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage());
		}
		if (reportName == 'testExternalPermission') {
			$window.open('./reports/'+reportName+'.php?language='+authenticationService.getCurrentLanguage()+'&testpermissionid='+$scope.currenttestexternalapprob.id+'&type=normal');
		}
		// if (reportName == 'testExternalPermissionForSigning') {
		// 	$window.open('./reports/testExternalPermissionBlank.php?language='+authenticationService.getCurrentLanguage());
		// }
	}

	$scope.refreshAll = function() {
		translationService.getTranslation($scope, 'testexternalapprobview', authenticationService.getCurrentLanguage());
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'testtypes', 'text', 'testtypes');
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(), 'approbationstatus', 'sequence', 'approbationstatus');
		listsService.getCoaches($scope, authenticationService.getCurrentLanguage());
		listsService.getAllTestsEx($scope, authenticationService.getCurrentLanguage());
		listsService.getAllTestDirectors($scope, authenticationService.getCurrentLanguage());
		listsService.getAllClubs($scope, authenticationService.getCurrentLanguage())
			.then(function() {
				$scope.homeclubs.splice(0, 0, {code:'OTHER', text:$scope.translationObj.main.homeclubsother});
				// $scope.homeclubs.splice(0, 0, {code:'NA', text:''});
			});
		$scope.getAlltestexternalapprobs();
		$rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
