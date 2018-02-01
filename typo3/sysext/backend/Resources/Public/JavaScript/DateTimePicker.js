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
 * Module: TYPO3/CMS/Backend/DateTimePicker
 * contains all logic for the date time picker used in FormEngine
 * and EXT:belog and EXT:scheduler
 */
define(['jquery'], function($) {
  "use strict";

  /**
   * @type {{options: {fieldSelector: string, format: *}}}
   * @exports TYPO3/CMS/Backend/DateTimePicker
   */
  var DateTimePicker = {
    options: {
      fieldSelector: '.t3js-datetimepicker',
      format: (opener != null && typeof opener.top.TYPO3 !== 'undefined' ? opener.top : top).TYPO3.settings.DateTimePicker.DateFormat
    }
  };

  /**
   * initialize date fields to add a datepicker to each field
   * note: this function can be called multiple times (e.g. after AJAX requests) because it only
   * applies to fields which haven't been used yet.
   */
  DateTimePicker.initialize = function() {
    // fetch the date time fields that haven't been initialized yet
    var $dateTimeFields = $(DateTimePicker.options.fieldSelector).filter(function() {
      return $(this).data('DateTimePicker') === undefined;
    });

    if ($dateTimeFields.length > 0) {
      require(['moment', 'TYPO3/CMS/Backend/Storage', 'twbs/bootstrap-datetimepicker'], function(moment, Storage) {
        var userLocale = Storage.Persistent.get('lang');
        var setLocale = userLocale ? moment.locale(userLocale) : false;

        // initialize the datepicker on each selected element
        $dateTimeFields.each(function() {
          DateTimePicker.initializeField(moment, $(this), setLocale);
        });

        $dateTimeFields.on('blur', function() {
          var $element = $(this);
          var $hiddenField = $element.parent().parent().find('input[type=hidden]');

          if ($element.val() === '') {
            $hiddenField.val('');
          } else {
            var type = $element.data('dateType');
            var format = $element.data('DateTimePicker').format();
            var date = moment.utc($element.val(), format);
            if (date.isValid()) {
              $hiddenField.val(DateTimePicker.formatDateForHiddenField(date, type));
            } else {
              $element.val(DateTimePicker.formatDateForHiddenField(moment.utc($hiddenField.val()), type));
            }
          }
        });

        // on datepicker change, write the selected date with the timezone offset to the hidden field
        $dateTimeFields.on('dp.change', function(evt) {
          var $element = $(this);
          var $hiddenField = $element.parent().parent().find('input[type=hidden]');
          var type = $element.data('dateType');
          var value = '';

          if ($element.val() !== '') {
            value = DateTimePicker.formatDateForHiddenField(evt.date.utc(), type);
          }
          $hiddenField.val(value);

          $(document).trigger('formengine.dp.change', [$(this)]);
        });
      });
    }
  };

  /**
   * Initialize a single field
   *
   * @param {moment} moment
   * @param {object} $element
   * @param {string} locale
   */
  DateTimePicker.initializeField = function(moment, $element, locale) {
    var format = DateTimePicker.options.format;
    var type = $element.data('dateType');
    var options = {
      sideBySide: true,
      showTodayButton: true,
      toolbarPlacement: 'bottom',
      icons: {
        time: 'fa fa-clock-o',
        date: 'fa fa-calendar',
        up: 'fa fa-chevron-up',
        down: 'fa fa-chevron-down',
        previous: 'fa fa-chevron-left',
        next: 'fa fa-chevron-right',
        today: 'fa fa-calendar-o',
        clear: 'fa fa-trash'
      }
    };

    // set options based on type
    switch (type) {
      case 'datetime':
        options.format = format[1];
        break;
      case 'date':
        options.format = format[0];
        break;
      case 'time':
        options.format = 'HH:mm';
        break;
      case 'timesec':
        options.format = 'HH:mm:ss';
        break;
      case 'year':
        options.format = 'YYYY';
        break;
    }

    // datepicker expects the min and max dates to be formatted with options.format but unix timestamp given
    if ($element.data('dateMindate')) {
      $element.data('dateMindate', moment.unix($element.data('dateMindate')).format(options.format));
    }
    if ($element.data('dateMaxdate')) {
      $element.data('dateMaxdate', moment.unix($element.data('dateMaxdate')).format(options.format));
    }

    if (locale) {
      options.locale = locale;
    }

    // initialize the date time picker on this element
    $element.datetimepicker(options);
  };

  /**
   * Format a given date for the hidden FormEngine field
   *
   * Format the value for the hidden field that is passed on to the backend, i.e. most likely DataHandler.
   * The format for that is the timestamp for time fields, and a full-blown ISO-8601 timestamp for all date-related fields.
   *
   * @param {moment} date
   * @param {string} type Type of the date
   * @returns {string}
   */
  DateTimePicker.formatDateForHiddenField = function(date, type) {
    if (type === 'time' || type === 'timesec') {
      date.year(1970).month(0).date(1);
    }
    return date.format();
  };

  $(DateTimePicker.initialize);
  return DateTimePicker;
});
