/**
 * This directive creates a button, that when clicked, 
 * 				open a file selector 
 * 				copy the files in the specified directory
 * 				updates the DB 
 * 
 * 	Image must be less than 8M
 * 
 *	inputs :
 * 		subdirectory : sub directory to concatenate to the [private website images]/ directory.
 *		fileprefix : This is the first part of the file name. Will be concatenated with a guid.
 *		tablename :  This table must have an IMAGEFILENAME column.
 *		idcolumnname :  This is the name of the column for the fk
 *		id : This is the id of the parent object (to put im the 'idcolumnname' of the 'tablename')
 *		isDisabled : expression to determine if button is disabled or not.
 *		callback : callback function to call ONLY IN CASE OF SUCCESS.
 */
angular.module('core').directive( "uploadwebsitepicturesbutton", ['$q', 'dialogService', 'authenticationService', 'translationService', 'Upload', '$timeout', function($q, dialogService, authenticationService, translationService, Upload, $timeout) {
	return {
		templateUrl:'./core/directives/uploadwebsitepictures/uploadwebsitepictures.template.html',
		scope: {
			subdirectory: '=',
			fileprefix: '=',
			tablename: '=',
			idcolumnname: '=',
			id: '=',
			isDisabled: '=',
			callback: '&'
		},
		link: function( scope, element, attrs ) {
			translationService.getTranslation(scope, 'core/directives/uploadwebsitepictures', authenticationService.getCurrentLanguage());

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
			 * This function that uploads the image(s) for the current element
			 * @param {*} file 
			 * @param {*} errFiles 
			 * @returns 
			 */
			scope.uploadPictures = function (files, errFiles) {
				scope.f = files;
				if (errFiles && errFiles[0]) {
					displayUploadError(errFiles[0]);
				}
				scope.nboffilesimported = 0;
				scope.nboffiles = files.length;
				var chain = $q.when();
				angular.forEach(files, function (file) {
					if (file) {
						if (file.type.indexOf('jpeg') === -1 && file.name.indexOf('.jpg') === -1) {
							dialogService.alertDlg('only jpg files are allowed.');
							return;
						}
						chain = chain.then(function () {
							return scope.promise = Upload.upload({
								url: '../../backend/uploadpictures.php',
								method: 'POST',
								file: file,
								data: {
									'subDirectory': scope.subdirectory,
									'filePrefix': scope.fileprefix,
									'tableName': scope.tablename,
									'idcolumnname': scope.idcolumnname,
									'id': scope.id
								}
							}).then(function (data) {
								$timeout(function () {
									if (data.data.success) {
										scope.nboffilesimported++;
										// Is this the last file to import?
										if (scope.nboffilesimported == scope.nboffiles) {
											// Execute callback function in case of success
											scope.callback();
										}
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
						});
					}
				});

			}
		}
	}
}]);
