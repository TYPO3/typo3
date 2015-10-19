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
 * Module: TYPO3/CMS/T3editor/FileEdit
 * File edit for ext:t3editor
 * @exports TYPO3/CMS/T3editor/FileEdit
 */
define(['jquery', 'TYPO3/CMS/T3editor/T3editor'], function ($, T3editor) {
	'use strict';

	$(function() {

		// Remove document.editform.submit from save and close onclick
		// Form will be submitted by the new on click handler
		var $saveAndCloseButton = $('[data-name="_saveandclose"], [name="_saveandclose"]'),
			$saveButton = $('[data-name="_save"], [name="_save"]');

		var onClick = $saveAndCloseButton.attr('onclick');
		$saveAndCloseButton.attr('onclick', onClick.replace('document.editform.submit();', ''));

		// Remove onclick for save icon, saving is done by an AJAX-call
		$saveButton.removeAttr('onclick');

		$saveButton.on('click', function(e) {
			e.preventDefault();

			if (!T3editor || !T3editor.instances[0]) {
				document.editform.submit();
				return false;
			}

			T3editor.saveFunction(T3editor.instances[0]);
			return false;
		});

		$saveAndCloseButton.on('click', function(e) {
			e.preventDefault();

			if (!T3editor || !T3editor.instances[0]) {
				document.editform.submit();
				return false;
			}
			T3editor.updateTextarea(T3editor.instances[0]);
			document.editform.submit();
			return false;
		});

	});
});
