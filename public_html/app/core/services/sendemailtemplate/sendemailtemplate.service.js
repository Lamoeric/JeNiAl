/**
 * This service sends a email from a defined template.
 * The template is read from the DB then formated using Remarkable then send to the specified email address
 */
angular.module('core').service('sendEmailTemplateService', ['$http', 'dialogService', function($http, dialogService) {
    those = this;

    those.remarkable = new Remarkable({
        html:         false,        // Enable HTML tags in source
        xhtmlOut:     false,        // Use '/' to close single tags (<br />)
        breaks:       false         // Convert '\n' in paragraphs into <br>
    });

    this.sendEmailTemplate = function(emailtemplateid, language, emailaddress, callback) {
        // those.selectedObject = selectedObject;
        /* Retrieve the email template details */
        those.promise = $http({
            method: 'post',
            url: './core/services/sendemailtemplate/sendemailtemplate.php',
            data: $.param({'id' : emailtemplateid, 'language' : language, 'type' : 'getEmailtemplateDetails' }),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).
        success(function(data, status, headers, config) {
            if (data.success && !angular.isUndefined(data.data)) {
                those.currentEmailtemplate = data.data[0];
                /* This is where we need to send the email */
                those.promise = $http({
                    method: 'post',
                    url: './core/services/sendemailtemplate/sendemailtemplate.php',
                    data: $.param({'title' : those.currentEmailtemplate.title, 'emailbody' : those.remarkable.render(those.currentEmailtemplate.paragraphtext), 'emailaddress' : emailaddress, 'language' : language, 'type' : 'sendEmail'}),
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).
                success(function(data, status, headers, config) {
                    if (data.success) {
                        if (callback) callback();
                        // dialogService.alertDlg(those.translationObj.main.msgemailsent);
                        return;
                    } else {
                        dialogService.displayFailure(data);
                    }
                }).
                error(function(data, status, headers, config) {
                    dialogService.displayFailure(data);
                });
            } else {
                dialogService.displayFailure(data);
            }
        }).
        error(function(data, status, headers, config) {
            dialogService.displayFailure(data);
        });
    }

}]);
