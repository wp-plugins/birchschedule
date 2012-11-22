jQuery(function($){
    var params = birs_params;
    var ajaxUrl = params.ajax_url;
    var allSchedule = params.all_schedule;
    var servicePriceMap = params.service_price_map;
    
    function changeStaffOptions() {
        var serviceId = $('#birs_appointment_service').val();
        var locationId = $('#birs_appointment_location').val();
        var serviceStaffMap = params.service_staff_map[serviceId];
        var locationStaffMap = params.location_staff_map[locationId];
        birchschedule.changeStaffOptions(serviceStaffMap, locationStaffMap);
    }

    function getTimeOptions(){
        $('#birs_appointment_time').html('');
        var postData = $('#birs_appointment_form').serialize();
        postData += '&' + $.param({
            action: 'birs_get_avaliable_time'
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
            $('#birs_appointment_time').html(data);    
        }, 'html');
    }
    function changeAppointmentPrice() {
        var serviceId = $('#birs_appointment_service').val();
        $('#birs_appointment_price').val(servicePriceMap[serviceId].price);
    }
    
    $('#birs_appointment_date').datepicker({
        beforeShowDay: function(date){
            var day = date.getDay();
            var locationId = $('#birs_appointment_location').val();
            var staffId = $('#birs_appointment_staff').val();
            if(_.has(allSchedule, staffId) && _.has(allSchedule[staffId], locationId)){
                var schedule = allSchedule[staffId][locationId];
            
                return [_.has(schedule, day), ""]
            } else {
                return [false, ""];
            }
        }
    });
    changeStaffOptions();    
    changeAppointmentPrice();
    $('#birs_appointment_service').change(function(){
        changeStaffOptions();
        changeAppointmentPrice();
    });
    $('#birs_appointment_location').change(function(){
        changeStaffOptions();
    });
    $('#birs_appointment_staff').change(function(){
        $('#birs_appointment_date').val('');
    });
    $('#birs_appointment_location').change(function(){
        $('#birs_appointment_date').val('');
    });
    $('#birs_appointment_service').change(function(){
        $('#birs_appointment_date').val('');
    });
    $('#birs_appointment_date').change(function(){
        getTimeOptions();
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
                $('.birchschedule p[class=error]').hide();
                $('#birs_booking_box').hide();
                $('#birs_booking_success').html(doc.find('#birs_success').html());
                $('#birs_booking_success').show();
                return;
            } else {
                $('.birchschedule p[class=error]').hide();
                doc.find('#birs_errors p').each(function(idx, elt){
                    var tagId = $(elt).attr('id') + '_error';
                    $('#' + tagId).html($(elt).text());
                    $('#' + tagId).show();
                });
                return;
            }
        }, 'text');
    });

});