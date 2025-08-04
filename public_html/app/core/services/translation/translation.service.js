angular.module('core').service('translationService', function($resource) {

	var those = this;
	this.translationObj = null;
	this.completeArr = [];

	// Transform the record containing programname - formname - fieldname - text into a object with the syntax
	// translationObj.formname.fieldname = value
	// To use in HTML, use the following syntax : {{translationObj.leftPane.registration}} to get the value of the registration field for the left pane form
	this.transformTranslation = function(data, translationObj) {
		if (!translationObj) translationObj = {};
		for (var i = 0; data && i < data.length; i++) {
			if (translationObj[data[i].formname] == null) {
				translationObj[data[i].formname] = {};
			}
			translationObj[data[i].formname][data[i].fieldname] = data[i].text;
		}
		return translationObj;
	}

	this.getTranslation = function($scope, pathPrefix, language) {
		var languageFilePath = pathPrefix + '/translation_' + language + '.json';
		// console.log(languageFilePath);
		var file = $resource(languageFilePath);
		var translation = $resource(languageFilePath).query();
		translation.$promise.then(function(data) {
			// Data successfully loaded into 'user'
			$scope.translationObj = those.transformTranslation(data, $scope.translationObj);
			// console.log("data:", result); 
		}).catch(function(error) {
			// console.error("Error fetching user:", error);
		});
		return translation.$promise;

		// return $resource(languageFilePath).query(function (data) {
		// 		$scope.translationObj = those.transformTranslation(data, $scope.translationObj);
		// });
	};

	this.getNavbarTranslation = function($scope, language) {
		var languageFilePath = 'translation_navbar_' + language + '.json';
		// console.log(languageFilePath);
		var translation = $resource(languageFilePath).query();
		translation.$promise.then(function(data) {
			$scope.translationObj = those.transformTranslation(data);
			$scope.$broadcast('navbartranslation.loaded');
		});
		return translation.$promise;
	};

	this.getTranslationObj = function() {
		return this.translationObj;
	};
});
