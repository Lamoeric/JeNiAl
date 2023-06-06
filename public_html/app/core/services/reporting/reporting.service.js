// This service handles the selection dialog box for members
angular.module('core').service('reportingService', ['$http', '$window','anycodesService', 'authenticationService', 'translationService', 'dialogService', function($http, $window, anycodesService, authenticationService, translationService, dialogService) {
	thisReportingService = this;

  // This function creates a report and then displays it
  this.createAndDisplayReport = function(reportName, parameters) {
    return $http({
      method: 'post',
      url: './reports/' + reportName,
      data: $.param({'parameters' : parameters}),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(filename) {
      if (filename && filename.indexOf('error') == -1) {
        // filename = filename.replace("C:\\wamp\\www\\", "http://localhost/");
        $window.open(filename);
        return true;
      } else {
        dialogService.displayFailure(filename);
        return false;
      }
    }).
    error(function(filename) {
      dialogService.displayFailure(filename);
      return false;
    });
  }
	// This function creates a report and return the filename
	this.createAndReturnReport = function(reportName, parameters) {
    $http({
      method: 'post',
      url: './reports/' + reportName,
      data: $.param({'parameters' : parameters}),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(filename) {
      if (filename && filename.indexOf('error') == -1) {
        filename = filename.replace("C:\\wamp\\www\\", "http://localhost/");
        // filename = filename.replace("cpa_admin", "...");
        // $window.open("http://localhost/cpa_admin/tmp/FOOB2AE.pdf")
        return filename;
      } else {
        dialogService.displayFailure(filename);
      }
    }).
    error(function(filename) {
      dialogService.displayFailure(filename);
      return false;
    });
  }
}]);
