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
 * @deprecated with CMS 7, will be removed in CMS 8
 */
var ToolbarManager = Class.create({

	/**
	 * hides all toolbar item menus except for the which was clicked
	 */
	hideOthers: function(toolbarItem) {},

	/**
	 * Hide all expanded toolbar menus
	 */
	hideAll: function() {},

	/**
	 * refreshs positioning of all submenus
	 */
	refreshAll: function() {},

	/**
	 * @param elementId The parent element of the menu to be positioned
	 */
	positionMenu: function(elementId) {}

});

var TYPO3BackendToolbarManager = new ToolbarManager();