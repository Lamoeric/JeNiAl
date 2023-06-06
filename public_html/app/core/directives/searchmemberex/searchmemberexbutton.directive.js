// This directive creates a button, that when clicked, creates a advanced search member dialog box
// The selected members is directly copied into the variable passed in parameter to the directive.
// The state of the form including this directive will be set to dirty if a member is selected.
//	inputs :
//		members : members array into which to copy selected member
//		isFormPristine : expression to determine if button is disabled or not
angular.module('core').directive( "searchMemberExButton", ['$uibModal', '$http', '$filter', 'anycodesService', 'authenticationService', 'translationService', 'dialogService', 'listsService', function($uibModal, $http, $filter, anycodesService, authenticationService, translationService, dialogService, listsService) {
	return {
		require: '^form',				// To set the $dirty flag if required
		template:'<button class="btn btn-primary" ng-disabled="isFormPristine" id="searchmember">{{translationObj.searchmember.buttontitlesearch}}</button>',
		scope: {
			members: '=members',
			isFormPristine: '=isFormPristine',
			sessionid: '=sessionid',
		},

		link: function( scope, element, attrs, formCtrl) {
			scope.internalControl = {};			// This object holds all the functions needed by the HTML template. HTML must use control.xxx() to call such function.
			element.bind( "click", function() {
				if (!scope.members) {
					scope.members = [];
				}
				
				scope.newObj = {};
				scope.newObj.searchFilter = null;
				scope.newObj.selectedItem = null;
				scope.newObj.filter = {};
				scope.newObj.filter.sessionid = null;
				scope.newObj.selectedMembers = [];

				angular.copy(scope.members, scope.newObj.selectedMembers);
				scope.formObj = formCtrl;
				scope.newObj.filter.sessionid = scope.sessionid;
				searchMember();
			});

			// Creates the list of available members (members that have not already been selected)
			scope.createMemberList = function() {
				var found = false;
				if (!scope.membersTotal) scope.membersTotal = [];
				scope.membersAvailable = [];
				if (scope.newObj.selectedMembers.length != 0) {
					for (var x = scope.membersTotal.length; x--;) {
						found = false;
						for (var y = scope.newObj.selectedMembers.length; y--;) {
							if (scope.membersTotal[x].id == scope.newObj.selectedMembers[y].id) {
								found = true;
								break;
							}
						}
						// Member has not been selected, add it to the available list of members
						if (!found) {
							scope.membersAvailable.splice(0, 0, scope.membersTotal[x]);
						}
					}
				} else {
					scope.membersAvailable = scope.membersTotal.slice();
				}
			}
			//									    <li ng-repeat="item in filtered = (membersAvailable | filter:searchFilter:control.customSearch as results)"

			scope.internalControl.search = function(searchParams) {
				scope.promise = $http({
						method: 'post',
						url: './core/directives/searchmemberex/searchmember.php',
						data: $.param({'filter' : scope.newObj.filter, 'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllObjects'}),
						headers: {'Content-Type': 'application/x-www-form-urlencoded'}
				}).
				success(function(data, status, headers, config) {
					scope.membersAvailable = [];
					scope.membersTotal = [];
					if (data.success) {
						if (!angular.isUndefined(data.data) ) {
							scope.membersTotal = data.data;
							scope.createMemberList();
						} else {
							scope.membersTotal = [];
							scope.createMemberList();
						}
					} else {
						dialogService.displayFailure(data);
					}
				}).
				error(function(data, status, headers, config) {
					dialogService.displayFailure(data);
				});
			};
			
			scope.internalControl.setCurrent = function (leftobj, index) {
				if (leftobj != null) {
					scope.selectedObject = leftobj;
					scope.selectedObjectIndex = index;
//					scope.getObjectDetails(leftobj);
				} else {
					scope.selectedObject = null;
					scope.selectedObjectIndex = null;
					scope.currentObject = null;
				}
			}

			scope.internalControl.remove = function(item) {
				for (var x = scope.newObj.selectedMembers.length; x--; ) {
					if (scope.newObj.selectedMembers[x].id == item.id) {
						scope.newObj.selectedMembers.splice(x, 1);
						break;
					}
				}
				// Reset available member list
				scope.createMemberList();
			}

			scope.internalControl.copyAll = function(searchFilter, filtered) {
				// Find the same array of members as the one displayed in UI, list could have been filtered
				var filteredArr = $filter('filter')(scope.membersAvailable, searchFilter, scope.internalControl.customSearch);
				// Create list of selected member ID
				var arrMemberId = [];
				for (var i = 0; i < scope.newObj.selectedMembers.length; i++) {
					arrMemberId.push(scope.newObj.selectedMembers[i].id);
				}
				// Copy filtered list to the selected list of members, do not duplicate members
				for (var i = 0; i < filteredArr.length; i++) {
					if (arrMemberId.indexOf(filteredArr[i].id) == -1) {
						scope.newObj.selectedMembers.push(filteredArr[i]);
					}
				}
				// Reset available member list
				scope.createMemberList();
			}

			scope.internalControl.customSearch = function(actual, expected) {
				// Ignore object.
				if (angular.isObject(actual)) return false;
				function removeAccents(value) {
					return value.toString()
																.replace(/á/g, 'a')
																.replace(/â/g, 'a')
																.replace(/à/g, 'a')
																.replace(/é/g, 'e')
																.replace(/è/g, 'e')
																.replace(/ê/g, 'e')
																.replace(/ë/g, 'e')
																.replace(/í/g, 'i')
																.replace(/ï/g, 'i')
																.replace(/ì/g, 'i')
																.replace(/ó/g, 'o')
																.replace(/ô/g, 'o')
																.replace(/ú/g, 'u')
																.replace(/ü/g, 'u')
																.replace(/û/g, 'u')
																.replace(/ç/g, 'c');
					}
				actual = removeAccents(angular.lowercase('' + actual));
				expected = removeAccents(angular.lowercase('' + expected));

				return actual.indexOf(expected) !== -1;
			}

			scope.internalControl.movedFromSelected = function(item) {
				if (item.registered == 0) {
					for (var x = scope.newObj.selectedMembers.length; x--; ) {
						if (scope.newObj.selectedMembers[x].id == item.id) {
							scope.newObj.selectedMembers.splice(x, 1);
							break;
						}
					}
					// Reset available member list
					scope.createMemberList();
				} else {
					return false;
				}
			}

			scope.internalControl.movedFromAvailable = function(item) {
				// Reset available member list
				scope.createMemberList();
			}

			// This is the function that creates the modal to search members
			function searchMember() {
//				scope.searchParams = {phpfilename:'./core/directives/searchmemberex/searchmember.php',
//															exceptionmemberid:scope.member ? scope.member.id : null,
//															language:authenticationService.getCurrentLanguage()};
				translationService.getTranslation(scope, 'core/directives/searchmemberex', authenticationService.getCurrentLanguage());
				listsService.getAllSessionCourses(scope, scope.newObj.filter.sessionid, authenticationService.getCurrentLanguage());
				// Send the parameters to the modal form
				$uibModal.open({
						animation: false,
						templateUrl: './core/directives/searchmemberex/searchmembers.template.html',
						controller: 'childeditorex.controller',
						scope: scope,
						size: 'xl',
						backdrop: 'static',
						resolve: {
							newObj: 	function() {return scope.newObj;},		    		// The object to edit
							control: 	function() {return scope.internalControl;},		// The control object containing all validation functions
							callback: function() {return null;}											// Callback function to overwrite the normal validation
						}
				})
				.result.then(function(returnObj) {
					// User clicked OK and everything was valid.
					angular.copy(returnObj.selectedMembers, scope.members)
					scope.membersAvailable = null;
					scope.newObj = {};

				}, function() {
					// User clicked CANCEL.
				});
			};
			translationService.getTranslation(scope, 'core/directives/searchmemberex', authenticationService.getCurrentLanguage());
			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'qualifications',			'text', 'qualifications');
			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'registrationfilters',	'sequence', 'registrationfilters');

		}
	}
}]);
