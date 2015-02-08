(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.services.edit');

    addAction('birchschedule.initAfter', function(){
        var priceTypeEl = $("#birs_service_price_type"); 
        var priceEl = $('#birs_service_price');
        
        if(priceTypeEl.attr('value') !== 'fixed'){
            priceEl.hide();
        }
        priceTypeEl.change(function(){
            if($(this).attr('value') === 'fixed'){
                priceEl.show();
            } else {
                priceEl.hide();
            }
        });
    });
})(jQuery);