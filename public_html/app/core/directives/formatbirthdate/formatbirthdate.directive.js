angular.module('core').directive('formatBirthdate', function() {
    return {
      require: 'ngModel',
      link: function(scope, element, attrs, modelCtrl) {
        var formatNumber = function(number) {

		      if (!number) { return ''; }
					number = number.replace(/[^0-9]+/g, '');
		      number = String(number);

		      // Will return formattedNumber.
		      var formattedNumber = number;

					// if the first character is '1', strip it out and add it back
					// var c = (number[0] == '1') ? '1 ' : '';
					// number = number[0] == '1' ? number.slice(1) : number;

					// YYYY-MM-DD
					var year = number.substring(0,4);
					var month = number.substring(4, 6);
          if (month && month/1 > 12) month = 12;
					var day = number.substring(6, 8);
          if (day && day/1 > 31) day = 31;

					if (month) {
						formattedNumber = year + "-" + month;
					}
					if (day) {
						formattedNumber += ("-" + day);
					}
          if (formattedNumber !== number) {
            modelCtrl.$setViewValue(formattedNumber);
            modelCtrl.$render();
          }
					return formattedNumber;
        }

        modelCtrl.$parsers.push(formatNumber);
        formatNumber(scope[attrs.ngModel]); // formatNumber initial value
      }
    };
  });
