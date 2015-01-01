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
 * contains all logic for the date time picker used in FormEngine
 * and EXT:belog and EXT:scheduler
 */
define('TYPO3/CMS/Backend/DateTimePicker', ['jquery'], function ($) {

	var DateTimePicker = {
		options: {
			fieldSelector: '.t3js-datetimepicker',
			format: (opener ? opener.top : top).TYPO3.settings.DateTimePicker.DateFormat
		}
	};

	/**
	 * initialize date fields to add a datepicker to each field
	 * note: this function can be called multiple times (e.g. after AJAX requests) because it only
	 * applies to fields which haven't been used yet.
	 */
	DateTimePicker.initialize = function() {
		// fetch the date time fields that heven't been initialized yet
		var $dateTimeFields = $(DateTimePicker.options.fieldSelector).filter(function() {
			return ($(this).data('DateTimePicker') == undefined);
		});

		if ($dateTimeFields.length > 0) {
			require(['moment', 'twbs/bootstrap-datetimepicker'], function(moment) {

				// initialize the datepicker on each selected element
				$dateTimeFields.each(function() {
					var $container = $(this);
					var $currentElement = $container.find('.form-control');
					var format = DateTimePicker.options.format;
					var isDateTimeField = $currentElement.hasClass('tceforms-datetimefield') || $currentElement.hasClass('datetime');
					var isDateField = $currentElement.hasClass('tceforms-datefield') || $currentElement.hasClass('date');
					var isTimeField = $currentElement.hasClass('tceforms-timefield') || $currentElement.hasClass('time');
					var isTimeSecField = $currentElement.hasClass('tceforms-timesecfield');
					var isYearField = $currentElement.hasClass('tceforms-timesecfield');

					var options = {
						pick12HourFormat: false,
						pickDate: true,
						pickTime: true,
						useSeconds: false,
						sideBySide: true,
						icons: {
							time: 'fa fa-clock-o',
							date: 'fa fa-calendar',
							up: 'fa fa-arrow-up',
							down: 'fa fa-arrow-down'
						}
					};

					if (isDateTimeField) {
						options.format = format[1];
					}
					if (isDateField) {
						options.format = format[0];
						options.pickTime = false;
					}
					if (isTimeSecField) {
						options.format = 'hh:mm:ss';
						options.pickDate = false;
						options.useSeconds = true;
					}
					if (isTimeField) {
						options.pickDate = false;
						options.format = 'hh:mm';
					}
					if (isYearField) {
						options.format = 'YYYY';
						options.pickDate = true;
						options.pickTime = false;
					}

					// initialize the date time picker on this element
					$container.datetimepicker(options);
				});

				$dateTimeFields.on('blur', '.form-control', function(event) {
					var $target = $(event.target);
					var $datePicker = $target.closest(DateTimePicker.options.fieldSelector);
					var $hiddenField = $datePicker.find('input[type=hidden]');
					var calculateTimeZoneOffset = $datePicker.data('date-offset');

					if ($target.val() == '') {
						$hiddenField.val('');
					} else {
						var format = $datePicker.data('DateTimePicker').format;
						var date = moment($target.val(), format);
						if (typeof calculateTimeZoneOffset != 'undefined') {
							var timeZoneOffset = parseInt(calculateTimeZoneOffset);
						} else {
							var timeZoneOffset = date.zone() * 60;
						}

						if (date.isValid()) {
							$hiddenField.val(date.unix() - timeZoneOffset);
						} else {
							date = moment($hiddenField.val() + timeZoneOffset, 'X');
							$target.val(date.format(format));
						}
					}
				});

				// on datepicker change, write the selected date with the timezone offset to the hidden field
				$dateTimeFields.on('dp.change', function(event) {
					var date = event.date;
					var $datePicker = $(event.currentTarget);
					var calculateTimeZoneOffset = $datePicker.data('date-offset');
					if (typeof calculateTimeZoneOffset != 'undefined') {
						var timeZoneOffset = parseInt(calculateTimeZoneOffset);
					} else {
						var timeZoneOffset = date.zone() * 60;
					}
					var $hiddenField = $datePicker.find('input[type=hidden]');
					$hiddenField.val(date.unix() - timeZoneOffset);
				});
			});
		}
	};

	/**
	 * initialize function
	 */
	return function() {
		DateTimePicker.initialize();
		return DateTimePicker;
	}();
});
