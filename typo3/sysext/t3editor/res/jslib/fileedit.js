/***************************************************************
*  Copyright notice
*
*  (c) 2011 Tobias Liebig <mail_typo3@etobi.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

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
