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

  const sass = require('sass');

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		paths: {
			resources: 'Resources/',
			private: '<%= paths.resources %>Private/Styles',
			public: '<%= paths.resources %>Public/Css'
		},
    sass: {
      options: {
        implementation: sass
      },
      styleguide: {
        files: {
          "<%= paths.public %>/backend.css": "<%= paths.private %>/backend.scss",
          "<%= paths.public %>/frontend.css": "<%= paths.private %>/frontend.scss"
        }
      }
    },
		watch: {
			less: {
				files: '<%= paths.private %>/**/*.scss',
				tasks: 'sass'
			}
		}
	});

	// Register tasks
	grunt.loadNpmTasks('grunt-sass');

	/**
	 * grunt default task
	 *
	 * call "$ grunt"
	 *
	 * this will trigger the CSS build
	 */
	grunt.registerTask('default', ['sass']);

};
