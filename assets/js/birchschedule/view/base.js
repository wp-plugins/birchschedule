(function($){
    var params = birchschedule_view;

    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view');

    defineFunction(ns, 'getDatepickerI18nOptions', function(){
        return params.datepicker_i18n_options;
    });

    defineFunction(ns, 'getFullcalendarI18nOptions', function(){
        return params.fc_i18n_options;

    });

    defineFunction(ns, 'getI18nMessages', function(){
        return params.i18n_messages;
    });

    defineFunction(ns, 'getI18nCountries', function() {
        return params.i18n_countries;
    });

    defineFunction(ns, 'getI18nStates', function() {
        return params.i18n_states;
    });

    defineFunction(ns, 'ifShowDayForDatepicker', function(date, staffId, locationId){
        if(!birchschedule.model.isDayAvaliableByNow(date)){
            return [false, ""];
        }
        var day = date.getDay();
        if(!birchschedule.model.isDayAvaliableByDaysOff(date, staffId)) {
            return [false, ""];
        }
        if(!birchschedule.model.isDayAvaliableBySchedules(date, staffId, 
                locationId, day)) {
            return [false, ""];
        }
        if(!birchschedule.model.isDayAvaliableByBookingPreferences(date)) {
            return [false, ""];
        }
        return [true, ""];
    });

    defineFunction(ns, 'initStateField', function(stateId){
        var stateEl = $('#' + stateId);
        var stateSelectEl = $('#' + stateId + '_select');
        stateSelectEl.change(function(){
            var state = stateSelectEl.val();
            stateEl.val(state);
        });
    });

    defineFunction(ns, 'switchStateProvince', function(countryId, stateId){
        var countries = ns.getI18nCountries();
        var states = ns.getI18nStates();
        var countryEl = $('#' + countryId);
        var stateEl = $('#' + stateId);
        var stateSelectEl = $('#' + stateId + '_select');
        var country = countryEl.val();
        var state = stateEl.val();

        if(_.has(states, country)){
            stateEl.hide();
            var options = "";
            _.each(states[country], function(value, key) {
                options += "<option value='" + key + "'>" + value + "</option>";
            });
            if(stateSelectEl.select2) {
                stateSelectEl.select2('container').show();
            } else {
                stateSelectEl.show();
            }
            stateSelectEl.html(options);
            stateSelectEl.change();
        } else {
            stateEl.show();
            stateEl.val('');
            if(stateSelectEl.select2) {
                stateSelectEl.select2('container').hide();
            } else {
                stateSelectEl.hide();
            }
        }
    });
    
    defineFunction(ns, 'changeStaffOptions', function(serviceStaffMap, locationStaffMap,
            staffOrder) {
        var staffId = $('#birs_appointment_staff').val();

        var availableStaff = {};
        if(serviceStaffMap && locationStaffMap) {
            availableStaff = _.pick( serviceStaffMap, _.keys(locationStaffMap));
        }
        $('#birs_appointment_staff').empty();
        $.each(staffOrder, function(index, key) {
            if(_(availableStaff).has(key)) {
                var value = availableStaff[key];
                $('#birs_appointment_staff').
                    append($("<option></option>").attr("value", key).text(value));
            }
        });
        if(staffId && _(availableStaff).has(staffId)) {
            $('#birs_appointment_staff').val(staffId);
        }
        $('#birs_appointment_staff').trigger('change');
    });


})(jQuery);