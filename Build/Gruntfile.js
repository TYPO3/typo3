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

module.exports = function (grunt) {

  const sass = require('node-sass');

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
      sources: 'Sources/',
      root: '../',
      sass: '<%= paths.sources %>Sass/',
      typescript: '<%= paths.sources %>/TypeScript/',
      sysext: '<%= paths.root %>typo3/sysext/',
      form: '<%= paths.sysext %>form/Resources/',
      dashboard: '<%= paths.sysext %>dashboard/Resources/',
      frontend: '<%= paths.sysext %>frontend/Resources/',
      adminpanel: '<%= paths.sysext %>adminpanel/Resources/',
      install: '<%= paths.sysext %>install/Resources/',
      linkvalidator: '<%= paths.sysext %>linkvalidator/Resources/',
      backend: '<%= paths.sysext %>backend/Resources/',
      t3editor: '<%= paths.sysext %>t3editor/Resources/',
      workspaces: '<%= paths.sysext %>workspaces/Resources/',
      ckeditor: '<%= paths.sysext %>rte_ckeditor/Resources/',
      core: '<%= paths.sysext %>core/Resources/',
      node_modules: 'node_modules/',
      t3icons: '<%= paths.node_modules %>@typo3/icons/dist/'
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
        implementation: sass,
        outputStyle: 'expanded',
        precision: 8,
        includePaths: [
          'node_modules/bootstrap-sass/assets/stylesheets',
          'node_modules/font-awesome/scss',
          'node_modules/eonasdan-bootstrap-datetimepicker/src/sass',
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
      dashboard: {
        files: {
          "<%= paths.dashboard %>Public/Css/dashboard.css": "<%= paths.sass %>dashboard.scss"
        }
      },
      dashboard_modal: {
        files: {
          "<%= paths.dashboard %>Public/Css/Modal/style.css": "<%= paths.sass %>dashboard_modal.scss"
        }
      },
      adminpanel: {
        files: {
          "<%= paths.adminpanel %>Public/Css/adminpanel.css": "<%= paths.sass %>adminpanel.scss"
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
          '<%= paths.t3editor %>Public/Css/t3editor.css': '<%= paths.sass %>editor.scss'
        }
      }
    },
    postcss: {
      options: {
        map: false,
        processors: [
          require('autoprefixer')(),
          require('postcss-clean')({
            rebase: false,
            level: {
              1: {
                specialComments: 0
              }
            }
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
      adminpanel: {
        src: '<%= paths.adminpanel %>Public/Css/*.css'
      },
      backend: {
        src: '<%= paths.backend %>Public/Css/*.css'
      },
      core: {
        src: '<%= paths.core %>Public/Css/*.css'
      },
      dashboard: {
        src: '<%= paths.dashboard %>Public/Css/*.css'
      },
      dashboard_modal: {
        src: '<%= paths.dashboard %>Public/Css/Modal/*.css'
      },
      form: {
        src: '<%= paths.form %>Public/Css/*.css'
      },
      linkvalidator: {
        src: '<%= paths.linkvalidator %>Public/Css/*.css'
      },
      t3editor: {
        src: '<%= paths.t3editor %>Public/Css/**/*.css'
      },
      workspaces: {
        src: '<%= paths.workspaces %>Public/Css/*.css'
      }
    },
    exec: {
      ts: ((process.platform === 'win32') ? 'node_modules\\.bin\\tsc.cmd' : './node_modules/.bin/tsc') + ' --project tsconfig.json',
      'yarn-install': 'yarn install'
    },
    eslint: {
      options: {
        cache: true,
        cacheLocation: './.cache/eslintcache/',
        configFile: 'eslintrc.js'
      },
      files: {
        src: [
          '<%= paths.typescript %>/**/*.ts',
          './types/**/*.ts'
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
        files: '<%= paths.typescript %>/**/*.ts',
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
          cwd: '<%= paths.root %>Build/JavaScript/',
          src: ['**/*.js', '**/*.js.map'],
          dest: '<%= paths.sysext %>',
          rename: function (dest, src) {
            var srccleaned = src.replace('Resources/Public/TypeScript', 'Resources/Public/JavaScript');
            srccleaned = srccleaned.replace('Tests/', 'Tests/JavaScript/');
            var destination = dest + srccleaned;

            // Apply terser configuration for regular files only
            var config = {
              terser: {
                typescript: {
                  files: []
                }
              }
            };
            var uglyfile = {};
            uglyfile[destination] = destination;
            config.terser.typescript.files.push(uglyfile);
            grunt.config.merge(config);

            return destination;
          }
        }]
      },
      core_icons: {
        files: [{
          expand: true,
          cwd: '<%= paths.t3icons %>',
          src: ['**/*.svg', '!install/*', '!module/*'],
          dest: '<%= paths.sysext %>core/Resources/Public/Icons/T3Icons/',
          ext: '.svg'
        }]
      },
      install_icons: {
        files: [
          {
            expand: true,
            cwd: '<%= paths.t3icons %>install/',
            src: ['**/*.svg'],
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/modules/',
            ext: '.svg'
          }
        ]
      },
      module_icons: {
        files: [
          {
            dest: '<%= paths.sysext %>about/Resources/Public/Icons/module-about.svg',
            src: '<%= paths.t3icons %>module/module-about.svg'
          },
          {
            dest: '<%= paths.sysext %>adminpanel/Resources/Public/Icons/module-adminpanel.svg',
            src: '<%= paths.t3icons %>module/module-adminpanel.svg'
          },
          {
            dest: '<%= paths.sysext %>belog/Resources/Public/Icons/module-belog.svg',
            src: '<%= paths.t3icons %>module/module-belog.svg'
          },
          {
            dest: '<%= paths.sysext %>beuser/Resources/Public/Icons/module-beuser.svg',
            src: '<%= paths.t3icons %>module/module-beuser.svg'
          },
          {
            dest: '<%= paths.sysext %>backend/Resources/Public/Icons/module-cshmanual.svg',
            src: '<%= paths.t3icons %>module/module-cshmanual.svg'
          },
          {
            dest: '<%= paths.sysext %>backend/Resources/Public/Icons/module-page.svg',
            src: '<%= paths.t3icons %>module/module-page.svg'
          },
          {
            dest: '<%= paths.sysext %>backend/Resources/Public/Icons/module-sites.svg',
            src: '<%= paths.t3icons %>module/module-sites.svg'
          },
          {
            dest: '<%= paths.sysext %>backend/Resources/Public/Icons/module-templates.svg',
            src: '<%= paths.t3icons %>module/module-templates.svg'
          },
          {
            dest: '<%= paths.sysext %>backend/Resources/Public/Icons/module-urls.svg',
            src: '<%= paths.t3icons %>module/module-urls.svg'
          },
          {
            dest: '<%= paths.sysext %>backend/Resources/Public/Icons/module-contentelements.svg',
            src: '<%= paths.t3icons %>module/module-contentelements.svg'
          },
          {
            dest: '<%= paths.sysext %>lowlevel/Resources/Public/Icons/module-config.svg',
            src: '<%= paths.t3icons %>module/module-config.svg'
          },
          {
            dest: '<%= paths.sysext %>lowlevel/Resources/Public/Icons/module-dbint.svg',
            src: '<%= paths.t3icons %>module/module-dbint.svg'
          },
          {
            dest: '<%= paths.sysext %>extensionmanager/Resources/Public/Icons/module-extensionmanager.svg',
            src: '<%= paths.t3icons %>module/module-extensionmanager.svg'
          },
          {
            dest: '<%= paths.sysext %>filelist/Resources/Public/Icons/module-filelist.svg',
            src: '<%= paths.t3icons %>module/module-filelist.svg'
          },
          {
            dest: '<%= paths.sysext %>form/Resources/Public/Icons/module-form.svg',
            src: '<%= paths.t3icons %>module/module-form.svg'
          },
          {
            dest: '<%= paths.sysext %>indexed_search/Resources/Public/Icons/module-indexed_search.svg',
            src: '<%= paths.t3icons %>module/module-indexed_search.svg'
          },
          {
            dest: '<%= paths.sysext %>info/Resources/Public/Icons/module-info.svg',
            src: '<%= paths.t3icons %>module/module-info.svg'
          },
          {
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install.svg',
            src: '<%= paths.t3icons %>module/module-install.svg'
          },
          {
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install-environment.svg',
            src: '<%= paths.t3icons %>module/module-install-environment.svg'
          },
          {
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install-maintenance.svg',
            src: '<%= paths.t3icons %>module/module-install-maintenance.svg'
          },
          {
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install-settings.svg',
            src: '<%= paths.t3icons %>module/module-install-settings.svg'
          },
          {
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install-upgrade.svg',
            src: '<%= paths.t3icons %>module/module-install-upgrade.svg'
          },
          {
            dest: '<%= paths.sysext %>recordlist/Resources/Public/Icons/module-list.svg',
            src: '<%= paths.t3icons %>module/module-list.svg'
          },
          {
            dest: '<%= paths.sysext %>beuser/Resources/Public/Icons/module-permission.svg',
            src: '<%= paths.t3icons %>module/module-permission.svg'
          },
          {
            dest: '<%= paths.sysext %>recycler/Resources/Public/Icons/module-recycler.svg',
            src: '<%= paths.t3icons %>module/module-recycler.svg'
          },
          {
            dest: '<%= paths.sysext %>reports/Resources/Public/Icons/module-reports.svg',
            src: '<%= paths.t3icons %>module/module-reports.svg'
          },
          {
            dest: '<%= paths.sysext %>scheduler/Resources/Public/Icons/module-scheduler.svg',
            src: '<%= paths.t3icons %>module/module-scheduler.svg'
          },
          {
            dest: '<%= paths.sysext %>setup/Resources/Public/Icons/module-setup.svg',
            src: '<%= paths.t3icons %>module/module-setup.svg'
          },
          {
            dest: '<%= paths.sysext %>tstemplate/Resources/Public/Icons/module-tstemplate.svg',
            src: '<%= paths.t3icons %>module/module-tstemplate.svg'
          },
          {
            dest: '<%= paths.sysext %>viewpage/Resources/Public/Icons/module-viewpage.svg',
            src: '<%= paths.t3icons %>module/module-viewpage.svg'
          },
          {
            dest: '<%= paths.sysext %>workspaces/Resources/Public/Icons/module-workspaces.svg',
            src: '<%= paths.t3icons %>module/module-workspaces.svg'
          }
        ]
      },
      extension_icons: {
        files: [
          {
            dest: '<%= paths.sysext %>form/Resources/Public/Icons/Extension.svg',
            src: '<%= paths.t3icons %>module/module-form.svg'
          },
          {
            dest: '<%= paths.sysext %>rte_ckeditor/Resources/Public/Icons/Extension.svg',
            src: '<%= paths.t3icons %>module/module-rte-ckeditor.svg'
          }
        ]
      },
      fonts: {
        files: [
          {
            expand: true,
            cwd: '<%= paths.node_modules %>source-sans-pro',
            src: ['WOFF/OTF/**', 'WOFF2/TTF/**'],
            dest: '<%= paths.sysext %>backend/Resources/Public/Fonts/SourceSansPro'
          },
          {
            expand: true,
            cwd: '<%= paths.node_modules %>font-awesome/fonts',
            src: ['**/*', '!FontAwesome.otf'],
            dest: '<%= paths.sysext %>backend/Resources/Public/Fonts/FontAwesome'
          }
        ]
      },
      t3editor: {
        files: [
          {
            expand: true,
            cwd: '<%= paths.node_modules %>codemirror',
            dest: '<%= paths.t3editor %>Public/JavaScript/Contrib/cm',
            src: ['**/*', '!**/src/**', '!rollup.config.js']
          }
        ]
      }
    },
    newer: {
      options: {
        cache: './.cache/grunt-newer/'
      }
    },
    npmcopy: {
      options: {
        clean: false,
        report: false,
        srcPrefix: "node_modules/"
      },
      ckeditor: {
        options: {
          copyOptions: {
            // Using null encoding to allow passthrough of binary files in `process`
            encoding: null,
            // Convert CRLF to LF in plain text files to mimic git's autocrlf behaviour
            process: (content, srcpath) => srcpath.match(/\.(css|js|txt|html|md)$/) ? content.toString('utf8').replace(/\r\n/g, '\n') : content
          },
          destPrefix: "<%= paths.ckeditor %>Public/JavaScript/Contrib"
        },
        files: {
          'ckeditor.js': 'ckeditor4/ckeditor.js',
          'plugins/': 'ckeditor4/plugins/',
          'skins/': 'ckeditor4/skins/',
          'lang/': 'ckeditor4/lang/'
        }
      },
      ckeditor_externalplugins: {
        options: {
          copyOptions: {
            // Using null encoding to allow passthrough of binary files in `process`
            encoding: null,
            // Convert CRLF to LF in plain text files to mimic git's autocrlf behaviour
            process: (content, srcpath) => srcpath.match(/\.(css|js|txt|html|md)$/) ? content.toString('utf8').replace(/\r\n/g, '\n') : content
          },
          destPrefix: "<%= paths.ckeditor %>Public/JavaScript/Contrib/plugins"
        },
        files: {
          'wordcount/plugin.js': 'ckeditor-wordcount-plugin/wordcount/plugin.js',
          'wordcount/lang/': 'ckeditor-wordcount-plugin/wordcount/lang/',
          'wordcount/css/': 'ckeditor-wordcount-plugin/wordcount/css/',
        }
      },
      dashboard: {
        options: {
          destPrefix: "<%= paths.dashboard %>Public"
        },
        files: {
          'JavaScript/Contrib/muuri.js': 'muuri/dist/muuri.min.js',
          'JavaScript/Contrib/chartjs.js': 'chart.js/dist/Chart.min.js',
          'Css/Contrib/chart.css': 'chart.js/dist/Chart.min.css'
        }
      },
      all: {
        options: {
          destPrefix: "<%= paths.core %>Public/JavaScript/Contrib"
        },
        files: {
          'nprogress.js': 'nprogress/nprogress.js',
          'tablesort.js': 'tablesort/dist/tablesort.min.js',
          'tablesort.dotsep.js': 'tablesort/dist/sorts/tablesort.dotsep.min.js',
          'require.js': 'requirejs/require.js',
          'moment.js': 'moment/min/moment-with-locales.min.js',
          'moment-timezone.js': 'moment-timezone/builds/moment-timezone-with-data.min.js',
          'cropper.min.js': 'cropper/dist/cropper.min.js',
          'imagesloaded.pkgd.min.js': 'imagesloaded/imagesloaded.pkgd.min.js',
          'bootstrap-datetimepicker.js': 'eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js',
          'autosize.js': 'autosize/dist/autosize.min.js',
          /* disabled for removed sourcemap reference in file
          'taboverride.min.js': 'taboverride/build/output/taboverride.min.js',
          */
          'broadcastchannel-polyfill.js': 'broadcastchannel-polyfill/index.js',
          'jquery.minicolors.js': '../node_modules/@claviska/jquery-minicolors/jquery.minicolors.min.js',
          '../../Images/colorpicker/jquery.minicolors.png': '../node_modules/@claviska/jquery-minicolors/jquery.minicolors.png',
          /* disabled until autocomplete formatGroup is fixed to pass on the index too
                       'jquery.autocomplete.js': '../node_modules/devbridge-autocomplete/dist/jquery.autocomplete.min.js',
                     */
          /**
           * d3/d3.js requires a patch  https://github.com/d3/d3-request/pull/34/files
           * to solve issue with basic auth in Chrome 64, see https://forge.typo3.org/issues/83741
           * for now the file is manually patched by us, thus can't be automatically updated
           */
          // 'd3/d3.js': 'd3/build/d3.min.js',
          /**
           * copy needed parts of jquery
           */
          'jquery/jquery.js': 'jquery/dist/jquery.js',
          'jquery/jquery.min.js': 'jquery/dist/jquery.min.js',
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
          // jquery-ui/sortable.js requires a patch @see: https://github.com/jquery/jquery-ui/pull/1093
          // for this reason this lib is modified and can't be copied
          // For the moment this is ok, because we stuck on version 1.11.4 which is very old
          // the jquery ui stuff should be replaced by modern libs asap
          // 'jquery-ui/sortable.js': 'jquery-ui/ui/sortable.js',
          'jquery-ui/widget.js': 'jquery-ui/ui/widget.js',
          'Sortable.min.js': 'sortablejs/Sortable.min.js'
        }
      }
    },
    terser: {
      options: {
        output: {
          ecma: 8
        }
      },
      thirdparty: {
        files: {
          "<%= paths.core %>Public/JavaScript/Contrib/broadcastchannel-polyfill.js": ["<%= paths.core %>Public/JavaScript/Contrib/broadcastchannel-polyfill.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/require.js": ["<%= paths.core %>Public/JavaScript/Contrib/require.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/nprogress.js": ["<%= paths.core %>Public/JavaScript/Contrib/nprogress.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/core.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/core.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/draggable.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/draggable.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/droppable.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/droppable.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/mouse.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/mouse.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/position.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/position.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/resizable.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/resizable.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/selectable.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/selectable.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/widget.js": ["<%= paths.core %>Public/JavaScript/Contrib/jquery-ui/widget.js"],
          "<%= paths.install %>Public/JavaScript/chosen.jquery.min.js": ["<%= paths.node_modules %>chosen-js/chosen.jquery.js"],
          "<%= paths.core %>Public/JavaScript/Contrib/bootstrap-datetimepicker.js": ["<%= paths.core %>Public/JavaScript/Contrib/bootstrap-datetimepicker.js"]
        }
      },
      t3editor: {
        files: [
          {
            expand: true,
            src: [
              '<%= paths.t3editor %>Public/JavaScript/Contrib/cm/**/*.js',
              '!<%= paths.t3editor %>Public/JavaScript/Contrib/cm/**/*.min.js'
            ],
            dest: '<%= paths.t3editor %>Public/JavaScript/Contrib/cm',
            cwd: '.',
            rename: function (dest, src) {
              return src;
            }
          }
        ]
      },
      typescript: {
        options: {
          output: {
            preamble: '/*\n' +
              ' * This file is part of the TYPO3 CMS project.\n' +
              ' *\n' +
              ' * It is free software; you can redistribute it and/or modify it under\n' +
              ' * the terms of the GNU General Public License, either version 2\n' +
              ' * of the License, or any later version.\n' +
              ' *\n' +
              ' * For the full copyright and license information, please read the\n' +
              ' * LICENSE.txt file that was distributed with this source code.\n' +
              ' *\n' +
              ' * The TYPO3 project - inspiring people to share!' +
              '\n' +
              ' */',
            comments: /^!/
          }
        },
        // Generated by copy:ts_files task
        files: {}
      }
    },
    imagemin: {
      flags: {
        files: [
          {
            cwd: '<%= paths.sysext %>core/Resources/Public/Icons/Flags',
            src: ['**/*.{png,jpg,gif}'],
            dest: '<%= paths.sysext %>core/Resources/Public/Icons/Flags',
            expand: true
          }
        ]
      }
    },
    lintspaces: {
      html: {
        src: [
          '<%= paths.sysext %>*/Resources/Private/**/*.html'
        ],
        options: {
          editorconfig: '../.editorconfig'
        }
      }
    }
  });

  // Register tasks
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-npmcopy');
  grunt.loadNpmTasks('grunt-terser');
  grunt.loadNpmTasks('grunt-postcss');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-exec');
  grunt.loadNpmTasks('grunt-eslint');
  grunt.loadNpmTasks('grunt-stylelint');
  grunt.loadNpmTasks('grunt-lintspaces');
  grunt.loadNpmTasks('grunt-contrib-imagemin');
  grunt.loadNpmTasks('grunt-newer');

  /**
   * grunt default task
   *
   * call "$ grunt"
   *
   * this will trigger the CSS build
   */
  grunt.registerTask('default', ['css']);

  /**
   * grunt lint
   *
   * call "$ grunt lint"
   *
   * this task does the following things:
   * - eslint
   * - stylelint
   * - lintspaces
   */
  grunt.registerTask('lint', ['eslint', 'stylelint', 'lintspaces']);

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
   * - formatsass
   * - sass
   * - postcss
   */
  grunt.registerTask('css', ['formatsass', 'newer:sass', 'newer:postcss']);

  /**
   * grunt update task
   *
   * call "$ grunt update"
   *
   * this task does the following things:
   * - yarn install
   * - copy some components to a specific destinations because they need to be included via PHP
   */
  grunt.registerTask('update', ['exec:yarn-install', 'npmcopy']);

  /**
   * grunt compile-typescript task
   *
   * call "$ grunt compile-typescript"
   *
   * This task does the following things:
   * - 1) Check all TypeScript files (*.ts) with ESLint which are located in sysext/<EXTKEY>/Resources/Private/TypeScript/*.ts
   * - 2) Compiles all TypeScript files (*.ts) which are located in sysext/<EXTKEY>/Resources/Private/TypeScript/*.ts
   */
  grunt.registerTask('compile-typescript', ['tsconfig', 'eslint', 'exec:ts']);

  /**
   * grunt scripts task
   *
   * call "$ grunt scripts"
   *
   * this task does the following things:
   * - 1) Compiles TypeScript (see compile-typescript)
   * - 2) Copy all generated JavaScript files to public folders
   * - 3) Minify build
   */
  grunt.registerTask('scripts', ['compile-typescript', 'newer:copy:ts_files', 'newer:terser:typescript']);

  /**
   * grunt clear-build task
   *
   * call "$ grunt clear-build"
   *
   * Removes all build-related assets, e.g. cache and built files
   */
  grunt.registerTask('clear-build', function () {
    grunt.option('force');
    grunt.file.delete('.cache');
    grunt.file.delete('JavaScript');
  });

  /**
   * grunt tsconfig task
   *
   * call "$ grunt tsconfig"
   *
   * this task updates the tsconfig.json file with modules paths for all sysexts
   */
  grunt.task.registerTask('tsconfig', function () {
    var config = grunt.file.readJSON("tsconfig.json");
    config.compilerOptions.paths = {};
    var sysext = grunt.config.get('paths.sysext');
    grunt.file.expand(sysext + '*/Resources/Public/JavaScript').forEach(function (dir) {
      var extname = ('_' + dir.match(/sysext\/(.*?)\//)[1]).replace(/_./g, function (match) {
        return match.charAt(1).toUpperCase();
      });
      var namespace = 'TYPO3/CMS/' + extname + '/*';
      var path = dir + "/*";
      var extensionTypeScriptPath = path.replace('Public/JavaScript', 'Public/TypeScript').replace(sysext, '');
      config.compilerOptions.paths[namespace] = [path, extensionTypeScriptPath];
    });

    grunt.file.write('tsconfig.json', JSON.stringify(config, null, 4));
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
  grunt.registerTask('build', ['clear-build', 'update', 'compile-typescript', 'copy', 'format', 'css', 'terser', 'imagemin']);
};
