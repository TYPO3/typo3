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
define([
  'jquery',
  'moment',
  'TYPO3/CMS/Backend/Hashing/Md5',
  'TYPO3/CMS/Backend/DocumentSaveActions',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Severity'
], function($, moment, Md5, DocumentSaveActions, Modal, Severity) {

  /**
   * The main FormEngineValidation object
   *
   * @type {{rulesSelector: string, inputSelector: string, markerSelector: string, groupFieldHiddenElement: string, relatedFieldSelector: string, errorClass: string, lastYear: number, lastDate: number, lastTime: number, USmode: number, passwordDummy: string}}
   * @exports TYPO3/CMS/Backend/FormEngineValidation
   */
  var FormEngineValidation = {
    rulesSelector: '[data-formengine-validation-rules]',
    inputSelector: '[data-formengine-input-params]',
    markerSelector: '.t3js-formengine-validation-marker',
    groupFieldHiddenElement: '.t3js-formengine-field-group input[type=hidden]',
    relatedFieldSelector: '[data-relatedfieldname]',
    errorClass: 'has-error',
    lastYear: 0,
    lastDate: 0,
    lastTime: 0,
    USmode: 0,
    passwordDummy: '********'
  };

  /**
   * @type {Map<string, Function>}
   */
  const customEvaluations = new Map();

  /**
   * Initialize validation for the first time
   */
  FormEngineValidation.initialize = function() {
    $(document).find('.' + FormEngineValidation.errorClass).removeClass(FormEngineValidation.errorClass);

    // Initialize input fields
    FormEngineValidation.initializeInputFields().promise().done(function() {
      // Bind to field changes
      $(document).on('change', FormEngineValidation.rulesSelector, function() {
        FormEngineValidation.validateField(this);
        FormEngineValidation.markFieldAsChanged(this);
      });

      FormEngineValidation.registerSubmitCallback();
    });

    var today = new Date();
    FormEngineValidation.lastYear = FormEngineValidation.getYear(today);
    FormEngineValidation.lastDate = FormEngineValidation.getDate(today);
    FormEngineValidation.lastTime = 0;
    FormEngineValidation.validate();
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
        value = FormEngineValidation.formatValue(evalList[i], value, config);
      }
      // Prevent password fields to be overwritten with original value
      if (value.length && $humanReadableField.attr('type') !== 'password') {
        $humanReadableField.val(value);
      }
    }
    $humanReadableField.data('main-field', fieldName);
    $humanReadableField.data('config', config);
    $humanReadableField.on('change', function() {
      FormEngineValidation.updateInputField($(this).attr('data-formengine-input-name'));
    });

    // add the attribute so that acceptance tests can know when the field initialization has completed
    $humanReadableField.attr('data-formengine-input-initialized', 'true');
  };

  /**
   * @param {string} name
   * @param {Function} handler
   */
  FormEngineValidation.registerCustomEvaluation = function(name, handler) {
    if (!customEvaluations.has(name)) {
      customEvaluations.set(name, handler);
    }
  }

  /**
   * Format field value
   *
   * @param {String} type
   * @param {String} value
   * @param {Object} config
   * @returns {String}
   */
  FormEngineValidation.formatValue = function(type, value, config) {
    var theString = '';
    var parsedInt, theTime;
    switch (type) {
      case 'date':
        // poor man’s ISO-8601 detection: if we have a "-" in it, it apparently is not an integer.
        if (value.toString().indexOf('-') > 0) {
          var date = moment.utc(value);
          if (FormEngineValidation.USmode) {
            theString = date.format('MM-DD-YYYY');
          } else {
            theString = date.format('DD-MM-YYYY');
          }
        } else {
          parsedInt = value * 1;
          if (!parsedInt) {
            return '';
          }
          theTime = new Date(parsedInt * 1000);
          const day = (theTime.getUTCDate()).toString(10).padStart(2, '0');
          const month = (theTime.getUTCMonth() + 1).toString(10).padStart(2, '0');
          const year = this.getYear(theTime);
          if (FormEngineValidation.USmode) {
            theString = month + '-' + day + '-' + year;
          } else {
            theString = day + '-' + month + '-' + year;
          }
        }
        break;
      case 'datetime':
        if (value.toString().indexOf('-') <= 0 && !parseInt(value)) {
          return '';
        }
        theString = FormEngineValidation.formatValue('time', value, config) + ' ' + FormEngineValidation.formatValue('date', value, config);
        break;
      case 'time':
      case 'timesec':
        var dateValue;
        if (value.toString().indexOf('-') > 0) {
          dateValue = moment.utc(value);
        } else {
          parsedInt = parseInt(value);
          if (!parsedInt && value.toString() !== '0') {
            return '';
          }
          dateValue = moment.unix(parsedInt).utc();
        }
        if (type === 'timesec') {
          theString = dateValue.format('HH:mm:ss');
        } else {
          theString = dateValue.format('HH:mm');
        }
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
      // After updating the value of the main field, dispatch a "change" event to inform e.g. the "RequestUpdate"
      // component, which always listens to the main field instead of the "human readable field", about it.
      $mainField.get(0).dispatchEvent(new Event('change'));
      $humanReadableField.val(formattedValue);
    }
  };

  /**
   * Run validation for field
   *
   * @param {HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement|jQuery} field
   * @param {String} [value=$field.val()]
   * @returns {String}
   */
  FormEngineValidation.validateField = function(field, value) {
    if (field instanceof $) {
      field = field.get(0);
    }

    value = value || field.value || '';

    if (typeof field.dataset.formengineValidationRules === 'undefined') {
      return value;
    }

    var rules = JSON.parse(field.dataset.formengineValidationRules);
    var markParent = false;
    var selected = 0;
    // keep the original value, validateField should not alter it
    var returnValue = value;
    var $relatedField;
    var minItems;
    var maxItems;

    if (!$.isArray(value)) {
      value = FormEngineValidation.ltrim(value);
    }

    $.each(rules, function(k, rule) {
      if (markParent) {
        // abort any further validation as validating the field already failed
        return false;
      }
      switch (rule.type) {
        case 'required':
          if (value === '') {
            markParent = true;
            field.closest(FormEngineValidation.markerSelector).classList.add(FormEngineValidation.errorClass);
          }
          break;
        case 'range':
          if (value !== '') {
            if (rule.minItems || rule.maxItems) {
              $relatedField = $(document).find('[name="' + field.dataset.relatedfieldname + '"]');
              if ($relatedField.length) {
                selected = FormEngineValidation.trimExplode(',', $relatedField.val()).length;
              } else {
                selected = field.value;
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
            if (typeof rule.lower !== 'undefined') {
              var minValue = rule.lower * 1;
              if (!isNaN(minValue) && value < minValue) {
                markParent = true;
              }
            }
            if (typeof rule.upper !== 'undefined') {
              var maxValue = rule.upper * 1;
              if (!isNaN(maxValue) && value > maxValue) {
                markParent = true;
              }
            }
          }
          break;
        case 'select':
        case 'category':
          if (rule.minItems || rule.maxItems) {
            $relatedField = $(document).find('[name="' + field.dataset.relatedfieldname + '"]');
            if ($relatedField.length) {
              selected = FormEngineValidation.trimExplode(',', $relatedField.val()).length;
            } else if (field instanceof HTMLSelectElement) {
              selected = field.querySelectorAll('option:checked').length;
            } else {
              selected = field.querySelectorAll('input[value]:checked').length;
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
            selected = FormEngineValidation.trimExplode(',', field.value).length;
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
            selected = FormEngineValidation.trimExplode(',', field.value).length;
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
      }
    });

    const isValid = !markParent;
    field.closest(FormEngineValidation.markerSelector).classList.toggle(FormEngineValidation.errorClass, !isValid);
    FormEngineValidation.markParentTab($(field), isValid);

    $(document).trigger('t3-formengine-postfieldvalidation');

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
          var theChar = value.substr(a, 1);
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
          if (alpha || num || special) {
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
          // Escape special characters, see https://stackoverflow.com/a/6969486/4828813
          config.is_in = config.is_in.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
          var re = new RegExp('[^' + config.is_in + ']+', 'g');
          newString = theValue.replace(re, '');
        } else {
          newString = theValue;
        }
        returnValue = newString;
        break;
      case 'nospace':
        returnValue = ('' + value).replace(/ /g, '');
        break;
      case 'md5':
        if (value !== '') {
          returnValue = Md5.hash(value);
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
        returnValue = String(value).trim();
        break;
      case 'datetime':
        if (value !== '') {
          theCmd = value.substr(0, 1);
          returnValue = FormEngineValidation.parseDateTime(value);
        }
        break;
      case 'date':
        if (value !== '') {
          theCmd = value.substr(0, 1);
          returnValue = FormEngineValidation.parseDate(value);
        }
        break;
      case 'time':
      case 'timesec':
        if (value !== '') {
          theCmd = value.substr(0, 1);
          returnValue = FormEngineValidation.parseTime(value, command);
        }
        break;
      case 'year':
        if (value !== '') {
          theCmd = value.substr(0, 1);
          returnValue = FormEngineValidation.parseYear(value);
        }
        break;
      case 'null':
        // unknown type null, we ignore it
        break;
      case 'password':
        // password is only a display evaluation, we ignore it
        break;
      default:
        if (typeof TBE_EDITOR.customEvalFunctions !== 'undefined') {
          if (customEvaluations.has(command)) {
            returnValue = customEvaluations.get(command).call(null, value);
          } else if (typeof TBE_EDITOR.customEvalFunctions[command] === 'function') {
            returnValue = TBE_EDITOR.customEvalFunctions[command](value);
          }
        }
    }
    return returnValue;
  };

  /**
   * Validate the complete form
   */
  FormEngineValidation.validate = function(section) {
    if (typeof section === 'undefined' || section instanceof Document) {
      $(document).find(FormEngineValidation.markerSelector + ', .t3js-tabmenu-item')
        .removeClass(FormEngineValidation.errorClass)
        .removeClass('has-validation-error');
    }

    const sectionElement = section || document;
    $(sectionElement).find(FormEngineValidation.rulesSelector).each(function() {
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
          $field.val(newValue);
        }
      }
    });
  };

  /**
   * Helper function to mark a field as changed.
   *
   * @param {HTMLInputElement|HTMLTextAreaElement|jQuery} field
   */
  FormEngineValidation.markFieldAsChanged = function(field) {
    if (field instanceof $) {
      field = field.get(0);
    }
    if (!(field instanceof HTMLElement)) {
      return;
    }
    const paletteField = field.closest('.t3js-formengine-palette-field');
    paletteField.classList.add('has-change');
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
    for (var i = 0; i < items.length; i++) {
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
    var theVal = '' + value,
      returnValue;

    if (!value) {
      return 0;
    }

    returnValue = parseInt(theVal, 10);
    if (isNaN(returnValue)) {
      return 0;
    }
    return returnValue;
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
   * Trims leading whitespace characters
   *
   * @param {String} value
   * @returns {String}
   */
  FormEngineValidation.ltrim = function(value) {
    var theVal = '' + value;
    if (!value) {
      return '';
    }
    return theVal.replace(/^\s+/, '');
  };

  /**
   * Trims trailing whitespace characters
   *
   * @param {String} value
   * @returns {String}
   */
  FormEngineValidation.btrim = function(value) {
    var theVal = '' + value;
    if (!value) {
      return '';
    }
    return theVal.replace(/\s+$/, '');
  };

  /**
   * Parse datetime value
   *
   * @param {String} value
   * @returns {*}
   */
  FormEngineValidation.parseDateTime = function(value) {
      var index = value.indexOf(' ');
      if (index !== -1) {
        var dateVal = FormEngineValidation.parseDate(value.substr(index, value.length));
        FormEngineValidation.lastTime = dateVal + FormEngineValidation.parseTime(value.substr(0, index), 'time');
      } else {
        // only date, no time
        FormEngineValidation.lastTime = FormEngineValidation.parseDate(value);

    }
    return FormEngineValidation.lastTime;
  };

  /**
   * Parse date value
   *
   * @param {String} value
   * @returns {*}
   */
  FormEngineValidation.parseDate = function(value) {
    var today = new Date();
    var values = FormEngineValidation.split(value);

    if (values.values[1] && values.values[1].length > 2) {
      var temp = values.values[1];
      values = FormEngineValidation.splitSingle(temp);
    }

    var year = (values.values[3]) ? FormEngineValidation.parseInt(values.values[3]) : FormEngineValidation.getYear(today);
    var usMode = FormEngineValidation.USmode ? 1 : 2;
    var month = (values.values[usMode]) ? FormEngineValidation.parseInt(values.values[usMode]) : today.getUTCMonth() + 1;
    usMode = FormEngineValidation.USmode ? 2 : 1;

    var day = (values.values[usMode]) ? FormEngineValidation.parseInt(values.values[usMode]) : today.getUTCDate();
    var theTime = moment.utc();
    theTime.year(parseInt(year)).month(parseInt(month) - 1).date(parseInt(day)).hour(0).minute(0).second(0);
    FormEngineValidation.lastDate = theTime.unix();

    return FormEngineValidation.lastDate;
  };

  /**
   * Parse time value
   *
   * @param {String} value
   * @param {String} type
   * @returns {*}
   */
  FormEngineValidation.parseTime = function(value, type) {
    var today = new Date();
    var values = FormEngineValidation.split(value);

    if (values.values[1] && values.values[1].length > 2) {
      var temp = values.values[1];
      values = FormEngineValidation.splitSingle(temp);
    }

    var sec = (values.values[3]) ? FormEngineValidation.parseInt(values.values[3]) : today.getUTCSeconds();
    var min = (values.values[2]) ? FormEngineValidation.parseInt(values.values[2]) : today.getUTCMinutes();
    var hour = (values.values[1]) ? FormEngineValidation.parseInt(values.values[1]) : today.getUTCHours();
    var theTime = moment.utc();
    theTime.year(1970).month(0).date(1).hour(hour).minute(min).second(type === 'timesec' ? sec : 0);

    FormEngineValidation.lastTime = theTime.unix();
    if (FormEngineValidation.lastTime < 0) {
      FormEngineValidation.lastTime += 24 * 60 * 60;
    }
    return FormEngineValidation.lastTime;
  };

  /**
   * Parse year value
   *
   * @param {String} value
   * @returns {*}
   */
  FormEngineValidation.parseYear = function(value) {
    var today = new Date();
    var values = FormEngineValidation.split(value);

    FormEngineValidation.lastYear = (values.values[1]) ? FormEngineValidation.parseInt(values.values[1]) : FormEngineValidation.getYear(today);
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
    var timeObj = new Date(timestamp * 1000);
    timeObj.setTime((timestamp - timeObj.getTimezoneOffset() * 60) * 1000);
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
    return Date.parse(timeObj) / 1000;
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
   * @param {Boolean} isValid
   */
  FormEngineValidation.markParentTab = function($element, isValid) {
    var $panes = $element.parents('.tab-pane');
    $panes.each(function() {
      var $pane = $(this);
      if (isValid) {
        // If incoming element is valid, check for errors in the same sheet
        isValid = $pane.find('.has-error').length === 0;
      }
      var id = $pane.attr('id');
      $(document)
        .find('a[href="#' + id + '"]')
        .closest('.t3js-tabmenu-item')
        .toggleClass('has-validation-error', !isValid);
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
    result.values[1] = theVal.substr(0, 2);
    result.values[2] = theVal.substr(2, 2);
    result.values[3] = theVal.substr(4, 10);
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
    for (var a = 0; a < value.length; a++) {
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

  FormEngineValidation.registerSubmitCallback = function () {
    DocumentSaveActions.getInstance().addPreSubmitCallback(function (e) {
      if ($('.' + FormEngineValidation.errorClass).length > 0) {
        Modal.confirm(
          TYPO3.lang.alert || 'Alert',
          TYPO3.lang['FormEngine.fieldsMissing'],
          Severity.error,
          [
            {
              text: TYPO3.lang['button.ok'] || 'OK',
              active: true,
              btnClass: 'btn-default',
              name: 'ok',
            },
          ]
        ).on('button.clicked', function () {
          Modal.dismiss();
        });

        e.stopImmediatePropagation();
      }
    });
  }

  return FormEngineValidation;
});
