// This service handles the selection dialog box for members
angular.module('core').service('selectMembersService', ['$uibModal', '$http', 'anycodesService', 'authenticationService', 'translationService', function($uibModal, $http, anycodesService, authenticationService, translationService) {
	thisSelectMembersService = this;
	// This is the function that creates the modal to select members
	this.selectMembers = function(scope) {
		scope.selectedMembers = {};
		// scope.selectedMembers.callback = that.validateSelectMembers;
		scope.selectedMembers.callback = function(editObjForm, selectedMembers) {
			var retVal = null;
			if (selectedMembers) {
				thisSelectMembersService.transformMemberSelectionCriteria(selectedMembers);
				if (selectedMembers.registration == null) { //} && selectedMembers.selectedQualifications.length == 0) {
					retVal = '#editObjFieldMandatory';
				} else if (selectedMembers.registration != null && selectedMembers.registration != 'PERCOURSE') {
				} else if (selectedMembers.registration == 'PERCOURSE') {
					if (selectedMembers.selectedCourses.length == 0) {
						retVal = '#editObjCoursedMandatory';
					}
				}
			}
			return retVal;
		}

		translationService.getTranslation(scope, 'core/services/selectmembers', authenticationService.getCurrentLanguage());
		return $uibModal.open({
				animation: false,
				templateUrl: 'core/services/selectmembers/selectmembers.template.html',
				controller: 'childeditor.controller',
				scope: scope,
				size: 'lg',
				backdrop: 'static',
				resolve: {
					newObj: function () {
						return scope.selectedMembers;
					}
				}
		})
		.result.then(function(selectedMembers) {
			thisSelectMembersService.transformMemberSelectionCriteria(selectedMembers);
			return selectedMembers;
			return;
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// Transforms the selectedMembers criteria
	this.transformMemberSelectionCriteria = function(selectedMembers) {
		selectedMembers.selectedCourses = [];
		angular.forEach(selectedMembers.courses, function(cb, key) {
			if (cb) {
				selectedMembers.selectedCourses.push(cb)
			}
		})

		selectedMembers.selectedQualifications = [];
		angular.forEach(selectedMembers.qualifications, function(cb, key) {
			if (cb) {
				selectedMembers.selectedQualifications.push(cb)
			}
		})
	}

	// This is the function to validate the selectmember template
	this.validateSelectMembers = function(editObjForm, selectedMembers) {
		var retVal = null;
		thisSelectMembersService.transformMemberSelectionCriteria(selectedMembers);
		if (selectedMembers.registration == null) { //} && selectedMembers.selectedQualifications.length == 0) {
			retVal = '#editObjFieldMandatory';
		} else if (selectedMembers.registration != null && selectedMembers.registration != 'PERCOURSE') {
		} else if (selectedMembers.registration == 'PERCOURSE') {
			if (selectedMembers.selectedCourses.length == 0) {
				retVal = '#editObjCoursedMandatory';
			}
		}
		return retVal;
	}


}]);
