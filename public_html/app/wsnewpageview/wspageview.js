'use strict';

angular.module('cpa_admin.wsnewpageview', ['ngRoute'])

.config(['$routeProvider', function($routeProvider) {
	$routeProvider.when('/wsnewpageview', {
		templateUrl: 'wsnewpageview/wspageview.html',
		controller: 'wsnewpageviewCtrl',
		resolve: {
			auth: function ($q, authenticationService) {
				var userInfo = authenticationService.getUserInfo();
				if (userInfo) {
					if (userInfo.privileges.admin_access==true) {
						return $q.when(userInfo);
					} else {
						return $q.reject({authenticated: true, validRights: false, newLocation:null});
					}
				} else {
					return $q.reject({authenticated: false, newLocation: "/wsnewpageview"});
				}
			}
		}
	});
}])

.controller('wsnewpageviewCtrl', ['$rootScope', '$scope', '$http', '$uibModal', '$sce', '$timeout', '$window', 'Upload', 'anycodesService', 'dialogService', 'listsService', 'authenticationService', 'translationService', function($rootScope, $scope, $http, $uibModal, $sce, $timeout, $window, Upload, anycodesService, dialogService, listsService, authenticationService, translationService) {

	$scope.progName = "wsnewpageview";
	$scope.currentWspage = null;
	$scope.isFormPristine = true;
	$scope.selectedPageObj = null;
	$scope.selectedPageIndex = null;
	$scope.selectedSectionObj = null;
	$scope.selectedSectionIndex = null;
	$scope.models = {selectedPageObj:null,selectedPageSectionObj:null};
	$scope.remarkable = new Remarkable({
			html:         false,        // Enable HTML tags in source
			xhtmlOut:     false,        // Use '/' to close single tags (<br />)
			breaks:       false         // Convert '\n' in paragraphs into <br>
//      langPrefix:   'language-',  // CSS language prefix for fenced blocks
//      typographer:  false,        // Enable some language-neutral replacement + quotes beautification
//      quotes: '����',             // Double + single quotes replacement pairs, when typographer enabled, and smartquotes on. Set doubles to '��' for Russian, '��' for German.
//      highlight: function (/*str, lang*/) { return ''; } // Highlighter function. Should return escaped HTML, or '' if the source string is not changed
		});
	$scope.config = null;
	$scope.formList = [{name:'detailsForm'}];

	/**
	 * This function checks if anything is dirty
	 * @returns true if any of the forms are dirty, false otherwise
	 */
	$scope.isDirty = function () {
		return $rootScope.isDirty($scope, $scope.formList);
	};

	/**
	 * This function sets one form dirty to indicate the whole thing is dirty
	 */
	$scope.setDirty = function () {
		$rootScope.setDirty($scope, $scope.formList);
	};

	/**
	 * This function sets all the forms as pristine
	 */
	$scope.setPristine = function () {
		$rootScope.setPristine($scope, $scope.formList);
	};

	/**
	 * This function validates all forms and display error and warning messages
	 * @returns false if something is invalid
	 */
	$scope.validateAllForms = function () {
		return $rootScope.validateAllForms($scope, $scope.formList);
	}

	$scope.convertParagraph = function(paragraph) {
		if (paragraph) {
			paragraph.msgfr =  "<H3>" + (paragraph.title_fr!=null && paragraph.title_fr!='' ? paragraph.title_fr : '') + "</H3>" +
							"<H4>" + (paragraph.subtitle_fr!=null && paragraph.subtitle_fr!='' ? paragraph.subtitle_fr : '') + "</H4>" +
							"<p>" + (paragraph.paragraphtext_fr!=null && paragraph.paragraphtext_fr!='' ? $scope.remarkable.render(paragraph.paragraphtext_fr) : '') + "</p>";
			paragraph.msgfr =  $sce.trustAsHtml(paragraph.msgfr);
			paragraph.msgen =  "<H3>" + (paragraph.title_en!=null && paragraph.title_en!='' ? paragraph.title_en : '') + "</H3>" +
							"<H4>" + (paragraph.subtitle_en!=null && paragraph.subtitle_en!='' ? paragraph.subtitle_en : '') + "</H4>" +
							"<p>" + (paragraph.paragraphtext_en!=null && paragraph.paragraphtext_en!='' ? $scope.remarkable.render(paragraph.paragraphtext_en) : '') + "</p>";
			paragraph.msgen =  $sce.trustAsHtml(paragraph.msgen);
		}
//		if (paragraph) {
//			paragraph.msgfr =  "<H3>" + (paragraph.title_fr!=null && paragraph.title_fr!='' ? paragraph.title_fr : '[Titre vide]') + "</H3>" +
//							"<H4>" + (paragraph.subtitle_fr!=null && paragraph.subtitle_fr!='' ? paragraph.subtitle_fr : '[Sous-titre vide]') + "</H4>" +
//							"<p>" + (paragraph.paragraphtext_fr!=null && paragraph.paragraphtext_fr!='' ? $scope.remarkable.render(paragraph.paragraphtext_fr) : '[Texte vide]') + "</p>";
//			paragraph.msgfr =  $sce.trustAsHtml(paragraph.msgfr);
//			paragraph.msgen =  "<H3>" + (paragraph.title_en!=null && paragraph.title_en!='' ? paragraph.title_en : '[Titre vide]') + "</H3>" +
//							"<H4>" + (paragraph.subtitle_en!=null && paragraph.subtitle_en!='' ? paragraph.subtitle_en : '[Sous-titre vide]') + "</H4>" +
//							"<p>" + (paragraph.paragraphtext_en!=null && paragraph.paragraphtext_en!='' ? $scope.remarkable.render(paragraph.paragraphtext_en) : '[Texte vide]') + "</p>";
//			paragraph.msgen =  $sce.trustAsHtml(paragraph.msgen);
//		}
	}

	$scope.getAllWspage = function () {
		$scope.promise = $http({
			method: 'post',
			url: './wsnewpageview/managewspage.php',
			data: $.param({'language' : authenticationService.getCurrentLanguage(), 'type' : 'getAllPages' }),
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).
		success(function(data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope.websiteurl = data.websiteurl;
					$scope.pagelist = data.data;
					$scope.config = data.config;
					$scope.setPristine();
					$scope.selectedPageObj = null;
					$scope.selectedSectionObj = null;
					$scope.models = {selectedPageObj:null,selectedPageSectionObj:null};
					for (var i = 0; i < $scope.pagelist.length; i++) {
						for (var x = 0; x < $scope.pagelist[i].sections.length; x++) {
							var section = $scope.pagelist[i].sections[x].section;
							section.displayimagefilename = section.imagefilename + '?decache=' + Math.random();
							for (var y = 0; y < section.paragraphs.length; y++) {
								$scope.convertParagraph(section.paragraphs[y]);
							}
						}
					}
					$scope.setCurrentPage(null, $scope.selectedPageIndex)
					$scope.setCurrentSection(null, $scope.selectedSectionIndex)
				} else {
					$scope.pagelist = null;
				}
			} else {
				if (!data.success) {
					dialogService.displayFailure(data);
				}
			}
		}).
		error(function(data, status, headers, config) {
			dialogService.displayFailure(data);
		});
	};

	$scope.dropCallback = function(index, item, external, type) {
		$scope.setDirty();
		// Return false here to cancel drop. Return true if you insert the item yourself.
		$scope.models.selectedPageObj = item;
	return item;
	};

	$scope.setCurrentPage = function(page, index) {
		if ($scope.validateAllForms()) {
			$scope.selectedPageIndex = index;
			$scope.selectedPageObj = page ? page : index != null ? $scope.pagelist[index] : null;
			$scope.models.selectedPageObj = $scope.selectedPageObj;
			
			$scope.selectedPageSections = $scope.selectedPageObj ? $scope.selectedPageObj.sections : null;
			$scope.selectedSectionObj = null;
			$scope.models.selectedPageSectionObj = null;
		}
	}

	$scope.setCurrentSection = function(pagesection, index) {
		$scope.selectedSectionIndex = index;
		if ($scope.selectedSectionObj && $scope.validateAllForms()) {
			$scope.selectedSectionObj = pagesection.section;
			$scope.models.selectedPageSectionObj = pagesection;
		} else if ($scope.selectedSectionObj==null) {
			$scope.selectedSectionObj = pagesection ? pagesection.section : index != null ? $scope.selectedPageSections[index].section : null;
			$scope.models.selectedPageSectionObj =  pagesection ? pagesection : index != null ? $scope.selectedPageSections[index] : null;;
		}
	}

	$scope.setCurrentInternal = function (page, index) {
			$scope.getAllWspage();
			$scope.setPristine();
	}

	$scope.setCurrent = function (page, index) {
		if ($scope.isDirty()) {
			dialogService.confirmDlg($scope.translationObj.main.msgformdirty, "YESNO", $scope.setCurrentInternal, null, page, index);
		} else {
			$scope.setCurrentInternal(page, index);
		}
	};

	$scope.saveToDB = function() {
		if ($scope.pagelist == null || !$scope.isDirty()) {
			dialogService.alertDlg("Nothing to save!", null);
		} else {
			if ($scope.validateAllForms() == false) return;
			$scope.promise = $http({
				method: 'post',
				url: './wsnewpageview/managewspage.php',
				data: $.param({'pages' : JSON.stringify($scope.pagelist), 'type' : 'updateEntireSite' }),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).
			success(function(data, status, headers, config) {
				if (data.success) {
					// Select this page to reset everything
					$scope.setCurrentInternal(null, null);
					$scope.setPristine();
					$scope.selectedPageObj = null;
					$scope.selectedSectionObj = null;
					$scope.models = {selectedPageObj:null,selectedPageSectionObj:null};
					return true;
				} else {
					dialogService.displayFailure(data);
					return false;
				}
			}).
			error(function(data, status, headers, config) {
				dialogService.displayFailure(data);
				return false;
			});
		}
	};

	// This is the function that creates the modal to create/edit ice
	$scope.editSection = function(newSection) {
		$scope.newSection = {};
		// Keep a pointer to the current section
		$scope.currentSection = newSection;
		// Copy in another object
		angular.copy(newSection, $scope.newSection);
		$uibModal.open({
			animation: false,
			templateUrl: 'wsnewpageview/newsection.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newSection;
				}
			}
		})
		.result.then(function(newSection) {
			// User clicked OK and everything was valid.
			angular.copy(newSection, $scope.currentSection);
			if ($scope.currentSection.pagename != null) {
				$scope.currentSection.status = 'Modified';
			} else {
				// No new sections
//        $scope.currentSection.status = 'New';
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that creates the modal to create/edit ice
	$scope.editPage = function(newPage) {
		$scope.newPage = {};
		// Keep a pointer to the current section
		$scope.currentPage = newPage;
		// Copy in another object
		angular.copy(newPage, $scope.newPage);
		$uibModal.open({
			animation: false,
			templateUrl: 'wsnewpageview/newpage.template.html',
			controller: 'childeditor.controller',
			scope: $scope,
			size: 'lg',
			backdrop: 'static',
			resolve: {
				newObj: function () {
					return $scope.newPage;
				}
			}
		})
		.result.then(function(newPage) {
			// User clicked OK and everything was valid.
			angular.copy(newPage, $scope.currentPage);
			if (authenticationService.getCurrentLanguage() == 'en-ca') {
				$scope.currentPage.navbarlabeltext = $scope.currentPage.navbarlabel_en;
			} else {
				$scope.currentPage.navbarlabeltext = $scope.currentPage.navbarlabel_fr;
			}
			if ($scope.currentPage.name != null) {
				$scope.currentPage.status = 'Modified';
			} else {
				// No page creation
//        $scope.currentPage.status = 'New';
			}
			$scope.setDirty();
		}, function() {
			// User clicked CANCEL.
			// alert('canceled');
		});
	};

	// This is the function that displays the upload error messages
	$scope.displayUploadError = function(errFile) {
		// dialogService.alertDlg($scope.translationObj.detailssection.msgerrinvalidfile);
		if (errFile.$error == 'maxSize') {
			dialogService.alertDlg($scope.translationObj.detailssection.msgerrinvalidfilesize + errFile.$errorParam);
		} else if (errFile.$error == 'maxWidth') {
			dialogService.alertDlg($scope.translationObj.detailssection.msgerrinvalidmaxwidth + errFile.$errorParam);
		} else if (errFile.$error == 'maxHeight') {
			dialogService.alertDlg($scope.translationObj.detailssection.msgerrinvalidmaxheight + errFile.$errorParam);
		}
	}

	// This is the function that uploads the image for the current event
	$scope.uploadMainImage = function(file, errFiles) {
		$scope.f = file;
		if (errFiles && errFiles[0]) {
			$scope.displayUploadError(errFiles[0]);
		}
		if (file) {
			if (file.type.indexOf('jpeg') === -1 || file.name.indexOf('.jpg') === -1) {
				dialogService.alertDlg('only jpg files are allowed.');
				return;
			}
			file.upload = Upload.upload({
				url: './wsnewpageview/uploadmainimage.php',
				method: 'POST',
				file: file,
				data: {
					'mainobj': $scope.selectedSectionObj
				}
			});
			file.upload.then(function (data) {
				$timeout(function () {
					if (data.data.success) {
						dialogService.alertDlg($scope.translationObj.detailssection.msguploadcompleted);
						// Select this event to reset everything
						$scope.setCurrentInternal(null, null);
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
	
	$scope.testRemarkable = function() {
		var md = new Remarkable({
			html:         false,        // Enable HTML tags in source
			xhtmlOut:     false,        // Use '/' to close single tags (<br />)
			breaks:       false         // Convert '\n' in paragraphs into <br>
//      langPrefix:   'language-',  // CSS language prefix for fenced blocks
//      typographer:  false,        // Enable some language-neutral replacement + quotes beautification
//      quotes: '����',             // Double + single quotes replacement pairs, when typographer enabled, and smartquotes on. Set doubles to '��' for Russian, '��' for German.
//      highlight: function (/*str, lang*/) { return ''; } // Highlighter function. Should return escaped HTML, or '' if the source string is not changed
		});

		console.log(md.render('\<h3\># Remarkable rulezz!'));  
	}
	
	$scope.showPreview = function() {
		$window.open('http://' +$scope.websiteurl + '?preview=true', "_blank");
	}
	
	$scope.refreshAll = function() {
		$scope.getAllWspage();
//    listsService.getAllPages($scope, authenticationService.getCurrentLanguage());
		anycodesService.getAnyCodes($scope, $http, authenticationService.getCurrentLanguage(),'yesno', 'text', 'yesnos');
		translationService.getTranslation($scope, 'wsnewpageview', authenticationService.getCurrentLanguage());
//    $rootScope.repositionLeftColumn();
	}

	$scope.refreshAll();
}]);
