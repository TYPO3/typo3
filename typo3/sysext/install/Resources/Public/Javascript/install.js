/***************************************************************
*
*  Javascript functions to provide AJAX calls for install tool
*
*  Copyright notice
*
*  (c) 2009 Marcus Krause, Helmut Hummel <security@typo3.org>
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
 *
 * @author	Marcus Krause
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
				document.getElementsByName('TYPO3_INSTALL[localconf.php][encryptionKey]').item(0).value=xhr.responseText;
			}.bind(this)
		});
	}
};
