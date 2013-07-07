jQuery(function($){
    var params = birs_calendar_params;
    var ajaxUrl = params.ajax_url;
    var addAppointmentTitle = params.add_appointment_title;
    var editAppointmentTitle = params.edit_appointment_title;
    var gmtOffset = params.gmt_offset;
    var locationStaffMap = params.location_staff_map;
    var fcTimeFormat = params.fc_time_format;
    var i18n = params.i18n;
    var staffOrder = params.staff_order;
    
    var showMessage = birchschedule.showMessage;
    function changeStaffOptions() {
        var locationId = $('#birs_calendar_location').val();
        var assignedStaff = locationStaffMap[locationId];
        var html = '';
        if(!assignedStaff){
            assignedStaff = {};
        }
        $.each(staffOrder, function(index, key){
            if(_(assignedStaff).has(key)) {
                var value = assignedStaff[key];
                html += '<option value="' + key + '">' + value + '</option>';                            
            }
        });
        $('#birs_calendar_staff').html(html);
    };
    function openEditDialog(title){
        var dialog = $('#birs_add_new_dialog');
        dialog.dialog('option', 'title', title);
        $('#save_appointment').click(function(){
            var postData = $('#birs_appointment_form').serialize();
            postData += '&' + $.param({
                action: 'birs_save_appointment'
            });
            $.post(ajaxUrl, postData, function(data, status, xhr){
                data = '<div>' + data + '</div>';
                var doc = $(data).find('#birs_response');
                if(doc.find('#birs_errors').length > 0){
                    $('label.error').hide();
                    $('div.birs_error').hide();
                    doc.find('#birs_errors p').each(function(idx, elt){
                        var tagId = $(elt).attr('id') + '_error';
                        $('#' + tagId).html($(elt).text());
                        $('#' + tagId).show();
                    });
                    var tabs = [ '#birs_client_details', '#birs_appointment_details', '#birs_appointment_info'];
                    var filter = function(){
                        return $(this).css('display') != 'none';
                    };
                    $.each(tabs, function(index, value){
                        if($(value + ' label.error').filter(filter).length > 0 || $(value + ' div.birs_error').filter(filter).length > 0) {
                            $('#birs_appointment_edit').tabs('select', value);
                        }
                    });
                } else {
                    dialog.dialog('close');
                    showMessage('#birs_calendar_status2', 'Appointment saved');
                    $('#birs_calendar').fullCalendar('refetchEvents');
                }
            }, 'html');
        });
        dialog.html('');
        dialog.dialog('open');
    };
    
    $('body').append('<div id="birs_calendar_status1" class="center"></div>');
    $('body').append('<div id="birs_calendar_status2" class="center"></div>');
    
    changeStaffOptions();
    $('#birs_calendar_location').change(function(){
        changeStaffOptions();
    });
    
    var fcOptions = $.extend(params.fc_i18n_options, {
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        defaultView: params.default_calendar_view,
        ignoreTimezone: true,
        gmtOffset: gmtOffset,
        weekMode: 'liquid',
        editable: true,
        disableDragging: true,
        disableResizing: true,
        selectable: false,
        allDaySlot: true,
        slotMinutes: 15,
        firstHour: 9,
        timeFormat: fcTimeFormat,
        axisFormat: fcTimeFormat,
        dayClick: function(date, allDay, jsEvent, view){
            if(view.name === 'month') {
                calendar.fullCalendar('changeView', 'agendaDay');
                calendar.fullCalendar('gotoDate', date);
            }
        },
        eventClick: function(calEvent, jsEvent, view){
            if(!calEvent.editable) {
                return;
            }
            var dialog = $('#birs_add_new_dialog');
            dialog.data('appointmentId', calEvent.id);
            openEditDialog(editAppointmentTitle);
        },
        events: function(start, end, callback){
            showMessage('#birs_calendar_status1', i18n.loading_appointments, {
                sticky: true
            });
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
                    $('#birs_calendar_status1').jGrowl('close');
                }
            });
        }
    });
    var calendar = $('#birs_calendar').fullCalendar(fcOptions);
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
            $('#birs_add_new_dialog').html("<p style='margin: 0 10px;'>" + 
                i18n.loading + "</p>");
            var queryData = {
                action: 'birs_render_edit_form',
                birs_appointment_location: $('#birs_calendar_location').val(),
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
                    $('#birs_add_new_dialog').triggerHandler('birchschedule.editFormReady');
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