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

        $('#birs_appointment_service').change(changeStaffOptions);

        $('#birs_appointment_date').datepicker();
        $('#birs_appointment_service').change(function(){
            setPrice();
        });
        changeStateUi();
        $('#birs_client_country').change(changeStateUi);

        $('#birs_appointment_edit').tabs({
            selected: 0
        });
        var appointmentId = $('#birs_appointment_id').val();
        if(!appointmentId){
            setPrice();
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
    });
});
