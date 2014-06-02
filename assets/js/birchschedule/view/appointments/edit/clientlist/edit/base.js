(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.appointments.edit.clientlist.edit');

    defineFunction(ns, 'showEdit', function(clientId) {
    	var row = $('#birs_client_list_row_' + clientId);
    	var editRow = $('#birs_client_list_row_edit_' + clientId);

        var ajaxUrl = birchschedule.model.getAjaxUrl();
        postData = $.param({
            action: 'birchschedule_view_appointments_edit_clientlist_edit_render_edit',
            birs_appointment_id: $('#birs_appointment_id').val(),
            birs_client_id: clientId
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
        	editRow.find('td').html(data);
        	row.hide();
	    	editRow.show();
            birchpress.util.scrollTo(editRow);
        });
    });

    defineFunction(ns, 'hideRowEdits', function() {
    	$('.wp-list-table.birs_clients .birs_row_edit').find('td').html('');
    	$('.wp-list-table.birs_clients .birs_row_edit').hide();
    });

    defineFunction(ns, 'showRows', function() {
    	$('.wp-list-table.birs_clients .birs_row').show();
    });

    defineFunction(ns, 'save', function() {
        var ajaxUrl = birchschedule.model.getAjaxUrl();
        var i18nMessages = birchschedule.view.getI18nMessages();
        var save_button = $('#birs_appointment_client_edit_save');
        var postData = $('form').serialize();
        postData += '&' + $.param({
            action: 'birchschedule_view_appointments_edit_clientlist_edit_save'
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
            var result = birchschedule.model.parseAjaxResponse(data);
            if(result.errors) {
                birchschedule.view.showFormErrors(result.errors);
            } 
            else if(result.success) {
                window.location.reload();
            }
            save_button.val(i18nMessages['Save']);
            save_button.prop('disabled', false);
        });
        save_button.val(i18nMessages['Please wait...']);
        save_button.prop('disabled', true);
    });

    defineFunction(ns, 'initEdit', function() {
        birchschedule.view.initCountryStateField('birs_client_country', 'birs_client_state');
        $('#birs_appointment_client_edit_cancel').click(function(){
        	ns.hideRowEdits();
        	ns.showRows();
        });
        $('#birs_appointment_client_edit_save').click(function(){
        	ns.save();
        });
    });

    defineFunction(ns, 'init', function() {
    	$('.wp-list-table.birs_clients .row-actions .edit a').click(function(eventObject){
    		ns.hideRowEdits();
            var clientId = $(eventObject.target).attr('data-item-id');
    		ns.showEdit(clientId);
    	});
    });

    addAction('birchschedule.initAfter', ns.init);
})(jQuery);