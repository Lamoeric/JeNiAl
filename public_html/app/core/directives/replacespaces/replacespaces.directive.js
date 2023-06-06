angular.module('core').directive('replacespaces', function() {
    return {
      require: 'ngModel',
      link: function(scope, element, attrs, modelCtrl) {
        var replacespaces = function(inputValue) {
          if (inputValue == undefined) inputValue = '';
          var newString = inputValue.replace(/[\s]/g, '_');
          if (newString !== inputValue) {
            modelCtrl.$setViewValue(newString);
            modelCtrl.$render();
          }
          return newString;
        }
        modelCtrl.$parsers.push(replacespaces);
        replacespaces(scope[attrs.ngModel]); // newString initial value
      }
    };
  });
