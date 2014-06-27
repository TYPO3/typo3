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
 * iFrame panel
 *
 * @author	Steffen Kamper
 */

Ext.ns('TYPO3');

TYPO3.iframePanel = Ext.extend(Ext.Panel, {
	name: 'iframe',
	iframe: null,
	src: Ext.isIE && Ext.isSecure ? Ext.SSL_SECURE_URL : 'about:blank',
	maskMessage: ' ',
	doMask: true,

		// component build
	initComponent: function() {
		this.bodyCfg = {
			tag: 'iframe',
			frameborder: '0',
			src: this.src,
			name: this.name,
			style: 'float:left;' // this is needed to prevent offset of 2.5 pixel, see #15771
		}
		Ext.apply(this, {

		});
		TYPO3.iframePanel.superclass.initComponent.apply(this, arguments);

		// apply the addListener patch for 'message:tagging'
		this.addListener = this.on;

	},

	onRender : function() {
		TYPO3.iframePanel.superclass.onRender.apply(this, arguments);
		this.maskMessage = ' ';
		this.iframe = Ext.isIE ? this.body.dom.contentWindow : window.frames[this.name];
		this.body.dom[Ext.isIE ? 'onreadystatechange' : 'onload'] = this.loadHandler.createDelegate(this);
	},

	loadHandler: function() {
		this.src = this.body.dom.src;
		this.removeMask();
	},

	getIframe: function() {
		return this.iframe;
	},
	getUrl: function() {
		return this.body.dom.src;
	},

	setUrl: function(source) {
		this.body.dom.src = source;
		this.setMask();
	},

	resetUrl: function() {
		this.setUrl(this.src);
	},

	getIdFromUrl: function() {
		var url = Ext.urlDecode(this.getUrl().split('?')[1]);
		return url.id;
	},

	refresh: function() {
		if (!this.isVisible()) {
            return;
        }
        this.setMask();
		this.body.dom.src = this.body.dom.src;
	},

	/** @private */
	setMask: function() {
		if (this.doMask) {
			this.el.mask(this.maskMessage, 'x-mask-loading-message');
			this.el.addClass('t3-mask-loading');
				// add an onClick handler to remove the mask while clicking on the loading message
				// useful if user cancels loading and wants to access the content again
			this.el.child('.x-mask-loading-message').on(
				'click',
				function() {
					this.el.unmask();
				},
				this
			);
		}
	},

	removeMask: function() {
		if (this.doMask) {
			this.el.unmask();
		}
	}
});
Ext.reg('iframePanel', TYPO3.iframePanel);
