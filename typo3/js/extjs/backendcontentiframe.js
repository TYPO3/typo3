/*
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

TYPO3.backendContentIframePanel = Ext.extend(TYPO3.iframePanel ,{
	setUrl: function(source) {
		var card;
		var wrapper;
		wrapper = Ext.getCmp('typo3-contentContainerWrapper');
		this.url = source;
		if(wrapper) {
			card = Ext.getCmp('typo3-card-' + TYPO3.ModuleMenu.App.loadedModule);
			if((card != undefined) && (source.search('extjspaneldummy.html') > -1)) {
				wrapper.getLayout().setActiveItem('typo3-card-' + TYPO3.ModuleMenu.App.loadedModule);
				if (typeof wrapper.getComponent(('typo3-card-' + TYPO3.ModuleMenu.App.loadedModule)).setUrl === 'function') {
					wrapper.getComponent(('typo3-card-' + TYPO3.ModuleMenu.App.loadedModule)).setUrl(source);
				}
			} else {
				wrapper.getLayout().setActiveItem(this.id);
				this.body.dom.src = source;
				this.startLoader();
			}
		}
	},

	getUrl: function () {
		var wrapper;
		var card;
		wrapper = Ext.getCmp('typo3-contentContainerWrapper');

		if(wrapper) {
			card = wrapper.getLayout().activeItem;
			if(card.id == this.id) {
				return this.body.dom.src;
			} else if(typeof card.getUrl == 'function') {
				return card.getUrl();
			} else {
				return this.url;
			}
		}
	}
});
Ext.reg('backendContentIframePanel', TYPO3.backendContentIframePanel);