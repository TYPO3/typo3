var typo3pageModule = {
	/**
	 * Initialization
	 */
	init: function() {
		typo3pageModule.enableHighlighting();
	},

	/**
	 * This method is used to bind the higlighting function "setActive"
	 * to the mouseenter event and the "setInactive" to the mouseleave event.
	 */
	enableHighlighting: function() {
		Ext.select('div.t3-page-ce')
			.on('mouseenter',
				typo3pageModule.setActive,
				typo3pageModule)
			.on('mouseleave',
				typo3pageModule.setInactive,
				typo3pageModule);
	},

	/**
	 * This method is used to unbind the higlighting function "setActive"
	 * from the mouseenter event and the "setInactive" from the mouseleave event.
	 */
	disableHighlighting: function() {
		Ext.select('div.t3-page-ce')
			.un('mouseenter',
				typo3pageModule.setActive,
				typo3pageModule)
			.un('mouseleave',
				typo3pageModule.setInactive,
				typo3pageModule);
	},

	/**
	 * This method is used as an event handler when the 
	 * user hovers the a content element.
	 */
	setActive: function(e, t) {
		Ext.get(t).findParent('div.t3-page-ce', null, true).addClass('active');
	},

	/**
	 * This method is used as event handler to unset active state of
	 * a content element when the mouse of the user leaves the 
	 * content element.
	 */
	setInactive: function(e, t) {
		Ext.get(t).findParent('div.t3-page-ce', null, true).removeClass('active');
		
	}
}

Ext.onReady(function() {
	typo3pageModule.init();
});