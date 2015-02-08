(function($) {
    var params = birchschedule_view_admincommon;

    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.admincommon');

    defineFunction(ns, 'getI18nMessages', function(){
        return params.i18n_messages;
    });

    defineFunction(ns, 'showMessage', function(selector, message, options) {
        options = _.extend({
            life: 1000,
            position: 'bottom-right'
        }, options);
        if(selector === '') {
            $.jGrowl(message, options);
        } else {
            $(selector).jGrowl(message, options);
            $(selector).jGrowl('update');
        }
    });

    defineFunction(ns, 'hideMessage', function(selector) {
        if(selector === '') {
            $.jGrowl('close');
        } else {
            $(selector).jGrowl("close");
        }
    });

    defineFunction(ns, 'showTab', function(tab) {
        tab.addClass("wp-tab-active");
        tab.siblings().removeClass("wp-tab-active");
        var blockSelector = tab.children("a").attr('href');
        $(blockSelector).show();
        $(blockSelector).siblings('.wp-tab-panel').hide();
    });

})(jQuery);