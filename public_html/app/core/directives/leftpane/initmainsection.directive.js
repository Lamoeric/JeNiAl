angular.module('core').directive('initmainsection', [function() {
  return {
    restrict: 'A',
    // require: 'ngModel',
    link: function(scope, elm, attrs, ctrl) {
      // For delay display
      scope.delay = 0;
      scope.minDuration = 0;
      scope.message = 'Please Wait...';
      scope.backdrop = true;
      scope.promise = null;
    }
  }
}]);
