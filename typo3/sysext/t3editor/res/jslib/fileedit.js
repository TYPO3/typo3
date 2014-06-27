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

Ext.onReady(function() {
	Ext.select('.t3-icon-document-save, .t3-icon-document-save-close').each(function(element) {
		var link = element.up('a');
		if (link) {
			link.dom.setAttribute(
				'onclick',
				link.dom.getAttribute('onclick').replace('document.editform.submit();', '')
			);
			link.on('click', function(event, target) {
				event.stopEvent();
				if (!T3editor || !T3editor.instances[0]) {
					document.editform.submit();
					return false;
				}
				if (Ext.get(target).hasClass('t3-icon-document-save')) {
					if (!T3editor.instances[0].disabled) {
						T3editor.instances[0].saveFunctionEvent();
					} else {
						document.editform.submit();
					}
				} else {
					if (!T3editor.instances[0].disabled) {
						T3editor.instances[0].updateTextareaEvent();
					}
					document.editform.submit();
				}
				return false;
			});
		}
	});
});
