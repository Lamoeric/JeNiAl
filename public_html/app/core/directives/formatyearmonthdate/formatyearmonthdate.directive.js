angular.module('core').directive('formatYearmonthdate', function() {
    return {
      require: 'ngModel',
      link: function(scope, element, attrs, modelCtrl) {
        var formatNumber = function(number) {

		      if (!number) { return ''; }
					number = number.replace(/[^0-9]+/g, '');
		      number = String(number);

		      // Will return formattedNumber.
		      var formattedNumber = number;

					// YYYY-MM
					var year = number.substring(0,4);
					var month = number.substring(4, 6);
          if (month && month/1 > 12) month = 12;
					// var day = number.substring(6, 8);

					if (month) {
						formattedNumber = year + "-" + month;
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
