/***************************************************************
*  Copyright notice
*
*  (c) 2011 Kay Strobach <typo3@kay-strobach.de>
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

TYPO3.modulePanel = Ext.extend(Ext.Panel, {
	name: 'modulePanel',
	maskMessage: ' ',
	doMask: true,
	border: false,
		// component build
	initComponent: function() {
		Ext.apply(this, {
			tbarCfg: {
				cls: 't3skin-typo3-module-panel-toolbar'
			},
			bbarCfg: {
				cls: 't3skin-typo3-module-panel-toolbar'
			}
		});
		TYPO3.modulePanel.superclass.initComponent.apply(this, arguments);
		this.addEvents('uriChanged');
	},
	setUrl: function(url) {
		var paramsString;
		var params;
		this.url = url;
		paramsString = url.split("?");
		params = Ext.urlDecode(paramsString[paramsString.length - 1]);
		this.fireEvent('uriChanged', params.id, url, params, this);
	},
	getUrl: function getUrl() {
		return this.url;
	}
});
Ext.reg('modulePanel', TYPO3.modulePanel);
