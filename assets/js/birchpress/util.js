(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;

    var ns = namespace('birchpress.util');

	defineFunction(ns, 'isMobile', function(){
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent);
	});	

	defineFunction(ns, 'getServerNow', function(serverGmtOffset){
        var now = new Date();
        var localOffset = now.getTimezoneOffset();
        var timestamp = now.getTime() + (localOffset - serverGmtOffset) * 60 * 1000;
        return new Date(timestamp);
	});	

	defineFunction(ns, 'scrollTo', function(selector, duration){
        if(!duration) {
            duration = 600;
        }
        $('html, body').animate({
             scrollTop: $(selector).offset().top
        }, duration);		
	});	

})(jQuery);