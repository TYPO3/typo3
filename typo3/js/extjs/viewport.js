/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Stefan Galinski <stefan.galinski@gmail.com>
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

Ext.ns('TYPO3');

/**
 * Extends the viewport with some functionality for TYPO3.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Viewport = Ext.extend(Ext.Viewport, {
	/**
	 * Contains the navigation widgets in a simple array and identified by an unique idea
	 *
	 * @see registerNavigationWidget()
	 * @var object
	 */
	navigationWidgetContainer: {},

	/**
	 * Contains the meta informations of the navigation widgets
	 *
	 * @see registerNavigationWidget()
	 * @var object
	 */
	navigationWidgetMetaData: {},

	/**
	 * The topbar area
	 *
	 * @var Ext.Panel
	 */
	Topbar: null,

	/**
	 * The content area
	 *
	 * @var Ext.Panel
	 */
	ContentContainer: null,

	/**
	 * The navigation frame area
	 *
	 * @var Ext.Panel
	 */
	NavigationContainer: null,

	/**
	 * The module menu area
	 *
	 * @var Ext.Panel
	 */
	ModuleMenuContainer: null,

	/**
	 * The debug console
	 * 
	 * @var Ext.TabPanel
	 */
	DebugConsole: null,

	/**
	 * Initializes the ExtJS viewport with the given configuration.
	 *
	 * @return void
	 */
	initComponent: function() {
		// adjust the width of module menu and the height of the topbar
		this.initialConfig.items[0].height = TYPO3.configuration.topBarHeight;
		this.initialConfig.items[1].width = TYPO3.configuration.moduleMenuWidth;

		// call parent constructor
		TYPO3.Viewport.superclass.initComponent.apply(this, arguments);

		this.ContentContainer = Ext.ComponentMgr.get('typo3-contentContainer');
		this.NavigationContainer = Ext.ComponentMgr.get('typo3-navigationContainer');
		this.Topbar = Ext.ComponentMgr.get('typo3-topbar');
		this.ModuleMenuContainer = Ext.ComponentMgr.get('typo3-module-menu');

		// adds the debug console and some listeners to consider the initial hiding of
		// the debug console (the viewport needs to be resized if it's expand/collapse)
		// -> see the TYPO3.BackendSizeManager
		this.DebugConsole = Ext.ComponentMgr.get('typo3-debug-console');
		this.DebugConsole.addListener({
			'resize': {
				scope: this,
				fn: function() {
					this.fireEvent('resize');
				}
			},
			'collapse': {
				scope: this,
				fn: function() {
					this.fireEvent('resize');
				}
			}
		});
	},

	/**
	 * Loads a module into the content container
	 *
	 * @param mainModuleName string name of the main module (e.g. web)
	 * @param subModuleName string name of the sub module (e.g. page)
	 * @param contentScript string the content provider (path to a php script)
	 * @return void
	 */
	loadModule: function(mainModuleName, subModuleName, contentScript) {
		var navigationWidgetActive = false;
		var widgetMainModule = '';
		var widgetSubModule = '';
		var widget = null;
		for (var widgetId in this.navigationWidgetMetaData) {
			widgetMainModule = this.navigationWidgetMetaData[widgetId].mainModule;
			widgetSubModule = this.navigationWidgetMetaData[widgetId].subModule;
			widget = this.navigationWidgetMetaData[widgetId].widget;

			if ((widgetMainModule === mainModuleName || widgetMainModule === '*') &&
				(widgetSubModule === subModuleName || widgetSubModule === '*')
			) {
				widget.show();
				navigationWidgetActive = true;
			} else {
				widget.hide();
			}
		}

		if (navigationWidgetActive) {
			this.NavigationContainer.show();
		} else {
			this.NavigationContainer.hide();
		}

		// append the typo3 path if it wasn't already applied
		// this is important for backwards compatibility (e.g. shortcuts)
		if (contentScript.indexOf(top.TS.PATH_typo3) !== 0) {
			contentScript = top.TS.PATH_typo3 + contentScript;
		}
		Ext.get('content').set({
			src: contentScript
		});

		this.NavigationContainer.ownerCt.doLayout();
	},

	/**
	 * Adds the given widget to the navigation container. The key will be the id attribute
	 * of the given widget.
	 *
	 * @param mainModule string main module or wildcard (*) for all
	 * @param subModule string sub module or wildcard (*) for all
	 * @param widget object ExtJS widget (e.g. an Ext.Panel); must contain an id attribute!
	 * @return void
	 */
	registerNavigationWidget: function(mainModule, subModule, widget) {
			// only one instance of specific widget may be exists!
		if (this.navigationWidgetMetaData[widget.id] === undefined) {
			this.navigationWidgetMetaData[widget.id] = {
				mainModule: mainModule,
				subModule: subModule,
				widget: widget
			};

				// always take the full width and height
			widget.anchor = '100% 100%';
			this.NavigationContainer.add(widget);
		}
	}
});

Ext.reg('typo3Viewport', TYPO3.Viewport);
