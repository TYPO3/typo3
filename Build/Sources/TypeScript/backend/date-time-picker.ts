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

import flatpickr from 'flatpickr';
import ShortcutButtonsPlugin from 'shortcut-buttons-flatpickr';
import { DateTime } from 'luxon';
import ThrottleEvent from '@typo3/core/event/throttle-event';
import type { PostValidationEvent } from '@typo3/backend/form-engine-validation';
import '@typo3/backend/input/clearable';

const ISO8601_LOCALTIME = 'ISO8601_LOCALTIME';

interface FlatpickrInputElement extends HTMLInputElement {
  _flatpickr: flatpickr.Instance;
}

/**
 * Module: @typo3/backend/date-time-picker
 * contains all logic for the date time picker used in FormEngine, EXT:belog and EXT:scheduler
 */
class DateTimePicker {
  private readonly format: string = (typeof opener?.top?.TYPO3 !== 'undefined' ? opener.top : top).TYPO3.settings.DateTimePicker.DateFormat;

  /**
   * initialize date fields to add a datepicker to each field
   * note: this function can be called multiple times (e.g. after AJAX requests) because it only
   * applies to fields which haven't been used yet.
   */
  public initialize(element: HTMLInputElement): void {
    if (!(element instanceof HTMLInputElement) || typeof element.dataset.datepickerInitialized !== 'undefined') {
      return;
    }

    let userLocale = document.documentElement.lang;
    if (!userLocale || userLocale === 'en') {
      // flatpickr's English localization is "default"
      userLocale = 'default';
    } else if (userLocale === 'ch') {
      // Fix our made up locale "ch"
      userLocale = 'zh';
    }

    element.dataset.datepickerInitialized = '1';
    import('flatpickr/dist/l10n').then((): void => {
      this.initializeField(element, userLocale as flatpickr.Options.LocaleKey);
    });
  }

  /**
   * Initialize a single field
   */
  private initializeField(inputElement: HTMLInputElement, locale: flatpickr.Options.LocaleKey): void {
    const scrollEvent = this.getScrollEvent();
    const options = this.getDateOptions(inputElement);
    options.locale = locale;
    options.onOpen = [
      (): void => {
        scrollEvent.bindTo(document.querySelector('.t3js-module-body'));
      }
    ];
    options.onClose = (): void => {
      scrollEvent.release();
    };

    // initialize the date time picker on this element
    const dateTimePicker = flatpickr(inputElement, options);
    if (dateTimePicker.altInput instanceof HTMLInputElement) {
      // Explicitly handle clearing input (invoked by clearable control) if altInput is available
      dateTimePicker.input.addEventListener('typo3:internal:clear', (): void => {
        // @todo The `typo3:internal:clear` event is a hack and must fall again
        dateTimePicker.clear();
      });
    }

    dateTimePicker._input.addEventListener('change', (e: Event): void => {
      // Update selected date in picker after manually entering a value
      const value = (e.target as HTMLInputElement).value;
      const parsedDate: Date = dateTimePicker.parseDate(value, dateTimePicker.config.altFormat);

      dateTimePicker.setDate(parsedDate, true);
    });

    dateTimePicker._input.addEventListener('keyup', (e: KeyboardEvent): void => {
      if (e.key === 'Escape') {
        dateTimePicker.close();
      }
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
      const distanceFromBottom = window.innerHeight - bounds.bottom;
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
  private getDateOptions(inputElement: HTMLInputElement): flatpickr.Options.Options {

    const format = this.format;
    const type = inputElement.dataset.dateType;
    const now = new Date();
    const options: flatpickr.Options.Options = {
      altFormat: '',
      allowInput: true,
      altInput: true,
      ariaDateFormat: 'DDDD',
      // We configure a dummy dateFormat token and use luxon parsing/formatting
      // internally, as flatpickr cannot be configured to ignore timezones
      // (in order to behave similar to <input type=datetime-local>)
      dateFormat: ISO8601_LOCALTIME,
      defaultHour: now.getHours(),
      defaultMinute: now.getMinutes(),
      enableSeconds: false,
      enableTime: false,
      formatDate: (date: Date, format: string) => {
        const dt = DateTime.fromJSDate(date);
        if (format === ISO8601_LOCALTIME) {
          return dt.toISO({ suppressMilliseconds: true, includeOffset: false });
        }
        return dt.toFormat(format);
      },
      parseDate: (currentDateString: string, format: string): Date => {
        if (format === ISO8601_LOCALTIME) {
          const localDate = DateTime.fromISO(currentDateString);
          if (!localDate.isValid) {
            throw new Error('Invalid ISO8601 date: ' + currentDateString);
          }
          return localDate.toJSDate();
        }
        return DateTime.fromFormat(currentDateString, format).toJSDate();
      },
      onReady: (dates: Date[], currentDateString: string, self: flatpickr.Instance): void => {
        if (self.altInput !== undefined) {
          // Transfer the id of the original input to the altInput
          // This is a hack for the picker button - one would use `{wrap: true}` in flatpickr, but this apparently
          // collides with using altInput – sigh.
          self.altInput.id = self.input.id;
          self.input.removeAttribute('id');
          self.altInput.clearable();
          if (self.input.dataset.formengineInputName !== undefined) {
            self.altInput.dataset.formengineDatepickerRealInputName = self.input.dataset.formengineInputName;
          }

          // Register a custom event handler for `t3-formengine-postfieldvalidation` to be able to toggle the `has-error` class on the mirrored field
          self.altInput.form.addEventListener('t3-formengine-postfieldvalidation', (e: CustomEvent<PostValidationEvent>): void => {
            if (e.detail.field === self.input) {
              self.altInput.classList.toggle('has-error', !e.detail.isValid);
            }
          });
        }
      },
      onChange: (dates: Date[], currentDateString: string, self: flatpickr.Instance): void => {
        self.input.dispatchEvent(new Event('formengine.dp.change'));
      },
      maxDate: '',
      minDate: '',
      minuteIncrement: 1,
      noCalendar: false,
      showMonths: 1,
      // Disable month dropdown in `time`, `timesec` or `year` fields to fix:
      //   `TypeError: Cannot set properties of undefined (setting 'tabIndex')` in flatpickr
      // Rproducible when entering an arbitrary time (e.g. "13:30")
      // and pressing the TAB key in a time field.
      monthSelectorType: type.startsWith('date') ? 'dropdown' : 'static',
      weekNumbers: true,
      time_24hr: !Intl.DateTimeFormat(navigator.language, { hour: 'numeric' }).resolvedOptions().hour12,
      plugins: [
        ShortcutButtonsPlugin({
          theme: 'typo3',
          button: [
            {
              label: top.TYPO3.lang['labels.datepicker.today'] || 'Today'
            },
          ],
          onClick: (index: number, fp: flatpickr.Instance) => {
            fp.setDate(new Date(), true);
          }
        })
      ],
    };

    // set options based on type
    switch (type) {
      case 'datetime':
        options.altFormat = format[1];
        options.enableTime = true;
        break;
      case 'date':
        options.altFormat = format[0];
        break;
      case 'time':
        options.altFormat = 'HH:mm';
        options.enableTime = true;
        options.noCalendar = true;
        break;
      case 'timesec':
        options.altFormat = 'HH:mm:ss';
        options.enableSeconds = true;
        options.enableTime = true;
        options.noCalendar = true;
        break;
      case 'year':
        options.altFormat = 'yyyy';
        break;
      default:
    }

    if (inputElement.dataset.dateMinDate !== undefined) {
      options.minDate = options.parseDate(inputElement.dataset.dateMinDate, ISO8601_LOCALTIME);
      options.minDate.setSeconds(0);
    }
    if (inputElement.dataset.dateMaxDate !== undefined) {
      options.maxDate = options.parseDate(inputElement.dataset.dateMaxDate, ISO8601_LOCALTIME);
      options.maxDate.setSeconds(59);
    }

    return options;
  }
}

export default new DateTimePicker();
