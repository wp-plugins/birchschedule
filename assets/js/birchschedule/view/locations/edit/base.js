(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.locations.edit');

    addAction('birchschedule.initAfter', function(){
        birchschedule.view.initCountryStateField('birs_location_country', 'birs_location_state');
	});
})(jQuery);