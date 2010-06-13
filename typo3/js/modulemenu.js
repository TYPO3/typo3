/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Ingo Renner <ingo@typo3.org>
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


/**
 * observes clicks on menuHeader and toggles child ul
 *
 * @author	Ingo Renner
 * @author	Steffen Kamper
 */
var ModuleMenu = Class.create({

	/**
	 * initially register event listeners
	 */
	initialize: function() {

			// initialize event listeners
		Event.observe(document, 'dom:loaded', function(){
			this.registerEventListeners();
		}.bind(this));

			// initialize some variables
		this.currentlyHighLightedMainModule = '';
		this.currentlyHighlightedModuleId   = '';
	},

	/**
	 * registers the event listeners, can be used to re-register them after refreshing the menu
	 */
	registerEventListeners: function() {
		$$('#typo3-menu li.menuSection div').invoke('observe', 'click', this.toggleMenu);
		if (Prototype.Browser.IE) {
				//mouseenter and mouseleave are only available but thats our target
			$$('#typo3-menu li.menuSection li').invoke('observe', 'mouseenter', this.toggleHoverClass);
			$$('#typo3-menu li.menuSection li').invoke('observe', 'mouseleave', this.toggleHoverClass);		
		}
	},
	
	/**
	 * toggles the hover classname for IE menu hover support
	 */
	toggleHoverClass: function(event) {
		var menuItem = Event.element(event);
		menuItem.toggleClassName('hover');
	},

	/**
	 * toggles the associated submodule menu when clicking a main module header
	 */
	toggleMenu: function(event) {
		var mainModuleHeader = Event.element(event);

		var mainMenuId       = mainModuleHeader.up().identify();
		var subModulesMenu   = mainModuleHeader.next('ul');
		if (!subModulesMenu) {
			return;
		}
		var state            = subModulesMenu.visible();

			// save state
		var save = new Ajax.Request('ajax.php', {
			parameters : 'ajaxID=ModuleMenu::saveMenuState&menuid=' + mainMenuId + '&state=' + state
		});

		if (state) {
			Effect.BlindUp(subModulesMenu, {duration : 0.1});
			$(mainModuleHeader).removeClassName('expanded');
			$(mainModuleHeader).addClassName('collapsed');
		} else {
			Effect.BlindDown(subModulesMenu, {duration : 0.1});
			$(mainModuleHeader).removeClassName('collapsed');
			$(mainModuleHeader).addClassName('expanded');
		}
	},

	/**
	 * refreshes the complete module menu
	 */
	refreshMenu: function() {
		var refresh = new Ajax.Updater('typo3-menu', TS.PATH_typo3 + 'ajax.php', {
			parameters   : 'ajaxID=ModuleMenu::render',
			asynchronous : false,
			evalScripts  : true
		});

		this.registerEventListeners();
		this.highlightModule(this.currentlyHighlightedModuleId, this.currentlyHighLightedMainModule);
	},

	/**
	 * de-highlights the old menu item and highlights the new one
	 *
	 * @param	string		css module id to highlight
	 */
	highlightModule: function(moduleId, mainModule) {
			// reset the currently highlighted module
		$$('#typo3-menu .highlighted').invoke('removeClassName', 'highlighted');

			// highlight the new one
		if ($(moduleId)) {
			$(moduleId).addClassName('highlighted');
		}

		if (undefined !== mainModule) {
			this.currentlyHighLightedMainModule = mainModule;
		}
		this.currentlyHighlightedModuleId = moduleId;

		// kept for backwards compatibility
		// @TODO: remove in TYPO3 4.5
		// @deprecated since TYPO3 4.3, remove in 4.5
		top.currentlyHighLightedId   = moduleId;
		top.currentlyHighLightedMain = mainModule;
	}

});

var TYPO3ModuleMenu = new ModuleMenu();


/*******************************************************************************
 *
 * Backwards compatability handling down here
 *
 ******************************************************************************/

/**
 * Highlight module:
 */
var currentlyHighLightedId = '';
var currentlyHighLighted_restoreValue = '';
var currentlyHighLightedMain = '';
function highlightModuleMenuItem(trId, mainModule) {
	TYPO3ModuleMenu.highlightModule(trId, mainModule);
}









