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

module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		paths: {
			root   : "../",
			t3skin : "<%= paths.root %>typo3/sysext/t3skin/Resources/",
			core   : "<%= paths.root %>typo3/sysext/core/Resources/"
		},
		less: {
			t3skin: {
				options: {
					outputSourceFiles: true
				},
				src : '<%= paths.t3skin %>Private/Styles/t3skin.less',
				dest: '<%= paths.t3skin %>Public/Css/visual/t3skin.css'
			}
		},
		watch: {
			less: {
				files: '<%= paths.t3skin %>Private/Styles/**/*.less',
				tasks: 'less'
			}
		},
		bowercopy: {
			options: {
				clean: false,
				report: false,
				runBower: false,
				srcPrefix: "<%= paths.core %>Contrib/components/"
			},
			all: {
				files: {
					'<%= paths.core %>Public/JavaScript/Contrib/requirejs/': '/requirejs/require.js',
					'<%= paths.core %>Public/JavaScript/Contrib/moment/': '/moment/moment.js'
				}
			}
		}
	});

	// Register tasks
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-bowercopy');
	grunt.loadNpmTasks('grunt-npm-install');
	grunt.loadNpmTasks('grunt-bower-just-install');


	/**
	 * grunt update task
	 *
	 * call "$ grunt update"
	 *
	 * this task does the following things:
	 * - npn install
	 * - bower install
	 * - copy some bower components to a specific destinations because they need to be included via PHP
	 */
	grunt.registerTask('update', ['npm-install', 'bower_install', 'bowercopy']);
};