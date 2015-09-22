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
 * File edit for ext:t3editor
 */
define('TYPO3/CMS/T3editor/FileEdit', ['jquery', 'TYPO3/CMS/T3editor/T3editor'], function ($, T3editor) {

	$(document).ready(function() {

		// Remove document.editform.submit from save and close onclick
		// Form will be submitted by the new on click handler
		var onClick = $('.t3js-fileedit-save-close').attr('onclick');
		$('.t3js-fileedit-save-close').attr('onclick', onClick.replace('document.editform.submit();', ''));

		// Remove onclick for save icon, saving is done by an AJAX-call
		$('.t3js-fileedit-save').removeAttr('onclick');

		$('.t3js-fileedit-save').on('click', function(e) {
			e.preventDefault();

			if (!T3editor || !T3editor.instances[0]) {
				document.editform.submit();
				return false;
			}

			T3editor.saveFunction(T3editor.instances[0]);
			return false;
		});

		$('.t3js-fileedit-save-close').on('click', function(e) {
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
