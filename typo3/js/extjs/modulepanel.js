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
