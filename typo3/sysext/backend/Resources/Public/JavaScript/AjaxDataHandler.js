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
 * AjaxDataHandler - Javascript functions to work with AJAX and interacting with tce_db.php
 */
define('TYPO3/CMS/Backend/AjaxDataHandler', ['jquery', 'TYPO3/CMS/Backend/FlashMessages'], function ($) {
	var AjaxDataHandler = {};

	AjaxDataHandler.initialize = function() {

		// click events for all action icons to hide/unhide
		$(document).on('click', '.t3js-record-hide', function(evt) {
			evt.preventDefault();
			var $anchorElement = $(this);
			var $iconElement   = $anchorElement.find('span');
			var $rowElement    = $anchorElement.closest('tr[data-uid]');
			var table  = $anchorElement.closest('table[data-table]').data('table');
			var hasVisibleState  = $anchorElement.data('state') === 'visible';
			var params = $anchorElement.data('params');

			var removeClass = hasVisibleState ? 'fa-toggle-on' : 'fa-toggle-off';
			var addClass    = hasVisibleState ? 'fa-toggle-off' : 'fa-toggle-on';
			var nextState   = hasVisibleState ? 'hidden' : 'visible';
			var nextParams  = hasVisibleState ? params.replace('=1', '=0') : params.replace('=0', '=1');

			// add a spinner
			$iconElement.removeClass(removeClass);
			AjaxDataHandler._showSpinnerIcon($iconElement);

			// make the AJAX call to toggle the visibility
			AjaxDataHandler._call(params).done(function(result) {
				AjaxDataHandler._hideSpinnerIcon($iconElement);
				// print messages on errors
				if (result.hasErrors) {
					$.each(result.messages, function(position, message) {
						top.TYPO3.Flashmessage.display(message.severity, message.title, message.message);
					});
					// revert to the old class
					$iconElement.addClass(removeClass);
				} else {
					$anchorElement.data('state', nextState).data('params', nextParams);
					$iconElement.removeClass(removeClass).addClass(addClass);
					if (nextState === 'hidden') {
						// add overlay icon
						$rowElement.find('td.col-icon span.t3-icon').append('<span class="t3-icon t3-icon-status t3-icon-status-overlay t3-icon-overlay-hidden t3-icon-overlay">&nbsp;</span>');
					} else {
						// remove overlay icon
						$rowElement.find('td.col-icon span.t3-icon span.t3-icon').remove();
					}
					$rowElement.fadeTo('fast', 0.4, function() {
						$rowElement.fadeTo('fast', 1);
					});

					if (table === 'pages' && top.TYPO3 && top.TYPO3.Backend && top.TYPO3.Backend.NavigationContainer && top.TYPO3.Backend.NavigationContainer.PageTree) {
						top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
					}
				}

			});
		});
	};

	/**
	 * AJAX call to tce_db.php
	 * returns a jQuery Promise to work with
	 * @private
	 */
	AjaxDataHandler._call = function(params) {
		return $.getJSON(TYPO3.settings.ajaxUrls['DataHandler::process'], params);
	};

	/**
	 * Replace the given icon with a spinner icon
	 * @private
	 */
	AjaxDataHandler._showSpinnerIcon = function($iconElement) {
		$iconElement.addClass('fa-spin fa-circle-o-notch');
	};

	/**
	 * Removes the spinner icon classes
	 * @private
	 */
	AjaxDataHandler._hideSpinnerIcon = function($iconElement) {
		$iconElement.removeClass('fa-spin fa-circle-o-notch');
	};

	/**
	 * initialize and return the object
	 */
	return function() {
		AjaxDataHandler.initialize();

		// return the object in the global space
		return AjaxDataHandler;
	}();
});
