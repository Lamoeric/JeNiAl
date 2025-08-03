angular.module('core').service('errorHandlingService', ['$log', '$injector', 'dialogService', function ($log, $injector, dialogService) {

	this.saveErrorToDB = function (message, stack, cause, programName) {
		var http = $injector.get('$http');
		var rootScope = $injector.get('$rootScope');
		var userId = rootScope && rootScope.userInfo && rootScope.userInfo.userid ? rootScope.userInfo.userid : '';
		return http({
			method: 'post',
			url: './core/services/errorhandling/errorhandling.php',
			data: $.param({ 'user': userId, 'message': message, 'stack': stack, 'cause': cause, 'progname': programName, 'type': 'insertLog' }),
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).
		success(function (data, status, headers, config) {
			return;
		}).
		error(function (data, status, headers, config) {
			return;
		});
	}

	this.logException = function (exception, cause, programTitle) {
		$log.error(exception, cause);
		this.saveErrorToDB(exception.message, exception.stack, cause, programTitle);
		// Display generic message to user
		dialogService.alertDlg("Oops, an exception occured!");
	}

	this.logDataError = function (data, programName) {
		var message = null, stack = null;
		if (data.message) {
			message = data.message;
			stack = null;
		} else {
			// This is a big message from PHP and we are going to try to extract the main message
			var toto = "( ! )</span>";
			message = data.substring(data.indexOf(toto) + toto.length, data.indexOf("</th></tr>"));
			stack = data;
		}
		$log.error(message, stack);
		this.saveErrorToDB(message, stack, null, programName);
		// Display generic message to user
		dialogService.alertDlg("Oops, an exception occured!");
	}
}]);