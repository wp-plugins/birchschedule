(function() {

	var root = window;
	
	var actions = {};
	var filters = {};

	var _assert = function(assertion, message) {
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

	var namespace = function(nsName){
		_assert(_.isString(nsName));

        var ns = nsName.split('.');
        var currentStr = ns[0];
        var current = root[currentStr] = createNs(currentStr, root[currentStr]);
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

	var doAction = function() {
		var args = argumentsToArray(arguments);
		_assert(args.length >= 1, 'At least one argument is required. The arguments are ' + args);
		_assert(_.isString(args[0]), 'The hook name should be string.');

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

	var applyFilters = function() {
		var args = argumentsToArray(arguments);
		_assert(args.length >= 2, 'At least two arguments are required. The arguments are ' + args);
		_assert(_.isString(args[0]), 'The hook name should be string.');

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

	var defineFunction = function(ns, fnName, fn) {
		_assert(_.isObject(ns) && _.has(ns, 'nsString'), 'The namespace(1st argument) should be a namespace object.');
		_assert(_.isString(fnName), 'The function name(2nd argument) should be a string.');
		_assert(_.isFunction(fn), 'The 3rd argument should be a function');

		var hookable = function() {
			var args = argumentsToArray(arguments);
			var filterName = ns.nsString + '.' + fnName;
			var preFilterName = filterName + 'Args';

			var actionBefore = filterName + 'Before';
			var actionAfter = filterName + 'After';

			var beforeArgs = args.slice(0);
			beforeArgs.unshift(actionBefore);
			doAction.apply(ns, beforeArgs);

			var preArgs = [];
			preArgs.unshift(preFilterName, args);
			args = applyFilters.apply(ns, preArgs);

			var realFn = hookable.findRealFunction(args);
			var result = realFn.apply(ns, args);

			var fArgs = args.slice(0);
			fArgs.unshift(filterName, result);
			result = applyFilters.apply(ns, fArgs);

			var afterArgs = args.slice(0);
			afterArgs.unshift(actionAfter);
			afterArgs.push(result);
			doAction.apply(ns, afterArgs);

			return result;
		};

		hookable.findRealFunction = function(args) {
			return hookable.fn;
		};
		hookable.fn = fn;

		ns[fnName] = hookable;

		return hookable;
	};

	var parsePriority = function(arg) {
		arg = parseInt(arg);
		if(_.isNaN(arg) || arg < 0) {
			arg = 10;
		}
		return arg;
	};

	var addHookFunction = function(fnMap, hookName, fn, priority) {
		_assert(_.isString(hookName), 'The hook name should be a string.');
		_assert(_.isFunction(fn), 'The action or filter should be a function');

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
		_assert(_.isString(hookName), 'The hook name should be a string.');
		_assert(_.isFunction(fn), 'The action or filter should be a function');

		priority = parsePriority(priority);
		if(_.has(fnMap, hookName)) {
			var hookDef = fnMap[hookName];
			if(_.isArray(hookDef) && _.isArray(hookDef[priority])) {
				_.without(hookDef[priority], fn);
			}
		}
	};

	var addAction = function(hookName, fn, priority) {
		addHookFunction(actions, hookName, fn, priority);
	};

	var addFilter = function(hookName, fn, priority) {
		addHookFunction(filters, hookName, fn, priority);
	};

	var removeAction = function(hookName, fn, priority) {
		removeHookFunction(actions, hookName, fn, priority);
	};

	var removeFilter = function(hookName, fn, priority) {
		removeHookFunction(filters, hookName, fn, priority);
	};

    var noop = function() {};

    var identity = function(a) { return a; };

    var match = function(value, methods) {
        if(_.has(methods, value)) {
            return methods[value];
        } else {
            return false;
        }
    };

    var pluck = function(property) {
        return function(object) {
            return object[property];
        }
    };


    var multimethod = function(dispatch) { 

        var _dispatch,
            _methods   = {},
            _default   = noop;

        var _lookup    = function() {
            var criteria    = _dispatch.apply(self, arguments),
                method      = match(criteria, _methods);
            if(method !== false) {
                return method;
            } else {
                return _default;
            }
        };

        var toValue = function(subject, args) {
            if(_.isFunction(subject)) {
                return subject.apply(self, args);
            } else {
                return subject;
            }
        };

        var self = function() {
            var method = _lookup.apply(self, arguments);
            return toValue.call(self, method, arguments);
        };

        self['dispatch'] = function(dispatch) {
            if(_.isFunction(dispatch)) {
                _dispatch = dispatch;
            } else if(_.isString(dispatch)) {
                _dispatch = pluck(dispatch);
            } else {
                throw "dispatch requires a function or a string.";
            }
            return self;
        };

        self.dispatch(dispatch || identity);

        self['when'] = function(matchValue, fn) {
            _methods[matchValue] = fn;
            return self;
        };

        self['remove'] = function(matchValue) {
            if(_.has(_methods, matchValue)) {
                delete _methods[matchValue];
            }
            return self;
        };

        self['setDefault'] = function(method) {
            _default = method;
	        Object.defineProperty(self, 'default', {
	            value: _default,
	            writable: false
	        });

            return self;
        };

        return self;
    };

	root.birchpress = {

		namespace: namespace,

		defineFunction: defineFunction,

		multimethod: multimethod,

		addAction: addAction,

		addFilter: addFilter,

		removeAction: removeAction,

		removeFilter: removeFilter,		

		assert: _assert
	};
	
}());
