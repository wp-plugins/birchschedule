jQuery(function($){
    window.birchschedule = {};

    birchschedule.changeStateUi = function(countryId, stateId, provinceId){
        var countryEl = $('#' + countryId);
        var stateEl = $('#' + stateId);
        var provinceEl = $('#' + provinceId);
        var country = countryEl.val();
        if(country == 'US'){
            stateEl.show();
            provinceEl.hide();
        } else {
            stateEl.hide();
            provinceEl.show();
        }
    }
    
    birchschedule.changeStaffOptions = function(serviceStaffMap, locationStaffMap) {
        var staffId = $('#birs_appointment_staff').val();

        var availableStaff = _.pick(serviceStaffMap, _.keys(locationStaffMap));
        var html = "";
        $.each(availableStaff, function(key, value){
            html += '<option value="' + key + '">' + value + '</option>';                            
        });
        $('#birs_appointment_staff').html(html);
        $('#birs_appointment_staff').val(staffId);
    }
        

});