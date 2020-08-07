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

import $ from 'jquery';
import moment from 'moment';
import PersistentStorage = require('./Storage/Persistent');

/**
 * Module: TYPO3/CMS/Backend/DateTimePicker
 * contains all logic for the date time picker used in FormEngine
 * and EXT:belog and EXT:scheduler
 */
class DateTimePicker {
  private fieldSelector: string = '.t3js-datetimepicker';
  private format: string = (opener != null && typeof opener.top.TYPO3 !== 'undefined' ? opener.top : top)
    .TYPO3.settings.DateTimePicker.DateFormat;

  /**
   * Format a given date for the hidden FormEngine field
   *
   * Format the value for the hidden field that is passed on to the backend, i.e. most likely DataHandler.
   * The format for that is the timestamp for time fields, and a full-blown ISO-8601 timestamp for all date-related fields.
   *
   * @param {moment} date
   * @param {string} type
   * @returns {string}
   */
  private static formatDateForHiddenField(date: any, type: string): string {
    if (type === 'time' || type === 'timesec') {
      date.year(1970).month(0).date(1);
    }
    return date.format();
  }

  constructor(selector?: string) {
    $((): void => {
      this.initialize(selector);
    });
  }

  /**
   * initialize date fields to add a datepicker to each field
   * note: this function can be called multiple times (e.g. after AJAX requests) because it only
   * applies to fields which haven't been used yet.
   */
  public initialize(selector?: string): void {
    // fetch the date time fields that haven't been initialized yet
    const $dateTimeFields = $(selector || this.fieldSelector).filter((index: number, element: HTMLElement): boolean => {
      return typeof $(element).data('DateTimePicker') === 'undefined';
    });

    if ($dateTimeFields.length > 0) {
      require(['twbs/bootstrap-datetimepicker'], (): void => {
        let userLocale = PersistentStorage.get('lang');
        // Fix our made up locale "ch"
        if (userLocale === 'ch') {
          userLocale = 'zh-cn';
        }
        const setLocale = userLocale ? moment.locale(userLocale) : '';

        // initialize the datepicker on each selected element
        $dateTimeFields.each((index: number, element: HTMLElement): void => {
          this.initializeField($(element), setLocale);
        });

        $dateTimeFields.on('blur', (e: JQueryEventObject): void => {
          const $element = $(e.currentTarget);
          const $hiddenField = $element.parent().parent().find('input[type="hidden"]');

          if ($element.val() === '') {
            $hiddenField.val('');
          } else {
            const type = $element.data('dateType');
            const format = $element.data('DateTimePicker').format();
            const date = moment.utc($element.val(), format);
            if (date.isValid()) {
              $hiddenField.val(DateTimePicker.formatDateForHiddenField(date, type));
            } else {
              $element.val(DateTimePicker.formatDateForHiddenField(moment.utc($hiddenField.val()), type));
            }
          }
        });

        // on datepicker change, write the selected date with the timezone offset to the hidden field
        $dateTimeFields.on('dp.change', (e: any): void => {
          const $element = $(e.currentTarget);
          const $hiddenField = $element.parent().parent().find('input[type=hidden]');
          const type = $element.data('dateType');
          let value = '';

          if ($element.val() !== '') {
            value = DateTimePicker.formatDateForHiddenField(e.date.utc(), type);
          }
          $hiddenField.val(value);

          $(document).trigger('formengine.dp.change', [$element]);
        });
      });
    }
  }

  /**
   * Initialize a single field
   *
   * @param {JQuery} $element
   * @param {string} locale
   */
  private initializeField($element: JQuery, locale: string): void {
    const format = this.format;
    const type = $element.data('dateType');
    const options = {
      format: '',
      locale: '',
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
        clear: 'fa fa-trash',
      },
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
      default:
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
  }
}

export = new DateTimePicker();
