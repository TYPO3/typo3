/***************************************************************
 *
 *  Various JavaScript functions for the Install Tool
 *
 *  Copyright notice
 *
 *  (c) 2009-2010 Marcus Krause, Helmut Hummel, Lars Houmark
 *  (c) 2013 Wouter Wolters <typo3@wouterwolters.nl>
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
 * Method to detect if Install Tool is loaded as standalone
 * If this is the case then an extra class will be added to the body tag
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 */
$(document).ready(function() {
	if (top.location === document.location) {
		// standalone-mode, add class to the body tag
		$('body').addClass('standalone');
	}

	$('#encryptionKey').click(function() {
		$.ajax({
			url: '../../index.php?eID=tx_install_ajax&cmd=encryptionKey'
		}).done(function(data) {
			$('#t3-install-form-encryptionkey').val(data);
		});
	});

	// Used in database compare section to select/deselect checkboxes
	$('#checkall input').on('click', function() {
		$(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);
	});

});