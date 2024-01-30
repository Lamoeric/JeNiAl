// Adds the $scope[propname] array with all the possible codes for the codename
angular.module('core').service('anycodesService', ['dialogService', function (dialogService) {

	this.getAnyCodes = function ($scope, $http, language, codename, orderby, propname) {
		var promise = $http({
			method: 'post',
			url: 'core/services/anycode/getCodes.php',
			data: $.param({ 'language': language, 'codename': codename, 'orderby': orderby }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).success(function (data, status, headers, config) {
			if (data.success) {
				if (!angular.isUndefined(data.data)) {
					$scope[propname] = data.data;
				}
			} else {
				dialogService.displayFailure(data);
			}
		}).error(function (data, status, headers, config) {
			dialogService.displayFailure(data);
		});

		return promise;
	};

	this.convertCodeToDesc = function ($scope, listName, code) {
		var list = $scope[listName];
		for (var i = 0; i < list.length; i++) {
			if (list[i].code == code) {
				return list[i].text;
			}
		}
		return "";
	};

	this.convertIdToDesc = function ($scope, listName, id) {
		var list = $scope[listName];
		for (var i = 0; i < list.length; i++) {
			if (list[i].id == id) {
				return list[i].text;
			}
		}
		return "";
	};

	this.convertIdToLabel = function ($scope, listName, id) {
		var list = $scope[listName];
		for (var i = 0; i < list.length; i++) {
			if (list[i].id == id) {
				return list[i].label;
			}
		}
		return "";
	};

	this.convertCodeToLabel = function ($scope, listName, code) {
		var list = $scope[listName];
		for (var i = 0; i < list.length; i++) {
			if (list[i].code == code) {
				return list[i].label;
			}
		}
		return "";
	};
}]);
