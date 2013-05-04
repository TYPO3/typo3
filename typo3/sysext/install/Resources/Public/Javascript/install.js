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

	// Loads the encryption key by an AJAX call
	load: function(obj) {
		$.get('../../index.php?eID=' + this.eID + '&cmd=' + this.cmd, function(data) {
			document.getElementsByName('TYPO3_INSTALL[LocalConfiguration][encryptionKey]').item(0).value=data;
		});
	}
};

$(function() {
	// Standalone-mode, add class to the body tag
	if (top.location === document.location) {
		$('body').addClass('standalone');
	}

	$("#toggle-environment").click(function() {
		$("#environment-advanced, #environment-simple").toggle();
	});
});
