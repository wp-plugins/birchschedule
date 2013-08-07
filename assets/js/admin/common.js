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
    birchschedule.showTab = function(tab) {
        tab.addClass("wp-tab-active");
        tab.siblings().removeClass("wp-tab-active");
        var blockSelector = tab.children("a").attr('href');
        $(blockSelector).show();
        $(blockSelector).siblings('.wp-tab-panel').hide();
    }
});