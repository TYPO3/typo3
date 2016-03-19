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
		banner: '/*!\n' +
			' * This file is part of the TYPO3 CMS project.\n' +
			' *\n' +
			' * It is free software; you can redistribute it and/or modify it under\n' +
			' * the terms of the GNU General Public License, either version 2\n' +
			' * of the License, or any later version.\n' +
			' *\n' +
			' * For the full copyright and license information, please read the\n' +
			' * LICENSE.txt file that was distributed with this source code.\n' +
			' *\n' +
			' * The TYPO3 project - inspiring people to share!\n' +
			' */\n',
		paths: {
			resources: 'Resources/',
			private: '<%= paths.resources %>Private/Styles',
			public: '<%= paths.resources %>Public/Css'
		},
		less: {
			styleguide: {
				options: {
					banner: '<%= banner %>',
					outputSourceFiles: true
				},
				files: {
					"<%= paths.public %>/styles.css": "<%= paths.private %>/backend.less"
				}
			}
		},
		watch: {
			less: {
				files: '<%= paths.private %>/**/*.less',
				tasks: 'less'
			}
		}
	});

	// Register tasks
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-watch');

	/**
	 * grunt default task
	 *
	 * call "$ grunt"
	 *
	 * this will trigger the CSS build
	 */
	grunt.registerTask('default', ['less']);

};
