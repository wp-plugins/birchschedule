(function($){

    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;

    var ns = namespace('birchschedule');
    
    defineFunction(ns, 'init', function(){});

    $(function(){
        birchschedule.init();
    });
})(jQuery);