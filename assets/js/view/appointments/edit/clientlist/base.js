(function($){
    var namespace = birchpress.namespace;
    var defineFunction = birchpress.defineFunction;
    var addAction = birchpress.addAction;

    var ns = namespace('birchschedule.view.appointments.edit.clientlist');

    var multimethod = function(fn) {

        return birchpress.multimethod()
                    .dispatch(function(viewState) {
                        return viewState.view;
                    })
                    .setDefault(fn);
    };

    var viewState = {
    	view: 'list',
    	clientId: 0
    };

    defineFunction(ns, 'getViewState', function() {
    	return viewState;
    });

    defineFunction(ns, 'setViewState', function(state) {
		viewState = _.extend(viewState, state);
		ns.render(viewState);
    });

    defineFunction(ns, 'render', multimethod(function(viewState) {
    	$('.wp-list-table.birs_clients .birs_row').show();
    	$('.wp-list-table.birs_clients tbody tr:not(.birs_row)').find('td').html('');
    	$('.wp-list-table.birs_clients tbody tr:not(.birs_row)').hide();
    }));

    defineFunction(ns, 'init', function() {
    	ns.render({
    		view: 'list',
    		clientId: 0
    	});
    });

    addAction('birchschedule.initAfter', ns.init);

})(jQuery);