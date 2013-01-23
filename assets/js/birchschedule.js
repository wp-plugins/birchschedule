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
    function showDatepicker() {
        $('#birs_appointment_datepicker').datepicker({
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
                if(_.has(allDayoffs, staffId)) {
                    var dayoffsJson = allDayoffs[staffId];    
                    var dayoffs = $.parseJSON(dayoffsJson);
                    var selectedDay = $.datepicker.formatDate('mm/dd/yy', date);
                    if(dayoffs && _.contains(dayoffs, selectedDay)) {
                        return [false, ""];
                    }
                }
                if(_.has(allSchedule, staffId) && _.has(allSchedule[staffId], locationId)){
                    var schedule = allSchedule[staffId][locationId];
                
                    return [_.has(schedule, day), ""]
                } else {
                    return [false, ""];
                }
            },
            onSelect: function(dateText) {
                $('#birs_appointment_date').val(dateText);
                getTimeOptions();
            }
        });
    }
    function refreshDatetime() {
        $('#birs_appointment_datepicker').datepicker('destroy');
        showDatepicker();
        $('#birs_appointment_timeoptions').html('');
        $('#birs_appointment_time').val('');
        $('#birs_appointment_date').val('');
    }
    changeStaffOptions();    
    changeAppointmentPrice();
    showDatepicker();
    $('#birs_appointment_datepicker').datepicker("setDate", getServerNow());
    $('#birs_appointment_service').change(function(){
        changeStaffOptions();
        changeAppointmentPrice();
        refreshDatetime();
    });
    $('#birs_appointment_location').change(function(){
        changeStaffOptions();
        refreshDatetime();
    });
    $('#birs_appointment_staff').change(function(){
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