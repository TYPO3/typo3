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
 * AjaxDataHandler - Javascript functions to work with AJAX and interacting with tce_db.php
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Notification'], function ($, Modal) {
	'use strict';

	var AjaxDataHandler = {};

	/**
	 * generic function to call from the outside the script and validate directly showing errors
	 * @param parameters
	 * @return a jQuery deferred object (promise)
	 */
	AjaxDataHandler.process = function(parameters) {
		return AjaxDataHandler._call(parameters).done(function(result) {
			if (result.hasErrors) {
				AjaxDataHandler.handleErrors(result);
			}
		});
	};

	AjaxDataHandler.initialize = function() {

		// HIDE/UNHIDE: click events for all action icons to hide/unhide
		$(document).on('click', '.t3js-record-hide', function(evt) {
			evt.preventDefault();
			var $anchorElement   = $(this);
			var $iconElement     = $anchorElement.find('i');
			var $rowElement      = $anchorElement.closest('tr[data-uid]');
			var params           = $anchorElement.data('params');
			var removeClass      = $anchorElement.data('state') === 'visible' ? 'fa-toggle-on' : 'fa-toggle-off';

			// add a spinner
			$iconElement.removeClass(removeClass);
			AjaxDataHandler._showSpinnerIcon($iconElement);

			// make the AJAX call to toggle the visibility
			AjaxDataHandler._call(params).done(function(result) {
				AjaxDataHandler._hideSpinnerIcon($iconElement);
				// print messages on errors
				if (result.hasErrors) {
					AjaxDataHandler.handleErrors(result);
					// revert to the old class
					$iconElement.addClass(removeClass);
				} else {
					// adjust overlay icon
					AjaxDataHandler.toggleRow($rowElement);
				}
			});
		});

		// DELETE: click events for all action icons to delete
		$(document).on('click', '.t3js-record-delete', function(evt) {
			evt.preventDefault();
			var $anchorElement = $(this);
			var $modal = Modal.confirm($anchorElement.data('title'), $anchorElement.data('message'), top.TYPO3.Severity.warning, [
				{
					text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
					active: true,
					name: 'cancel'
				},
				{
					text: $(this).data('button-ok-text') || TYPO3.lang['button.delete'] || 'Delete',
					btnClass: 'btn-warning',
					name: 'delete'
				}
			]);
			$modal.on('button.clicked', function(e) {
				if (e.target.name === 'cancel') {
					Modal.dismiss();
				} else if (e.target.name === 'delete') {
					Modal.dismiss();
					AjaxDataHandler.deleteRecord($anchorElement);
				}
			});
		});
	};

	/**
	 * Toggle row visibility after record has been changed
	 *
	 * @param $rowElement
	 */
	AjaxDataHandler.toggleRow = function($rowElement) {
		var $anchorElement = $rowElement.find('.t3js-record-hide');
		var table = $anchorElement.closest('table[data-table]').data('table');
		var params = $anchorElement.data('params');
		var nextParams, nextState, className;

		if ($anchorElement.data('state') === 'hidden') {
			nextState = 'visible';
			nextParams = params.replace('=0', '=1');
			className = 'fa-toggle-on';
		} else {
			nextState = 'hidden';
			nextParams = params.replace('=1', '=0');
			className = 'fa-toggle-off';
		}
		$anchorElement.data('state', nextState).data('params', nextParams);

		// Update tooltip title
		$anchorElement.tooltip('hide').one('hidden.bs.tooltip', function() {
			var nextTitle = $anchorElement.data('toggleTitle');
			// Bootstrap Tooltip internally uses only .attr('data-original-title')
			$anchorElement
				.data('toggleTitle', $anchorElement.attr('data-original-title'))
				.attr('data-original-title', nextTitle)
				.tooltip('show');
		});

		var $iconElement = $anchorElement.find('i');
		$iconElement.addClass(className);

		var $icon = $rowElement.find('td.col-icon span.t3-icon');
		var $overlayIcon = $icon.find('span.t3-icon');
		if ($overlayIcon.length) {
			$overlayIcon.remove();
		} else {
			$icon.append('<span class="t3-icon t3-icon-status t3-icon-status-overlay t3-icon-overlay-hidden t3-icon-overlay">&nbsp;</span>');
		}

		$rowElement.fadeTo('fast', 0.4, function() {
			$rowElement.fadeTo('fast', 1);
		});
		if (table === 'pages') {
			AjaxDataHandler.refreshPageTree();
		}
	};

	/**
	 * delete record by given element (icon in table)
	 * don't call it directly!
	 *
	 * @param element
	 */
	AjaxDataHandler.deleteRecord = function(element) {
		var $anchorElement = $(element);
		var elementClass = 'fa-trash';
		var params = $anchorElement.data('params');
		var $iconElement = $anchorElement.find('span');

		// add a spinner
		$iconElement.removeClass(elementClass);
		AjaxDataHandler._showSpinnerIcon($iconElement);

		// make the AJAX call to toggle the visibility
		AjaxDataHandler._call(params).done(function(result) {
			AjaxDataHandler._hideSpinnerIcon($iconElement);
			// revert to the old class
			$iconElement.addClass(elementClass);
			// print messages on errors
			if (result.hasErrors) {
				AjaxDataHandler.handleErrors(result);
			} else {
				var $table = $anchorElement.closest('table[data-table]');
				var $panel = $anchorElement.closest('.panel');
				var $panelHeading = $panel.find('.panel-heading');
				var table = $table.data('table');
				var $rowElements = $anchorElement.closest('tr[data-uid]');
				var uid = $rowElements.data('uid');
				var $translatedRowElements = $table.find('[data-l10nparent=' + uid + ']').closest('tr[data-uid]');
				$rowElements = $rowElements.add($translatedRowElements);

				$rowElements.fadeTo('slow', 0.4, function() {
					$rowElements.slideUp('slow', 0, function() {
						$rowElements.remove();
						if ($table.find('tbody tr').length === 0) {
							$panel.slideUp('slow');
						}
					});
				});
				if ($anchorElement.data('l10parent') === '0' || $anchorElement.data('l10parent') === '') {
					var count = Number($panelHeading.find('.t3js-table-total-items').html());
					$panelHeading.find('.t3js-table-total-items').html(count-1);
				}

				if (table === 'pages') {
					AjaxDataHandler.refreshPageTree();
				}
			}
		});
	};

	/**
	 * handle the errors from result object
	 *
	 * @param result
	 * @private
	 */
	AjaxDataHandler.handleErrors = function(result) {
		$.each(result.messages, function(position, message) {
			top.TYPO3.Notification.error(message.title, message.message);
		});
	};

	/**
	 * refresh the page tree
	 * @private
	 */
	AjaxDataHandler.refreshPageTree = function() {
		if (top.TYPO3 && top.TYPO3.Backend && top.TYPO3.Backend.NavigationContainer && top.TYPO3.Backend.NavigationContainer.PageTree) {
			top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
		}
	};

	/**
	 * AJAX call to tce_db.php
	 * returns a jQuery Promise to work with
	 * @private
	 */
	AjaxDataHandler._call = function(params) {
		return $.getJSON(TYPO3.settings.ajaxUrls['record_process'], params);
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
