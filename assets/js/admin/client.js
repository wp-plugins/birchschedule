jQuery(function($){
    function changeStateUi(){
        birchschedule.changeStateUi('birs_client_country', 'birs_client_state', 'birs_client_province');
    };
    changeStateUi();
    $('#birs_client_country').change(changeStateUi);
});