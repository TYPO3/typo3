/**
 * Initialize
 *
 * Adds a listener to be notified when the document is ready
 * (before onload and before images are loaded).
 * Shorthand of Ext.EventManager.onDocumentReady.
 *
 * @param {Function} fn The method the event invokes.
 * @param {Object} scope (optional) The scope (this reference) in which the handler function executes. Defaults to the browser window.
 * @param {Boolean} options (optional) Options object as passed to {@link Ext.Element#addListener}. It is recommended that the options
 * {single: true} be used so that the handler is removed on first invocation.
 *
 * @return void
 */
Ext.onReady(function() {
		// Instantiate new viewport
	var viewport = new TYPO3.Form.Wizard.Viewport({});
		// When the window is resized, the viewport has to be resized as well
	Ext.EventManager.onWindowResize(viewport.doLayout, viewport);
});
