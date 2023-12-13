angular.module('core').directive('formatPostalCode', function() {
	return {
		require: 'ngModel',
		link: function(scope, element, attrs, modelCtrl) {
			var formatPostalCode = function(originalCode) {
				if (!originalCode) { return ''; }
				// originalCode = originalCode.replace(/[^0-9]+/g, '');
				originalCode = originalCode.replaceAll(' ', '');
				originalCode = String(originalCode);
				var formattedCode = '';//originalCode;

                // LNL NLN
                var codeArr = [];
				codeArr[0] = originalCode[0] &&  isNaN(originalCode[0]) ? originalCode[0] : null;
				codeArr[1] = originalCode[1] && !isNaN(originalCode[1]) ? originalCode[1] : null;
				codeArr[2] = originalCode[2] &&  isNaN(originalCode[2]) ? originalCode[2] : null;
				codeArr[3] = originalCode[3] && !isNaN(originalCode[3]) ? originalCode[3] : null;
				codeArr[4] = originalCode[4] &&  isNaN(originalCode[4]) ? originalCode[4] : null;
                codeArr[5] = originalCode[5] && !isNaN(originalCode[5]) ? originalCode[5] : null;
                for (var i = 0; i < codeArr.length; i++){
                    if (codeArr[i] != null) {
                        formattedCode += codeArr[i];
                    } else {
                        break;
                    }
                    if (i == 2) formattedCode += ' ';
                }
				if (formattedCode !== originalCode) {
					modelCtrl.$setViewValue(formattedCode);
					modelCtrl.$render();
				}
				return formattedCode;
			}

			modelCtrl.$parsers.push(formatPostalCode);
			formatPostalCode(scope[attrs.ngModel]); // formatPostalCode initial value
      	}
    };
});
