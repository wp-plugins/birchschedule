(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.appointments.edit.clientlist.edit');

    defineFunction(ns, 'render', function(viewState) {
        birchschedule.view.appointments.edit.clientlist.render.fn.default(viewState);
        var clientId = viewState.clientId;
        if(viewState.view === 'edit') {
            var row = $('#birs_client_list_row_' + clientId);
            var editRow = $('#birs_client_list_row_edit_' + clientId);

            var data = editRow.attr('data-edit-html');
            editRow.find('td').html(data);
            ns.initForm();
            row.hide();
            editRow.show();
            birchpress.util.scrollTo(editRow);
        }
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

    defineFunction(ns, 'initForm', function() {
        birchschedule.view.initCountryStateField('birs_client_country', 'birs_client_state');
        $('#birs_appointment_client_edit_cancel').click(function(){
            birchschedule.view.appointments.edit.clientlist.setViewState({
                view: 'list'
            });
        });
        $('#birs_appointment_client_edit_save').click(function(){
        	ns.save();
        });
    });

    defineFunction(ns, 'init', function() {
        birchschedule.view.appointments.edit.clientlist.render.fn.when('edit', ns.render);
    	$('.wp-list-table.birs_clients .row-actions .edit a').click(function(eventObject){
            var clientId = $(eventObject.target).attr('data-item-id');
            birchschedule.view.appointments.edit.clientlist.setViewState({
                view: 'edit',
                clientId: clientId
            });
    	});
    });

    addAction('birchschedule.initAfter', ns.init);

})(jQuery);