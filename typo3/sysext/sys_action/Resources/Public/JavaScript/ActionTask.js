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
 * JavaScript to handle confirm windows in the task center module
 */
define('TYPO3/CMS/SysAction/ActionTask', ['jquery'], function ($) {
	$(function() {
		$(document).on('click', '.t3js-confirm-trigger', function(e) {
			e.preventDefault();
			var $link = $(this);
			top.TYPO3.Modal.confirm($link.data('title'), $link.data('message'))
				.on('confirm.button.ok', function() {
					self.location.href = $link.attr('href');
					top.TYPO3.Modal.currentModal.trigger('modal-dismiss');
				})
				.on('confirm.button.cancel', function() {
					top.TYPO3.Modal.currentModal.trigger('modal-dismiss');
				});
			return false;
		});
	});
});
