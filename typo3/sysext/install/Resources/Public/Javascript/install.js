/***************************************************************
*
*  Various JavaScript functions for the Install Tool
*
*  Copyright notice
*
*  (c) 2009-2010 Marcus Krause, Helmut Hummel, Lars Houmark
*  All rights reserved
*
*  This script is part of the TYPO3 backend provided by
*  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
*
*  Released under GNU/GPL (see license file in /typo3/)
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
*  This copyright notice MUST APPEAR in all copies of this script
*
***************************************************************/


/**
 * Javascript class to provide AJAX calls for Install Tool
 *
 * @author Marcus Krause, Helmut Hummel <security@typo3.org>
 */
var EncryptionKey = {
	thisScript: '../../index.php',
	eID: 'tx_install_ajax',
	cmd: 'encryptionKey',

		// loads the ecryption key by an AJAX call
	load: function(obj) {
			// fallback if AJAX is not possible (e.g. IE < 6)
		if (typeof Ajax.getTransport() != 'object') {
			window.location.href = this.thisScript + '?eID=' + this.eID + '&cmd=' + this.cmd;
			return;
		}

		new Ajax.Request(this.thisScript, {
			method: 'get',
			parameters: '?eID=' + this.eID + '&cmd=' + this.cmd,
			onComplete: function(xhr) {
				document.getElementsByName('TYPO3_INSTALL[LocalConfiguration][encryptionKey]').item(0).value=xhr.responseText;
			}.bind(this)
		});
	}
};

/**
 * Prototype method to detect if the Install Tool is loaded
 * in the backend or as a standalone.
 *
 * If it standalone, a class is added to the body tag in order
 * to different the CSS style for that version.
 *
 * @author Lars Houmark <lars@houmark.com>
 */
document.observe("dom:loaded", function() {
	if (top.location === document.location) {
			// standalone-mode, add class to the body tag
			$(document.body).addClassName('standalone');
	}
});
