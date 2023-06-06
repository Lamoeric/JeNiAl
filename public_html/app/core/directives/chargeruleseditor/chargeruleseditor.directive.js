// This directive creates a button, that when clicked, creates a charge's rules editor dialog box
// The created/modified rule is directly copied into the charge passed in parameter to the directive.
// The state of the form including this directive will be set to dirty if a rule is created/modified.
//	inputs :
// 		type : 1 = icon is a pen, 2 = icon is a +
//		rule : rule object into which to copy the created/modified rule.
//		charge : charge object. Needed to attach the rule to the proper charge.
//		isDisabled : expression to determine if button is disabled or not.
//		callback : callback function to call.
angular.module('core').directive("chargeruleseditor", ['$uibModal', '$http', 'listsService', 'anycodesService', 'authenticationService', 'translationService', 'dateFilter', function($uibModal, $http, listsService, anycodesService, authenticationService, translationService, dateFilter) {
	return {
		require: '^form',				// To set the $dirty flag after copying the created/modified rule
		template:'<button class="btn btn-primary glyphicon glyphicon-pencil" ng-disabled="isDisabled" id="chargeruleseditor" ng-if="type==1"></button><button class="btn btn-primary glyphicon glyphicon-plus" ng-disabled="isDisabled" id="chargeruleseditor" ng-if="type==2"></button>',
		scope: {
			type: '=',
			rule: '=rule',
			charge: '=charge',
			isDisabled: '=isDisabled',
			callback: '&callback',
		},

		link: function( scope, element, attrs, formCtrl) {
			scope.internalControl = {};			// This object holds all the functions needed by the HTML template. HTML must use control.xxx() to call such function.
			element.bind( "click", function() {
				scope.formObj = formCtrl;
				if (!scope.rule) scope.rule = {};
				editRule(scope.charge, scope.rule);
			});

			// Called when user clicks on the edit rule button.
			// This function opens a dialog box to edit a rule
			function editRule(charge, newRule) {
				translationService.getTranslation(scope, 'core/directives/chargeruleseditor', authenticationService.getCurrentLanguage());
				// var userInfo = authenticationService.getUserInfo();
				scope.newRule = {};
				// Keep a pointer to the current rule
				if (newRule && newRule.id) {
					scope.currentRule = newRule;
					// Copy in another object
					angular.copy(newRule, scope.newRule);
					if (scope.currentRule.ruleparameters) {
						scope.newRule.ruleparameters = angular.fromJson(newRule.ruleparameters);
					}
					scope.newRule.ruletypeobj = {};
					scope.newRule.ruletypeobj.code = scope.newRule.ruletype;
					if (scope.newRule.ruletype == 1) {
						scope.courselevels = listsService.getAllCourseLevels(scope, authenticationService.getCurrentLanguage(), scope.newRule.ruleparameters.coursecode);
					}
				} else {
					// This is a new rule
					scope.currentRule = {};
				}
				$uibModal.open({
						animation: false,
						templateUrl: './core/directives/chargeruleseditor/chargeruleseditor.template.html',
						controller: 'childeditorex.controller',
						scope: scope,
						size: 'md',
						backdrop: 'static',
						resolve: {
							newObj: 	function() {return scope.newRule;},						// The object to edit
							control: 	function() {return scope.internalControl;},		// The control object containing all validation functions
							callback: function() {return null;}											// Callback function to overwrite the normal validation
						}
					})
					.result.then(function(newRule) {
						// User clicked OK and everything was valid.
						angular.copy(newRule, scope.currentRule);
						switch(scope.currentRule.ruletype) {
							case "1":		// multicoursesrule
								scope.currentRule.ruleparameters.scope = "theOrder";
								break;
							case "2":
								scope.currentRule.ruleparameters.scope = "theSession";
								break
						}
						// Put everything in a json
						scope.currentRule.ruleparameters = angular.toJson(scope.currentRule.ruleparameters);
						scope.currentRule.ruletype = scope.currentRule.ruletypeobj.code;
						scope.currentRule.ruletypelabel = scope.currentRule.ruletypeobj.text;
						// If already saved in DB, put status to Modified, else to New
						if (scope.currentRule.id != null) {
							scope.currentRule.status = 'Modified';
						} else {
							scope.currentRule.status = 'New';
							if (charge.rules == null) charge.rules = [];
							// Don't insert twice in list
							if (charge.rules.indexOf(scope.currentRule) == -1) {
								charge.rules.push(scope.currentRule);
							}
						}
						if (scope.callback) scope.callback();
						scope.formObj.$dirty = true;
					}, function() {
							// User clicked CANCEL.
				});
			}

			// Called when user changes the rule type field
			// Used by the chargeruleseditor.template.html form, multicoursesrule.template.html
			scope.internalControl.onRuleTypeChange = function(newObj) {
				newObj.ruletype = newObj.ruletypeobj.code;
				newObj.ruleparameters = null;
			}

			// Called when user changes the course field
			// Used by the chargeruleseditor.template.html form, multicoursesrule.template.html
			scope.internalControl.onCourseChange = function(newObj) {
				newObj.courselevel = null;
				scope.courselevels = listsService.getAllCourseLevels(scope, authenticationService.getCurrentLanguage(), newObj.ruleparameters.coursecode);
			}

			// Called when user changes the test field
			// Used by the chargeruleseditor.template.html form
			scope.internalControl.nullifyCourseLevel = function(newObj) {
				if (newObj) {
					newObj.courselevel = null;
				}
			}

			anycodesService.getAnyCodes(scope, $http, authenticationService.getCurrentLanguage(), 'ruletypes', 'sequence', 'ruletypes');
			listsService.getAllCoursesForRules(scope, authenticationService.getCurrentLanguage());
		}
	}
}]);
