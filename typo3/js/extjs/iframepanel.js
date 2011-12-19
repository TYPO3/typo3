/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 * iFrame panel
 *
 * @author	Steffen Kamper
 */
Ext.define('TYPO3.iframePanel', {
	extend: 'Ext.panel.Panel',
	alias: ['widget.iframePanel'],

	name: 'iframe',
	iframe: null,
	src: Ext.isIE && Ext.isSecure ? Ext.SSL_SECURE_URL : 'about:blank',
	maskMessage: ' ',
	doMask: true,
	border: false,
		// Build the iframePanel component
	initComponent: function() {
		this.callParent(arguments);
			// Add the iframe element as item
		this.add({
			xtype: 'component',
			itemId: 'iframe',
			autoEl: {
				tag: 'iframe',
				name: this.name,
				frameborder: '0',
				src: this.src
			},
			listeners: {
				afterrender: {
					fn: this.onAfterRender,
					scope: this,
					single: true
				}
			}
		});
	},
	onAfterRender: function() {
		this.maskMessage = ' ';
		this.iframeEl = this.getComponent('iframe').getEl();
		this.iframe = Ext.isIE ? this.iframeEl.dom.contentWindow : window.frames[this.name];
		this.iframeEl.dom[Ext.isIE ? 'onreadystatechange' : 'onload'] = Ext.Function.bind(this.loadHandler, this);
	},

	loadHandler: function() {
		this.src = this.iframeEl.dom.src;
		this.removeMask();
	},

	getIframe: function() {
		return this.iframe;
	},
	getUrl: function() {
		return this.iframeEl.dom.src;
	},

	getModuleWrapper: function() {
		return this.findParentBy(
			function(container, component) {
				if(container.id === 'typo3-contentContainerWrapper') {
					return true;
				}
			}
		);
	},

	setUrl: function(source) {
		var wrapper;
		wrapper = this.getModuleWrapper();
		if(wrapper) {
			if(wrapper.getComponent('typo3-card-' + TYPO3.ModuleMenu.App.loadedModule)) {
				wrapper.getLayout().setActiveItem('typo3-card-' + TYPO3.ModuleMenu.App.loadedModule);
				TYPO3.ModuleMenu.App.openInContentFrame(source);
			} else {
				wrapper.getLayout().setActiveItem(this.id);
				this.iframeEl.dom.src = source;
				this.setMask();
			}
		}
	},

	resetUrl: function() {
		this.setMask();
		this.iframeEl.dom.src = this.src;
	},

	getIdFromUrl: function() {
		var queryString = this.getUrl().split('?')[1];
		if (queryString) {
			var url = Ext.urlDecode(queryString);
		}
		return url ? url.id : null;
	},

	refresh: function() {
		if (!this.isVisible()) {
		    return;
		}
		this.setMask();
		this.iframeEl.dom.src = this.iframeEl.dom.src;
	},

	/** @private */
	setMask: function() {
		if (this.doMask) {
			this.getEl().mask(this.maskMessage, 'x-mask-loading-message');
			this.getEl().addCls('t3-mask-loading');
				// Add an onClick handler to remove the mask while clicking on the loading message
				// useful if user cancels loading and wants to access the content again
			this.getEl().child('.x-mask-loading-message').on(
				'click',
				function() {
					this.getEl().unmask();
				},
				this
			);
		}
	},

	removeMask: function() {
		if (this.doMask) {
			this.getEl().unmask();
		}
	}
});
