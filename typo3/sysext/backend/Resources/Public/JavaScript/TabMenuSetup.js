/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2013 Daniel Sattler <daniel.sattler@b13.de>
 *      2013 Benjamin Mack <benni@typo3.org>
 *  All rights reserved
 *
 *  Released under GNU/GPL2+ (see license file in the main directory)
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  This copyright notice MUST APPEAR in all copies of this script
 *
 ***************************************************************/
/**
 * JavaScript RequireJS module called "TYPO3/CMS/Backend/TabMenuSetup"
 *
 * requires the tab menu jQuery plugin that does the logic
 * and calls the plugin for each menu (.typo3-dyntabmenu items).
 *
 * After that, does the usual RequireJS logic and returns the object
 *
 */
define('TYPO3/CMS/Backend/TabMenuSetup', ['jquery', 'TYPO3/CMS/Backend/Plugins/TabMenuPlugin'], function($) {

	/**
	 * part 1: The main module of this file
	 * initialize the TabMenu by applying the jQuery plugin
	 */
	var TabMenu = {
		options: {
			tabSelector: '[data-toggle="TabMenu"]'
			,tabMenuContainerSelector : '.typo3-dyntabmenu'
		}
		,initialize: function() {
			var me = this;

			// initialize all tabMenus that are available on dom ready
			$(document).ready(function() {

				$(me.options.tabMenuContainerSelector).tabMenu('initialize', me.options);
			});
		}
	};

	/**
     * part 2: initialize the RequireJS module, require possible post-initialize hooks,
	 * and return the main object
	 */
	var initialize = function(options) {

		TabMenu.initialize();

		// load required modules to hook in the post initialize function
		if (undefined !== TYPO3.settings && undefined !== TYPO3.settings.RequireJS && undefined !== TYPO3.settings.RequireJS.PostInitializationModules && undefined !== TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/TabMenuSetup']) {
			$.each(TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/TabMenuSetup'], function(pos, moduleName) {
				require([moduleName]);
			});
		}

		// return the object in the global space
		return TabMenu;
	};

	// call the main initialize function and execute the hooks
	return initialize();
});