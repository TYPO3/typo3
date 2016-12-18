'use strict';

/**
 * Helper function to implement DataProvider
 * @param {Function|Array|Object} values
 * @param {Function} func
 */
function using(values, func) {
	if (values instanceof Function) {
		values = values();
	}

	if (values instanceof Array) {
		values.forEach(function(value) {
			if (!(value instanceof Array)) {
				value = [value];
			}

			func.apply(this, value);
		});
	} else {
		var objectKeys = Object.keys(values);

		objectKeys.forEach(function(key) {
			if (!(values[key] instanceof Array)) {
				values[key] = [values[key]];
			}

			values[key].push(key);

			func.apply(this, values[key]);
		});
	}
}
