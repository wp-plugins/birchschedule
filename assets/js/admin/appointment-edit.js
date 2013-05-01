jQuery(function($){
    var params = birs_appointment_edit_params;
    $('#birs_add_new_dialog').bind('birchschedule.editFormReady', function(){
        var changeStateUi = function(){
            birchschedule.changeStateUi('birs_client_country', 'birs_client_state', 'birs_client_province');
        };
        var changeStaffOptions = function() {
            var serviceId = $('#birs_appointment_service').val();
            var locationId = $('#birs_calendar_location').val();
            var serviceStaffMap = params.service_staff_map[serviceId];
            var locationStaffMap = params.location_staff_map[locationId];
            birchschedule.changeStaffOptions(serviceStaffMap, locationStaffMap);
        }
        var showMessage = birchschedule.showMessage;
        var setPrice = function(){
            var serviceId = $('#birs_appointment_service').val();
            if(serviceId) {
                var servicePriceMap = params.service_price_map;
                var price = servicePriceMap[serviceId]['price'];
                if(price){
                    $('#birs_appointment_price').val(price);
                }
            }
        };
        var setDuration = function() {
            var serviceId = $('#birs_appointment_service').val();
            if(serviceId) {
                var serviceDurationMap = params.service_duration_map;
                var duration = serviceDurationMap[serviceId]['duration'];
                if(duration){
                    $('#birs_appointment_duration').val(duration);
                }
            }
        };
        var updatePaymentInfo = function() {
            var price = parseFloat($('#birs_appointment_price').val());
            if(isNaN(price)) {
                price = 0;
            }
            var paid = 0;
            $('#birs_payments_table tbody tr').each(function(){
                var amount = parseFloat($(this).attr('data-payment-amount'));
                if(isNaN(amount)) {
                    amount = 0;
                }
                paid += amount;
            });
            price = price.toFixed(2);
            paid = paid.toFixed(2);
            var due = (price - paid).toFixed(2);
            $('#birs_appointment_paid').html(paid);
            $('#birs_appointment_due').html(due);
            if(due > 0) {
                $('#birs_appointment_amount_to_pay').val(due);
            } else {
                $('#birs_appointment_amount_to_pay').val(0);
            }
        }

        $('#birs_appointment_service').select2({
            'width': '80%'
        });
        $('#birs_appointment_staff').select2({
            'width': '80%'
        });
        $('#birs_appointment_time').select2({
            'width': '80%'
        });

        $('#birs_appointment_service').change(changeStaffOptions);

        var datepickerOptions = $.extend(params.datepicker_i18n_options, {
            'dateFormat': params.jquery_date_format,
            'onSelect': function(dateText, instance) {
                var date = datepicker.datepicker('getDate');
                var dateValue = $.datepicker.formatDate('mm/dd/yy', date);
                $('#birs_appointment_date').val(dateValue);
            }
        });
        var datepicker = $('#birs_appointment_datepicker').datepicker(datepickerOptions);
        $('#birs_appointment_service').change(function(){
            setPrice();
            setDuration();
        });
        changeStateUi();
        $('#birs_client_country').change(changeStateUi);

        $('#birs_appointment_edit').tabs({
            selected: 0
        });
        var appointmentId = $('#birs_appointment_id').val();
        if(!appointmentId || appointmentId == 0){
            setPrice();
            setDuration();
        }
        $('#delete_appointment').click(function(){
            var postData = {
                action: 'birs_delete_appointment',
                birs_appointment_id: $('#birs_appointment_id').val(),
                _wpnonce: $('#birs_delete_appointment_nonce').val()
            };
            var ajaxUrl = params.ajax_url;
            $.post(ajaxUrl, postData, function(data, status, xhr){
                var dialog = $('#birs_add_new_dialog');
                dialog.dialog('close');
                showMessage('#birs_calendar_status2' ,'Appointment deleted');
                $('#birs_calendar').fullCalendar('refetchEvents');
            }, 'text');

        });
        $('#birs_add_payment').click(function(){
            var postData = $('#birs_appointment_form').serialize();
            postData += '&' + $.param({
                action: 'birs_add_new_appointment_payment'
            });
            var ajaxUrl = params.ajax_url;
            $.post(ajaxUrl, postData, function(data, status, xhr){
                $(data).prependTo('#birs_payments_table tbody');
                $('#birs_payments_table').
                    triggerHandler('birchschedule.addNewAppointmentPaymentReady');
            }, 'html');
        });
        $('#birs_appointment_price').keyup(function(){
            updatePaymentInfo();
        });
        $('#birs_payments_table').bind('birchschedule.addNewAppointmentPaymentReady', function(){
            $('#birs_payments_table tbody tr .row-actions .delete a').click(function(){
                var paymentTRID = $(this).attr('data-payment-trid');
                $('#birs_payments_table tbody tr[data-payment-trid="' +
                    paymentTRID + '"]').remove();
                updatePaymentInfo();
            });
            updatePaymentInfo();
        });
        updatePaymentInfo();
    });
});
