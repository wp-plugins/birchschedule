jQuery(function($){
    var ajaxUrl = birs_params.ajax_url;
    var addAppointmentTitle = birs_params.add_appointment_title;
    var editAppointmentTitle = birs_params.edit_appointment_title;
    var serviceStaffMap = birs_params.service_staff_map;
    var servicePriceMap = birs_params.service_price_map;
    var gmtOffset = birs_params.gmt_offset;
    
    function openEditDialog(title){
        var dialog = $('#birs_add_new_dialog');
        dialog.dialog('option', 'title', title);
        $('#save_appointment').click(function(){
            var postData = $('#birs_appointment_form').serialize();
            var locationId = $('#birs_calendar_location').attr('value');
            postData += '&' + $.param({
                birs_appointment_location: locationId,
                action: 'birs_save_appointment'
            });
            $.post(ajaxUrl, postData, function(data, status, xhr){
                data = '<div>' + data + '</div>';
                var doc = $(data).find('#birs_response');
                if(doc.find('#birs_errors').length > 0){
                    $('tr[class=error]').hide();
                    doc.find('#birs_errors p').each(function(idx, elt){
                        var tagId = $(elt).attr('id') + '_error';
                        $('#' + tagId).html($(elt).text());
                        $('#' + tagId).parentsUntil('#birs_appointment_info', 'tr.error').show();
                    });
                } else {
                    dialog.dialog('close');
                    $('#birs_calendar').fullCalendar('refetchEvents');
                }
            }, 'html');
        });
        dialog.html('');
        dialog.dialog('open');
    };
    
    function changeStateUi(){
        birchschedule.changeStateUi('birs_client_country', 'birs_client_state', 'birs_client_province');
    };

    var calendar = $('#birs_calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        defaultView: 'agendaWeek',
        ignoreTimezone: true,
        gmtOffset: gmtOffset,
        weekMode: 'liquid',
        editable: true,
        disableDragging: true,
        disableResizing: true,
        selectable: false,
        allDaySlot: false,
        slotMinutes: 15,
        firstHour: 9,
        eventClick: function(calEvent, jsEvent, view){
            var dialog = $('#birs_add_new_dialog');
            dialog.data('appointmentId', calEvent.id);
            openEditDialog(editAppointmentTitle);
        },
        events: function(start, end, callback){
            $('#birs_calendar_status').show();
            var locationId = $('#birs_calendar_location').attr('value');
            var staffId = $('#birs_calendar_staff').attr('value');
            start = moment(start).format('YYYY-MM-DD HH:mm:ss');
            end = moment(end).format('YYYY-MM-DD HH:mm:ss');
            $.ajax({
                url: ajaxUrl,
                dataType: 'html',
                data: {
                    action: 'birs_query_appointments',
                    birs_time_start: start,
                    birs_time_end: end,
                    birs_location_id: locationId,
                    birs_staff_id: staffId
                },
                success: function(doc){
                    doc = '<div>' + doc + '</div>';
                    var events = $.parseJSON($(doc).find('#birs_response').text());
                    callback(events);
                    $('#birs_calendar_status').hide();
                }
            });
        }
    });
    $('#birs_calendar_location').change(function(){
        calendar.fullCalendar('refetchEvents');
    });
    $('#birs_calendar_staff').change(function(){
        calendar.fullCalendar('refetchEvents');
    });
    $('#birs_calendar_refresh').click(function(){
        calendar.fullCalendar('refetchEvents');
    });
    $('#birs_add_new_dialog').dialog({
        'autoOpen': false,
        'modal': true,
        'title': addAppointmentTitle,
        position: 'top',
        zIndex: 100000,
        width: 600,
        open: function(event, ui){
            $('#birs_add_new_dialog').html("<p style='margin: 0 10px;'>Loading...</p>");
            var queryData = {
                action: 'birs_render_edit_form',
                birs_appointment_staff: $('#birs_calendar_staff').val()
            };
            var dialog = $('#birs_add_new_dialog');
            var appointmentId = dialog.data('appointmentId');
            if(appointmentId){
                queryData.birs_appointment_id = appointmentId;
            }
            $.ajax({
                url: ajaxUrl,
                data: queryData,
                success: function(doc){
                    $('#birs_add_new_dialog').html(doc);
                    $('#birs_appointment_date').datepicker();
                    var setPrice = function(){
                        var serviceId = $('#birs_appointment_service').val();
                        $('#birs_appointment_price').val(servicePriceMap[serviceId]['price']);
                    };
                    $('#birs_appointment_service').change(function(){
                        setPrice();
                    });
                    changeStateUi();
                    $('#birs_client_country').change(changeStateUi);

                    $('#birs_appointment_edit').tabs();
                    if(!appointmentId){
                        setPrice();
                    }
                    $('#delete_appointment').click(function(){
                        var postData = {
                            action: 'birs_delete_appointment',
                            birs_appointment_id: $('#birs_appointment_id').val(),
                            _wpnonce: $('#birs_delete_appointment_nonce').val()
                        };
                        $.post(ajaxUrl, postData, function(data, status, xhr){
                            dialog.dialog('close');
                            $('#birs_calendar').fullCalendar('refetchEvents');
                        }, 'text');

                    });
                }
            });
        }
    });
    $('#birs_add_appointment').click(function(){
        var dialog = $('#birs_add_new_dialog');
        dialog.removeData('appointmentId');
        openEditDialog(addAppointmentTitle);
    });
});