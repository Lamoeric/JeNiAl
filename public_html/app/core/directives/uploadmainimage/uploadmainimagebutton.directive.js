/**
 * This directive creates a button, that when clicked, 
 * 				open a file selector 
 * 				copy the file in the specified directory
 * 
 * 	Image must be 2500px*1500px and less than 4MB
 * 
 *	inputs :
 *		isDisabled : expression to determine if button is disabled or not.
 *		callback : callback function to call ONLY IN CASE OF SUCCESS.
 */
angular.module('core').directive( "uploadmainimagebutton", ['$http', 'dialogService', 'authenticationService', 'translationService', 'Upload', '$timeout', function($http, dialogService, authenticationService, translationService, Upload, $timeout) {
	return {
		// template:'<button class="btn btn-primary" type="file" ngf-select="uploadMainImage($file, $invalidFiles)" accept="image/jpeg" ngf-max-height="500" ngf-max-width="500" ngf-max-size="300KB" ng-disabled="isDisabled">{{translationObj.main.buttontitleloadmainimage}}</button>',
		templateUrl:'./core/directives/uploadmainimage/uploadmainimage.template.html',
		scope: {
			isDisabled: '=',
			callback: '&'
		},
		link: function( scope, element, attrs ) {
			translationService.getTranslation(scope, 'core/directives/uploadmainimage', authenticationService.getCurrentLanguage());

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
						url: '../backend/changeMainImage.php',
						method: 'POST',
						file: file,
						// data: {
						//     'awesomeThings': $scope.awesomeThings,
						//     'targetPath' : '/media/'
						// }
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
