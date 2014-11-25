/**
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
		// @deprecated since 7.0 and will be removed with CMS 9, use info instead of information
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
		switch (severity) {
			case TYPO3.Severity.notice:
				className = 'notice';
				break;
			case TYPO3.Severity.info:
				className = 'info';
				break;
			case TYPO3.Severity.ok:
				className = 'success';
				break;
			case TYPO3.Severity.warning:
				className = 'warning';
				break;
			case TYPO3.Severity.error:
				className = 'danger';
				break;
			default:
				className = 'info';
		}
		duration = duration || 5;
		if (!this.messageContainer) {
			this.messageContainer = $('<div id="alert-container"></div>').appendTo('body');
		}
		$box = $('<div class="alert alert-' + className + ' alert-dismissible fade in" role="alert">' +
		'<button type="button" class="close" data-dismiss="alert">' +
		'<span aria-hidden="true">&times;</span>' +
		'<span class="sr-only">Close</span>' +
		'</button>' +
		'<h4>' + title + '</h4>' +
		'<p>' + message + '</p>' +
		'</div>');
		$box.appendTo(this.messageContainer);
		$box.fadeIn().delay(duration * 1000).slideUp();
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
