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

/**
 * module to handle the User menu on the top
 */
define('TYPO3/CMS/Backend/Toolbar/UserMenu', ['jquery'], function($) {

	var UserMenu = {
		options: {
			containerSelector:  '#topbar-user-menu'
			, toolbarItemSelector: '.toolbar-item'
			, menuSelector: '.toolbar-item-menu'
			, menuItemSelector: '.toolbar-item-menu li'
		}
	};

	/**
	 * initialize the events for opening and closing the menu on clicking the
	 * toolbarItem and on one of the menuItems
	 */
	UserMenu.initializeEvents = function() {
		$(UserMenu.options.toolbarItemSelector + ', ' + UserMenu.options.menuItemSelector, UserMenu.options.containerSelector).on('click', function() {
			UserMenu.toggleMenu();
		});
	};

	/**
	 * shows/hides the menu depending on the current state
	 */
	UserMenu.toggleMenu = function() {
		$(UserMenu.options.menuSelector, UserMenu.options.containerSelector).toggle();
	};

	/**
	 * initialize and return the Menu object
	 */
	return function() {
		$(document).ready(function() {
			UserMenu.initializeEvents();
		});

		TYPO3.Toolbar = TYPO3.Toolbar || {};
		TYPO3.Toolbar.UserMenu = UserMenu;
		return UserMenu;
	}();
});