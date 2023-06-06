angular.module('core').directive('ignoreDirty', [function() {
    return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, elm, attrs, ctrl) {
      ctrl.$setPristine = function() {};
      ctrl.$pristine = false;
    }
  }
}]);
