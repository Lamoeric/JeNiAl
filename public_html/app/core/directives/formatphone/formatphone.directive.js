angular.module('core').directive('formatPhone', function() {
    return {
		require: 'ngModel',
		link: function(scope, element, attrs, modelCtrl) {
			var formatNumber = function(number) {
				if (!number) { return ''; }
				number = number.replace(/[^0-9]+/g, '');
				number = String(number);

				// Will return formattedNumber.
				// If phonenumber isn't longer than an area code, just show number
				var formattedNumber = number;

				// if the first character is '1', strip it out and add it back
				var c = (number[0] == '1') ? '1 ' : '';
				number = number[0] == '1' ? number.slice(1) : number;

				// # (###) ###-#### as c (area) front-end
				var area = number.substring(0,3);
				var front = number.substring(3, 6);
				var end = number.substring(6, 10);
				var ext = number.substring(10, 20);

				if (front) {
					formattedNumber = (c + "(" + area + ") " + front);
				}
				if (end) {
					formattedNumber += ("-" + end);
				}
				if (ext) {
					formattedNumber += (" #" + ext);
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
