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

	/**
	 * Grunt stylefmt task
	 */
	grunt.registerMultiTask('formatsass', 'Grunt task for stylefmt', function () {
		var options = this.options(),
			done = this.async(),
			stylefmt = require('stylefmt'),
			scss = require('postcss-scss'),
			files = this.filesSrc.filter(function (file) {
				return grunt.file.isFile(file);
			}),
			counter = 0;
		this.files.forEach(function (file) {
			file.src.filter(function (filepath) {
				var content = grunt.file.read(filepath);
				var settings = {
					from: filepath,
					syntax: scss
				};
				stylefmt.process(content, settings).then(function (result) {
					grunt.file.write(file.dest, result.css);
					grunt.log.success('Source file "' + filepath + '" was processed.');
					counter++;
					if (counter >= files.length) done(true);
				});
			});
		});
	});

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		paths: {
			resources : 'Resources/',
			sass      : '<%= paths.resources %>Public/Sass/',
			root      : '../',
			sysext    : '<%= paths.root %>typo3/sysext/',
			form      : '<%= paths.sysext %>form/Resources/',
			frontend  : '<%= paths.sysext %>frontend/Resources/',
			install   : '<%= paths.sysext %>install/Resources/',
			linkvalidator : '<%= paths.sysext %>linkvalidator/Resources/',
			backend   : '<%= paths.sysext %>backend/Resources/',
			t3editor  : '<%= paths.sysext %>t3editor/Resources/',
			workspaces: '<%= paths.sysext %>workspaces/Resources/',
			ckeditor  : '<%= paths.sysext %>rte_ckeditor/Resources/',
			core      : '<%= paths.sysext %>core/Resources/',
			bower     : 'bower_components/',
			flags     : '<%= paths.bower %>region-flags/svg/',
			t3icons   : '<%= paths.bower %>typo3-icons/dist/',
			npm       : 'node_modules/'
		},
		stylelint: {
			options: {
				configFile: '<%= paths.root %>.stylelintrc',
			},
			sass: ['<%= paths.sass %>**/*.scss']
		},
		formatsass: {
			sass: {
				files: [{
					expand: true,
					cwd: '<%= paths.sass %>',
					src: ['**/*.scss'],
					dest: '<%= paths.sass %>'
				}]
			}
		},
		sass: {
			options: {
				outputStyle: 'expanded',
				precision: 8,
				includePaths: [
					'bower_components/bootstrap-sass/assets/stylesheets',
					'bower_components/fontawesome/scss',
					'bower_components/eonasdan-bootstrap-datetimepicker/src/sass',
					'node_modules/tagsort'
				]
			},
			backend: {
				files: {
					"<%= paths.backend %>Public/Css/backend.css": "<%= paths.sass %>backend.scss"
				}
			},
			core: {
				files: {
					"<%= paths.core %>Public/Css/errorpage.css": "<%= paths.sass %>errorpage.scss"
				}
			},
			form: {
				files: {
					"<%= paths.form %>Public/Css/form.css": "<%= paths.sass %>form.scss"
				}
			},
			frontend: {
				files: {
					"<%= paths.frontend %>Public/Css/adminpanel.css": "<%= paths.sass %>adminpanel.scss"
				}
			},
			install: {
				files: {
					"<%= paths.install %>Public/Css/install.css": "<%= paths.sass %>install.scss"
				}
			},
			linkvalidator: {
				files: {
					"<%= paths.linkvalidator %>Public/Css/linkvalidator.css": "<%= paths.sass %>linkvalidator.scss"
				}
			},
			workspaces: {
				files: {
					"<%= paths.workspaces %>Public/Css/preview.css": "<%= paths.sass %>workspace.scss"
				}
			},
			t3editor: {
				files: {
					'<%= paths.t3editor %>Public/Css/t3editor.css': '<%= paths.sass %>editor.scss',
					'<%= paths.t3editor %>Public/Css/t3editor_inner.css': '<%= paths.sass %>editor_inner.scss',
					'<%= paths.t3editor %>Public/Css/t3editor_typoscript_colors.css': '<%= paths.sass %>editor_typoscript_colors.scss'
				}
			}
		},
		postcss: {
			options: {
				map: false,
				processors: [
					require('autoprefixer')({
						browsers: [
							'Chrome >= 57',
							'Firefox >= 52',
							'Edge >= 14',
							'Explorer >= 11',
							'iOS >= 9',
							'Safari >= 8',
							'Android >= 4',
							'Opera >= 43'
						]
					}),
					require('postcss-clean')({
						keepSpecialComments: 0
					}),
					require('postcss-banner')({
						banner: 'This file is part of the TYPO3 CMS project.\n' +
							'\n' +
							'It is free software; you can redistribute it and/or modify it under\n' +
							'the terms of the GNU General Public License, either version 2\n' +
							'of the License, or any later version.\n' +
							'\n' +
							'For the full copyright and license information, please read the\n' +
							'LICENSE.txt file that was distributed with this source code.\n' +
							'\n' +
							'The TYPO3 project - inspiring people to share!',
						important: true,
						inline: false
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
		watch: {
			options: {
				livereload: true
			},
			sass: {
				files: '<%= paths.sass %>**/*.scss',
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
					{ dest: '<%= paths.sysext %>form/Resources/Public/Icons/module-form.svg', src: '<%= paths.t3icons %>module/module-form.svg' },
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
			extension_icons: {
				files: [
					{ dest: '<%= paths.sysext %>form/Resources/Public/Icons/Extension.svg', src: '<%= paths.t3icons %>module/module-form.svg' }
				]
			},
			fonts: {
				files: [
					{ dest: '<%= paths.sysext %>backend/Resources/Public/Fonts/FontAwesome/fontawesome-webfont.eot', src: '<%= paths.bower %>fontawesome/fonts/fontawesome-webfont.eot' },
					{ dest: '<%= paths.sysext %>backend/Resources/Public/Fonts/FontAwesome/fontawesome-webfont.svg', src: '<%= paths.bower %>fontawesome/fonts/fontawesome-webfont.svg' },
					{ dest: '<%= paths.sysext %>backend/Resources/Public/Fonts/FontAwesome/fontawesome-webfont.ttf', src: '<%= paths.bower %>fontawesome/fonts/fontawesome-webfont.ttf' },
					{ dest: '<%= paths.sysext %>backend/Resources/Public/Fonts/FontAwesome/fontawesome-webfont.woff', src: '<%= paths.bower %>fontawesome/fonts/fontawesome-webfont.woff' },
					{ dest: '<%= paths.sysext %>backend/Resources/Public/Fonts/FontAwesome/fontawesome-webfont.woff2', src: '<%= paths.bower %>fontawesome/fonts/fontawesome-webfont.woff2' }
				]
			},
			npm: {
				files: [
					{dest: '<%= paths.install %>Public/JavaScript/chosen.jquery.js', src: '<%= paths.npm %>chosen-js/chosen.jquery.js'}
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
			ckeditor: {
				options: {
					destPrefix: "<%= paths.ckeditor %>Public/JavaScript/Contrib"
				},
				files: {
					'ckeditor.js': 'ckeditor/ckeditor.js',
					'plugins/': 'ckeditor/plugins/',
					'skins/': 'ckeditor/skins/',
					'lang/': 'ckeditor/lang/'
				}
			},
			all: {
				options: {
					destPrefix: "<%= paths.core %>Public/JavaScript/Contrib"
				},
				files: {
					'nprogress.js': 'nprogress/nprogress.js',
					'jquery.matchHeight-min.js': 'matchHeight/dist/jquery.matchHeight-min.js',
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
					/* disabled until autocomplete formatGroup is fixed to pass on the index too
					 'jquery.autocomplete.js': 'devbridge-autocomplete/src/jquery.autocomplete.js',
					 */
					'd3/d3.js': 'd3/d3.min.js',
					/**
					 * copy needed parts of jquery
					 */
					'jquery/jquery-3.2.1.js': 'jquery/dist/jquery.js',
					'jquery/jquery-3.2.1.min.js': 'jquery/dist/jquery.min.js',
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
					"<%= paths.install %>Public/JavaScript/chosen.jquery.min.js": ["<%= paths.install %>Public/JavaScript/chosen.jquery.js"],
					"<%= paths.core %>Public/JavaScript/Contrib/bootstrap-datetimepicker.js": ["<%= paths.core %>Public/JavaScript/Contrib/bootstrap-datetimepicker.js"]
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
	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-bowercopy');
	grunt.loadNpmTasks('grunt-npm-install');
	grunt.loadNpmTasks('grunt-bower-just-install');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-svgmin');
	grunt.loadNpmTasks('grunt-postcss');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks("grunt-ts");
	grunt.loadNpmTasks('grunt-tslint');
	grunt.loadNpmTasks('grunt-stylelint');

	/**
	 * grunt default task
	 *
	 * call "$ grunt"
	 *
	 * this will trigger the CSS build
	 */
	grunt.registerTask('default', ['css']);

	/**
	 * grunt format
	 *
	 * call "$ grunt format"
	 *
	 * this task does the following things:
	 * - formatsass
	 * - lint
	 */
	grunt.registerTask('format', ['formatsass', 'stylelint']);

	/**
	 * grunt css task
	 *
	 * call "$ grunt css"
	 *
	 * this task does the following things:
	 * - sass
	 * - postcss
	 */
	grunt.registerTask('css', ['sass', 'postcss']);

	/**
	 * grunt update task
	 *
	 * call "$ grunt update"
	 *
	 * this task does the following things:
	 * - npm install
	 * - bower install
	 * - copy some bower components to a specific destinations because they need to be included via PHP
	 */
	grunt.registerTask('update', ['npm-install', 'bower_install', 'bowercopy']);

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
	grunt.registerTask('scripts', ['tslint', 'tsclean', 'ts', 'copy:ts_files']);

	grunt.task.registerTask('tsclean', function() {
		grunt.option('force');
		grunt.file.delete("JavaScript");
	});

	/**
	 * grunt build task
	 *
	 * call "$ grunt build"
	 *
	 * this task does the following things:
	 * - execute update task
	 * - execute copy task
	 * - compile sass files
	 * - uglify js files
	 * - minifies svg files
	 * - compiles TypeScript files
	 */
	grunt.registerTask('build', ['update', 'scripts', 'copy', 'format', 'css', 'uglify', 'svgmin']);
};
