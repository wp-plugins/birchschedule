(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.clients.edit');

    addAction('birchschedule.initAfter', function(){
        birchschedule.view.initCountryStateField('birs_client_country', 'birs_client_state');
    });
})(jQuery);