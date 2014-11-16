(function(_) {
	window.birchbase = {};

	var actions = {};
	var filters = {};

	birchbase.assert = function(assertion, message) {
		if(!assertion) {
			throw new Error(message);
		}
	};

	var createNs = function(nsString, ns) {
		if(!_.isObject(ns)) {
			ns = {};
		}
		ns.nsString = nsString;
		return ns;
	};

	birchbase.namespace = function(nsName){
		birchbase.assert(_.isString(nsName));

        var ns = nsName.split('.');
        var currentStr = ns[0];
        var current = window[currentStr] = createNs(currentStr, window[currentStr]);
        var sub = ns.slice(1);
        len = sub.length;
        for(var i = 0; i < len; ++i) {
            currentStr = currentStr + '.' + sub[i];
            current[sub[i]] = createNs(currentStr, current[sub[i]]);
            current = current[sub[i]];
        }

	    return current;
	};

	var argumentsToArray = function(args) {
		return Array.prototype.slice.call(args);
	};

	birchbase.doAction = function() {
		var args = argumentsToArray(arguments);
		birchbase.assert(args.length >= 1, 'At least one argument is required. The arguments are ' + args);
		birchbase.assert(_.isString(args[0]), 'The hook name should be string.');

		var context = this;
		var hookName = args[0];
		var fnArgs = args.slice(1);
		if(_.has(actions, hookName)) {
			var hookDef = actions[hookName];
			if(_.isArray(hookDef)) {
				_.each(hookDef, function(priorityDef, priority){
					if(_.isArray(priorityDef)) {
						_.each(priorityDef, function(fn, index){
							fn.apply(context, fnArgs);
						});
					}
				});
			}
		}
	};

	birchbase.appyFilters = function() {
		var args = argumentsToArray(arguments);
		birchbase.assert(args.length >= 2, 'At least two arguments are required. The arguments are ' + args);
		birchbase.assert(_.isString(args[0]), 'The hook name should be string.');

		var context = this;
		var hookName = args[0];
		var value = args[1];
		if(_.has(filters, hookName)) {
			var hookDef = filters[hookName];
			if(_.isArray(hookDef)) {
				_.each(hookDef, function(priorityDef, priority){
					if(_.isArray(priorityDef)) {
						_.each(priorityDef, function(fn, index){
							var fnArgs = args.slice(2);
							fnArgs.unshift(value);
							value = fn.apply(context, fnArgs);
						});
					}
				});
			}
		}
		return value;
	};

	birchbase.defineFunction = function(ns, fnName, fn) {
		birchbase.assert(_.isObject(ns) && _.has(ns, 'nsString'), 'The namespace(1st argument) should be a namespace object.');
		birchbase.assert(_.isString(fnName), 'The function name(2nd argument) should be a string.');
		birchbase.assert(_.isFunction(fn), 'The 3rd argument should be a function');

		ns[fnName] = function() {
			var args = argumentsToArray(arguments);
			var filterName = ns.nsString + '.' + fnName;
			var actionBefore = filterName + 'Before';
			var actionAfter = filterName + 'After';

			var bArgs = args.slice(0);
			bArgs.unshift(actionBefore);
			birchbase.doAction.apply(ns, bArgs);

			var result = fn.apply(ns, args);

			var fArgs = args.slice(0);
			fArgs.unshift(filterName, result);
			result = birchbase.appyFilters.apply(ns, fArgs);

			var aArgs = args.slice(0);
			aArgs.unshift(actionAfter);
			aArgs.push(result);
			birchbase.doAction.apply(ns, aArgs);

			return result;
		};
	};

	var parsePriority = function(arg) {
		arg = parseInt(arg);
		if(_.isNaN(arg) || arg < 0) {
			arg = 10;
		}
		return arg;
	};

	var addHookFunction = function(fnMap, hookName, fn, priority) {
		birchbase.assert(_.isString(hookName), 'The hook name should be a string.');
		birchbase.assert(_.isFunction(fn), 'The action or filter should be a function');

		priority = parsePriority(priority);
		if(_.has(fnMap, hookName)) {
			var hookDef = fnMap[hookName];
			if(_.isArray(hookDef) && _.isArray(hookDef[priority])) {
				hookDef[priority].push(fn);
			} else {
				hookDef = [];
				hookDef[priority] = [fn];
			}
		} else {
			fnMap[hookName] = [];
			fnMap[hookName][priority] = [fn];
		}
	};

	var removeHookFunction = function(fnMap, hookName, fn, priority) {
		birchbase.assert(_.isString(hookName), 'The hook name should be a string.');
		birchbase.assert(_.isFunction(fn), 'The action or filter should be a function');

		priority = parsePriority(priority);
		if(_.has(fnMap, hookName)) {
			var hookDef = fnMap[hookName];
			if(_.isArray(hookDef) && _.isArray(hookDef[priority])) {
				_.without(hookDef[priority], fn);
			}
		}
	};

	birchbase.addAction = function(hookName, fn, priority) {
		addHookFunction(actions, hookName, fn, priority);
	};

	birchbase.addFilter = function(hookName, fn, priority) {
		addHookFunction(filters, hookName, fn, priority);
	};

	birchbase.removeAction = function(hookName, fn, priority) {
		removeHookFunction(actions, hookName, fn, priority);
	};

	birchbase.removeFilter = function(hookName, fn, priority) {
		removeHookFunction(filters, hookName, fn, priority);
	};

})(_);
