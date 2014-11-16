(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;

    var ns = namespace('birchpress.util');

	defineFunction(ns, 'isMobile', function(){
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent);
	});	

	defineFunction(ns, 'getNow4Locale', function(serverGmtOffset){
        var now = new Date();
        return ns.getDate4Locale(now, serverGmtOffset);
	});

    defineFunction(ns, 'getDate4Locale', function(date, serverGmtOffset) {
        var localOffset = date.getTimezoneOffset();
        var timestamp = date.getTime() + (localOffset - serverGmtOffset) * 60 * 1000;
        return new Date(timestamp);
    });

    defineFunction(ns, 'getDate4Server', function(date, serverGmtOffset) {
        var localOffset = date.getTimezoneOffset();
        var timestamp = date.getTime() + (serverGmtOffset - localOffset) * 60 * 1000;
        return new Date(timestamp);
    });

	defineFunction(ns, 'scrollTo', function(selector, duration, addition){
        if(!duration) {
            duration = 600;
        }
        if(!addition) {
            addition = 0;
        }
        $('html, body').animate({
             scrollTop: $(selector).offset().top + addition
        }, duration);		
	});

    defineFunction(ns, 'getUnixTimestamp', function(timestamp){
        return Math.round(timestamp / 1000);
    });

    defineFunction(ns, 'parseParams', function(query){
        var re = /([^&=]+)=?([^&]*)/g;
        var decode = function(str) {
            return decodeURIComponent(str.replace(/\+/g, ' '));
        };
        var params = {};
        if (query) {
            if (query.substr(0, 1) == '?') {
                query = query.substr(1);
            }

            var e = re.exec(query);
            while (e) {
                var k = decode(e[1]);
                var v = decode(e[2]);
                if (params[k] !== undefined) {
                    if (!$.isArray(params[k])) {
                        params[k] = [params[k]];
                    }
                    params[k].push(v);
                } else {
                    params[k] = v;
                }
                e = re.exec(query);
            }
        }
        return params;
    });

    defineFunction(ns, 'parseAjaxResponse', function(doc) {
        doc = '<div>' + doc + '</div>';
        var success = false;
        var errors = false;
        if($(doc).find('#birs_success').length > 0) {
            var code = $(doc).find('#birs_success').attr('code');
            var message = $(doc).find('#birs_success').html();
            success = {
                'code': code,
                'message': message
            };
        }
        if($(doc).find('#birs_errors').length > 0) {
            errors = {};
            var errorEls = $(doc).find('#birs_errors').children();
            errorEls.each(function(index, elDom){
                var el = $(elDom);
                errors[el.attr('id')] = el.html();
            });
        }
        return {
            'success': success,
            'errors': errors
        };
    });

})(jQuery);