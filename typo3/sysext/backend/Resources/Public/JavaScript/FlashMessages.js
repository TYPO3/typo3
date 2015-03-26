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
 * FlashMessage rendered by jQuery
 *
 * @author Steffen Kamper <info@sk-typo3.de> (ExtJS)
 * @author Frank Nägler <typo3@naegler.net> (jQuery)
 */

define('TYPO3/CMS/Backend/FlashMessages', ['jquery'], function ($) {
	var Severity = {
		notice: -2,
		// @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 9, use info instead of information
		information: -1,
		info: -1,
		ok: 0,
		warning: 1,
		error: 2
	};

	var Flashmessage = {
		messageContainer: null
	};

	/**
	 * Shows popup
	 * @member TYPO3.Flashmessage
	 * @param int severity (TYPO3.Severity.*)
	 * @param string title
	 * @param string message
	 * @param float duration in sec (default 5)
	 */
	Flashmessage.display = function (severity, title, message, duration) {
		var className = '';
		var icon = '';
		switch (severity) {
			case TYPO3.Severity.notice:
				className = 'notice';
				icon = 'lightbulb-o';
				break;
			case TYPO3.Severity.info:
				className = 'info';
				icon = 'info';
				break;
			case TYPO3.Severity.ok:
				className = 'success';
				icon = 'check';
				break;
			case TYPO3.Severity.warning:
				className = 'warning';
				icon = 'exclamation';
				break;
			case TYPO3.Severity.error:
				className = 'danger';
				icon = 'times';
				break;
			default:
				className = 'info';
				icon = 'info';
		}
		duration = duration || 5;
		if (!this.messageContainer) {
			this.messageContainer = $('<div id="alert-container"></div>').appendTo('body');
		}
		$box = $(
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
						'<h4 class="alert-title">' + title + '</h4>' +
						'<p class="alert-message">' + message + '</p>' +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>'
		);
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
		$box.appendTo(this.messageContainer);
		$box.delay('fast')
			.queue(function(next) {
				$(this).addClass('in');
				next();
			})
			.delay(duration * 1000)
			.queue(function(next) {
				$(this).alert('close');
				next();
			});
	};

	/**
	 * return the Flashmessage object
	 */
	return function () {
		TYPO3.Severity = Severity;
		TYPO3.Flashmessage = Flashmessage;
		return Flashmessage;
	}();
});
