jQuery(function($){
    function changeStateUi(){
        birchschedule.changeStateUi('birs_location_country', 'birs_location_state', 'birs_location_province');
    };
    changeStateUi();
    $('#birs_location_country').change(changeStateUi);
});