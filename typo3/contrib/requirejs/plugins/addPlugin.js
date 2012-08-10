/**
 * requirejs jquery plugin loader mechanism
 * this loader allows to specify a specific version of jQuery
 * to be required BEFORE the plugin is included.
 */
define({
	load: function (name, req, load, config) {
		var n = name.indexOf(":");

			// path / external URL to plugin that should be loaded
		var loadScript = name.substring(n+1, name.length-1).split('"')[1];

			// jQuery version that is needed
		var jQueryVersionName = name.substring(2, n-1);

		if(jQueryVersionName.match(/latest/)) {
			var jQueryObjectPrefix = "latest";
					// direct path to latest jQuery version which is setup in the DefaultConfiguration.php
			jQueryVersionName = "jqueryLatest";
		} else {
			 // generate version name from filename: jquery-1.5rc2 -> v15rc2
			var jQueryObjectPrefix = 'v' + jQueryVersionName.split("/").pop().split('-').pop().replace(/\./, '');

			if (jQueryObjectPrefix.match(/.min/)) {
					// remove .min for the path prefix
				jQueryObjectPrefix = jQueryObjectPrefix.replace(".min", "");
			}
		}
		jQueryObjectPrefix = 'jquery' + jQueryObjectPrefix;

		if (typeof TYPO3[jQueryObjectPrefix] === 'undefined') {
				// if jQuery version is undefined
			require(['requirejsPlugins/loadjQuery!' + jQueryVersionName], function() {
				jQuery = TYPO3[jQueryObjectPrefix];

				loadRequiredPlugin();
			})
		} else {
				// store reference to requiered jQuery version to register jQuery Plugin
			jQuery = TYPO3[jQueryObjectPrefix];
			loadRequiredPlugin();
		}

		var loadRequiredPlugin = function() {
			req([loadScript], function (value) {
					// return - source loaded
					load(value);
			});
		}

	}
});