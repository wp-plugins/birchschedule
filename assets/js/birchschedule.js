jQuery(function($){
    var params = birs_params;
    var scAttrs = birs_params_shortcode_attrs;
    var ajaxUrl = params.ajax_url;
    var allSchedule = params.all_schedule;
    var servicePriceMap = params.service_price_map;
    var serverGmtOffset = params.gmt_offset;
    var allDayoffs = params.all_dayoffs;
    var locationMap = params.location_map;
    var locationServiceMap = params.location_service_map;
    var locationStaffMap = params.location_staff_map;
    var serviceStaffMap = params.service_staff_map;
    var locationOrder = params.location_order; 
    var serviceOrder = params.service_order;
    var staffOrder = params.staff_order;
    var serviceDurationMap = params.service_duration_map;
    var datepickerI18nOptions = params.datepicker_i18n_options;
    var futureTime = params.future_time;
    var cutOffTime = params.cut_off_time;

    function getServerNow() {
        return birchschedule.getServerNow(serverGmtOffset);
    }

    function changeLocationOptions() {
        var html = '';
        $.each(locationOrder, function(index, key) {
            if(_(locationMap).has(key)) {
                html += '<option value="' + key + '">' + 
                    locationMap[key].post_title + '</option>';  
            }
        });
        $('#birs_appointment_location').html(html);
    }
    
    function changeServiceOptions() {
        var serviceId = $('#birs_appointment_service').val();
        var locationId = $('#birs_appointment_location').val();
        var avaliableServices = locationServiceMap[locationId];
        $('#birs_appointment_service').empty();
        $.each(serviceOrder, function(index, key) {
            if(_(avaliableServices).has(key)) {
                var value = avaliableServices[key];
                $('#birs_appointment_service').
                    append($("<option></option>").attr("value", key).text(value));
            }
        });
        if(serviceId && _(avaliableServices).has(serviceId)) {
            $('#birs_appointment_service').val(serviceId);
        }
        $('#birs_appointment_service').trigger('change');
    }
    
    function changeStaffOptions() {
        var serviceId = $('#birs_appointment_service').val();
        var locationId = $('#birs_appointment_location').val();
        var serviceStaffMap_ = serviceStaffMap[serviceId];
        var locationStaffMap_ = locationStaffMap[locationId];
        birchschedule.changeStaffOptions(serviceStaffMap_, locationStaffMap_, 
            staffOrder);
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
        if(serviceId) {
            $('#birs_appointment_price').val(servicePriceMap[serviceId].price);
        }
    }
    function changeAppointmentDuration() {
        var serviceId = $('#birs_appointment_service').val();
        if(serviceId) {
            $('#birs_appointment_duration').val(serviceDurationMap[serviceId].duration);
        }
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
        var options = $.extend(datepickerI18nOptions, {
            changeMonth: false,
            changeYear: false,
            'dateFormat': 'mm/dd/yy',
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
                if(!isDayAvaliableByBookingPreferences(date, futureTime,
                    cutOffTime)) {
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
    function scrollTo(selector, duration) {
        if(!duration) {
            duration = 600;
        }
        $('html, body').animate({
             scrollTop: $(selector).offset().top
        }, duration);
    }
    
    //start execute functions
    if(scAttrs['location_ids']) {
        locationOrder = _.intersection(locationOrder, scAttrs['location_ids']);
    }
    if(scAttrs['service_ids']) {
        serviceOrder = _.intersection(serviceOrder, scAttrs['service_ids']);
    }
    if(scAttrs['staff_ids']) {
        staffOrder = _.intersection(staffOrder, scAttrs['staff_ids']);
    }
    changeLocationOptions();
    changeServiceOptions();
    changeStaffOptions();    
    changeAppointmentPrice();
    changeAppointmentDuration();
    if(!birchschedule.isMobile()) {
        var serviceWidth = "100%";
        $('#birs_appointment_form select').select2({
            width: serviceWidth
        });
    }
    showDatepicker();
    $('#birs_appointment_datepicker').datepicker("setDate", getServerNow());
    $('#birs_appointment_location').on('change', function(){
        changeServiceOptions();
        changeStaffOptions();
        changeAppointmentPrice();
        changeAppointmentDuration();
    });
    $('#birs_appointment_service').on('change', function(){
        changeStaffOptions();
        changeAppointmentPrice();
        changeAppointmentDuration();
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
            data = '<div>' + data + '</div>';
            var doc = $(data).find('#birs_response');
            if(doc.find('#birs_success').length > 0){
                $('#birs_please_wait').hide("slow");
                if(doc.find('#birs_success_text').length > 0) {
                    $('.birs_error').hide("");
                    $('#birs_booking_box').hide();
                    $('#birs_booking_success').html(doc.find('#birs_success_text').html());
                    $('#birs_booking_success').show("slow", function() {
                        scrollTo("#birs_booking_success", 10);
                    });
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
                    $('#' + tagId).show("slow");
                });
                $('#birs_please_wait').hide("slow", function() {
                    scrollTo($(".birs_error:visible:first").
                        parentsUntil("#birs_appointment_form",
                            ".birs_form_field"));
                });
                return;
            }
        }, 'text');
        $('.birs_error').hide("slow");
        $('#birs_please_wait').show("slow");
    });

});