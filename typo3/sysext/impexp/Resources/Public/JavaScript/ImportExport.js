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
 * Module: TYPO3/CMS/Impexp/ImportExport
 * JavaScript to handle confirm windows in the Import/Export module
 * @exports TYPO3/CMS/Impexp/ImportExport
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal'], function ($, Modal) {
	'use strict';

	$(function() {
		$(document).on('click', '.t3js-confirm-trigger', function() {
			var $button = $(this);
			Modal.confirm($button.data('title'), $button.data('message'))
				.on('confirm.button.ok', function() {
					$('#t3js-submit-field')
						.attr('name', $button.attr('name'))
						.closest('form').submit();
					Modal.currentModal.trigger('modal-dismiss');
				})
				.on('confirm.button.cancel', function() {
					Modal.currentModal.trigger('modal-dismiss');
				});
		});
	});
});
