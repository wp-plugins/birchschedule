(function($){
    var params = birchschedule_view_appointments_edit;
    var locationsMap = params.locations_map;
    var servicesMap = params.services_map;
    var locationsOrder = params.locations_order;
    var locationsStaffMap = params.locations_staff_map;
    var staffOrder = params.staff_order;
    var locationsServicesMap = params.locations_services_map;
    var servicesStaffMap = params.services_staff_map;
    var servicesOrder = params.services_order;

    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.appointments.edit');

    defineFunction(ns, 'setStaffOptions', function(){
        var locationId = $('#birs_appointment_location').val();
        var serviceId = $('#birs_appointment_service').val();
        var options = birchschedule.model.getStaffOptions(locationsStaffMap, servicesStaffMap, 
            locationId, serviceId, staffOrder);
        var html = birchschedule.view.getOptionsHtml(options);

        var staffId = $('#birs_appointment_staff').val();
        $('#birs_appointment_staff').html(html);

        if(staffId && _(options.order).has(staffId)) {
            $('#birs_appointment_staff').val(staffId);
        }
    });

    defineFunction(ns, 'setStaffValue', function(){
        var appointmentStaffId = Number($('#birs_appointment_staff')
            .attr('data-value'));
        if(appointmentStaffId) {
            $('#birs_appointment_staff').val(appointmentStaffId);
        }    
    });

    defineFunction(ns, 'ifOnlyShowAvailable', function() {
        return false;
    });

    defineFunction(ns, 'reloadTimeOptions', function(){

    });

    defineFunction(ns, 'initDatepicker', function(){
        var config = {
            ifOnlyShowAvailable: ns.ifOnlyShowAvailable
        };
        return birchschedule.view.initDatepicker(config);
    });

    defineFunction(ns, 'reschedule', function() {
        var ajaxUrl = birchschedule.model.getAjaxUrl();
        var i18nMessages = birchschedule.view.getI18nMessages();
        var postData = $('form').serialize();
        postData += '&' + $.param({
            action: 'birchschedule_view_appointments_edit_reschedule'
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
            var result = birchschedule.model.parseAjaxResponse(data);
            if(result.errors) {
                birchschedule.view.showFormErrors(result.errors);
            } 
            else if(result.success) {
                var url = $.parseJSON(result.success.message).url;
                window.location = _.unescape(url);
            }
            $('#birs_appointment_actions_reschedule').val(i18nMessages['Reschedule']);
            $('#birs_appointment_actions_reschedule').prop('disabled', false);
        });
        $('#birs_appointment_actions_reschedule').val(i18nMessages['Please wait...']);
        $('#birs_appointment_actions_reschedule').prop('disabled', true);
    });

    defineFunction(ns, 'cancel', function() {
        var i18nMessages = birchschedule.view.getI18nMessages();
        var r = window.confirm(i18nMessages['Are you sure you want to cancel this appointment?']);
        if(r == true) {
            var ajaxUrl = birchschedule.model.getAjaxUrl();
            var appointmentId = $('#birs_appointment_id').val();
            var postData = $.param({
                action: 'birchschedule_view_appointments_edit_cancel',
                birs_appointment_id: appointmentId
            });
            $.post(ajaxUrl, postData, function(data, status, xhr){
                var result = birchschedule.model.parseAjaxResponse(data);
                if(result.success) {
                    var url = $.parseJSON(result.success.message).url;
                    window.location = _.unescape(url);
                }
            });
        }
    });


    defineFunction(ns, 'init', function(){
        var ajaxUrl = birchschedule.model.getAjaxUrl();

        ns.setStaffOptions();
        ns.setStaffValue();
        var datepicker = ns.initDatepicker();
        defineFunction(ns, 'refreshDatepicker', function(){
            birchschedule.view.refreshDatepicker(datepicker);
        });

        ns.reloadTimeOptions();

        $('#birs_appointment_staff').change(function(){
            ns.refreshDatepicker();
            ns.reloadTimeOptions();
        });

        $('#birs_appointment_date').on('change', function(){
            ns.reloadTimeOptions();
        });

        $('#birs_appointment_actions_reschedule').click(function() {
            ns.reschedule();
        });
        $('#birs_appointment_actions_cancel').click(function() {
            ns.cancel();
        })
    });
    addAction('birchschedule.initAfter', ns.init);
})(jQuery);