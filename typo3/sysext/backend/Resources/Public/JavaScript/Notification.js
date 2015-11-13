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
 * Module: TYPO3/CMS/Backend/Notification
 * Notification API for the TYPO3 backend
 */
define(['jquery'], function ($) {
	'use strict';

	/**
	 * Severity object
	 *
	 * @type {{notice: number, information: number, info: number, ok: number, warning: number, error: number}}
	 * @exports TYPO3/CMS/Backend/Severity
	 */
	var Severity = {
		notice: -2,
		// @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 9, use info instead of information
		information: -1,
		info: -1,
		ok: 0,
		warning: 1,
		error: 2
	};

	/**
	 * The main Notification object
	 *
	 * @type {{NOTICE: number, INFO: number, OK: number, WARNING: number, ERROR: number, messageContainer: null}}
	 * @exports TYPO3/CMS/Backend/Notification
	 */
	var Notification = {
		NOTICE: -2,
		INFO: -1,
		OK: 0,
		WARNING: 1,
		ERROR: 2,
		messageContainer: null
	};

	/**
	 * Show a notice notification
	 *
	 * @param {String} title The title for the notification
	 * @param {String} message The message for the notification
	 * @param {float} duration Time in seconds to show notification before it disappears, default 5, 0 = sticky
	 *
	 * @public
	 */
	Notification.notice = function(title, message, duration) {
		Notification.showMessage(title, message, Notification.NOTICE, duration);
	};

	/**
	 * Show an info notification
	 *
	 * @param {String} title The title for the notification
	 * @param {String} message The message for the notification
	 * @param {float} duration Time in seconds to show notification before it disappears, default 5, 0 = sticky
	 *
	 * @public
	 */
	Notification.info = function(title, message, duration) {
		Notification.showMessage(title, message, Notification.INFO, duration);
	};

	/**
	 * Show an ok notification
	 *
	 * @param {String} title The title for the notification
	 * @param {String} message The message for the notification
	 * @param {float} duration Time in seconds to show notification before it disappears, default 5, 0 = sticky
	 *
	 * @public
	 */
	Notification.success = function(title, message, duration) {
		Notification.showMessage(title, message, Notification.OK, duration);
	};

	/**
	 * Show a warning notification
	 *
	 * @param {String} title The title for the notification
	 * @param {String} message The message for the notification
	 * @param {float} duration Time in seconds to show notification before it disappears, default 5, 0 = sticky
	 *
	 * @public
	 */
	Notification.warning = function(title, message, duration) {
		Notification.showMessage(title, message, Notification.WARNING, duration);
	};

	/**
	 * Show an error notification
	 *
	 * @param {String} title The title for the notification
	 * @param {String} message The message for the notification
	 * @param {float} duration Time in seconds to show notification before it disappears, default 0, 0 = sticky
	 *
	 * @public
	 */
	Notification.error = function(title, message, duration) {
		duration = duration || 0;
		Notification.showMessage(title, message, Notification.ERROR, duration);
	};

	/**
	 * Show message
	 *
	 * @param {String} title The title for the notification
	 * @param {String} message The message for the notification
	 * @param {int} severity See constants in this object
	 * @param {float} duration Time in seconds to show notification before it disappears, default 5, 0 = sticky
	 *
	 * @private
	 */
	Notification.showMessage = function(title, message, severity, duration) {
		var className = '';
		var icon = '';
		switch (severity) {
			case Notification.NOTICE:
				className = 'notice';
				icon = 'lightbulb-o';
				break;
			case Notification.INFO:
				className = 'info';
				icon = 'info';
				break;
			case Notification.OK:
				className = 'success';
				icon = 'check';
				break;
			case Notification.WARNING:
				className = 'warning';
				icon = 'exclamation';
				break;
			case Notification.ERROR:
				className = 'danger';
				icon = 'times';
				break;
			default:
				className = 'info';
				icon = 'info';
		}

		duration = (typeof duration === 'undefined') ? 5 : parseFloat(duration);

		if (Notification.messageContainer === null) {
			Notification.messageContainer = $('<div id="alert-container"></div>').appendTo('body');
		}
		var $box = $(
			'<div class="alert alert-' + className + ' alert-dismissible fade" role="alert">' +
				'<button type="button" class="close" data-dismiss="alert">' +
					'<span aria-hidden="true"><i class="fa fa-times-circle"></i></span>' +
					'<span class="sr-only">Close</span>' +
				'</button>' +
				'<div class="media">' +
					'<div class="media-left">' +
						'<span class="fa-stack fa-lg">' +
							'<i class="fa fa-circle fa-stack-2x"></i>' +
							'<i class="fa fa-' + icon + ' fa-stack-1x"></i>' +
						'</span>' +
					'</div>' +
					'<div class="media-body">' +
						'<h4 class="alert-title"></h4>' +
						'<p class="alert-message text-pre-wrap"></p>' +
					'</div>' +
				'</div>' +
			'</div>'
		);
		$box.find('.alert-title').text(title);
		$box.find('.alert-message').text(message);
		$box.on('close.bs.alert', function(e) {
			e.preventDefault();
			$(this)
				.clearQueue()
				.queue(function(next) {
					$(this).removeClass('in');
					next();
				})
				.slideUp(function () {
					$(this).remove();
				});
		});
		$box.appendTo(Notification.messageContainer);
		$box.delay('fast')
			.queue(function(next) {
				$(this).addClass('in');
				next();
			});
		// if duration > 0 dismiss alert
		if (duration > 0) {
			$box.delay(duration * 1000)
				.queue(function(next) {
					$(this).alert('close');
					next();
				});
		}
	};

	if (typeof TYPO3.Severity === 'undefined') {
		TYPO3.Severity = Severity;
	}
	if (typeof top.TYPO3.Severity === 'undefined') {
		top.TYPO3.Severity = Severity;
	}
	if (typeof TYPO3.Notification === 'undefined') {
		TYPO3.Notification = Notification;
	}
	if (typeof top.TYPO3.Notification === 'undefined') {
		top.TYPO3.Notification = Notification;
	}

	return Notification;
});
