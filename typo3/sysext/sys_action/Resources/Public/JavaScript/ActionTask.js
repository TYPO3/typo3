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
 * Module: TYPO3/CMS/SysAction/ActionTask
 * JavaScript to handle confirm windows in the task center module
 * @exports TYPO3/CMS/SysAction/ActionTask
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal'], function ($, Modal) {
	$(function() {
		$(document).on('click', '.t3js-confirm-trigger', function(e) {
			e.preventDefault();
			var $link = $(this);
			Modal.confirm($link.data('title'), $link.data('message'))
				.on('confirm.button.ok', function() {
					self.location.href = $link.attr('href');
					Modal.currentModal.trigger('modal-dismiss');
				})
				.on('confirm.button.cancel', function() {
					Modal.currentModal.trigger('modal-dismiss');
				});
			return false;
		});
	});
});
