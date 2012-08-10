/**
 * RequireJS Plugin
 * Module loads a jQuery version and appends it to the global var TYPO3.
 *
 * Can be called like this:
 * <p>[
 * require(['pathToRequirejsPlugins/loadjQuery!pathTojQueryFile'], function() {
 * 	// required jquery version can be used
 * });
 * ]</p>
 *
 * @package TYPO3
 * @subpackage core
 */
define({
	load: function (name, req, load, config) {
		if (name.match(/latest/)) {
			var jQueryObjectPrefix = "latest";
				// Direct path to latest jQuery version which is setup in the typo3/classes/Requirejs.php
			name = "jqueryLatest";
		} else {
				// Generate version name from filename: jquery-1.5rc2 -> v15rc2
			var jQueryObjectPrefix = 'v' + name.split("/").pop().split('-').pop().replace(/\./, '');
		}
		jQueryObjectPrefix = 'jquery' + jQueryObjectPrefix;

		req([name], function (value) {

			if (name.match(/.min/)) {
					// Remove .min for the path prefix
				jQueryObjectPrefix = jQueryObjectPrefix.replace(".min", "");
			}

				// If global var TYPO3 doesn't exists, define it
			if (typeof TYPO3 === 'undefined') {
				TYPO3 = {};
			}

				// Store jquery in globel var TYPO3
			if (typeof TYPO3[jQueryObjectPrefix] === 'undefined') {
				TYPO3[jQueryObjectPrefix] = jQuery.noConflict(true);
			}

				// return - source loaded
			load(value);
		});
	}
});