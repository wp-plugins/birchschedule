(function($){
    var params = birchschedule_view_bookingform;
    var scAttrs = birchschedule_view_bookingform_sc_attrs;

    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.bookingform');

    defineFunction(ns, 'getServicesPricesMap', function() {
        return params.services_prices_map;
    });

    defineFunction(ns, 'getLocationsMap', function(){
        return params.locations_map;
    });

    defineFunction(ns, 'getLocationsServicesMap', function(){
        return params.locations_services_map;
    });

    defineFunction(ns, 'getLocationsStaffMap', function(){
        return params.locations_staff_map;
    });

    defineFunction(ns, 'getServicesStaffMap', function(){
        return params.services_staff_map;
    });
    
    defineFunction(ns, 'getLocationsOrder', function(){
        var locationIds = params.locations_order;
        if(scAttrs['location_ids']) {
            locationIds = _.intersection(locationIds, scAttrs['location_ids']);
        }
        return locationIds;
    });

    defineFunction(ns, 'getServicesOrder', function(){
        var serviceIds = params.services_order;
        if(scAttrs['service_ids']) {
            serviceIds = _.intersection(serviceIds, scAttrs['service_ids']);
        }
        return serviceIds;
    });

    defineFunction(ns, 'getStaffOrder', function(){
        var staffIds = params.staff_order;
        if(scAttrs['staff_ids']) {
            staffIds = _.intersection(staffIds, scAttrs['staff_ids']);
        }
        return staffIds;
    });

    defineFunction(ns, 'getServicesDurationMap', function(){
        return params.services_duration_map;
    });

    defineFunction(ns, 'setLocationOptions', function () {
        var locationsOrder = ns.getLocationsOrder();
        var locationsMap = ns.getLocationsMap();

        var options = birchschedule.model.getLocationOptions(locationsMap, locationsOrder);
        var html = birchschedule.view.getOptionsHtml(options);
        $('#birs_appointment_location').html(html);
    });
    
    defineFunction(ns, 'setServiceOptions', function() {
        var locationsServicesMap = ns.getLocationsServicesMap();
        var servicesOrder = ns.getServicesOrder();
        var locationId = $('#birs_appointment_location').val();

        var options = birchschedule.model.getServiceOptions(locationsServicesMap, 
            locationId, servicesOrder);
        var html = birchschedule.view.getOptionsHtml(options);

        var serviceId = parseInt($('#birs_appointment_service').val());
        $('#birs_appointment_service').html(html);

        if(serviceId && _(options.order).contains(serviceId)) {
            $('#birs_appointment_service').val(serviceId);
        }
    });
    
    defineFunction(ns, 'setStaffOptions', function() {
        var locationId = $('#birs_appointment_location').val();
        var serviceId = $('#birs_appointment_service').val();
        var locationsStaffMap = ns.getLocationsStaffMap();
        var servicesStaffMap = ns.getServicesStaffMap();
        var staffOrder = ns.getStaffOrder();

        var options = birchschedule.model.getStaffOptions(locationsStaffMap, servicesStaffMap, 
            locationId, serviceId, staffOrder);
        var html = birchschedule.view.getOptionsHtml(options);

        var staffId = parseInt($('#birs_appointment_staff').val());
        $('#birs_appointment_staff').html(html);
        var avaliableStaff = options.order.join();
        $('#birs_appointment_avaliable_staff').val(avaliableStaff);

        if(staffId && _(options.order).contains(staffId)) {
            $('#birs_appointment_staff').val(staffId);
        }
    });

    defineFunction(ns, 'getFormQueryData', function(){
        var postData = $('#birs_appointment_form').serialize();
        return postData;
    });

    defineFunction(ns, 'selectTime', function(el) {
        $('#birs_appointment_time').val($(el).attr('data-time'));

        var alternativeStaff = $(el).attr('data-alternative-staff');
        $('#birs_appointment_alternative_staff').val(alternativeStaff);

        $('#birs_appointment_timeoptions .birs_option').removeClass('selected');
        $(el).addClass('selected');
    });

    defineFunction(ns, 'reloadTimeOptions', function(){
        var dateValue = $('#birs_appointment_date').val();
        if(!dateValue) {
            return;
        }

        var i18n = birchschedule.view.getI18nMessages();
        var pluginUrl = birchschedule.view.getPluginUrl();
        var waitImgUrl = pluginUrl + '/assets/images/ajax-loader.gif';
        var waiting_html = "<div id='birs_appointment_timeoptions'>" + 
            i18n['Please wait...']  + 
            "<img src=" + waitImgUrl + " />"
            "</div>";
        $('.birs_form_field.birs_appointment_time .birs_field_content').html(waiting_html);
        $('#birs_appointment_alternative_staff').val('');
        $('#birs_appointment_time').val('');

        var ajaxUrl = birchschedule.model.getAjaxUrl();
        var postData = ns.getFormQueryData();
        postData += '&' + $.param({
            action: 'birchschedule_view_bookingform_get_avaliable_time'
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
            $('.birs_form_field.birs_appointment_time .birs_field_content').html(data);
            $('#birs_appointment_timeoptions .birs_option').click(function(e) {
                ns.selectTime(e.target);
            });
            $('#birs_appointment_timeoptions').change(function(){
                ns.selectTime($('#birs_appointment_timeoptions option:selected'));
            });
        }, 'html');
    });

    defineFunction(ns, 'setAppointmentPrice', function(){
        var serviceId = $('#birs_appointment_service').val();
        if(serviceId) {
            $('#birs_appointment_price').val(ns.getServicesPricesMap()[serviceId].price);
        }
    });

    defineFunction(ns, 'setAppointmentDuration', function(){
        var serviceId = $('#birs_appointment_service').val();
        if(serviceId) {
            $('#birs_appointment_duration').val(ns.getServicesDurationMap()[serviceId].duration);
        }
    });

    defineFunction(ns, 'initDatepicker', function(){
        var gotoDate = birchschedule.model.getNow4Locale();
        if(scAttrs['date']) {
            gotoDate = $.datepicker.parseDate('mm/dd/yy', scAttrs['date']);
        }
        return birchschedule.view.initDatepicker({
            gotoDate: gotoDate
        });
    });

    defineFunction(ns, 'refreshDatepicker', function(datepicker){
        birchschedule.view.refreshDatepicker(datepicker);
    });

    defineFunction(ns, 'bookSucceed', function(message) {
        var fns = {};
        fns['text'] = function(message) {
            $('.birs_error').hide("");
            $('#birs_booking_box').hide();
            $('#birs_booking_success').html(message);
            $('#birs_booking_success').show("slow", function() {
                birchpress.util.scrollTo(
                    $("#birs_booking_success"),
                    600, -40);
            });
        }
        return fns;
    });

    defineFunction(ns, 'bookingSucceed', function(fn, message){
        fn(message);
    });

    defineFunction(ns, 'bookAppointment', function() {
        var ajaxUrl = birchschedule.model.getAjaxUrl();
        var postData = $('form').serialize();
        postData += '&' + $.param({
            action: 'birchschedule_view_bookingform_schedule'
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
            $('#birs_please_wait').hide("slow");
            var result = birchschedule.model.parseAjaxResponse(data);
            if(result.errors) {
                birchschedule.view.showFormErrors(result.errors);
            } 
            else if(result.success) {
                var bookSucceed = ns.bookSucceed();
                ns.bookingSucceed(bookSucceed[result.success.code], result.success.message);
            }
        });
        $('.birs_error').hide("");
        $('#birs_please_wait').show("slow");
    });

    defineFunction(ns, 'init', function(){
        var getNow4Locale = birchschedule.model.getNow4Locale;

        ns.setLocationOptions();
        ns.setServiceOptions();
        ns.setStaffOptions();    
        ns.setAppointmentPrice();
        ns.setAppointmentDuration();

        var datepicker = ns.initDatepicker();
        $('#birs_appointment_location').on('change', function(){
            ns.setServiceOptions();
            ns.setStaffOptions();
            ns.setAppointmentPrice();
            ns.setAppointmentDuration();
            ns.refreshDatepicker(datepicker);
            ns.reloadTimeOptions();
        });

        $('#birs_appointment_service').on('change', function(){
            ns.setStaffOptions();
            ns.setAppointmentPrice();
            ns.setAppointmentDuration();
            ns.refreshDatepicker(datepicker);
            ns.reloadTimeOptions();
        });

        $('#birs_appointment_staff').on('change', function(){
            ns.refreshDatepicker(datepicker);
            ns.reloadTimeOptions();
        });

        $('#birs_appointment_date').on('change', function(){
            ns.reloadTimeOptions();
        });

        $('#birs_book_appointment').click(function(){
            ns.bookAppointment();
        });
    });
        
    addAction('birchschedule.initAfter', ns.init);

})(jQuery);