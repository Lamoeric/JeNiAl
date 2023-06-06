// Adds the $scope[propname] array with all the possible codes for the codename
angular.module('core').service('parseISOdateService', [function() {

	this.parseDate = function(datestring) {
		if (datestring) {
			var b = datestring.split(/\D/);
			return new Date(b[0], b[1]-1, b[2], (b[3]||0),
										 (b[4]||0), (b[5]||0), (b[6]||0));
		}
		return null;
	};

	this.parseDateWithoutTime = function(dateString) {
		if (dateString && dateString != "0000-00-00" && dateString != "1899-11-30") {
			var newDateString = dateString + "T00:00:00";
			var b = newDateString.split(/\D/);
			return new Date(b[0], b[1]-1, b[2], (b[3]||0),
										 (b[4]||0), (b[5]||0), (b[6]||0));
		}
		return null;
	};

}]);
