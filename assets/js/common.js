jQuery(function($){
    window.namespace = function(){
        var len1 = arguments.length,
            i = 0,
            len2,
            j,
            main,
            ns,
            sub,
            current;

        for(; i < len1; ++i) {
            main = arguments[i];
            ns = arguments[i].split('.');
            current = window[ns[0]];
            if (current === undefined) {
                current = window[ns[0]] = {};
            }
            sub = ns.slice(1);
            len2 = sub.length;
            for(j = 0; j < len2; ++j) {
                current = current[sub[j]] = current[sub[j]] || {};
            }
        }
        return current;
    }
    namespace('birchschedule');

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
    
    birchschedule.getServerNow = function(serverGmtOffset) {
        var now = new Date();
        var localOffset = now.getTimezoneOffset();
        var timestamp = now.getTime() + (localOffset - serverGmtOffset) * 60 * 1000;
        return new Date(timestamp);
    }

});