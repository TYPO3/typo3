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
 * JavaScript RequireJS module called "TYPO3/CMS/Backend/TabMenuSetup"
 *
 * requires the tab menu jQuery plugin that does the logic
 * and calls the plugin for each menu (.typo3-dyntabmenu items).
 *
 * After that, does the usual RequireJS logic and returns the object
 */
define('TYPO3/CMS/Backend/TabMenuSetup', ['jquery', 'TYPO3/CMS/Backend/Plugins/TabMenuPlugin'], function($) {

	/**
	 * part 1: The main module of this file
	 * initialize the TabMenu by applying the jQuery plugin
	 */
	var TabMenu = {
		options: {
			tabSelector: '[data-toggle="TabMenu"]',
			tabMenuContainerSelector : '.typo3-dyntabmenu-tabs'
		},
		initialize: function() {
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
