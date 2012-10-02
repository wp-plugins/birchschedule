jQuery(function($) {
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
});