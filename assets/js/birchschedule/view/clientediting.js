(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchpress.view.clientediting');

    addAction('birchschedule.initAfter', function(){
		function switchStateProvince(){
		    birchschedule.view.switchStateProvince('birs_client_country', 
                'birs_client_state');
		};
        birchschedule.view.initStateField('birs_client_state');
		$('#birs_client_country').change(switchStateProvince);
    });
})(jQuery);