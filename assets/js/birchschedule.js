jQuery(function($){
    var params = birs_params;
    var ajaxUrl = params.ajax_url;
    var allSchedule = params.all_schedule;
    var servicePriceMap = params.service_price_map;
    var serverGmtOffset = params.gmt_offset;
    var allDayoffs = params.all_dayoffs;
    
    function getServerNow() {
        return birchschedule.getServerNow(serverGmtOffset);
    }
    
    function changeStaffOptions() {
        var serviceId = $('#birs_appointment_service').val();
        var locationId = $('#birs_appointment_location').val();
        var serviceStaffMap = params.service_staff_map[serviceId];
        var locationStaffMap = params.location_staff_map[locationId];
        birchschedule.changeStaffOptions(serviceStaffMap, locationStaffMap);
    }

    function getTimeOptions(){
        $('#birs_appointment_timeoptions').html('');
        $('#birs_appointment_time').val('');
        var postData = $('#birs_appointment_form').serialize();
        postData += '&' + $.param({
            action: 'birs_get_avaliable_time'
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
            $('#birs_appointment_timeoptions').html(data);
            $('#birs_appointment_timeoptions a').click(function() {
                $('#birs_appointment_time').val($(this).attr('data-time'));
                $('#birs_appointment_timeoptions a').removeClass('selected');
                $(this).addClass('selected');
            });
        }, 'html');
    }
    function changeAppointmentPrice() {
        var serviceId = $('#birs_appointment_service').val();
        $('#birs_appointment_price').val(servicePriceMap[serviceId].price);
    }
    function isDayAvaliableByBookingPreferences(date, futureTime, cutOffTime) {
        var serverNow = getServerNow();
        var timeOfServer = serverNow.getTime();
        var timeOfSelect = date.getTime();
        var hoursBetween = (timeOfSelect - timeOfServer) / (1000 * 60 * 60) + 24; 
        var daysBetween = hoursBetween / 24;
        if(daysBetween > futureTime || hoursBetween < cutOffTime) {
            return false;
        }
        return true;
    }
    function isDayAvaliableBySchedules(date, day, locationId, staffId) {
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
    function isDayAvaliableByDaysOff(date, staffId) {
        if(_.has(allDayoffs, staffId)) {
            var dayoffsJson = allDayoffs[staffId];    
            var dayoffs = $.parseJSON(dayoffsJson);
            var selectedDay = $.datepicker.formatDate('mm/dd/yy', date);
            if(dayoffs && _.contains(dayoffs, selectedDay)) {
                return false;
            }
        }
        return true;
    }
    function showDatepicker() {
        var options = $.extend(params.datepicker_i18n_options, {
            beforeShowDay: function(date){
                var serverNow = getServerNow();
                var serverToday = $.datepicker.formatDate('yy-mm-dd', serverNow);
                var selectedDay = $.datepicker.formatDate('yy-mm-dd', date);
                if(serverToday > selectedDay) {
                    return [false, ""];
                }
                var day = date.getDay();
                var locationId = $('#birs_appointment_location').val();
                var staffId = $('#birs_appointment_staff').val();
                if(!isDayAvaliableByDaysOff(date, staffId)) {
                    return [false, ""];
                }
                if(!isDayAvaliableBySchedules(date, day, locationId, staffId)) {
                    return [false, ""];
                }
                if(!isDayAvaliableByBookingPreferences(date, params.future_time,
                    params.cut_off_time)) {
                    return [false, ""];
                }
                return [true, ""];
            },
            onSelect: function(dateText) {
                $('#birs_appointment_date').val(dateText);
                getTimeOptions();
            }
        });
        $('#birs_appointment_datepicker').datepicker(options);
    }
    function refreshDatetime() {
        $('#birs_appointment_datepicker').datepicker('destroy');
        showDatepicker();
        $('#birs_appointment_timeoptions').html('');
        $('#birs_appointment_time').val('');
        $('#birs_appointment_date').val('');
    }
    
    //start execute functions
    $('#birs_appointment_location').select2({
        width: '50%'
    });
    $('#birs_appointment_service').select2({
        width: '50%'
    });
    $('#birs_appointment_staff').select2({
        width: '50%'
    });
    changeStaffOptions();    
    changeAppointmentPrice();
    showDatepicker();
    $('#birs_appointment_datepicker').datepicker("setDate", getServerNow());
    $('#birs_appointment_service').on('change', function(){
        changeStaffOptions();
        changeAppointmentPrice();
        refreshDatetime();
    });
    $('#birs_appointment_location').on('change', function(){
        changeStaffOptions();
        refreshDatetime();
    });
    $('#birs_appointment_staff').on('change', function(){
        refreshDatetime();
    });
    $('#birs_book_appointment').click(function(){
        var postData = $('#birs_appointment_form').serialize();
        postData += '&' + $.param({
            action: 'birs_save_appointment_frontend'
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
            $('#birs_please_wait').hide();
            data = '<div>' + data + '</div>';
            var doc = $(data).find('#birs_response');
            if(doc.find('#birs_success').length > 0){
                if(doc.find('#birs_success_text').length > 0) {
                    $('.birs_error').hide();
                    $('#birs_booking_box').hide();
                    $('#birs_booking_success').html(doc.find('#birs_success_text').html());
                    $('#birs_booking_success').show();
                    window.location.hash = '#birs_booking_success';
                    return;
                }
                if(doc.find('#birs_success_redirect').length > 0) {
                    window.location = doc.find('#birs_success_redirect').html();
                    return;
                }
            } else {
                $('.birs_error').hide();
                doc.find('#birs_errors p').each(function(idx, elt){
                    var tagId = $(elt).attr('id') + '_error';
                    $('#' + tagId).html($(elt).text());
                    $('#' + tagId).show();
                });
                return;
            }
        }, 'text');
        $('.birs_error').hide();
        $('#birs_please_wait').show();
    });

});