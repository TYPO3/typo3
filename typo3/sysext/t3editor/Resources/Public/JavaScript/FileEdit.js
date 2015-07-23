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
		$('.t3-icon-document-save, .t3-icon-document-save-close').each(function() {
			var $link = $(this).parent('a');
			if ($link) {

				// Only remove onclick for save, save and close needs to have document.editform.submit to be removed
				if ($link.children('span').hasClass('t3-icon-document-save')) {
					$link.removeAttr('onclick');
				} else {
					var onClick = $link.attr('onclick');
					$link.attr('onclick', onClick.replace('document.editform.submit();', ''));
				}

				$link.on('click', function(e) {
					e.preventDefault();
					if (!T3editor || !T3editor.instances[0]) {
						document.editform.submit();
						return false;
					}
					if ($(this).children('span').hasClass('t3-icon-document-save')) {
						T3editor.saveFunction(T3editor.instances[0]);
					} else {
						T3editor.updateTextarea(T3editor.instances[0]);
						document.editform.submit();
					}
					return false;
				});
			}
		});
	});
});
