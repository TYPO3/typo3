/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Stefan Galinski <stefan.galinski@gmail.com>
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
 * Extends the viewport with some functionality for TYPO3.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
Ext.define('TYPO3.Viewport', {
	extend: 'Ext.container.Viewport',
	alias: ['widget.typo3Viewport'],

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
	 * Dummy panel, shown when no NavigationContainer is in use
	 *
	 * @var Ext.Panel
	 */
	NavigationDummy: null,

	/**
	 * The iframe navigation component
	 *
	 * @var TYPO3.iframePanel
	 */
	NavigationIframe: null,

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
			// Adjust the height of the topbar and the width of the module menu
		var topbar = this.initialConfig.items[0];
		topbar.height = TYPO3.configuration.topBarHeight;
		var moduleMenu = this.initialConfig.items[1];
		moduleMenu.width = TYPO3.configuration.moduleMenuWidth;
			// Call parent
		this.callParent(arguments);
			// Set references to components
		this.ContentContainer = Ext.getCmp('typo3-contentContainer');
		this.NavigationContainer = Ext.getCmp('typo3-navigationContainer');
		this.NavigationDummy = Ext.getCmp('typo3-navigationDummy');
		this.NavigationIframe = Ext.getCmp('typo3-navigationIframe');
		this.Topbar = Ext.getCmp('typo3-topbar');
		this.ModuleMenuContainer = Ext.getCmp('typo3-module-menu');
		this.DebugConsole = Ext.getCmp('typo3-debug-console');
	}
});
