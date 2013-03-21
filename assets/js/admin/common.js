jQuery(function($) {
    birchschedule.showMessage = function(selector, message, options) {
        options = _.extend({
            life: 1000,
            header: '&nbsp'
        }, options);
        if(selector === '') {
            $.jGrowl(message, options);
        } else {
            $(selector).jGrowl(message, options);
        }
    };
});