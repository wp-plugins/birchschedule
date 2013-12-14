(function($){
    var params = birchschedule_model;

    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.model');

    defineFunction(ns, 'getAjaxUrl', function(){
        return params.ajax_url;
    });

    defineFunction(ns, 'getAllSchedule', function(){
        return params.all_schedule;
    });

    defineFunction(ns, 'getServerGmtOffset', function(){
        return params.gmt_offset;
    });

    defineFunction(ns, 'getAllDaysOff', function(){
        return params.all_daysoff;
    });

    defineFunction(ns, 'getFutureTime', function(){
        return params.future_time;
    });

    defineFunction(ns, 'getCutOffTime', function(){
        return params.cut_off_time;
    });

    defineFunction(ns, 'getServerNow', function(){
        var gmtOffset = ns.getServerGmtOffset();
        return birchpress.util.getServerNow(gmtOffset);
    });

    defineFunction(ns, 'isDayAvaliableByBookingPreferences', 
        function(date){
            var futureTime = ns.getFutureTime();
            var cutOffTime = ns.getCutOffTime();
            var serverNow = ns.getServerNow();
            var timeOfServer = serverNow.getTime();
            var timeOfSelect = date.getTime();
            var hoursBetween = (timeOfSelect - timeOfServer) / (1000 * 60 * 60) + 24; 
            var daysBetween = hoursBetween / 24;
            if(daysBetween > futureTime || hoursBetween < cutOffTime) {
                return false;
            }
            return true;
        }
    );

    defineFunction(ns, 'isDayAvaliableBySchedules',
        function(date, staffId, locationId, day){
            var allSchedule = ns.getAllSchedule();
            if(!_.has(allSchedule, staffId)) {
                return false;
            }
            var schedules_of_weekday = allSchedule[staffId][locationId]['schedules'][day];
            var avalibility = false;
            _.each(schedules_of_weekday, function(schedule, index) {
                var selectedDay = $.datepicker.formatDate('yy-mm-dd', date);
                var dateStart = $.datepicker.formatDate('yy-mm-dd', 
                        $.datepicker.parseDate('mm/dd/yy', schedule['date_start']));
                var dateEnd = $.datepicker.formatDate('yy-mm-dd', 
                        $.datepicker.parseDate('mm/dd/yy', schedule['date_end']));
                if((selectedDay >= dateStart || !dateStart) && 
                    (selectedDay <= dateEnd || !dateEnd)) {
                    avalibility = true;    
                }
            });
            return avalibility;
        }
    );

    defineFunction(ns, 'isDayAvaliableByDaysOff',
        function(date, staffId){
            var allDaysoff = ns.getAllDaysOff();
            if(_.has(allDaysoff, staffId)) {
                var dayoffsJson = allDaysoff[staffId];    
                var dayoffs = $.parseJSON(dayoffsJson);
                var selectedDay = $.datepicker.formatDate('mm/dd/yy', date);
                if(dayoffs && _.contains(dayoffs, selectedDay)) {
                    return false;
                }
            }
            return true;
        }
    );

    defineFunction(ns, 'isDayAvaliableByNow', function(date){
        var serverNow = ns.getServerNow();
        var serverToday = $.datepicker.formatDate('yy-mm-dd', serverNow);
        var selectedDay = $.datepicker.formatDate('yy-mm-dd', date);
        if(serverToday > selectedDay) {
            return false;
        }
        return true;
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
            }
        }
        if($(doc).find('#birs_errors').length > 0) {
            var errors = {};
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