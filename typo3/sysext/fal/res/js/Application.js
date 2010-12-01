Ext.ns('TYPO3.Components.filelist');

TYPO3.Components.filelist = Ext.apply(new Ext.util.Observable, {

	/**
	 * List of all bootstrap objects which have been registered
	 * @private
	 */
	bootstrappers: [],

	/**
	 * Main bootstrap. This is called by Ext.onReady and calls all registered
	 * bootstraps.
	 *
	 * This method is called automatically.
	 */
	bootstrap: function() {
		this._registerDummyConsoleLogIfNeeded();
		Ext.util.Observable.capture(this, this._eventDisplayCallback, this);
		this._initializeConfiguration();
		this._invokeBootstrappers();
		Ext.QuickTips.init();
		this.fireEvent('TYPO3.Components.filelist.afterBootstrap');
	},

	/**
	 * Registers a new bootstrap class.
	 */
	registerBootstrap: function(bootstrap) {
		this.bootstrappers.push(bootstrap);
	},

	/**
	 * If the console is deactivated, install a dummy function to prevent errors.
	 * @private
	 */
	_registerDummyConsoleLogIfNeeded: function() {
		if (typeof window.console == 'undefined') {
			window.console = {
				log: function() {}
			};
		}
	},

	/**
	 * Initialize the configuration object in .Configuration
	 * @private
	 */
	_initializeConfiguration: function() {
	},

	/**
	 * Invoke the registered bootstrappers.
	 * @private
	 */
	_invokeBootstrappers: function() {
		Ext.each(
			this.bootstrappers,
			function(bootstrapper) {
				bootstrapper.initialize();
			}
		);
	},

	/**
	 * This method is called for each event going through this event bridge.
	 * @private
	 */
	_eventDisplayCallback: function() {
		//console.log(arguments);
	}
});

Ext.onReady(TYPO3.Components.filelist.bootstrap, TYPO3.Components.filelist);