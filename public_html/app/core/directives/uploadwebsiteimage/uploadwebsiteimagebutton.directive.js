/**
 * This directive creates a button, that when clicked, 
 * 				open a file selector 
 * 				copy the file in the specified directory
 * 				updates the DB 
 * 				deletes the old file
 * 
 * 	Image must be 500px*500px and less than 300 KB
 * 
 *	inputs :
 * 		subdirectory : sub directory to concatenate to the [private website images]/ directory.
 *		fileprefix : This is the first part of the file name. Will be concatenated with a guid.
 *		tablename :  This table must have an IMAGEFILENAME column.
 *		id : This is the id of the object. 
 *		oldfilename : name of the file to delete.
 *		isDisabled : expression to determine if button is disabled or not.
 *		callback : callback function to call ONLY IN CASE OF SUCCESS.
 */
angular.module('core').directive( "uploadwebsiteimagebutton", ['$http', 'dialogService', 'authenticationService', 'translationService', 'Upload', '$timeout', function($http, dialogService, authenticationService, translationService, Upload, $timeout) {
	return {
		// template:'<button class="btn btn-primary" type="file" ngf-select="uploadMainImage($file, $invalidFiles)" accept="image/jpeg" ngf-max-height="500" ngf-max-width="500" ngf-max-size="300KB" ng-disabled="isDisabled">{{translationObj.main.buttontitleloadmainimage}}</button>',
		templateUrl:'./core/directives/uploadwebsiteimage/uploadwebsiteimage.template.html',
		scope: {
			subdirectory: '=',
			fileprefix: '=',
			tablename: '=',
			id: '=',
			name: '=',
			oldfilename: '=',
			isDisabled: '=',
			language: '=',
			callback: '&'
		},
		link: function( scope, element, attrs ) {
			translationService.getTranslation(scope, 'core/directives/uploadwebsiteimage', authenticationService.getCurrentLanguage());

			/**
			 * This function displays the upload error messages
			 * @param {*} errFile the file in error
			 */
			displayUploadError = function (errFile) {
				if (errFile.$error == 'maxSize') {
					dialogService.alertDlg(scope.translationObj.main.msgerrinvalidfilesize + errFile.$errorParam);
				} else if (errFile.$error == 'maxWidth') {
					dialogService.alertDlg(scope.translationObj.main.msgerrinvalidmaxwidth + errFile.$errorParam);
				} else if (errFile.$error == 'maxHeight') {
					dialogService.alertDlg(scope.translationObj.main.msgerrinvalidmaxheight + errFile.$errorParam);
				}
			}

			/**
			 * This function that uploads the image for the current element
			 * @param {*} file 
			 * @param {*} errFiles 
			 * @returns 
			 */
			scope.uploadMainImage = function (file, errFiles) {
				scope.f = file;
				if (errFiles && errFiles[0]) {
					displayUploadError(errFiles[0]);
				}
				if (file) {
					if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
						dialogService.alertDlg('only jpg files are allowed.');
						return;
					}
					file.upload = Upload.upload({
						url: '../../backend/uploadmainimage.php',
						method: 'POST',
						file: file,
						data: {
							'subDirectory': scope.subdirectory,
							'filePrefix': scope.fileprefix,
							'tableName': scope.tablename,
							'id': scope.id ? scope.id : null,
							'name': scope.name ? scope.name : null,
							'oldFileName': scope.oldfilename,
							'language': (scope.language && scope.language != '' ? scope.language : null),
							'pattern': (scope.language && scope.language != '' ? 2 : 1)
						}
					});
					file.upload.then(function (data) {
						$timeout(function () {
							if (data.data.success) {
								dialogService.alertDlg(scope.translationObj.main.msguploadcompleted);
								scope.callback();
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
		}
	}
}]);
