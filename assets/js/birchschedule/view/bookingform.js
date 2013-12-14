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

    defineFunction(ns, 'changeLocationOptions', function () {
        var html = '';
        $.each(ns.getLocationsOrder(), function(index, key) {
            if(_(ns.getLocationsMap()).has(key)) {
                html += '<option value="' + key + '">' + 
                    ns.getLocationsMap()[key].post_title + '</option>';  
            }
        });
        $('#birs_appointment_location').html(html);
    });
    
    defineFunction(ns, 'changeServiceOptions', function() {
        var serviceId = $('#birs_appointment_service').val();
        var locationId = $('#birs_appointment_location').val();
        var avaliableServices = ns.getLocationsServicesMap()[locationId];
        $('#birs_appointment_service').empty();
        $.each(ns.getServicesOrder(), function(index, key) {
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
    });
    
    defineFunction(ns, 'changeStaffOptions', function() {
        var serviceId = $('#birs_appointment_service').val();
        var locationId = $('#birs_appointment_location').val();
        var servicesStaffMap_ = ns.getServicesStaffMap()[serviceId];
        var locationsStaffMap_ = ns.getLocationsStaffMap()[locationId];
        birchschedule.view.changeStaffOptions(servicesStaffMap_, locationsStaffMap_, 
            ns.getStaffOrder());
    });

    defineFunction(ns, 'getFormQueryData', function(){
        var postData = $('#birs_appointment_form').serialize();
        return postData;
    });

    defineFunction(ns, 'getTimeOptions', function(){
        var ajaxUrl = birchschedule.model.getAjaxUrl();
        $('#birs_appointment_timeoptions').html('');
        $('#birs_appointment_time').val('');
        var postData = ns.getFormQueryData();
        postData += '&' + $.param({
            action: 'birchschedule_view_bookingform_get_avaliable_time'
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
            $('#birs_appointment_timeoptions').html(data);
            $('#birs_appointment_timeoptions a').click(function() {
                $('#birs_appointment_time').val($(this).attr('data-time'));
                $('#birs_appointment_timeoptions a').removeClass('selected');
                $(this).addClass('selected');
            });
        }, 'html');
    });

    defineFunction(ns, 'changeAppointmentPrice', function(){
        var serviceId = $('#birs_appointment_service').val();
        if(serviceId) {
            $('#birs_appointment_price').val(ns.getServicesPricesMap()[serviceId].price);
        }
    });

    defineFunction(ns, 'changeAppointmentDuration', function(){
        var serviceId = $('#birs_appointment_service').val();
        if(serviceId) {
            $('#birs_appointment_duration').val(ns.getServicesDurationMap()[serviceId].duration);
        }
    });

    defineFunction(ns, 'showDatepicker', function(){
        var getServerNow = birchschedule.model.getServerNow;
        var datepickerI18nOptions = birchschedule.view.getDatepickerI18nOptions();
        var options = $.extend(datepickerI18nOptions, {
            changeMonth: false,
            changeYear: false,
            'dateFormat': 'mm/dd/yy',
            beforeShowDay: function(date){
                var locationId = $('#birs_appointment_location').val();
                var staffId = $('#birs_appointment_staff').val();
                return birchschedule.view.ifShowDayForDatepicker(date, staffId, locationId);
            },
            onSelect: function(dateText) {
                $('#birs_appointment_date').val(dateText);
                ns.getTimeOptions();
            }
        });
        $('#birs_appointment_datepicker').datepicker(options);
        var selectDate = getServerNow();
        if(scAttrs['date']) {
            selectDate = $.datepicker.parseDate('mm/dd/yy', scAttrs['date']);
        }
        $('#birs_appointment_datepicker').datepicker("setDate", selectDate);
    });

    defineFunction(ns, 'refreshDatetime', function(){
        $('#birs_appointment_datepicker').datepicker('destroy');
        ns.showDatepicker();
        $('#birs_appointment_timeoptions').html('');
        $('#birs_appointment_time').val('');
        $('#birs_appointment_date').val('');
    });

    defineFunction(ns, 'bookAppointment', function(){
        var scrollTo = birchpress.util.scrollTo;
        var ajaxUrl = birchschedule.model.getAjaxUrl();
        var postData = ns.getFormQueryData();
        postData += '&' + $.param({
            action: 'birchschedule_view_bookingform_save_appointment'
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
            data = '<div>' + data + '</div>';
            var doc = $(data).find('#birs_response');
            if(doc.find('#birs_success').length > 0){
                $('#birs_please_wait').hide("slow");
                if(doc.find('#birs_success_text').length > 0) {
                    $('.birs_error').hide("");
                    $('#birs_booking_box').hide();
                    $('#birs_booking_success').html(doc.find('#birs_success_text').html());
                    $('#birs_booking_success').show("slow", function() {
                        scrollTo("#birs_booking_success", 10);
                    });
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
                    $('#' + tagId).show("slow");
                });
                $('#birs_please_wait').hide("slow", function() {
                    scrollTo($(".birs_error:visible:first").
                        parentsUntil("#birs_appointment_form",
                            ".birs_form_field"));
                });
                return;
            }
        }, 'text');
        $('.birs_error').hide("slow");
        $('#birs_please_wait').show("slow");
    });

    defineFunction(ns, 'init', function(){
        var getServerNow = birchschedule.model.getServerNow;

        ns.changeLocationOptions();
        ns.changeServiceOptions();
        ns.changeStaffOptions();    
        ns.changeAppointmentPrice();
        ns.changeAppointmentDuration();
        if(!birchpress.util.isMobile()) {
            var serviceWidth = "100%";
            $('#birs_appointment_form select').select2({
                width: serviceWidth,
                placeholder: "Select"
            });
        }
        ns.showDatepicker();
        $('#birs_appointment_location').on('change', function(){
            ns.changeServiceOptions();
            ns.changeStaffOptions();
            ns.changeAppointmentPrice();
            ns.changeAppointmentDuration();
        });
        $('#birs_appointment_service').on('change', function(){
            ns.changeStaffOptions();
            ns.changeAppointmentPrice();
            ns.changeAppointmentDuration();
        });
        $('#birs_appointment_staff').on('change', function(){
            ns.refreshDatetime();
        });
        $('#birs_book_appointment').click(function(){
            ns.bookAppointment();
        });
    });
        
    addAction('birchschedule.initAfter', ns.init);

})(jQuery);