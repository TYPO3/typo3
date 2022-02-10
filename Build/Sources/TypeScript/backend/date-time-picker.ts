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

import flatpickr from 'flatpickr/flatpickr.min';
import moment from 'moment';
import PersistentStorage from './storage/persistent';
import ThrottleEvent from '@typo3/core/event/throttle-event';

interface FlatpickrInputElement extends HTMLInputElement {
  _flatpickr: any;
}

/**
 * Module: @typo3/backend/date-time-picker
 * contains all logic for the date time picker used in FormEngine
 * and EXT:belog and EXT:scheduler
 */
class DateTimePicker {
  private format: string = (typeof opener?.top?.TYPO3 !== 'undefined' ? opener.top : top).TYPO3.settings.DateTimePicker.DateFormat;

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

  /**
   * initialize date fields to add a datepicker to each field
   * note: this function can be called multiple times (e.g. after AJAX requests) because it only
   * applies to fields which haven't been used yet.
   */
  public initialize(element: HTMLInputElement): void {
    if (!(element instanceof HTMLInputElement) || typeof element.dataset.datepickerInitialized !== 'undefined') {
      return;
    }

    let userLocale = PersistentStorage.get('lang');
    if (userLocale === '') {
      userLocale = 'default';
    } else if (userLocale === 'ch') {
      // Fix our made up locale "ch"
      userLocale = 'zh';
    }

    element.dataset.datepickerInitialized = '1';
    import('flatpickr/locales').then((): void => {
      this.initializeField(element, userLocale);
    });
  }

  /**
   * Initialize a single field
   *
   * @param {HTMLInputElement} inputElement
   * @param {string} locale
   */
  private initializeField(inputElement: HTMLInputElement, locale: string): void {
    const scrollEvent = this.getScrollEvent();
    const options = this.getDateOptions(inputElement);
    options.locale = locale;
    options.onOpen = [
      (): void => {
        scrollEvent.bindTo(document.querySelector('.t3js-module-body'))
      }
    ];
    options.onClose = (): void => {
      scrollEvent.release();
    };

    // initialize the date time picker on this element
    const dateTimePicker = flatpickr(inputElement, options);

    inputElement.addEventListener('input', (): void => {
      // Update selected date in picker
      const value = dateTimePicker._input.value
      const parsedDate = dateTimePicker.parseDate(value)
      const formattedDate = dateTimePicker.formatDate(parsedDate, dateTimePicker.config.dateFormat)

      if (value === formattedDate) {
        dateTimePicker.setDate(value);
      }
    });

    inputElement.addEventListener('change', (e: Event): void => {
      e.stopImmediatePropagation();

      const target = (e.target as FlatpickrInputElement);
      const hiddenField = inputElement.parentElement.parentElement.querySelector('input[type="hidden"]') as HTMLInputElement;

      if (target.value !== '') {
        const type = target.dataset.dateType;
        const date = moment.utc(target.value, target._flatpickr.config.dateFormat);
        if (date.isValid()) {
          hiddenField.value = DateTimePicker.formatDateForHiddenField(date, type);
        } else {
          target.value = DateTimePicker.formatDateForHiddenField(moment.utc(hiddenField.value), type);
        }
      } else {
        hiddenField.value = '';
      }

      target.dispatchEvent(new Event('formengine.dp.change'));
    });
  }

  /**
   * Due to some whack CSS the scrollPosition of the document stays 0 which renders a stuck date time picker.
   * Because of this the position is recalculated on scrolling `.t3js-module-body`.
   *
   * @return {ThrottleEvent}
   */
  private getScrollEvent(): ThrottleEvent {
    return new ThrottleEvent('scroll', (): void => {
      const activeFlatpickrElement = document.querySelector('.flatpickr-input.active') as FlatpickrInputElement;
      if (activeFlatpickrElement === null) {
        return;
      }

      const bounds = activeFlatpickrElement.getBoundingClientRect();
      const additionalOffset = 2;
      const calendarHeight = activeFlatpickrElement._flatpickr.calendarContainer.offsetHeight;
      const distanceFromBottom = window.innerHeight - bounds.bottom
      const showOnTop = distanceFromBottom < calendarHeight && bounds.top > calendarHeight;

      let newPosition;
      let arrowClass;
      if (showOnTop) {
        newPosition = bounds.y - calendarHeight - additionalOffset;
        arrowClass = 'arrowBottom';
      } else {
        newPosition = bounds.y + bounds.height + additionalOffset;
        arrowClass = 'arrowTop';
      }

      activeFlatpickrElement._flatpickr.calendarContainer.style.top = newPosition + 'px';
      activeFlatpickrElement._flatpickr.calendarContainer.classList.remove('arrowBottom', 'arrowTop');
      activeFlatpickrElement._flatpickr.calendarContainer.classList.add(arrowClass);
    }, 15);
  }

  /**
   * Initialize a single field
   *
   * @param {HTMLInputElement} inputElement
   */
  private getDateOptions(inputElement: HTMLInputElement): { [key: string]: any } {
    const format = this.format;
    const type = inputElement.dataset.dateType;
    const options = {
      allowInput: true,
      dateFormat: '',
      defaultDate: inputElement.value,
      enableSeconds: false,
      enableTime: false,
      formatDate: (date: Date, format: string) => {
        return moment(date).format(format);
      },
      parseDate: (datestr: string, format: string): Date => {
        return moment(datestr, format, true).toDate();
      },
      maxDate: '',
      minDate: '',
      minuteIncrement: 1,
      noCalendar: false,
      weekNumbers: true,
    };

    // set options based on type
    switch (type) {
      case 'datetime':
        options.dateFormat = format[1];
        options.enableTime = true;
        break;
      case 'date':
        options.dateFormat = format[0];
        break;
      case 'time':
        options.dateFormat = 'HH:mm';
        options.enableTime = true;
        options.noCalendar = true;
        break;
      case 'timesec':
        options.dateFormat = 'HH:mm:ss';
        options.enableSeconds = true;
        options.enableTime = true;
        options.noCalendar = true;
        break;
      case 'year':
        options.dateFormat = 'Y';
        break;
      default:
    }

    if (inputElement.dataset.dateMindate !== 'undefined') {
      options.minDate = inputElement.dataset.dateMindate;
    }
    if (inputElement.dataset.dateMaxdate !== 'undefined') {
      options.maxDate = inputElement.dataset.dateMaxdate;
    }

    return options;
  }
}

export default new DateTimePicker();
