jQuery(function($){
    var params = birs_enhanced_admin_params;
    var ajaxUrl = params.ajax_url;

    var changeStateUi = function(){
        birchschedule.changeStateUi('birs_client_country', 'birs_client_state', 'birs_client_province');
    };
    // begin patch for select2 and jquery ui 1.10.2+ compatibility
    $.ui.dialog.prototype._allowInteraction = function(e) {
        return !!$(e.target).closest('.ui-dialog, .ui-datepicker, .select2-drop').length;
    };
    // end patch
    var initClientSelector = function(){
        $('#birs_client_selector').select2({
            'width': '190px',
            allowClear: false,
            data: birs_client_options,
            minimumInputLength: 2
        });
        $('#birs_client_selector').on('change', function(e){
            var client_id = e.val;
            $.ajax({
                url: ajaxUrl,
                data: {
                    action: 'birs_load_selected_client',
                    birs_client_id: client_id
                },
                success: function(doc){
                    doc = '<div>' + doc + '</div>';
                    var basicInfo = $(doc).find('#birs_general_section_client');
                    var details = $(doc).find('#birs_client_details');
                    $('#birs_general_section_client').html(basicInfo.html());
                    $('#birs_client_details').html(details.html());
                    $('#birs_add_new_dialog').triggerHandler('birchschedule.clientDetailsChange');
                }
            });
            $('#birs_client_selector_status').show();
        });
    };
    
    $('#birs_add_new_dialog').bind('birchschedule.editFormReady', function(){
        initClientSelector();
        $('#birs_client_country').change(changeStateUi);
    });
    $('#birs_add_new_dialog').bind('birchschedule.clientDetailsChange', function(){
        initClientSelector();
        $('#birs_client_country').change(changeStateUi);
    });
});