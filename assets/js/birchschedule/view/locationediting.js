(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchpress.view.locationediting');

    addAction('birchschedule.initAfter', function(){
	    function switchStateProvince(){
	        birchschedule.view.switchStateProvince('birs_location_country', 
	        	'birs_location_state');
	    };
	    birchschedule.view.initStateField('birs_location_state');
	    $('#birs_location_country').change(switchStateProvince);
	});
})(jQuery);