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
			form      : '<%= paths.sysext %>form/Resources/',
			frontend  : '<%= paths.sysext %>frontend/Resources/',
			install   : '<%= paths.sysext %>install/Resources/',
			linkvalidator : '<%= paths.sysext %>linkvalidator/Resources/',
			backend   : '<%= paths.sysext %>backend/Resources/',
			t3editor  : '<%= paths.sysext %>t3editor/Resources/',
			workspaces: '<%= paths.sysext %>workspaces/Resources/',
			core      : '<%= paths.sysext %>core/Resources/',
			bower     : 'bower_components/',
			flags     : '<%= paths.bower %>region-flags/svg/',
			t3icons   : '<%= paths.bower %>wmdbsystems-typo3-icons/dist/',
			npm       : 'node_modules/'
		},
		less: {
			options: {
				banner: '<%= banner %>',
				outputSourceFiles: true
			},
			backend: {
				files: {
					"<%= paths.backend %>Public/Css/backend.css": "<%= paths.less %>backend.less"
				}
			},
			core: {
				files: {
					"<%= paths.core %>Public/Css/errorpage.css": "<%= paths.less %>errorpage.less"
				}
			},
			form: {
				files: {
					"<%= paths.form %>Public/Css/form.css": "<%= paths.less %>form.less"
				}
			},
			frontend: {
				files: {
					"<%= paths.frontend %>Public/Css/adminpanel.css": "<%= paths.less %>adminpanel.less"
				}
			},
			install: {
				files: {
					"<%= paths.install %>Public/Css/install.css": "<%= paths.less %>install.less"
				}
			},
			linkvalidator: {
				files: {
					"<%= paths.linkvalidator %>Public/Css/linkvalidator.css": "<%= paths.less %>linkvalidator.less"
				}
			},
			workspaces: {
				files: {
					"<%= paths.workspaces %>Public/Css/preview.css": "<%= paths.workspaces %>Private/Less/preview.less"
				}
			},
			t3editor: {
				files: {
					'<%= paths.t3editor %>Public/Css/t3editor.css': '<%= paths.t3editor %>Private/Less/t3editor.less',
					'<%= paths.t3editor %>Public/Css/t3editor_inner.css': '<%= paths.t3editor %>Private/Less/t3editor_inner.less',
					'<%= paths.t3editor %>Public/Css/t3editor_typoscript_colors.css': '<%= paths.t3editor %>Private/Less/t3editor_typoscript_colors.less'
				}
			}
		},
		postcss: {
			options: {
				map: false,
				processors: [
					require('autoprefixer')({ // add vendor prefixes
						browsers: [
							'Last 2 versions',
							'Firefox ESR',
							'IE 11'
						]
					})
				]
			},
			backend: {
				src: '<%= paths.backend %>Public/Css/*.css'
			},
			core: {
				src: '<%= paths.core %>Public/Css/*.css'
			},
			form: {
				src: '<%= paths.form %>Public/Css/*.css'
			},
			frontend: {
				src: '<%= paths.frontend %>Public/Css/*.css'
			},
			install: {
				src: '<%= paths.install %>Public/Css/*.css'
			},
			linkvalidator: {
				src: '<%= paths.linkvalidator %>Public/Css/*.css'
			},
			t3editor: {
				src: '<%= paths.t3editor %>Public/Css/*.css'
			},
			workspaces: {
				src: '<%= paths.workspaces %>Public/Css/*.css'
			}
		},
		ts: {
			default : {
				tsconfig: true,
				options: {
					verbose: false
				}
			}
		},
		tslint: {
			options: {
				configuration: 'tslint.json',
				force: false
			},
			files: {
				src: [
					'<%= paths.sysext %>*/Resources/Private/TypeScript/**/*.ts'
				]
			}
		},
		typings: {
			install: {}
		},
		watch: {
			options: {
				livereload: true
			},
			less: {
				files: '<%= paths.less %>**/*.less',
				tasks: 'css'
			},
			ts: {
				files: '<%= paths.sysext %>*/Resources/Private/TypeScript/**/*.ts',
				tasks: 'scripts'
			}
		},
		copy: {
			options: {
				punctuation: ''
			},
			ts_files: {
				files: [{
					expand: true,
					cwd: '<%= paths.root %>Build/JavaScript/typo3/sysext/',
					src: ['**/*.js', '**/*.js.map'],
					dest: '<%= paths.sysext %>',
					rename: function(dest, src) {
						return dest + src.replace('Resources/Private/TypeScript', 'Resources/Public/JavaScript');
					}
				}]
			},
			core_icons: {
				files: [{
					expand: true,
					cwd: '<%= paths.t3icons %>',
					src: ['**/*.svg', '!module/*'],
					dest: '<%= paths.sysext %>core/Resources/Public/Icons/T3Icons/',
					ext: '.svg'
				}]
			},
			module_icons: {
				files: [
					{ dest: '<%= paths.sysext %>about/Resources/Public/Icons/module-about.svg', src: '<%= paths.t3icons %>module/module-about.svg' },
					{ dest: '<%= paths.sysext %>belog/Resources/Public/Icons/module-belog.svg', src: '<%= paths.t3icons %>module/module-belog.svg' },
					{ dest: '<%= paths.sysext %>beuser/Resources/Public/Icons/module-beuser.svg', src: '<%= paths.t3icons %>module/module-beuser.svg' },
					{ dest: '<%= paths.sysext %>lowlevel/Resources/Public/Icons/module-config.svg', src: '<%= paths.t3icons %>module/module-config.svg' },
					{ dest: '<%= paths.sysext %>cshmanual/Resources/Public/Icons/module-cshmanual.svg', src: '<%= paths.t3icons %>module/module-cshmanual.svg' },
					{ dest: '<%= paths.sysext %>lowlevel/Resources/Public/Icons/module-dbint.svg', src: '<%= paths.t3icons %>module/module-dbint.svg' },
					{ dest: '<%= paths.sysext %>documentation/Resources/Public/Icons/module-documentation.svg', src: '<%= paths.t3icons %>module/module-documentation.svg' },
					{ dest: '<%= paths.sysext %>extensionmanager/Resources/Public/Icons/module-extensionmanager.svg', src: '<%= paths.t3icons %>module/module-extensionmanager.svg' },
					{ dest: '<%= paths.sysext %>filelist/Resources/Public/Icons/module-filelist.svg', src: '<%= paths.t3icons %>module/module-filelist.svg' },
					{ dest: '<%= paths.sysext %>func/Resources/Public/Icons/module-func.svg', src: '<%= paths.t3icons %>module/module-func.svg' },
					{ dest: '<%= paths.sysext %>indexed_search/Resources/Public/Icons/module-indexed_search.svg', src: '<%= paths.t3icons %>module/module-indexed_search.svg' },
					{ dest: '<%= paths.sysext %>info/Resources/Public/Icons/module-info.svg', src: '<%= paths.t3icons %>module/module-info.svg' },
					{ dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install.svg', src: '<%= paths.t3icons %>module/module-install.svg' },
					{ dest: '<%= paths.sysext %>lang/Resources/Public/Icons/module-lang.svg', src: '<%= paths.t3icons %>module/module-lang.svg' },
					{ dest: '<%= paths.sysext %>recordlist/Resources/Public/Icons/module-list.svg', src: '<%= paths.t3icons %>module/module-list.svg' },
					{ dest: '<%= paths.sysext %>backend/Resources/Public/Icons/module-page.svg', src: '<%= paths.t3icons %>module/module-page.svg' },
					{ dest: '<%= paths.sysext %>beuser/Resources/Public/Icons/module-permission.svg', src: '<%= paths.t3icons %>module/module-permission.svg' },
					{ dest: '<%= paths.sysext %>recycler/Resources/Public/Icons/module-recycler.svg', src: '<%= paths.t3icons %>module/module-recycler.svg' },
					{ dest: '<%= paths.sysext %>reports/Resources/Public/Icons/module-reports.svg', src: '<%= paths.t3icons %>module/module-reports.svg' },
					{ dest: '<%= paths.sysext %>scheduler/Resources/Public/Icons/module-scheduler.svg', src: '<%= paths.t3icons %>module/module-scheduler.svg' },
					{ dest: '<%= paths.sysext %>setup/Resources/Public/Icons/module-setup.svg', src: '<%= paths.t3icons %>module/module-setup.svg' },
					{ dest: '<%= paths.sysext %>taskcenter/Resources/Public/Icons/module-taskcenter.svg', src: '<%= paths.t3icons %>module/module-taskcenter.svg' },
					{ dest: '<%= paths.sysext %>tstemplate/Resources/Public/Icons/module-tstemplate.svg', src: '<%= paths.t3icons %>module/module-tstemplate.svg' },
					{ dest: '<%= paths.sysext %>version/Resources/Public/Icons/module-version.svg', src: '<%= paths.t3icons %>module/module-version.svg' },
					{ dest: '<%= paths.sysext %>viewpage/Resources/Public/Icons/module-viewpage.svg', src: '<%= paths.t3icons %>module/module-viewpage.svg' },
					{ dest: '<%= paths.sysext %>workspaces/Resources/Public/Icons/module-workspaces.svg', src: '<%= paths.t3icons %>module/module-workspaces.svg' }
				]
			},
			npm: {
				files: [
					{dest: '<%= paths.install %>Public/JavaScript/tagsort.min.js', src: '<%= paths.npm %>tagsort/tagsort.js'}
				]
			}
		},
		bowercopy: {
			options: {
				clean: false,
				report: false,
				runBower: false,
				srcPrefix: "bower_components/"
			},
			glob: {
				files: {
					// When using glob patterns, destinations are *always* folder names
					// into which matching files will be copied
					// Also note that subdirectories are **not** maintained
					// if a destination is specified
					// For example, one of the files copied here is
					// 'lodash/dist/lodash.js' -> 'public/js/libs/lodash/lodash.js'
					'<%= paths.sysext %>core/Resources/Public/Images/colorpicker': 'jquery-minicolors/*.png'
				}
			},
			all: {
				options: {
					destPrefix: "<%= paths.core %>Public/JavaScript/Contrib"
				},
				files: {
					'nprogress.js': 'nprogress/nprogress.js',
					'jquery.matchHeight-min.js': 'matchHeight/jquery.matchHeight-min.js',
					'jquery.dataTables.js': 'datatables/media/js/jquery.dataTables.min.js',
					'require.js': 'requirejs/require.js',
					'moment.js': 'moment/min/moment-with-locales.min.js',
					'moment-timezone.js': 'moment-timezone/builds/moment-timezone-with-data.min.js',
					'cropper.min.js': 'cropper/dist/cropper.min.js',
					'imagesloaded.pkgd.min.js': 'imagesloaded/imagesloaded.pkgd.min.js',
					'bootstrap-datetimepicker.js': 'eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js',
					'autosize.js': 'autosize/dist/autosize.min.js',
					'taboverride.min.js': 'taboverride/build/output/taboverride.min.js',
					'bootstrap-slider.min.js': 'seiyria-bootstrap-slider/dist/bootstrap-slider.min.js',
					/* disabled until events are not bound to document only
						see https://github.com/claviska/jquery-minicolors/issues/192
						see https://github.com/claviska/jquery-minicolors/issues/206
					'jquery.minicolors.js': 'jquery-minicolors/jquery.minicolors.min.js',
					 */
					/* disabled until autocomplete groupBy is fixed by the author
						see https://github.com/devbridge/jQuery-Autocomplete/pull/387
					'jquery.autocomplete.js': 'devbridge-autocomplete/src/jquery.autocomplete.js',
					 */
					'd3/d3.js': 'd3/d3.min.js',
					/**
					 * copy needed parts of jquery
					 */
					'jquery/jquery-2.2.3.js': 'jquery/dist/jquery.js',
					'jquery/jquery-2.2.3.min.js': 'jquery/dist/jquery.min.js',
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
		uglify: {
			thirdparty: {
				files: {
					"<%= paths.core %>Public/JavaScript/Contrib/require.js": ["<%= paths.core %>Public/JavaScript/Contrib/require.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/nprogress.js": ["<%= paths.core %>Public/JavaScript/Contrib/nprogress.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/core.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/core.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/draggable.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/draggable.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/droppable.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/droppable.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/mouse.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/mouse.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/position.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/position.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/resizable.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/resizable.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/selectable.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/selectable.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/sortable.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/sortable.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/widget.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/widget.js"],
					"<%= paths.install %>Public/JavaScript/tagsort.min.js": ["<%= paths.install %>Public/JavaScript/tagsort.min.js"]
				}
			}
		},
		svgmin: {
			options: {
				plugins: [
					{ removeViewBox: false }
				]
			},
			// Flags
			flags: {
				files: [{
					expand: true,
					cwd: '<%= paths.flags %>',
					src: '*.svg',
					dest: '<%= paths.sysext %>core/Resources/Public/Icons/Flags/SVG/',
					ext: '.svg',
					extDot: 'first'
				}]
			}
		}
	});

	// Register tasks
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-bowercopy');
	grunt.loadNpmTasks('grunt-npm-install');
	grunt.loadNpmTasks('grunt-bower-just-install');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-svgmin');
	grunt.loadNpmTasks('grunt-postcss');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-copy');
	grunt.loadNpmTasks("grunt-ts");
	grunt.loadNpmTasks('grunt-tslint');
	grunt.loadNpmTasks('grunt-typings');

	/**
	 * grunt default task
	 *
	 * call "$ grunt"
	 *
	 * this will trigger the CSS build
	 */
	grunt.registerTask('default', ['css']);

	/**
	 * grunt css task
	 *
	 * call "$ grunt css"
	 *
	 * this task does the following things:
	 * - less
	 * - postcss
	 */
	grunt.registerTask('css', ['less', 'postcss']);

	/**
	 * grunt update task
	 *
	 * call "$ grunt update"
	 *
	 * this task does the following things:
	 * - npm install
	 * - typings install
	 * - bower install
	 * - copy some bower components to a specific destinations because they need to be included via PHP
	 */
	grunt.registerTask('update', ['npm-install', 'typings', 'bower_install', 'bowercopy']);

	/**
	 * grunt scripts task
	 *
	 * call "$ grunt scripts"
	 *
	 * this task does the following things:
	 * - 1) Check all TypeScript files (*.ts) with TSLint which are located in sysext/<EXTKEY>/Resources/Private/TypeScript/*.ts
	 * - 2) Compiles all TypeScript files (*.ts) which are located in sysext/<EXTKEY>/Resources/Private/TypeScript/*.ts
	 * - 3) Copy all generated JavaScript and Map files to public folders
	 */
	grunt.registerTask('scripts', ['tslint', 'ts', 'copy:ts_files']);

	/**
	 * grunt build task
	 *
	 * call "$ grunt build"
	 *
	 * this task does the following things:
	 * - execute update task
	 * - execute copy task
	 * - compile less files
	 * - uglify js files
	 * - minifies svg files
	 * - compiles TypeScript files
	 */
	grunt.registerTask('build', ['update', 'scripts', 'copy', 'css', 'uglify', 'svgmin']);
};
