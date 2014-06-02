(function(_) {
	window.birchpress = {};

	var actions = {};
	var filters = {};

	birchpress.assert = function(assertion, message) {
		if(!assertion) {
			throw new Error(message);
		}
	}

	var createNs = function(nsString, ns) {
		if(!_.isObject(ns)) {
			ns = {};
		}
		ns['nsString'] = nsString;
		return ns;
	}

	birchpress.namespace = function(nsName){
		birchpress.assert(_.isString(nsName));

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
	}

	birchpress.doAction = function() {
		var args = argumentsToArray(arguments);
		birchpress.assert(args.length >= 1, 'At least one argument is required. The arguments are ' + args);
		birchpress.assert(_.isString(args[0]), 'The hook name should be string.');

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
	}

	birchpress.appyFilters = function() {
		var args = argumentsToArray(arguments);
		birchpress.assert(args.length >= 2, 'At least two arguments are required. The arguments are ' + args);
		birchpress.assert(_.isString(args[0]), 'The hook name should be string.');

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
	}

	birchpress.defineFunction = function(ns, fnName, fn) {
		birchpress.assert(_.isObject(ns) && _.has(ns, 'nsString'), 'The namespace(1st argument) should be a namespace object.');
		birchpress.assert(_.isString(fnName), 'The function name(2nd argument) should be a string.');
		birchpress.assert(_.isFunction(fn), 'The 3rd argument should be a function');

		ns[fnName] = function() {
			var args = argumentsToArray(arguments);
			var filterName = ns.nsString + '.' + fnName;
			var actionBefore = filterName + 'Before';
			var actionAfter = filterName + 'After';

			var bArgs = args.slice(0);
			bArgs.unshift(actionBefore);
			birchpress.doAction.apply(ns, bArgs);

			var result = fn.apply(ns, args);

			var fArgs = args.slice(0);
			fArgs.unshift(filterName, result);
			result = birchpress.appyFilters.apply(ns, fArgs);

			var aArgs = args.slice(0);
			aArgs.unshift(actionAfter);
			aArgs.push(result);
			birchpress.doAction.apply(ns, aArgs);

			return result;
		}
	};

	var parsePriority = function(arg) {
		arg = parseInt(arg);
		if(_.isNaN(arg) || arg < 0) {
			arg = 10;
		}
		return arg;
	};

	var addHookFunction = function(fnMap, hookName, fn, priority) {
		birchpress.assert(_.isString(hookName), 'The hook name should be a string.');
		birchpress.assert(_.isFunction(fn), 'The action or filter should be a function');

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
		birchpress.assert(_.isString(hookName), 'The hook name should be a string.');
		birchpress.assert(_.isFunction(fn), 'The action or filter should be a function');

		priority = parsePriority(priority);
		if(_.has(fnMap, hookName)) {
			var hookDef = fnMap[hookName];
			if(_.isArray(hookDef) && _.isArray(hookDef[priority])) {
				_.without(hookDef[priority], fn);
			}
		}
	};

	birchpress.addAction = function(hookName, fn, priority) {
		addHookFunction(actions, hookName, fn, priority);
	};

	birchpress.addFilter = function(hookName, fn, priority) {
		addHookFunction(filters, hookName, fn, priority);
	};

	birchpress.removeAction = function(hookName, fn, priority) {
		removeHookFunction(actions, hookName, fn, priority);
	}

	birchpress.removeFilter = function(hookName, fn, priority) {
		removeHookFunction(filters, hookName, fn, priority);
	}

})(_);
