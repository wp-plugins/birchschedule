(function($){
    var params = birchschedule_view_calendar;
    var addAppointmentTitle = params.add_appointment_title;
    var editAppointmentTitle = params.edit_appointment_title;
    var locationMap = params.location_map;
    var locationStaffMap = params.location_staff_map;
    var staffOrder = params.staff_order;
    var locationOrder = params.location_order;
    var defaultView = params.default_calendar_view;

    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.calendar');

    addAction('birchschedule.initAfter', function(){

        var ajaxUrl = birchschedule.model.getAjaxUrl();
        var gmtOffset = birchschedule.model.getServerGmtOffset();
        var showMessage = birchschedule.view.admincommon.showMessage;
        var hideMessage = birchschedule.view.admincommon.hideMessage;

        var fcI18nOptions = birchschedule.view.getFullcalendarI18nOptions();
        var i18n = birchschedule.view.admincommon.getI18nMessages();

        function changeLocationOptions() {
            var html = '';
            $.each(locationOrder, function(index, key) {
                if(_(locationMap).has(key)) {
                    html += '<option value="' + key + '">' + 
                        locationMap[key].post_title + '</option>';  
                }
            });
            $('#birs_calendar_location').html(html);
        }
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
            $selected_staff = $('#birs_calendar_staff').val();
            $('#birs_calendar_staff').html(html);
            if($selected_staff && _(assignedStaff).has($selected_staff)) {
                $('#birs_calendar_staff').val($selected_staff);
            }
        };
        function openEditDialog(title){
            var dialog = $('#birs_add_new_dialog');
            dialog.on('dialogopen', function(ent, ui) {
                $(this).parent().find('.ui-dialog-title').html(title);
                $('#save_appointment').click(function(){
                    if($('#save_appointment').hasClass('birs_disabled')) {
                        return;
                    }
                    var postData = $('#birs_appointment_form').serialize();
                    postData += '&' + $.param({
                        action: 'birchschedule_view_bookingadmin_save_appointment'
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
                            var tabs = [ '#birs_appointment_payments_details', '#birs_client_details', 
                                         '#birs_appointment_details', '#birs_appointment_info'];
                            var filter = function(){
                                return $(this).css('display') != 'none';
                            };
                            $.each(tabs, function(index, value){
                                if($(value + ' label.error').filter(filter).length > 0 || 
                                    $(value + ' div.birs_error').filter(filter).length > 0) {
                                    var tabIndex = tabs.length - index - 1;
                                    $('#birs_appointment_edit').tabs('option', 'active', tabIndex);
                                }
                            });
                        } else {
                            dialog.dialog('close');
                            showMessage('#birs_calendar_status2', 'Appointment saved');
                            $('#birs_calendar').fullCalendar('refetchEvents');
                        }
                        $('#save_appointment').removeClass('birs_disabled');
                        $('#save_appointment').val(i18n['Save']);
                    }, 'html');
                    $('#save_appointment').addClass('birs_disabled');
                    $('#save_appointment').val(i18n['Saving...']);
                });
            });
            dialog.html('');
            dialog.dialog('open');
        };
        
        $('body').append('<div id="birs_calendar_status1" class="center"></div>');
        $('body').append('<div id="birs_calendar_status2" class="center"></div>');
        
        $('#birs_calendar_location').change(function(){
            changeStaffOptions();
        });
        changeLocationOptions();
        changeStaffOptions();

        var fcOptions = $.extend(fcI18nOptions, {
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            defaultView: defaultView,
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
                var locationId = $('#birs_calendar_location').attr('value');
                var staffId = $('#birs_calendar_staff').attr('value');
                start = moment(start).format('YYYY-MM-DD HH:mm:ss');
                end = moment(end).format('YYYY-MM-DD HH:mm:ss');
                var handleEvents = function(doc, callback) {
                    doc = '<div>' + doc + '</div>';
                    var events = $.parseJSON($(doc).find('#birs_response').text());
                    callback(events);
                }
                $.ajax({
                    url: ajaxUrl,
                    dataType: 'html',
                    data: {
                        action: 'birchschedule_view_calendar_query_appointments',
                        birs_time_start: start,
                        birs_time_end: end,
                        birs_location_id: locationId,
                        birs_staff_id: staffId
                    },
                    success: function(doc){
                        hideMessage('#birs_calendar_status1');
                        handleEvents(doc, callback);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        hideMessage('#birs_calendar_status1');
                        if(jqXHR.status == 500) {
                            var doc = jqXHR.responseText;
                            handleEvents(doc, callback);
                        }
                    }
                });
                showMessage('#birs_calendar_status1', i18n['Loading appointments...'], {
                    sticky: true
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
                    i18n['Loading...'] + "</p>");
                var queryData = {
                    action: 'birchschedule_view_bookingadmin_render_edit_form',
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

})(jQuery);