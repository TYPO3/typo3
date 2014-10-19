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
 * Check-all / uncheck-all for the Database Recordlist fieldSelectBox
 */
define('TYPO3/CMS/Recordlist/FieldSelectBox', ['jquery'], function($) {

	var FieldSelectBox = {};

	FieldSelectBox.initializeEvents = function() {
		$('.fieldSelectBox .checkAll').change(function() {
			var checked = $(this).prop('checked');
			var $checkboxes = $('.fieldSelectBox tbody').find(':checkbox');
			$checkboxes.each(function() {
				if (!$(this).prop('disabled')) {
					$(this).prop('checked', checked);
				}
			});
		});
	};

	// initialize and return the FieldSelectBox object
	return function() {
		$(document).ready(function() {
			FieldSelectBox.initializeEvents();
		});

		TYPO3.FieldSelectBox = FieldSelectBox;
		return FieldSelectBox;
	}();
});
