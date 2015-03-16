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
 * System information menu handler
 */
define('TYPO3/CMS/Backend/Toolbar/SystemInformationMenu', ['jquery'], function($) {

	var SystemInformationMenu = {
		elements: {
			$counter: $('#t3js-systeminformation-counter')
		}
	};

	/**
	 * register event handlers
	 */
	SystemInformationMenu.initializeEvents = function() {
		var count = parseInt(SystemInformationMenu.elements.$counter.text());
		SystemInformationMenu.elements.$counter.toggle(count > 0);
	};

	/**
	 * initialize and return the Opendocs object
	 */
	$(document).ready(function() {
		SystemInformationMenu.initializeEvents();
	});

	TYPO3.SystemInformationMenu = SystemInformationMenu;
	return SystemInformationMenu;
});
