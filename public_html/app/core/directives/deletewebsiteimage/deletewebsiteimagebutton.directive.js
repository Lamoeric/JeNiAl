// This directive creates a button, that when clicked,
// 					deletes an image from the [private website images]/[subdir] directory
// 					clears the imagefilename column of the object
// 					updates the database
// 					calls back the caller
//	inputs :
// 		subdir : sub directory to concatenate to the [private website images]/ directory
//		obj : object containing at least an imagefilename and (id or name) property. If obj is not declared, you must set id and imagefilename.
//		context : this is in fact the last part of the table name to update. cpa_ws_+context. This table must have an IMAGEFILENAME column
//		id : This is the id of the object. 
//		imagefilename : name of the file to delete.
//		isDisabled : expression to determine if button is disabled or not.
//		callback : callback function to call ONLY IN CASE OF SUCCESS
angular.module('core').directive( "deletewebsiteimagebutton", ['$http', 'dialogService', 'authenticationService', 'translationService', function($http, dialogService, authenticationService, translationService) {
	return {
		template:'<button class="btn btn-primary glyphicon glyphicon-trash" ng-disabled="isDisabled"></button>',
		scope: {
			subdir: '=',
			dirsuffix: '=',
			obj: '=',
			context: '=',
			id: '=',
			imagefilename: '=',
			isDisabled: '=',
			callback: '&'
		},
		link: function( scope, element, attrs ) {
			translationService.getTranslation(scope, 'core/directives/deletewebsiteimage', authenticationService.getCurrentLanguage());

			element.bind( "click", function() {
				deleteImage();
			});

			function deleteImage(confirmed) {
				if (!confirmed) {
					dialogService.confirmDlg(scope.translationObj.main.confirmimagedeletion, "YESNO", deleteImage, null, true, null);
				} else {
					scope.promise = $http({
						method: 'post',
						url: './core/directives/deletewebsiteimage/deletewebsiteimage.php',
						data: $.param({'subdir' : scope.subdir, 'context' : scope.context, 'dirsuffix' : scope.dirsuffix, 'obj' : scope.obj, 'id' : scope.id, 'imagefilename' : scope.imagefilename}),
						headers: {'Content-Type': 'application/x-www-form-urlencoded'}
					}).
					success(function(data, status, headers, config) {
						if (data.success) {
							dialogService.alertDlg(scope.translationObj.main.imagedeleted);
							scope.callback();
						} else {
							dialogService.displayFailure(data);
						}
					}).
					error(function(data, status, headers, config) {
						dialogService.displayFailure(data);
					});
				}
			}
		}
	}
}]);
