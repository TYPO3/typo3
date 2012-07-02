/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 @author Kay Strobach    <typo3@kay-strobach.de>
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
				this.setMask();
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