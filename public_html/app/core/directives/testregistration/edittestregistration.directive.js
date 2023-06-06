// This directive creates a button, that when clicked, creates a test registration dialog box
// The created/modified test registration is directly copied into the test registration passed in parameter to the directive.
// The state of the form including this directive will be set to dirty if a test registration is created/modified.
//	inputs :
// 		type : 1 = icon is a pen, 2 = icon is a +
//		testregistration : testregistration object into which to copy the created/modified test registration.
//		period : period object. Needed to attach the testregistration to the proper period.
//		isDisabled : expression to determine if button is disabled or not.
//		callback : callback function to call.
angular.module('core').directive( "edittestregistration", ['$uibModal', '$http', 'dialogService', 'listsService', 'anycodesService', 'authenticationService', 'translationService', 'dateFilter', function($uibModal, $http, dialogService, listsService, anycodesService, authenticationService, translationService, dateFilter) {
	return {
		require: '^form',				// To set the $dirty flag after copying the created/modified test registration
		template:'<button class="btn btn-primary glyphicon glyphicon-pencil" ng-disabled="isDisabled" id="edittestregistration" ng-if="type==1"></button><button class="btn btn-primary glyphicon glyphicon-plus" ng-disabled="isDisabled" id="edittestregistration" ng-if="type==2"></button>',
		scope: {
			type: '=',
			testregistration: '=testregistration',
			period: '=period',
			isDisabled: '=isDisabled',
			callback: '&callback',
		},

		link: function( scope, element, attrs, formCtrl) {
			scope.internalControl = {};			// This object holds all the functions needed by the HTML template. HTML must use control.xxx() to call such function.
			element.bind( "click", function() {
				scope.formObj = formCtrl;
				if (!scope.testregistration) scope.testregistration = {};
				if (scope.testregistration.id) {
					// Read testregistration from the database before editing
					scope.promise = $http({
						method: 'post',
						url: './core/directives/testregistration/testregistrationbase.php',
						data: $.param({'testregistrationid' : scope.testregistration.id, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getOneRegistrationFullDetails'}),
						headers: {'Content-Type': 'application/x-www-form-urlencoded'}
					}).
					success(function(data, status, headers, config) {
						if (data.success) {
							// Copy over original testregistration... do not create a new object!
							// scope.testregistration = data.data[0]; // WRONG!
							angular.copy(data.data[0], scope.testregistration); // GOOD!
							editTestRegistration(scope.period, scope.testregistration);
						} else {
							dialogService.displayFailure(data);
						}
					}).
					error(function(data, status, headers, config) {
						dialogService.displayFailure(data);
					});
				} else {
					editTestRegistration(scope.period, scope.testregistration);
				}
			});

			// Called when user clicks on the edit test registration button.
			// This function opens a dialog box to edit a test registration
			function editTestRegistration(period, newRegistration) {
				translationService.getTranslation(scope, 'core/directives/testregistration', authenticationService.getCurrentLanguage());
				// TODO : need to check how to make this work again
				var userInfo = authenticationService.getUserInfo();
				var coachid = (scope.newRegistration ? scope.newRegistration.coachid : null);
				var member = (scope.newRegistration ? scope.newRegistration.member : null);
				scope.canRegister = false;
				scope.newRegistration = {};
				// Keep a pointer to the current registration
				if (newRegistration && newRegistration.id) {
					scope.currentRegistration = newRegistration;
					// Copy in another object
					angular.copy(newRegistration, scope.newRegistration);
					scope.newRegistration.period = period;
					listsService.getAllStarTestsForMember(scope, scope.newRegistration.testtype, scope.newRegistration.memberid, "allStarTestsByType", authenticationService.getCurrentLanguage());
					listsService.getDanceMusics(scope, scope.newRegistration.testid, authenticationService.getCurrentLanguage());
				} else {
					scope.currentRegistration = {};
					// This is a new registration, put back the coachid and the member object to save time
					scope.newRegistration.period = period;
					scope.newRegistration.coachid = coachid;
					scope.newRegistration.member = member;
					scope.newRegistration.newtestssessionsperiodsid = period.id;
					scope.newRegistration.newtestssessionsid = period.newtestssessionsid;
					scope.newRegistration.approbationstatus = "2"; // Approbation Pending
				}
				$uibModal.open({
						animation: false,
						templateUrl: './core/directives/testregistration/edittestregistration.template.html',
						controller: 'childeditorex.controller',
						scope: scope,
						size: 'md',
						backdrop: 'static',
						resolve: {
							newObj: 	function() {return scope.newRegistration;},		// The object to edit
							control: 	function() {return scope.internalControl;},		// The control object containing all validation functions
							callback: function() {return null;}											// Callback function to overwrite the normal validation
						}
					})
					.result.then(function(newRegistration) {
						// User clicked OK and everything was valid.
						angular.copy(newRegistration, scope.currentRegistration);
						// Check if this is a approbation by checking if approbation status is "Approved" and approvedby is empty
						if ((scope.currentRegistration.approbationstatus == 1 || scope.currentRegistration.approbationstatus == 0) && (!scope.currentRegistration.approvedby || scope.currentRegistration.approvedby == '')) {
							var userInfo = authenticationService.getUserInfo();
							scope.currentRegistration.approvedby = userInfo.userid;
							scope.currentRegistration.approvedon = new Date();
						}
						if (scope.currentRegistration.approvedon && scope.currentRegistration.approvedon != '') {
							scope.currentRegistration.approvedonstr = dateFilter(scope.currentRegistration.approvedon, 'yyyy-MM-dd HH:mm:ss');
						}
						// If already saved in DB, put status to Modified, else to New
						if (scope.currentRegistration.id != null) {
							scope.currentRegistration.status = 'Modified';
						} else {
							scope.currentRegistration.status = 'New';
							scope.currentRegistration.skaterfirstname = scope.currentRegistration.member.firstname;
							scope.currentRegistration.skaterlastname = scope.currentRegistration.member.lastname;
							if (period.registrations == null) period.registrations = [];
							// Don't insert twice in list
							if (period.registrations.indexOf(scope.currentRegistration) == -1) {
								period.registrations.push(scope.currentRegistration);
							}
						}
						if (scope.callback) scope.callback();
						scope.formObj.$dirty = true;
					}, function() {
							// User clicked CANCEL.
				});
			}

			// Called when user changes the test type field
			// Used by the edittestregistration.template.html form
			scope.internalControl.onTestTypeChange = function(newObj) {
				if (newObj) {
					listsService.getAllStarTestsForMember(scope, newObj.testtype, newObj.member.id, "allStarTestsByType", authenticationService.getCurrentLanguage());
					newObj.testsid = null;
					if (newObj.testtype != 'DANCE') {
						newObj.partnerid = null;
						newObj.musicid = null;
						newObj.partnersteps = null;
					}
				}
			}

			// Called when user changes the test field
			// Used by the edittestregistration.template.html form
			scope.internalControl.onTestChange = function(newObj) {
				if (newObj) {
					if (newObj.testtype == 'DANCE') {
						listsService.getDanceMusics(scope, newObj.testid, authenticationService.getCurrentLanguage());
						newObj.musicid = null;
					}
				}
			}

			// Called when user changes the approbation status field
			// Used by the edittestregistration.template.html form
			scope.internalControl.onApprobationStatusChange = function(newObj) {
				if (newObj) {
					if (newObj.approbationstatus == '2' || newObj.approbationstatus == '0') {
						newObj.result = null;
						newObj.approvedby = null;
						newObj.approvedon = null;
					} else {
						newObj.result = '0';
					}
				}
			}

			// Determines if a user can edit a test registration
			// Used by the edittestregistration.template.html form
			scope.internalControl.canEditRegistration = function(registration, fieldname) {
				var retVal = false;
				if (registration) {
					var userInfo = authenticationService.getUserInfo();
					// If registration is deleted (isdeleted == 1), it's over, do not allow edition.
					if (registration.isdeleted && registration.isdeleted == '1') {
						return false;
					}
					// Test director is allowed to edit the whole registration. This can lead to problems....
					// For example, changing the test once the test result is set will leave a trace in cpa_members_tests
					if (userInfo.privileges.testregistration_revise == true && (registration.status2 == null && registration.result == 0)) {
						return true;
					}
					// If registration is approved, do not allow edition. Only cancel.
					if (registration.approbationstatus == 1 && (scope.currentRegistration && scope.currentRegistration.approbationstatus == registration.approbationstatus) && fieldname != 'canceled') {
						return false;
					}
					// If registration is an existing one
					if (registration.id) {
						if (!registration.period.canedit) {
							retVal = false;
						} else {
							if (registration.createdby == authenticationService.getUserInfo().userid) {
								retVal =  true;
							}
						}
					} else {
						// New registration - all fields are editable
						retVal = true;
					}
					if (fieldname == 'approbationstatus') {
						if (userInfo && userInfo.privileges.testregistration_confirm == true) {
							retVal =  true;
						} else {
							retVal = false;
						}
					}
				}
				return retVal;
			}

			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(),'startesttypes', 'text', 'startesttypes');
			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'approbationstatus', 'sequence', 'approbationstatus');
			listsService.getCoaches(scope, authenticationService.getCurrentLanguage());
			listsService.getPartners(scope, authenticationService.getCurrentLanguage());
			listsService.getAllTestsEx(scope, authenticationService.getCurrentLanguage());
			listsService.getAllDanceMusics(scope, authenticationService.getCurrentLanguage());
		}
	}
}]);
