'use strict';

/**
 * Karma configuration
 */

module.exports = function(config) {
	config.set({
		// base path that will be used to resolve all patterns (eg. files, exclude)
		basePath: '../../../../../../',

		// frameworks to use
		// available frameworks: https://npmjs.org/browse/keyword/karma-adapter
		frameworks: ['jasmine', 'requirejs'],

		// list of files / patterns to load in the browser
		files: [
			{ pattern: 'typo3/sysext/core/Resources/Public/JavaScript/Contrib/jquery/jquery-2.2.3.js', included: true },
			{ pattern: 'typo3/sysext/**/Resources/Public/JavaScript/**/*.js', included: false },
			{ pattern: 'typo3/sysext/**/Tests/JavaScript/**/*.js', included: false },
			'typo3/sysext/core/Build/Configuration/JSUnit/Helper.js',
			'typo3/sysext/core/Build/Configuration/JSUnit/Bootstrap.js'
		],

		// list of files to exclude
		exclude: [
		],

		// preprocess matching files before serving them to the browser
		// available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
		preprocessors: {
			'typo3/sysext/**/Resources/Public/JavaScript/**/*.js': ['coverage']
		},

		// test results reporter to use
		// possible values: 'dots', 'progress', 'coverage', 'junit'
		// available reporters: https://npmjs.org/browse/keyword/karma-reporter
		reporters: ['progress', 'junit', 'coverage'],

		junitReporter: {
			outputDir: 'typo3temp/var/tests/',
			useBrowserName: false,
			outputFile: 'karma.junit.xml'
		},

		coverageReporter: {
			reporters: [
				{type: 'clover', dir: 'typo3temp', subdir: 'var/tests', file: 'karma.clover.xml'}
			]
		},

		// web server port
		port: 9876,

		// enable / disable colors in the output (reporters and logs)
		colors: true,

		// level of logging
		// possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
		logLevel: config.LOG_INFO,

		// enable / disable watching file and executing tests whenever any file changes
		autoWatch: true,

		// start these browsers
		// available browser launchers: https://npmjs.org/browse/keyword/karma-launcher
		// browsers: ['Firefox', 'Chrome', 'Safari', 'PhantomJS', 'Opera', 'IE'],
		browsers: ['PhantomJS'],

		// Continuous Integration mode
		// if true, Karma captures browsers, runs the tests and exits
		singleRun: false,

		// Concurrency level
		// how many browser should be started simultaneous
		concurrency: Infinity
	})
};
