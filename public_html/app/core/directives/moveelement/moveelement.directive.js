// This directive creates a form to change the index of an element
//  @author  Eric Lamoureux
//  inputs :
//    elementlist : the list of element
//    indexprop : the property in the element that holds the index
//    originalindex : the index of the element to move in the list
//    callback : The function to call once the reordering is done
angular.module('core').directive('moveelement', ['translationService', 'authenticationService', '$uibModal', 'anycodesService', '$http', function(translationService, authenticationService, $uibModal, anycodesService, $http) {
	return {
		restrict: 'E',
		scope: {
			elementlist: '=',
			originalindex: '=',
			indexprop: '=',
			callback: '&callback'
		},
		template: '<button class="btn btn-primary glyphicon glyphicon-move"></button>',
		link: function(scope, element, attrs) {
			element.bind( "click", function() {
				scope.editIndex();
			});

			// This opens a modal form to select the new index
			scope.editIndex = function() {
				 scope.newObject = {};
				 scope.newObject.elementlist = scope.elementlist;
				
				$uibModal.open({
						animation: false,
						templateUrl: './core/directives/moveelement/newindex.template.html',
						controller: 'childeditorex.controller',
						scope: scope,
						size: 'sm',
						backdrop: 'static',
						resolve: {
							newObj:   function() {return scope.newObject;},	// The object to edit
							control:  function() {return null},				// The control object containing all validation functions
							callback: function() {return null;}				// Callback function to overwrite the normal validation
						}
				})
				.result.then(function(newObject) {
					// User clicked OK and everything was valid.
					// Difference in index. Array is 0 based, but the list is what??? one based?
					var indexDiff = scope.elementlist[0][scope.indexprop];
					// Take the new index and reorder the elementlist.
					if (newObject.newIndex != scope.originalindex + indexDiff) {
						var element = scope.elementlist.splice(scope.originalindex, 1);
						scope.elementlist.splice(newObject.newIndex-1, 0, element[0]);
						//Change the value of all the prop of the elements in the array
						for (var x = 0; x < scope.elementlist.length; x++) {
							scope.elementlist[x][scope.indexprop] = x + indexDiff;
							scope.elementlist[x].status = 'Modified';
						}
						// Call callback function
						scope.callback();
					}

				}, function() {
					// User clicked CANCEL.
					// alert('canceled');
				});
			}
			translationService.getTranslation(scope, 'core/directives/moveelement', authenticationService.getCurrentLanguage());
		}
	};
}]);
