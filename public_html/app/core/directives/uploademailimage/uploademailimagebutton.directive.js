/**
 * This directive creates a button, that when clicked, 
 * 				open a file selector 
 * 				copy the file in the specified directory
 * 
 * 	Image must be 400px*100px and less than 20k
 * 
 *	inputs :
 *		isDisabled : expression to determine if button is disabled or not.
 *		callback : callback function to call ONLY IN CASE OF SUCCESS.
 */
angular.module('core').directive( "uploademailimagebutton", ['$http', 'dialogService', 'authenticationService', 'translationService', 'Upload', '$timeout', function($http, dialogService, authenticationService, translationService, Upload, $timeout) {
	return {
		templateUrl:'./core/directives/uploademailimage/uploademailimage.template.html',
		scope: {
			isDisabled: '=',
			callback: '&'
		},
		link: function( scope, element, attrs ) {
			translationService.getTranslation(scope, 'core/directives/uploademailimage', authenticationService.getCurrentLanguage());

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
						url: '../backend/changeemailimage.php',
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
