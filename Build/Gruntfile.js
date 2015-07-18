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
			resources : 'Resources/',
			less      : '<%= paths.resources %>Public/Less/',
			root      : '../',
			sysext    : '<%= paths.root %>typo3/sysext/',
			t3skin    : '<%= paths.sysext %>t3skin/Resources/',
			core      : '<%= paths.sysext %>core/Resources/'
		},
		less: {
			t3skin: {
				options: {
					banner: '<%= banner %>',
					outputSourceFiles: true
				},
				files: {
					"<%= paths.t3skin %>Public/Css/backend.css": "<%= paths.less %>backend.less"
				}
			}
		},
		watch: {
			less: {
				files: '<%= paths.less %>**/*.less',
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
					destPrefix: "<%= paths.core %>Public/JavaScript/Contrib"
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
					/* disabled until autocomplete groupBy is fixed by the author
						see https://github.com/devbridge/jQuery-Autocomplete/pull/387
					'jquery.autocomplete.js': 'devbridge-autocomplete/src/jquery.autocomplete.js',
					 */

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
					 * copy needed parts of jquery
					 */
					'jquery/jquery-1.11.3.js': 'jquery/dist/jquery.js',
					'jquery/jquery-1.11.3.min.js': 'jquery/dist/jquery.min.js',
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
		},
		copy: {
			/**
			 * Copy icons into correct location
			 */
			icons: {
				files: [
					{ src: 'Resources/Private/Icons/module-about.svg', dest: '<%= paths.sysext %>about/Resources/Public/Icons/module-about.svg' },
					{ src: 'Resources/Private/Icons/module-aboutmodules.svg', dest: '<%= paths.sysext %>aboutmodules/Resources/Public/Icons/module-aboutmodules.svg' },
					{ src: 'Resources/Private/Icons/module-belog.svg', dest: '<%= paths.sysext %>belog/Resources/Public/Icons/module-belog.svg' },
					{ src: 'Resources/Private/Icons/module-beuser.svg', dest: '<%= paths.sysext %>beuser/Resources/Public/Icons/module-beuser.svg' },
					{ src: 'Resources/Private/Icons/module-config.svg', dest: '<%= paths.sysext %>lowlevel/Resources/Public/Icons/module-config.svg' },
					{ src: 'Resources/Private/Icons/module-cshmanual.svg', dest: '<%= paths.sysext %>cshmanual/Resources/Public/Icons/module-cshmanual.svg' },
					{ src: 'Resources/Private/Icons/module-dbal.svg', dest: '<%= paths.sysext %>dbal/Resources/Public/Icons/module-dbal.svg' },
					{ src: 'Resources/Private/Icons/module-dbint.svg', dest: '<%= paths.sysext %>lowlevel/Resources/Public/Icons/module-dbint.svg' },
					{ src: 'Resources/Private/Icons/module-documentation.svg', dest: '<%= paths.sysext %>documentation/Resources/Public/Icons/module-documentation.svg' },
					{ src: 'Resources/Private/Icons/module-extensionmanager.svg', dest: '<%= paths.sysext %>extensionmanager/Resources/Public/Icons/module-extensionmanager.svg' },
					{ src: 'Resources/Private/Icons/module-filelist.svg', dest: '<%= paths.sysext %>filelist/Resources/Public/Icons/module-filelist.svg' },
					{ src: 'Resources/Private/Icons/module-func.svg', dest: '<%= paths.sysext %>func/Resources/Public/Icons/module-func.svg' },
					{ src: 'Resources/Private/Icons/module-indexed_search.svg', dest: '<%= paths.sysext %>indexed_search/Resources/Public/Icons/module-indexed_search.svg' },
					{ src: 'Resources/Private/Icons/module-info.svg', dest: '<%= paths.sysext %>info/Resources/Public/Icons/module-info.svg' },
					{ src: 'Resources/Private/Icons/module-install.svg', dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install.svg' },
					{ src: 'Resources/Private/Icons/module-lang.svg', dest: '<%= paths.sysext %>lang/Resources/Public/Icons/module-lang.svg' },
					{ src: 'Resources/Private/Icons/module-list.svg', dest: '<%= paths.sysext %>recordlist/Resources/Public/Icons/module-list.svg' },
					{ src: 'Resources/Private/Icons/module-page.svg', dest: '<%= paths.sysext %>backend/Resources/Public/Icons/module-page.svg' },
					{ src: 'Resources/Private/Icons/module-permission.svg', dest: '<%= paths.sysext %>beuser/Resources/Public/Icons/module-permission.svg' },
					{ src: 'Resources/Private/Icons/module-recycler.svg', dest: '<%= paths.sysext %>recycler/Resources/Public/Icons/module-recycler.svg' },
					{ src: 'Resources/Private/Icons/module-reports.svg', dest: '<%= paths.sysext %>reports/Resources/Public/Icons/module-reports.svg' },
					{ src: 'Resources/Private/Icons/module-scheduler.svg', dest: '<%= paths.sysext %>scheduler/Resources/Public/Icons/module-scheduler.svg' },
					{ src: 'Resources/Private/Icons/module-setup.svg', dest: '<%= paths.sysext %>setup/Resources/Public/Icons/module-setup.svg' },
					{ src: 'Resources/Private/Icons/module-taskcenter.svg', dest: '<%= paths.sysext %>taskcenter/Resources/Public/Icons/module-taskcenter.svg' },
					{ src: 'Resources/Private/Icons/module-tstemplate.svg', dest: '<%= paths.sysext %>tstemplate/Resources/Public/Icons/module-tstemplate.svg' },
					{ src: 'Resources/Private/Icons/module-version.svg', dest: '<%= paths.sysext %>version/Resources/Public/Icons/module-version.svg' },
					{ src: 'Resources/Private/Icons/module-viewpage.svg', dest: '<%= paths.sysext %>viewpage/Resources/Public/Icons/module-viewpage.svg' },
					{ src: 'Resources/Private/Icons/module-workspaces.svg', dest: '<%= paths.sysext %>workspaces/Resources/Public/Icons/module-workspaces.svg' }
				]
			}
		}
	});

	// Register tasks
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-bowercopy');
	grunt.loadNpmTasks('grunt-contrib-copy');
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

	/**
	 * grunt task to copy icons into correct location
	 */
	grunt.registerTask('build', ['copy:icons']);
};
