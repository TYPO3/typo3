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
			root    : "../",
			t3skin  : "<%= paths.root %>typo3/sysext/t3skin/Resources/",
			core    : "<%= paths.root %>typo3/sysext/core/Resources/"
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
				srcPrefix: "bower_components/"
			},
			all: {
				options: {
					destPrefix: "<%= paths.core %>Public/JavaScript/Contrib",
				},
				files: {
					'nprogress.js': 'nprogress/nprogress.js',
					'jquery.dataTables.js': 'datatables/media/js/jquery.dataTables.min.js',
					'require.js': 'requirejs/require.js',
					'moment.js': 'moment/moment.js',
					'cropper.min.js': 'cropper/dist/cropper.min.js',
					'imagesloaded.pkgd.min.js': 'imagesloaded/imagesloaded.pkgd.min.js',
					'bootstrap-datetimepicker.js': 'eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js',
					'autosize.js': 'autosize/dest/autosize.min.js',
					'placeholders.jquery.min.js': 'Placeholders.js/dist/placeholders.jquery.min.js',
					'taboverride.min.js': 'taboverride/build/output/taboverride.min.js',
					'bootstrap-slider.min.js': 'seiyria-bootstrap-slider/dist/bootstrap-slider.min.js',

					/**
					 * copy needed files of scriptaculous
					 */
					'scriptaculous/builder.js': 'scriptaculous-bower/builder.js',
					'scriptaculous/controls.js': 'scriptaculous-bower/controls.js',
					'scriptaculous/dragdrop.js': 'scriptaculous-bower/dragdrop.js',
					'scriptaculous/effects.js': 'scriptaculous-bower/effects.js',
					'scriptaculous/scriptaculous.js': 'scriptaculous-bower/scriptaculous.js',
					'scriptaculous/slider.js': 'scriptaculous-bower/slider.js',
					'scriptaculous/sound.js': 'scriptaculous-bower/sound.js',
					'scriptaculous/unittest.js': 'scriptaculous-bower/unittest.js',
					/**
					 * copy needed parts of jquery-ui
					 */
					'jquery-ui/core.js': 'jquery-ui/ui/core.js',
					'jquery-ui/draggable.js': 'jquery-ui/ui/draggable.js',
					'jquery-ui/droppable.js': 'jquery-ui/ui/droppable.js',
					'jquery-ui/mouse.js': 'jquery-ui/ui/mouse.js',
					'jquery-ui/position.js': 'jquery-ui/ui/position.js',
					'jquery-ui/resizable.js': 'jquery-ui/ui/resizable.js',
					'jquery-ui/selectable.js': 'jquery-ui/ui/selectable.js',
					'jquery-ui/sortable.js': 'jquery-ui/ui/sortable.js',
					'jquery-ui/widget.js': 'jquery-ui/ui/widget.js'
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
	 * grunt default task
	 *
	 * call "$ grunt"
	 *
	 * this will trigger the less build
	 */
	grunt.registerTask('default', ['less']);

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
