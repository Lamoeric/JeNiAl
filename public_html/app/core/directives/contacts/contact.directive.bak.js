angular.module('core').directive('contact', function() {
    return {
        scope: true,
        replace: true,
//        transclude:true,
        templateUrl: './core/directives/contacts/contact.template.html'
    }
});