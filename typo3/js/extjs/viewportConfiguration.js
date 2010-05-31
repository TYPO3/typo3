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
 * The backend viewport configuration
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Viewport.configuration = {
	layout: 'border',
	id: 'typo3-viewport',
	renderTo: Ext.getBody(),
	border: false,
	items: [{
		layout: 'absolute',
		region: 'north',
		id: 'typo3-topbar',
		height: 42,
		contentEl: 'typo3-top-container',
		border: false
	}, {
		layout: 'absolute',
		region: 'west',
		id: 'typo3-module-menu',
		contentEl: 'typo3-side-menu',
		width: 159,
		anchor: '100% 100%',
		border: false
	}, {
		region: 'center',
		layout: 'border',
		border: false,
		items: [{
			region: 'west',
			layout: 'absolute',
			id: 'typo3-navigationContainer',
			width: 300,
			anchor: '100% 100%',
			collapsible: true,
			collapseMode: 'mini',
			hideCollapseTool: true,
			animCollapse: false,
			split: true,
			autoScroll: true,
			hidden: true,
			border: false
		}, {
			region: 'center',
			layout: 'absolute',
			id: 'typo3-contentContainer',
			contentEl: 'typo3-content',
			anchor: '100% 100%',
			border: false
		}]
	}, {
		region: 'south',
		xtype: 'typo3DebugPanel',
		collapsible: true,
		collapseMode: 'mini',
		hideCollapseTool: true,
		animCollapse: false,
		split: true,
		autoScroll: true,
		hidden: true,
		height: 200,
		id: 'typo3-debug-console',
		border: false
	}]
};