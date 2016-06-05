/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Backend/FormEngineValidation
 * Contains all JS functions related to TYPO3 TCEforms/FormEngineValidation
 * @internal
 */
define(['jquery', 'TYPO3/CMS/Backend/FormEngine'], function ($, FormEngine) {

	/**
	 * The main FormEngineValidation object
	 *
	 * @type {{rulesSelector: string, inputSelector: string, markerSelector: string, dateTimeSelector: string, groupFieldHiddenElement: string, relatedFieldSelector: string, errorClass: string, lastYear: number, lastDate: number, lastTime: number, refDate: Date, USmode: number, passwordDummy: string}}
	 * @exports TYPO3/CMS/Backend/FormEngineValidation
	 */
	var FormEngineValidation = {
		rulesSelector: '[data-formengine-validation-rules]',
		inputSelector: '[data-formengine-input-params]',
		markerSelector: '.t3js-formengine-validation-marker',
		dateTimeSelector: '.t3js-datetimepicker',
		groupFieldHiddenElement: '.t3js-formengine-field-group input[type=hidden]',
		relatedFieldSelector: '[data-relatedfieldname]',
		errorClass: 'has-error',
		lastYear: 0,
		lastDate: 0,
		lastTime: 0,
		refDate: new Date(),
		USmode: 0,
		passwordDummy: '********'
	};

	/**
	 * Initialize validation for the first time
	 */
	FormEngineValidation.initialize = function() {
		$(document).find('.' + FormEngineValidation.errorClass).removeClass(FormEngineValidation.errorClass);

		// Initialize input fields
		FormEngineValidation.initializeInputFields().promise().done(function () {
			// Bind to field changes
			$(document).on('change', FormEngineValidation.rulesSelector, function() {
				// we need to wait, because the update of the select fields needs some time
				window.setTimeout(function() {
					FormEngineValidation.validate();
				}, 500);
				var $paletteField = $(this).closest('.t3js-formengine-palette-field');
				$paletteField.addClass('has-change');
			});

			// Bind to datepicker change event, but wait some milliseconds, because the init is not so fast
			window.setTimeout(function() {
				//noinspection JSUnusedLocalSymbols
				$(document).on('dp.change', FormEngineValidation.dateTimeSelector, function(event) {
					FormEngineValidation.validate();
					var $paletteField = $(this).closest('.t3js-formengine-palette-field');
					$paletteField.addClass('has-change');
				});
			}, 500);
		});

		var today = new Date();
		FormEngineValidation.lastYear = FormEngineValidation.getYear(today);
		FormEngineValidation.lastDate = FormEngineValidation.getDate(today);
		FormEngineValidation.lastTime = 0;
		FormEngineValidation.refDate = today;
		FormEngineValidation.USmode = 0;
	};

	/**
	 * Initialize all input fields
	 *
	 * @returns {Object}
	 */
	FormEngineValidation.initializeInputFields = function() {
		return $(document).find(FormEngineValidation.inputSelector).each(function() {
			var config = $(this).data('formengine-input-params');
			var fieldName = config.field;
			var $field = $('[name="' + fieldName + '"]');

			// ignore fields which already have been initialized
			if ($field.data('main-field') === undefined) {
				$field.data('main-field', fieldName);
				$field.data('config', config);
				FormEngineValidation.initializeInputField(fieldName);
			}
		});
	};

	/**
	 *
	 * @param {Number} mode
	 */
	FormEngineValidation.setUsMode = function(mode) {
		FormEngineValidation.USmode = mode;
	};

	/**
	 * Initialize field by name
	 *
	 * @param {String} fieldName
	 */
	FormEngineValidation.initializeInputField = function(fieldName) {
		var $field = $('[name="' + fieldName + '"]');
		var $humanReadableField = $('[data-formengine-input-name="' + fieldName + '"]');
		var $mainField = $('[name="' + $field.data('main-field') + '"]');
		if ($mainField.length === 0) {
			$mainField = $field;
		}

		var config = $mainField.data('config');
		if (typeof config !== 'undefined') {
			var evalList = FormEngineValidation.trimExplode(',', config.evalList);
			var value = $field.val();

			for (var i = 0; i < evalList.length; i++) {
				value = FormEngineValidation.formatValue(evalList[i], value, config)
			}
			// Prevent password fields to be overwritten with original value
			if (value.length && $humanReadableField.attr('type') != 'password') {
				$humanReadableField.val(value);
			}
		}

		$humanReadableField.data('main-field', fieldName);
		$humanReadableField.data('config', config);
		$humanReadableField.on('change', function() {
			FormEngineValidation.updateInputField($(this).attr('data-formengine-input-name'));
		});
		$humanReadableField.on('keyup', FormEngineValidation.validate);
	};

	/**
	 * Format field value
	 *
	 * @param {String} type
	 * @param {String} value
	 * @param {array} config
	 * @returns {String}
	 */
	FormEngineValidation.formatValue = function(type, value, config) {
		var theString = '';
		switch (type) {
			case 'date':
				if (!parseInt(value)) {
					return '';
				}
				theTime = new Date(parseInt(value) * 1000);
				if (FormEngineValidation.USmode) {
					theString = (theTime.getUTCMonth() + 1) + '-' + theTime.getUTCDate() + '-' + this.getYear(theTime);
				} else {
					theString = theTime.getUTCDate() + '-' + (theTime.getUTCMonth() + 1) + '-' + this.getYear(theTime);
				}
				break;
			case 'datetime':
				if (!parseInt(value)) {
					return '';
				}
				theString = FormEngineValidation.formatValue('time', value, config) + ' ' + FormEngineValidation.formatValue('date', value, config);
				break;
			case 'time':
			case 'timesec':
				if (!parseInt(value) && value.toString() !== '0') {
					return '';
				}
				var theTime = new Date(parseInt(value) * 1000);
				var h = theTime.getUTCHours();
				var m = theTime.getUTCMinutes();
				var s = theTime.getUTCSeconds();
				theString = h + ':' + ((m < 10) ? '0' : '') + m + ((type == 'timesec') ? ':' + ((s < 10) ? '0' : '') + s : '');
				break;
			case 'password':
				theString = (value) ? FormEngineValidation.passwordDummy : '';
				break;
			default:
				theString = value;
		}
		return theString;
	};

	/**
	 * Update input field after change
	 *
	 * @param {String} fieldName
	 */
	FormEngineValidation.updateInputField = function(fieldName) {
		var $field = $('[name="' + fieldName + '"]');
		var $mainField = $('[name="' + $field.data('main-field') + '"]');
		if ($mainField.length === 0) {
			$mainField = $field;
		}
		var $humanReadableField = $('[data-formengine-input-name="' + $mainField.attr('name') + '"]');

		var config = $mainField.data('config');
		if (typeof config !== 'undefined') {
			var evalList = FormEngineValidation.trimExplode(',', config.evalList);
			var newValue = $humanReadableField.val();
			var i;

			for (i = 0; i < evalList.length; i++) {
				newValue = FormEngineValidation.processValue(evalList[i], newValue, config);
			}

			var formattedValue = newValue;
			for (i = 0; i < evalList.length; i++) {
				formattedValue = FormEngineValidation.formatValue(evalList[i], formattedValue, config);
			}

			$mainField.val(newValue);
			$humanReadableField.val(formattedValue);
		}
	};

	/**
	 * Run validation for field
	 *
	 * @param {Object} $field
	 * @param {String} [value=$field.val()]
	 * @returns {String}
	 */
	FormEngineValidation.validateField = function($field, value) {
		value = value || $field.val();
		if (!$.isArray(value)) {
			value = FormEngineValidation.ltrim(value);
		}

		var rules = $field.data('formengine-validation-rules');
		var markParent = false;
		var selected = 0;
		var returnValue = value;
		var $relatedField;
		var minItems;
		var maxItems;
		$.each(rules, function(k, rule) {
			switch (rule.type) {
				case 'required':
					if (value === '') {
						markParent = true;
						$field.closest(FormEngineValidation.markerSelector).addClass(FormEngineValidation.errorClass);
					}
					break;
				case 'range':
					if (value !== '') {
						if (rule.minItems || rule.maxItems) {
							$relatedField = $(document).find('[name="' + $field.data('relatedfieldname') + '"]');
							if ($relatedField.length) {
								selected = FormEngineValidation.trimExplode(',', $relatedField.val()).length;
							} else {
								selected = $field.val();
							}
							if (typeof rule.minItems !== 'undefined') {
								minItems = rule.minItems * 1;
								if (!isNaN(minItems) && selected < minItems) {
									markParent = true;
								}
							}
							if (typeof rule.maxItems !== 'undefined') {
								maxItems = rule.maxItems * 1;
								if (!isNaN(maxItems) && selected > maxItems) {
									markParent = true;
								}
							}
						}
						if (typeof rule.config.lower !== 'undefined') {
							var minValue = rule.config.lower * 1;
							if (!isNaN(minValue) && value < minValue) {
								markParent = true;
							}
						}
						if (typeof rule.config.upper !== 'undefined') {
							var maxValue = rule.config.upper * 1;
							if (!isNaN(maxValue) && value > maxValue) {
								markParent = true;
							}
						}
					}
					break;
				case 'select':
					if (rule.minItems || rule.maxItems) {
						$relatedField = $(document).find('[name="' + $field.data('relatedfieldname') + '"]');
						if ($relatedField.length) {
							selected = FormEngineValidation.trimExplode(',', $relatedField.val()).length;
						} else {
							selected = $field.find('option:selected').length;
						}
						if (typeof rule.minItems !== 'undefined') {
							minItems = rule.minItems * 1;
							if (!isNaN(minItems) && selected < minItems) {
								markParent = true;
							}
						}
						if (typeof rule.maxItems !== 'undefined') {
							maxItems = rule.maxItems * 1;
							if (!isNaN(maxItems) && selected > maxItems) {
								markParent = true;
							}
						}
					}
					break;
				case 'group':
					if (rule.minItems || rule.maxItems) {
						selected = $field.find('option').length;
						if (typeof rule.minItems !== 'undefined') {
							minItems = rule.minItems * 1;
							if (!isNaN(minItems) && selected < minItems) {
								markParent = true;
							}
						}
						if (typeof rule.maxItems !== 'undefined') {
							maxItems = rule.maxItems * 1;
							if (!isNaN(maxItems) && selected > maxItems) {
								markParent = true;
							}
						}
					}
					break;
				case 'inline':
					if (rule.minItems || rule.maxItems) {
						selected = FormEngineValidation.trimExplode(',', $field.val()).length;
						if (typeof rule.minItems !== 'undefined') {
							minItems = rule.minItems * 1;
							if (!isNaN(minItems) && selected < minItems) {
								markParent = true;
							}
						}
						if (typeof rule.maxItems !== 'undefined') {
							maxItems = rule.maxItems * 1;
							if (!isNaN(maxItems) && selected > maxItems) {
								markParent = true;
							}
						}
					}
					break;
				case 'null':
					// unknown type null, we ignore it
					break;
				default:
			}
		});
		if (markParent) {
			// mark field
			$field.closest(FormEngineValidation.markerSelector).addClass(FormEngineValidation.errorClass);

			// check tabs
			FormEngineValidation.markParentTab($field);
		}
		return returnValue;
	};

	/**
	 * Process a value by given command and config
	 *
	 * @param {String} command
	 * @param {String} value
	 * @param {Array} config
	 * @returns {String}
	 */
	FormEngineValidation.processValue = function(command, value, config) {
		var newString = '';
		var theValue = '';
		var theCmd = '';
		var a = 0;
		var returnValue = value;
		switch (command) {
			case 'alpha':
			case 'num':
			case 'alphanum':
			case 'alphanum_x':
				newString = '';
				for (a = 0; a < value.length; a++) {
					theChar = value.substr(a, 1);
					var special = (theChar === '_' || theChar === '-');
					var alpha = (theChar >= 'a' && theChar <= 'z') || (theChar >= 'A' && theChar <= 'Z');
					var num = (theChar >= '0' && theChar <= '9');
					switch (command) {
						case 'alphanum':
							special = 0;
							break;
						case 'alpha':
							num = 0;
							special = 0;
							break;
						case 'num':
							alpha = 0;
							special = 0;
							break;
					}
					if (alpha || num || theChar == ' ' || special) {
						newString += theChar;
					}
				}
				if (newString !== value) {
					returnValue = newString;
				}
				break;
			case 'is_in':
				if (config.is_in) {
					theValue = '' + value;
					for (a = 0; a < theValue.length; a++) {
						theChar = theValue.substr(a, 1);
						if (config.is_in.indexOf(theChar) != -1) {
							newString += theChar;
						}
					}
				} else {
					newString = theValue;
				}
				returnValue = newString;
				break;
			case 'nospace':
				theValue = '' + value;
				newString = '';
				for (a = 0; a < theValue.length; a++) {
					var theChar = theValue.substr(a, 1);
					if (theChar != ' ') {
						newString += theChar;
					}
				}
				returnValue = newString;
				break;
			case 'md5':
				if (value !== '') {
					returnValue = MD5(value);
				}
				break;
			case 'upper':
				returnValue = value.toUpperCase();
				break;
			case 'lower':
				returnValue = value.toLowerCase();
				break;
			case 'int':
				if (value !== '') {
					returnValue = FormEngineValidation.parseInt(value);
				}
				break;
			case 'double2':
				if (value !== '') {
					returnValue = FormEngineValidation.parseDouble(value);
				}
				break;
			case 'trim':
				returnValue = FormEngineValidation.ltrim(FormEngineValidation.btrim(value));
				break;
			case 'datetime':
				if (value !== '') {
					theCmd = value.substr(0, 1);
					returnValue = FormEngineValidation.parseDateTime(value, theCmd);
				}
				break;
			case 'date':
				if (value !== '') {
					theCmd = value.substr(0, 1);
					returnValue = FormEngineValidation.parseDate(value, theCmd);
				}
				break;
			case 'time':
			case 'timesec':
				if (value !== '') {
					theCmd = value.substr(0, 1);
					returnValue = FormEngineValidation.parseTime(value, theCmd, command);
				}
				break;
			case 'year':
				if (value !== '') {
					theCmd = value.substr(0, 1);
					returnValue = FormEngineValidation.parseYear(value, theCmd);
				}
				break;
			case 'null':
				// unknown type null, we ignore it
				break;
			case 'password':
				// password is only a display evaluation, we ignore it
				break;
			default:
				if (typeof TBE_EDITOR.customEvalFunctions !== 'undefined' && typeof TBE_EDITOR.customEvalFunctions[command] === 'function') {
					returnValue = TBE_EDITOR.customEvalFunctions[command](value);
				}
		}
		return returnValue;
	};

	/**
	 * Validate the complete form
	 */
	FormEngineValidation.validate = function() {
		$(document).find(FormEngineValidation.markerSelector + ', .t3js-tabmenu-item')
			.removeClass(FormEngineValidation.errorClass)
			.removeClass('has-validation-error');

		$(FormEngineValidation.rulesSelector).each(function() {
			var $field = $(this);
			if (!$field.closest('.t3js-flex-section-deleted, .t3js-inline-record-deleted').length) {
				var modified = false;
				var currentValue = $field.val();
				var newValue = FormEngineValidation.validateField($field, currentValue);
				if ($.isArray(newValue) && $.isArray(currentValue)) {
					// handling for multi-selects
					if (newValue.length !== currentValue.length) {
						modified = true;
					} else {
						for (var i = 0; i < newValue.length; i++) {
							if (newValue[i] !== currentValue[i]) {
								modified = true;
								break;
							}
						}
					}
				} else if (newValue.length && currentValue !== newValue) {
					modified = true;
				}
				if (modified) {
					$field.attr('value', newValue);
					FormEngineValidation.setCaretPosition($field, 0);
				}
			}
		});
	};

	/**
	 * Set the caret position in a text field
	 *
	 * @param {Object} $element
	 * @param {Number} caretPos
	 */
	FormEngineValidation.setCaretPosition = function($element, caretPos) {
		var elem = $element.get(0);

		if (elem.createTextRange) {
			var range = elem.createTextRange();
			range.move('character', caretPos);
			range.select();
		} else {
			if (elem.selectionStart) {
				elem.focus();
				elem.setSelectionRange(caretPos, caretPos);
			} else {
				elem.focus();
			}
		}
	};

	/**
	 * Helper function to get clean trimmed array from comma list
	 *
	 * @param {String} delimiter
	 * @param {String} string
	 * @returns {Array}
	 */
	FormEngineValidation.trimExplode = function(delimiter, string) {
		var result = [];
		var items = string.split(delimiter);
		for (var i=0; i<items.length; i++) {
			var item = items[i].trim();
			if (item.length > 0) {
				result.push(item);
			}
		}
		return result;
	};

	/**
	 * Parse value to integer
	 *
	 * @param {(Number|String)} value
	 * @returns {Number}
	 */
	FormEngineValidation.parseInt = function(value) {
		var theVal = '' + value;
		if (!value) {
			return 0;
		}
		for (var a = 0; a < theVal.length; a++) {
			if (theVal.substr(a,1)!='0') {
				return parseInt(theVal.substr(a,theVal.length)) || 0;
			}
		}
		return 0;
	};

	/**
	 * Parse value to double
	 *
	 * @param {String} value
	 * @returns {String}
	 */
	FormEngineValidation.parseDouble = function(value) {
		var theVal = '' + value;
		theVal = theVal.replace(/[^0-9,\.-]/g, '');
		var negative = theVal.substring(0, 1) === '-';
		theVal = theVal.replace(/-/g, '');
		theVal = theVal.replace(/,/g, '.');
		if (theVal.indexOf('.') === -1) {
			theVal += '.0';
		}
		var parts = theVal.split('.');
		var dec = parts.pop();
		theVal = Number(parts.join('') + '.' + dec);
		if (negative) {
			theVal *= -1;
		}
		theVal = theVal.toFixed(2);

		return theVal;
	};

	/**
	 *
	 * @param {String} value
	 * @returns {String}
	 */
	FormEngineValidation.ltrim = function(value) {
		var theVal = '' + value;
		if (!value) {
			return '';
		}
		for (var a = 0; a < theVal.length; a++) {
			if (theVal.substr(a, 1) != ' ') {
				return theVal.substr(a, theVal.length);
			}
		}
		return '';
	};

	/**
	 *
	 * @param {String} value
	 * @returns {String}
	 */
	FormEngineValidation.btrim = function(value) {
		var theVal = '' + value;
		if (!value) {
			return '';
		}
		for (var a = theVal.length; a > 0; a--) {
			if (theVal.substr(a-1, 1) != ' ') {
				return theVal.substr(0, a);
			}
		}
		return '';
	};

	/**
	 * Parse datetime value
	 *
	 * @param {String} value
	 * @param {String} command
	 * @returns {*}
	 */
	FormEngineValidation.parseDateTime = function(value, command) {
		var today = new Date();
		var values = FormEngineValidation.split(value);
		var add = 0;
		switch (command) {
			case 'd':
			case 't':
			case 'n':
				FormEngineValidation.lastTime = FormEngineValidation.convertClientTimestampToUTC(FormEngineValidation.getTimestamp(today), 0);
				if (values.valPol[1]) {
					add = FormEngineValidation.pol(values.valPol[1], FormEngineValidation.parseInt(values.values[1]));
				}
				break;
			case '+':
			case '-':
				if (FormEngineValidation.lastTime == 0) {
					FormEngineValidation.lastTime = FormEngineValidation.convertClientTimestampToUTC(FormEngineValidation.getTimestamp(today), 0);
				}
				if (values.valPol[1]) {
					add = FormEngineValidation.pol(values.valPol[1], FormEngineValidation.parseInt(values.values[1]));
				}
				break;
			default:
				var index = value.indexOf(' ');
				if (index != -1) {
					var dateVal = FormEngineValidation.parseDate(value.substr(index, value.length), value.substr(0, 1));
					// set refDate so that evalFunc_input on time will work with correct DST information
					FormEngineValidation.refDate = new Date(dateVal * 1000);
					FormEngineValidation.lastTime = dateVal + FormEngineValidation.parseTime(value.substr(0,index), value.substr(0, 1), 'time');
				} else {
					// only date, no time
					FormEngineValidation.lastTime = FormEngineValidation.parseDate(value, value.substr(0, 1));
				}
		}
		FormEngineValidation.lastTime += add * 24 * 60 * 60;
		return FormEngineValidation.lastTime;
	};

	/**
	 * Parse date value
	 *
	 * @param {String} value
	 * @param {String} command
	 * @returns {*}
	 */
	FormEngineValidation.parseDate = function(value, command) {
		var today = new Date();
		var values = FormEngineValidation.split(value);
		var add = 0;
		switch (command) {
			case 'd':
			case 't':
			case 'n':
				FormEngineValidation.lastDate = FormEngineValidation.getTimestamp(today);
				if (values.valPol[1]) {
					add = FormEngineValidation.pol(values.valPol[1], FormEngineValidation.parseInt(values.values[1]));
				}
				break;
			case '+':
			case '-':
				if (values.valPol[1]) {
					add = FormEngineValidation.pol(values.valPol[1], FormEngineValidation.parseInt(values.values[1]));
				}
				break;
			default:
				var index = 4;
				if (values.valPol[index]) {
					add = FormEngineValidation.pol(values.valPol[index], FormEngineValidation.parseInt(values.values[index]));
				}
				if (values.values[1] && values.values[1].length > 2) {
					if (values.valPol[2]) {
						add = FormEngineValidation.pol(values.valPol[2], FormEngineValidation.parseInt(values.values[2]));
					}
					var temp = values.values[1];
					values = FormEngineValidation.splitSingle(temp);
				}

				var year = (values.values[3]) ? FormEngineValidation.parseInt(values.values[3]) : FormEngineValidation.getYear(today);
				if ((year >= 0 && year < 38) || (year >= 70 && year < 100) || (year >= 1902 && year < 2038)) {
					if (year < 100) {
						year = (year < 38) ? year += 2000 : year += 1900;
					}
				} else {
					year = FormEngineValidation.getYear(today);
				}
				var usMode = FormEngineValidation.USmode ? 1 : 2;
				var month = (values.values[usMode]) ? FormEngineValidation.parseInt(values.values[usMode]) : today.getUTCMonth() + 1;
				usMode = FormEngineValidation.USmode ? 2 : 1;
				var day = (values.values[usMode]) ? FormEngineValidation.parseInt(values.values[usMode]) : today.getUTCDate();

				var theTime = new Date(parseInt(year), parseInt(month)-1, parseInt(day));

				// Substract timezone offset from client
				FormEngineValidation.lastDate = FormEngineValidation.convertClientTimestampToUTC(FormEngineValidation.getTimestamp(theTime), 0);
		}
		FormEngineValidation.lastDate += add * 24 * 60 * 60;
		return FormEngineValidation.lastDate;
	};

	/**
	 * Parse time value
	 *
	 * @param {String} value
	 * @param {String} command
	 * @param {String} type
	 * @returns {*}
	 */
	FormEngineValidation.parseTime = function(value, command, type) {
		var today = new Date();
		var values = FormEngineValidation.split(value);
		var add = 0;
		switch (command) {
			case 'd':
			case 't':
			case 'n':
				FormEngineValidation.lastTime = FormEngineValidation.getTimeSecs(today);
				if (values.valPol[1]) {
					add = FormEngineValidation.pol(values.valPol[1], FormEngineValidation.parseInt(values.values[1]));
				}
				break;
			case '+':
			case '-':
				if (FormEngineValidation.lastTime == 0) {
					FormEngineValidation.lastTime = FormEngineValidation.getTimeSecs(today);
				}
				if (values.valPol[1]) {
					add = FormEngineValidation.pol(values.valPol[1], FormEngineValidation.parseInt(values.values[1]));
				}
				break;
			default:
				var index = (type == 'timesec') ? 4 : 3;
				if (values.valPol[index]) {
					add = FormEngineValidation.pol(values.valPol[index], FormEngineValidation.parseInt(values.values[index]));
				}
				if (values.values[1] && values.values[1].length > 2) {
					if (values.valPol[2]) {
						add = FormEngineValidation.pol(values.valPol[2], FormEngineValidation.parseInt(values.values[2]));
					}
					var temp = values.values[1];
					values = FormEngineValidation.splitSingle(temp);
				}
				var sec = (values.values[3]) ? FormEngineValidation.parseInt(values.values[3]) : today.getUTCSeconds();
				if (sec > 59) {
					sec = 59;
				}
				var min = (values.values[2]) ? FormEngineValidation.parseInt(values.values[2]) : today.getUTCMinutes();
				if (min > 59) {
					min = 59;
				}
				var hour = (values.values[1]) ? FormEngineValidation.parseInt(values.values[1]) : today.getUTCHours();
				if (hour >= 24) {
					hour = 0;
				}

				var theTime = new Date(FormEngineValidation.getYear(FormEngineValidation.refDate), FormEngineValidation.refDate.getUTCMonth(), FormEngineValidation.refDate.getUTCDate(), hour, min, (( type == 'timesec' ) ? sec : 0));

				// Substract timezone offset from client
				FormEngineValidation.lastTime = FormEngineValidation.convertClientTimestampToUTC(FormEngineValidation.getTimestamp(theTime), 1);
		}
		FormEngineValidation.lastTime += add * 60;
		if (FormEngineValidation.lastTime < 0) {
			FormEngineValidation.lastTime += 24 * 60 * 60;
		}
		return FormEngineValidation.lastTime;
	};

	/**
	 * Parse year value
	 *
	 * @param {String} value
	 * @param {String} command
	 * @returns {*}
	 */
	FormEngineValidation.parseYear = function(value, command) {
		var today = new Date();
		var values = FormEngineValidation.split(value);
		var add = 0;
		switch (command) {
			case 'd':
			case 't':
			case 'n':
				FormEngineValidation.lastYear = FormEngineValidation.getYear(today);
				if (values.valPol[1]) {
					add = FormEngineValidation.pol(values.valPol[1], FormEngineValidation.parseInt(values.values[1]));
				}
				break;
			case '+':
			case '-':
				if (values.valPol[1]) {
					add = FormEngineValidation.pol(values.valPol[1], FormEngineValidation.parseInt(values.values[1]));
				}
				break;
			default:
				if (values.valPol[2]) {
					add = FormEngineValidation.pol(values.valPol[2], FormEngineValidation.parseInt(values.values[2]));
				}
				var year = (values.values[1]) ? FormEngineValidation.parseInt(values.values[1]) : FormEngineValidation.getYear(today);
				if ((year >= 0 && year < 38) || (year >= 70 && year<100) || (year >= 1902 && year < 2038)) {
					if (year < 100) {
						year = (year < 38) ? year += 2000 : year += 1900;
					}
				} else {
					year = FormEngineValidation.getYear(today);
				}
				FormEngineValidation.lastYear = year;
		}
		FormEngineValidation.lastYear += add;
		return FormEngineValidation.lastYear;
	};

	/**
	 * Get year from date object
	 *
	 * @param {Date} timeObj
	 * @returns {?number}
	 */
	FormEngineValidation.getYear = function(timeObj) {
		if (timeObj === null) {
			return null;
		}
		return timeObj.getUTCFullYear();
	};

	/**
	 * Get date as timestamp from Date object
	 *
	 * @param {Date} timeObj
	 * @returns {Number}
	 */
	FormEngineValidation.getDate = function(timeObj) {
		var theTime = new Date(FormEngineValidation.getYear(timeObj), timeObj.getUTCMonth(), timeObj.getUTCDate());
		return FormEngineValidation.getTimestamp(theTime);
	};

	/**
	 *
	 * @param {String} foreign
	 * @param {String} value
	 * @returns {Object}
	 */
	FormEngineValidation.pol = function(foreign, value) {
		return eval(((foreign == '-') ? '-' : '') + value);
	};

	/**
	 * Substract timezone offset from client to a timestamp to get UTC-timestamp to be send to server
	 *
	 * @param {Number} timestamp
	 * @param {Number} timeonly
	 * @returns {*}
	 */
	FormEngineValidation.convertClientTimestampToUTC = function(timestamp, timeonly) {
		var timeObj = new Date(timestamp*1000);
		timeObj.setTime((timestamp - timeObj.getTimezoneOffset()*60)*1000);
		if (timeonly) {
			// only seconds since midnight
			return FormEngineValidation.getTime(timeObj);
		} else {
			// seconds since the "unix-epoch"
			return FormEngineValidation.getTimestamp(timeObj);
		}
	};

	/**
	 * Parse date string or object and return unix timestamp
	 *
	 * @param {(String|Date)} timeObj
	 * @returns {Number}
	 */
	FormEngineValidation.getTimestamp = function(timeObj) {
		return Date.parse(timeObj)/1000;
	};

	/**
	 * Seconds since midnight
	 *
	 * @param timeObj
	 * @returns {*}
	 */
	FormEngineValidation.getTime = function(timeObj) {
		return timeObj.getUTCHours() * 60 * 60 + timeObj.getUTCMinutes() * 60 + FormEngineValidation.getSecs(timeObj);
	};

	/**
	 *
	 * @param timeObj
	 * @returns {Number}
	 */
	FormEngineValidation.getSecs = function(timeObj) {
		return timeObj.getUTCSeconds();
	};

	/**
	 *
	 * @param timeObj
	 * @returns {Number}
	 */
	FormEngineValidation.getTimeSecs = function(timeObj) {
		return timeObj.getHours() * 60 * 60 + timeObj.getMinutes() * 60 + timeObj.getSeconds();
	};

	/**
	 * Find tab by field and mark it as has-validation-error
	 *
	 * @param {Object} $element
	 */
	FormEngineValidation.markParentTab = function($element) {
		var $panes = $element.parents('.tab-pane');
		$panes.each(function() {
			var $pane = $(this);
			var id = $pane.attr('id');
			$(document)
				.find('a[href="#' + id + '"]')
				.closest('.t3js-tabmenu-item')
				.addClass('has-validation-error');
		});
	};

	/**
	 *
	 * @param value
	 * @returns {{values: Array, pointer: number}}
	 */
	FormEngineValidation.splitSingle = function(value) {
		var theVal = '' + value;
		var result = {
			values: [],
			pointer: 3
		};
		result.values[1] = theVal.substr(0,2);
		result.values[2] = theVal.substr(2,2);
		result.values[3] = theVal.substr(4,10);
		return result;
	};

	/**
	 *
	 * @param theStr1
	 * @param delim
	 * @param index
	 * @returns {*}
	 */
	FormEngineValidation.splitStr = function(theStr1, delim, index) {
		var theStr = '' + theStr1;
		var lengthOfDelim = delim.length;
		var sPos = -lengthOfDelim;
		if (index < 1) {
			index = 1;
		}
		for (var a = 1; a < index; a++) {
			sPos = theStr.indexOf(delim, sPos + lengthOfDelim);
			if (sPos == -1) {
				return null;
			}
		}
		var ePos = theStr.indexOf(delim, sPos + lengthOfDelim);
		if (ePos == -1) {
			ePos = theStr.length;
		}
		return (theStr.substring(sPos + lengthOfDelim, ePos));
	};

	/**
	 *
	 * @param value
	 * @returns {{values: Array, valPol: Array, pointer: number, numberMode: number, theVal: string}}
	 */
	FormEngineValidation.split = function(value) {
		var result = {
			values: [],
			valPol: [],
			pointer: 0,
			numberMode: 0,
			theVal: ''
		};
		value += ' ';
		for (var a=0; a < value.length; a++) {
			var theChar = value.substr(a, 1);
			if (theChar < '0' || theChar > '9') {
				if (result.numberMode) {
					result.pointer++;
					result.values[result.pointer] = result.theVal;
					result.theVal = '';
					result.numberMode = 0;
				}
				if (theChar == '+' || theChar == '-') {
					result.valPol[result.pointer + 1] = theChar;
				}
			} else {
				result.theVal += theChar;
				result.numberMode = 1;
			}
		}
		return result;
	};

	FormEngineValidation.registerReady = function() {
		$(function() {
			FormEngineValidation.initialize();
			// Start first validation after one second, because all fields are initial empty (typo3form.fieldSet)
			window.setTimeout(function() {
				FormEngineValidation.validate();
			}, 1000);
		});
	};

	FormEngine.Validation = FormEngineValidation;

	return FormEngine.Validation;
});
