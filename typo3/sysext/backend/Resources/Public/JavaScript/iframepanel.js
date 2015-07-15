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


/**
 * iFrame panel
 */

Ext.ns('TYPO3');

TYPO3.iframePanel = Ext.extend(Ext.Panel, {
	name: 'iframe',
	iframe: null,
	src: Ext.isIE && Ext.isSecure ? Ext.SSL_SECURE_URL : 'about:blank',
	showLoadingIndicator: true,

		// component build
	initComponent: function() {
		this.bodyCfg = {
			tag: 'iframe',
			frameborder: '0',
			src: this.src,
			name: this.name,
			style: 'float:left;' // this is needed to prevent offset of 2.5 pixel, see #15771
		}
		TYPO3.iframePanel.superclass.initComponent.apply(this, arguments);

		// apply the addListener patch for 'message:tagging'
		this.addListener = this.on;

	},

	onRender : function() {
		TYPO3.iframePanel.superclass.onRender.apply(this, arguments);
		this.iframe = window.frames[this.name];
		this.body.dom['onload'] = this.loadHandler.createDelegate(this);
	},

	loadHandler: function() {
		this.src = this.body.dom.src;
		this.finishLoader();
	},

	getIframe: function() {
		return this.iframe;
	},
	getUrl: function() {
		return this.body.dom.src;
	},

	setUrl: function(source) {
		this.body.dom.src = source;
		this.startLoader();
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
		this.startLoader();
		this.body.dom.src = this.body.dom.src;
	},

	/**
	 * wrapper function for nprogress
	 */
	startLoader: function() {
		if (this.showLoadingIndicator) {
			require(['nprogress'], function(NProgress) {
				NProgress.configure({parent: '#typo3-contentContainer', showSpinner: false});
				NProgress.start();
			});
		}
	},

	finishLoader: function() {
		if (this.showLoadingIndicator) {
			require(['nprogress'], function(NProgress) {
				NProgress.done();
			});
		}
	}
});
Ext.reg('iframePanel', TYPO3.iframePanel);
