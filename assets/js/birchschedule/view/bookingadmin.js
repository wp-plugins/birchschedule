(function($){
    var params = birchschedule_view_bookingadmin;
    var locationMap = params.location_map;
    var locationOrder = params.location_order;
    var locationStaffMap = params.location_staff_map;
    var staffOrder = params.staff_order;
    var locationServiceMap = params.location_service_map;
    var serviceStaffMap = params.service_staff_map;
    var serviceOrder = params.service_order;
    var servicePriceMap = params.service_price_map;
    var serviceDurationMap = params.service_duration_map;

    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchpress.view.bookingadmin');

    addAction('birchschedule.initAfter', function(){
        var datepickerI18nOptions = birchschedule.view.getDatepickerI18nOptions();

        $('#birs_add_new_dialog').bind('birchschedule.editFormReady', function(){
            var ajaxUrl = birchschedule.model.getAjaxUrl();

            var appointmentPriceOrig = $('#birs_appointment_price').val();
            var appointmentDurationOrig = $('#birs_appointment_duration').val();

            var switchStateProvince = function(){
                birchschedule.view.switchStateProvince('birs_client_country', 'birs_client_state');
            };
            birchschedule.view.initStateField('birs_client_state');
            var changeLocationOptions = function() {
                var html = '';
                $.each(locationOrder, function(index, key) {
                    if(_(locationMap).has(key)) {
                        html += '<option value="' + key + '">' + 
                            locationMap[key].post_title + '</option>';  
                    }
                });
                $('#birs_appointment_location').html(html);
            }
            var changeServiceOptions = function () {
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
            var changeStaffOptions = function() {
                var serviceId = $('#birs_appointment_service').val();
                var locationId = $('#birs_appointment_location').val();
                birchschedule.view.changeStaffOptions(serviceStaffMap[serviceId], locationStaffMap[locationId], 
                    staffOrder);
            }
            var showMessage = birchschedule.view.admincommon.showMessage;
            var setPrice = function(){
                var serviceId = $('#birs_appointment_service').val();
                if(serviceId) {
                    var price = servicePriceMap[serviceId]['price'];
                    if(price !== null || price !== undefined){
                        $('#birs_appointment_price').val(price);
                        $('#birs_appointment_price').trigger('change');
                    }
                }
            };
            var setDuration = function() {
                var serviceId = $('#birs_appointment_service').val();
                if(serviceId) {
                    var duration = serviceDurationMap[serviceId]['duration'];
                    if(duration !== null || duration !== undefined){
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
            };
            var setAppointmentValue = function() {
                var appointmentLocationId = Number($('#birs_appointment_location')
                    .attr('data-value'));
                var appointmentServiceId = Number($('#birs_appointment_service')
                    .attr('data-value'));
                var appointmentStaffId = Number($('#birs_appointment_staff')
                    .attr('data-value'));
                if(appointmentLocationId) {
                    $('#birs_appointment_location').val(appointmentLocationId).trigger('change');
                }
                if(appointmentServiceId) {
                    $('#birs_appointment_service').val(appointmentServiceId).trigger('change');
                }
                if(appointmentStaffId) {
                    $('#birs_appointment_staff').val(appointmentStaffId).trigger('change');
                }
            };

            changeLocationOptions();
            changeServiceOptions();
            changeStaffOptions();
            $('#birs_appointment_location').select2({
                'width': '80%'
            });
            $('#birs_appointment_service').select2({
                'width': '80%'
            });
            $('#birs_appointment_staff').select2({
                'width': '80%'
            });
            $('#birs_appointment_time').select2({
                'width': '80%'
            });
            var datepickerOptions = $.extend(datepickerI18nOptions, {
                'onSelect': function(dateText, instance) {
                    var date = datepicker.datepicker('getDate');
                    var dateValue = $.datepicker.formatDate('mm/dd/yy', date);
                    $('#birs_appointment_date').val(dateValue);
                }
            });
            var datepicker = $('#birs_appointment_datepicker').datepicker(datepickerOptions);
            $('#birs_appointment_location').change(function() {
                changeServiceOptions();
                changeStaffOptions();
                setPrice();
                setDuration();
            });
            $('#birs_appointment_service').change(function(){
                changeStaffOptions();
                setPrice();
                setDuration();
            });
            $('#birs_appointment_price').change(function(){
                updatePaymentInfo();
            });
            setAppointmentValue();
            $('#birs_client_country').change(switchStateProvince);

            $('#birs_appointment_edit').tabs({
                selected: 0
            });
            var appointmentId = $('#birs_appointment_id').val();
            if(appointmentId && appointmentId !== '0'){
                $('#birs_appointment_duration').val(appointmentDurationOrig);
                $('#birs_appointment_price').val(appointmentPriceOrig);
            } else {
                setDuration();
                setPrice();
            }
            $('#delete_appointment').click(function(){
                var postData = {
                    action: 'birchschedule_view_bookingadmin_delete_appointment',
                    birs_appointment_id: $('#birs_appointment_id').val(),
                    _wpnonce: $('#birs_delete_appointment_nonce').val()
                };
                $.post(ajaxUrl, postData, function(data, status, xhr){
                    var dialog = $('#birs_add_new_dialog');
                    dialog.dialog('close');
                    showMessage('#birs_calendar_status2' ,'Appointment deleted');
                    $('#birs_calendar').fullCalendar('refetchEvents');
                }, 'text');

            });
            $('#birs_add_payment').click(function(){
                if($('#birs_add_payment').hasClass('birs_disabled')) {
                    return;
                }
                var postData = $('#birs_appointment_form').serialize();
                postData += '&' + $.param({
                    action: 'birchschedule_view_payments_add_new_payment'
                });
                $.post(ajaxUrl, postData, function(data, status, xhr){
                    $(data).prependTo('#birs_payments_table tbody');
                    $('#birs_payments_table').
                        triggerHandler('birchschedule.addNewAppointmentPaymentReady');
                    $('#birs_add_payment').removeClass('birs_disabled');
                }, 'html');
                $('#birs_add_payment').addClass('birs_disabled');
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
})(jQuery);
