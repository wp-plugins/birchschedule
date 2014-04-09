(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.appointments.edit.clientlist.payments');

    defineFunction(ns, 'showPayments', function(clientId) {
    	var row = $('#birs_client_list_row_' + clientId);
    	var paymentsRow = $('#birs_client_list_row_payments_' + clientId);

        var ajaxUrl = birchschedule.model.getAjaxUrl();
        postData = $.param({
            action: 'birchschedule_view_appointments_edit_clientlist_payments_render_payments',
            birs_appointment_id: $('#birs_appointment_id').val(),
            birs_client_id: clientId
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
        	paymentsRow.find('td').html(data);
        	row.hide();
	    	paymentsRow.show();
            birchpress.util.scrollTo(paymentsRow, 600, -20);
        });
    });

    defineFunction(ns, 'hideRowPayments', function() {
    	$('.wp-list-table.birs_clients .birs_row_payments').find('td').html('');
    	$('.wp-list-table.birs_clients .birs_row_payments').hide();
    });

    defineFunction(ns, 'updatePaymentInfo', function() {
        var price = parseFloat($('#birs_appointment1on1_price').val());
        if(isNaN(price)) {
            price = 0;
        }
        var paid = 0;
        $('#birs_payments_table tbody tr').each(function(){
            var amount = parseFloat($(this).attr('data-payment-amount'));
            if(isNaN(amount)) {
                amount = 0;
            }
            paid += amount;
        });
        price = price.toFixed(2);
        paid = paid.toFixed(2);
        var due = (price - paid).toFixed(2);
        $('#birs_appointment1on1_paid').html(paid);
        $('#birs_appointment1on1_due').html(due);
        if(due > 0) {
            $('#birs_appointment1on1_amount_to_pay').val(due);
        } else {
            $('#birs_appointment1on1_amount_to_pay').val(0);
        }
    });

    defineFunction(ns, 'initNewPayment', function(){
        $('#birs_payments_table tbody tr .row-actions .delete a').click(function(){
            var paymentTRID = $(this).attr('data-payment-trid');
            $('#birs_payments_table tbody tr[data-payment-trid="' +
                paymentTRID + '"]').remove();
            ns.updatePaymentInfo();
        });
        ns.updatePaymentInfo();
    });

    defineFunction(ns, 'addPayment', function() {
        var ajaxUrl = birchschedule.model.getAjaxUrl();
        if($('#birs_add_payment').hasClass('birs_disabled')) {
            return;
        }
        var postData = $('form').serialize();
        postData += '&' + $.param({
            action: 'birchschedule_view_appointments_edit_clientlist_payments_add_new_payment'
        });
        $.post(ajaxUrl, postData, function(data, status, xhr){
            $(data).prependTo('#birs_payments_table tbody');
            $('#birs_add_payment').removeClass('birs_disabled');
        }, 'html');
        $('#birs_add_payment').addClass('birs_disabled');
    });

    defineFunction(ns, 'save', function() {
        var ajaxUrl = birchschedule.model.getAjaxUrl();
        var i18nMessages = birchschedule.view.getI18nMessages();
        var save_button = $('#birs_appointment_client_payments_save');
        var postData = $('form').serialize();
        postData += '&' + $.param({
            action: 'birchschedule_view_appointments_edit_clientlist_payments_make_payments'
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

    defineFunction(ns, 'initPayments', function() {
        $('#birs_appointment1on1_price').keyup(function(){
            ns.updatePaymentInfo();
        });
        $('#birs_add_payment').click(function(){
            ns.addPayment();
        });
        $('#birs_appointment_client_payments_cancel').click(function(){
        	birchschedule.view.appointments.edit.clientlist.edit.hideRowEdits();
        	birchschedule.view.appointments.edit.clientlist.edit.showRows();
        });
        $('#birs_appointment_client_payments_save').click(function(){
        	ns.save();
        });
        ns.updatePaymentInfo();
    });

    defineFunction(ns, 'init', function() {
        addAction('birchschedule.view.appointments.edit.clientlist.edit.hideRowEditsAfter', ns.hideRowPayments);
    	$('.wp-list-table.birs_clients .row-actions .payments a').click(function(eventObject){
    		birchschedule.view.appointments.edit.clientlist.edit.hideRowEdits();
            var clientId = $(eventObject.target).attr('data-item-id');
    		ns.showPayments(clientId);
    	});
    });

    addAction('birchschedule.initAfter', ns.init);
})(jQuery);