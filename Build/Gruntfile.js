/* eslint-env node, commonjs */
/* eslint-disable @typescript-eslint/no-var-requires */

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

  const sass = require('sass');
  const esModuleLexer = require('es-module-lexer');

  grunt.registerTask('ckeditor:compile-locales', 'locale compilation for CKEditor', function() {
    require('./Scripts/node/ckeditor-locale-compiler.js');
  })

  /**
   * Grunt flag tasks
   */
  grunt.registerMultiTask('flags', 'Grunt task rendering the flags', function () {
    let done = this.async(),
      path = require('path'),
      sharp = require('sharp'),
      filesize = require('filesize'),
      files = this.filesSrc.filter(function (file) {
        return grunt.file.isFile(file);
      }),
      counter = 0;
    this.files.forEach(function (file) {
      file.src.filter(function (filepath) {
        const targetFilename = path.join(file.orig.dest, file.dest.substring(file.orig.dest.length, file.dest.length - 4) + '.webp');
        const overlay = Buffer.from('<svg width="32" height="32">	<path opacity="0.15" d="M30,6v20H2V6H30 M32,4H0v24h32V4L32,4z"/></svg>');
        sharp(filepath)
          .webp()
          .flatten({ background: '#ffffff' })
          .resize(32, 32, {
            fit: 'contain',
            position: 'center',
            background: 'transparent',
          })
          .composite([{ input: overlay }])
          .toFile(targetFilename)
          .then(data => {
            grunt.log.ok(`File ${targetFilename} created. ${filesize.filesize(data.size)}`)
            counter++;
            if (counter >= files.length) {
              done(true);
            }
          }).catch(function (err) {
            grunt.log.error('File "' + targetFilename + '" was not processed.');
            console.log(err)
          });
      });
    });
  });
  grunt.registerTask('flags-clear', function () {
    const path = '../typo3/sysext/core/Resources/Public/Icons/Flags';
    grunt.file.delete(path, { force: true });
    grunt.file.mkdir(path);
    grunt.log.ok(`Cleared ${path}.`)
  });
  grunt.registerTask('flags-build', ['flags-clear', 'flags']);

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    paths: {
      sources: 'Sources/',
      root: '../',
      sass: '<%= paths.sources %>Sass/',
      typescript: '<%= paths.sources %>TypeScript/',
      sysext: '<%= paths.root %>typo3/sysext/',
      form: '<%= paths.sysext %>form/Resources/',
      dashboard: '<%= paths.sysext %>dashboard/Resources/',
      frontend: '<%= paths.sysext %>frontend/Resources/',
      adminpanel: '<%= paths.sysext %>adminpanel/Resources/',
      install: '<%= paths.sysext %>install/Resources/',
      linkvalidator: '<%= paths.sysext %>linkvalidator/Resources/',
      backend: '<%= paths.sysext %>backend/Resources/',
      styleguide: '<%= paths.sysext %>styleguide/Resources/',
      workspaces: '<%= paths.sysext %>workspaces/Resources/',
      ckeditor: '<%= paths.sysext %>rte_ckeditor/Resources/',
      core: '<%= paths.sysext %>core/Resources/',
      node_modules: 'node_modules/',
      t3icons: '<%= paths.node_modules %>@typo3/icons/dist/'
    },
    flags: {
      flagIcons: {
        files: [{
          expand: true,
          cwd: '<%= paths.node_modules %>flag-icons/flags/4x3',
          // Excludes
          // - cp: Clipperton
          // - dg: Diego Garcia
          // - cefta: Central European Free Trade Agreement
          // - ta: Tristan da Cunha
          // - un: United Nations
          // - um: United States Minor Outlying Islands
          // - xx: Placeholder
          src: ['**/*.svg', '!cp.svg', '!dg.svg', '!cefta.svg', '!ta.svg', '!um.svg', '!un.svg', '!xx.svg'],
          dest: '<%= paths.core %>/Public/Icons/Flags'
        }]
      },
      overrides: {
        files: [{
          expand: true,
          cwd: '<%= paths.sources %>Icons/Flags',
          src: ['**/*.svg'],
          dest: '<%= paths.core %>/Public/Icons/Flags'
        }]
      },
    },
    stylelint: {
      options: {
        configFile: '<%= paths.root %>/Build/.stylelintrc',
      },
      sass: ['<%= paths.sass %>**/*.scss']
    },
    sass: {
      options: {
        implementation: sass,
        outputStyle: 'expanded',
        precision: 8
      },
      backend: {
        files: {
          '<%= paths.backend %>Public/Css/backend.css': '<%= paths.sass %>backend.scss'
        }
      },
      form: {
        files: {
          '<%= paths.form %>Public/Css/form.css': '<%= paths.sass %>form.scss'
        }
      },
      dashboard: {
        files: {
          '<%= paths.dashboard %>Public/Css/dashboard.css': '<%= paths.sass %>dashboard.scss'
        }
      },
      dashboard_modal: {
        files: {
          '<%= paths.dashboard %>Public/Css/Modal/style.css': '<%= paths.sass %>dashboard_modal.scss'
        }
      },
      adminpanel: {
        files: {
          '<%= paths.adminpanel %>Public/Css/adminpanel.css': '<%= paths.sass %>adminpanel.scss'
        }
      },
      styleguide: {
        files: {
          '<%= paths.styleguide %>Public/Css/styleguide-frontend.css': '<%= paths.sass %>styleguide-frontend.scss'
        }
      },
      webfonts: {
        files: {
          '<%= paths.backend %>Public/Css/webfonts.css': '<%= paths.sass %>webfonts.scss'
        }
      },
      workspaces: {
        files: {
          '<%= paths.workspaces %>Public/Css/preview.css': '<%= paths.sass %>workspace.scss'
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
            format: 'keep-breaks',
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
      dashboard: {
        src: '<%= paths.dashboard %>Public/Css/*.css'
      },
      dashboard_modal: {
        src: '<%= paths.dashboard %>Public/Css/Modal/*.css'
      },
      form: {
        src: '<%= paths.form %>Public/Css/*.css'
      },
      styleguide: {
        src: '<%= paths.styleguide %>Public/Css/*.css'
      },
      workspaces: {
        src: '<%= paths.workspaces %>Public/Css/*.css'
      }
    },
    exec: {
      ts: ((process.platform === 'win32') ? 'node_modules\\.bin\\tsc.cmd' : './node_modules/.bin/tsc') + ' --project tsconfig.json',
      rollup: ((process.platform === 'win32') ? 'node_modules\\.bin\\rollup.cmd' : './node_modules/.bin/rollup') + ' -c rollup/config.js',
      stylefix: ((process.platform === 'win32') ? 'node_modules\\.bin\\stylelint.cmd' : './node_modules/.bin/stylelint') + ' "<%= paths.sass %>**/*.scss" --fix --formatter verbose --cache --cache-location .cache/.stylelintcache --cache-strategy content',
      lintspaces: ((process.platform === 'win32') ? 'node_modules\\.bin\\lintspaces.cmd' : './node_modules/.bin/lintspaces') + ' --editorconfig ../.editorconfig "../typo3/sysext/*/Resources/Private/**/*.html"',
      'npm-install': 'npm install'
    },
    eslint: {
      options: {
        cache: true,
        cacheLocation: './.cache/eslintcache/'
      },
      files: {
        src: [
          '<%= paths.typescript %>/**/*.ts',
          './types/**/*.ts'
        ]
      }
    },
    watch: {
      sass: {
        files: '<%= paths.sass %>**/*.scss',
        tasks: ['css', 'bell']
      },
      ts: {
        files: '<%= paths.typescript %>/**/*.ts',
        tasks: ['scripts', 'bell']
      }
    },
    copy: {
      options: {
        punctuation: ''
      },
      ts_files: {
        options: {
          process: (source, srcpath) => {
            /* note: This requires grunt-task 'es-module-lexer-init' to be executed prior to this task */
            const [imports] = esModuleLexer.parse(source, srcpath);

            source = require('./util/map-import.js').mapImports(source, srcpath, imports);

            // Workaround for https://github.com/microsoft/TypeScript/issues/35802
            // > The 'this' keyword is equivalent to 'undefined' at the top level of an ES module
            source = source.replace('__decorate=this&&this.__decorate||function', '__decorate=function');

            return source;
          }
        },
        files: [{
          expand: true,
          cwd: '<%= paths.root %>Build/JavaScript/',
          src: ['**/*.js', '**/*.js.map', '!*/tests/**/*'],
          dest: '<%= paths.sysext %>',
          rename: (dest, src) => dest + src
            .replace('/', '/Resources/Public/JavaScript/')
        }]
      },
      core_icons: {
        files: [{
          expand: true,
          cwd: '<%= paths.t3icons %>',
          src: ['**/*.svg', 'icons.json', '!install/*', '!module/*'],
          dest: '<%= paths.sysext %>core/Resources/Public/Icons/T3Icons/',
        }]
      },
      install_icons: {
        files: [
          {
            expand: true,
            cwd: '<%= paths.t3icons %>svgs/install/',
            src: ['**/*.svg'],
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/modules/',
          }
        ]
      },
      module_icons: {
        files: [
          {
            dest: '<%= paths.sysext %>adminpanel/Resources/Public/Icons/module-adminpanel.svg',
            src: '<%= paths.t3icons %>svgs/module/module-adminpanel.svg'
          },
          {
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install.svg',
            src: '<%= paths.t3icons %>svgs/module/module-install.svg'
          },
          {
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install-environment.svg',
            src: '<%= paths.t3icons %>svgs/module/module-install-environment.svg'
          },
          {
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install-maintenance.svg',
            src: '<%= paths.t3icons %>svgs/module/module-install-maintenance.svg'
          },
          {
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install-settings.svg',
            src: '<%= paths.t3icons %>svgs/module/module-install-settings.svg'
          },
          {
            dest: '<%= paths.sysext %>install/Resources/Public/Icons/module-install-upgrade.svg',
            src: '<%= paths.t3icons %>svgs/module/module-install-upgrade.svg'
          }
        ]
      },
      extension_icons: {
        files: [
          {
            dest: '<%= paths.sysext %>form/Resources/Public/Icons/Extension.svg',
            src: '<%= paths.t3icons %>svgs/module/module-form.svg'
          },
          {
            dest: '<%= paths.sysext %>reactions/Resources/Public/Icons/Extension.svg',
            src: '<%= paths.t3icons %>svgs/module/module-reactions.svg'
          },
          {
            dest: '<%= paths.sysext %>rte_ckeditor/Resources/Public/Icons/Extension.svg',
            src: '<%= paths.t3icons %>svgs/module/module-rte-ckeditor.svg'
          },
          {
            dest: '<%= paths.sysext %>linkvalidator/Resources/Public/Icons/Extension.svg',
            src: '<%= paths.t3icons %>svgs/module/module-linkvalidator.svg'
          }
        ]
      },
      fonts: {
        files: [
          {
            expand: true,
            cwd: '<%= paths.node_modules %>source-sans/WOFF2/VAR/',
            src: ['*.otf.woff2'],
            dest: '<%= paths.sysext %>backend/Resources/Public/Fonts/SourceSans'
          }
        ]
      },
      lit: {
        options: {
          process: (source, srcpath) => {
            /* note: This requires grunt-task 'es-module-lexer-init' to be executed prior to this task */
            const [imports] = esModuleLexer.parse(source, srcpath);
            source = require('./util/map-import.js').mapImports(source, srcpath, imports);

            return source.replace(/\/\/# sourceMappingURL=[^ ]+/, '');
          }
        },
        files: [{
          expand: true,
          cwd: '<%= paths.node_modules %>',
          dest: '<%= paths.core %>Public/JavaScript/Contrib/',
          src: [
            'lit/*.js',
            'lit/decorators/*.js',
            'lit/directives/*.js',
            'lit-html/*.js',
            'lit-html/directives/*.js',
            'lit-element/*.js',
            'lit-element/decorators/*.js',
            '@lit/reactive-element/*.js',
            '@lit/reactive-element/decorators/*.js',
            '@lit/task/*.js',
          ],
        }]
      },
      t3editor: {
        files: [
          {
            expand: true,
            cwd: '<%= paths.node_modules %>@codemirror',
            dest: '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/',
            rename: (dest, src) => dest + src.replace('/dist/index', ''),
            src: ['*/dist/index.js']
          },
          {
            expand: true,
            cwd: '<%= paths.node_modules %>@lezer',
            dest: '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/',
            rename: (dest, src) => dest + src.replace('/dist/index.es', ''),
            src: ['*/dist/index.es.js']
          },
          {
            src: '<%= paths.node_modules %>@lezer/lr/dist/index.js',
            dest: '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/lr.js'
          },
          {
            src: '<%= paths.node_modules %>@lezer/common/dist/index.js',
            dest: '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/common.js'
          },
          {
            src: '<%= paths.node_modules %>@lezer/highlight/dist/index.js',
            dest: '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/highlight.js'
          },
          {
            src: '<%= paths.node_modules %>@lezer/javascript/dist/index.js',
            dest: '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/javascript.js'
          },
          {
            src: '<%= paths.node_modules %>crelt/index.es.js',
            dest: '<%= paths.backend %>Public/JavaScript/Contrib/crelt.js'
          },
          {
            src: '<%= paths.node_modules %>style-mod/src/style-mod.js',
            dest: '<%= paths.backend %>Public/JavaScript/Contrib/style-mod.js'
          },
          {
            src: '<%= paths.node_modules %>w3c-keyname/index.es.js',
            dest: '<%= paths.backend %>Public/JavaScript/Contrib/w3c-keyname.js'
          },
        ]
      }
    },
    clean: {
      lit: {
        options: {
          'force': true
        },
        src: [
          '<%= paths.core %>Public/JavaScript/Contrib/lit',
          '<%= paths.core %>Public/JavaScript/Contrib/lit-html',
          '<%= paths.core %>Public/JavaScript/Contrib/lit-element',
          '<%= paths.core %>Public/JavaScript/Contrib/@lit/reactive-element',
        ]
      }
    },
    npmcopy: {
      options: {
        clean: false,
        report: false,
        srcPrefix: 'node_modules/'
      },
      backend: {
        options: {
          destPrefix: '<%= paths.backend %>Public',
          copyOptions: {
            process: (source, srcpath) => {
              if (srcpath.match(/.*\.js$/)) {
                return require('./util/cjs-to-esm.js').cjsToEsm(source);
              }

              return source;
            }
          }
        },
        files: {
          'JavaScript/Contrib/alwan.js': 'alwan/dist/js/alwan.min.js',
          'JavaScript/Contrib/mark.js': 'mark.js/dist/mark.es6.min.js'
        }
      },
      dashboardToEs6: {
        options: {
          destPrefix: '<%= paths.dashboard %>Public',
          copyOptions: {
            process: (source, srcpath) => {
              if (srcpath.match(/.*\.js$/)) {
                return require('./util/cjs-to-esm.js').cjsToEsm(source);
              }

              return source;
            }
          }
        },
        files: {
          'JavaScript/Contrib/muuri.js': 'muuri/dist/muuri.min.js'
        }
      },
      umdToEs6: {
        options: {
          destPrefix: '<%= paths.core %>Public/JavaScript/Contrib',
          copyOptions: {
            process: (source, srcpath) => {
              let imports = [], prefix = '';

              if (srcpath === 'node_modules/tablesort/dist/sorts/tablesort.dotsep.min.js') {
                prefix = 'import Tablesort from "tablesort";';
              }

              if (srcpath === 'node_modules/tablesort/dist/sorts/tablesort.number.min.js') {
                prefix = 'import Tablesort from "tablesort";';
              }

              return require('./util/cjs-to-esm.js').cjsToEsm(source, imports, prefix);
            }
          }
        },
        files: {
          'flatpickr/flatpickr.min.js': 'flatpickr/dist/flatpickr.js',
          'flatpickr/locales.js': 'flatpickr/dist/l10n/index.js',
          'flatpickr/plugins/shortcut-buttons.min.js': 'shortcut-buttons-flatpickr/dist/shortcut-buttons-flatpickr.min.js',
          'interact.js': 'interactjs/dist/interact.min.js',
          'jquery.js': 'jquery/dist/jquery.js',
          'nprogress.js': 'nprogress/nprogress.js',
          'tablesort.js': 'tablesort/dist/tablesort.min.js',
          'tablesort.dotsep.js': 'tablesort/dist/sorts/tablesort.dotsep.min.js',
          'tablesort.number.js': 'tablesort/dist/sorts/tablesort.number.min.js',
          'taboverride.js': 'taboverride/build/output/taboverride.js',
        }
      },
      all: {
        options: {
          destPrefix: '<%= paths.core %>Public/JavaScript/Contrib'
        },
        files: {
          'autosize.js': 'autosize/dist/autosize.esm.js',
          'cropperjs.js': 'cropperjs/dist/cropper.esm.js',
          'css-tree.js': 'css-tree/dist/csstree.esm.js',
          'luxon.js': 'luxon/build/es6/luxon.js',
          'sortablejs.js': 'sortablejs/modular/sortable.complete.esm.js',
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
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/autocomplete.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/autocomplete.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/commands.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/commands.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-css.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-css.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-html.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-html.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-javascript.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-javascript.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-json.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-json.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-php.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-php.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-sql.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-sql.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/language.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/language.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-xml.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lang-xml.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lint.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/lint.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/search.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/search.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/state.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/state.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/theme-one-dark.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/theme-one-dark.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/view.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@codemirror/view.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/common.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@lezer/common.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/css.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@lezer/css.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/highlight.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@lezer/highlight.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/html.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@lezer/html.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/javascript.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@lezer/javascript.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/json.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@lezer/json.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/lr.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@lezer/lr.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/php.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@lezer/php.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/@lezer/xml.js': ['<%= paths.backend %>Public/JavaScript/Contrib/@lezer/xml.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/crelt.js': ['<%= paths.backend %>Public/JavaScript/Contrib/crelt.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/style-mod.js': ['<%= paths.backend %>Public/JavaScript/Contrib/style-mod.js'],
          '<%= paths.backend %>Public/JavaScript/Contrib/w3c-keyname.js': ['<%= paths.backend %>Public/JavaScript/Contrib/w3c-keyname.js'],
          '<%= paths.core %>Public/JavaScript/Contrib/cropperjs.js': ['<%= paths.core %>Public/JavaScript/Contrib/cropperjs.js'],
          '<%= paths.core %>Public/JavaScript/Contrib/flatpickr/flatpickr.min.js': ['<%= paths.core %>Public/JavaScript/Contrib/flatpickr/flatpickr.min.js'],
          '<%= paths.core %>Public/JavaScript/Contrib/flatpickr/locales.js': ['<%= paths.core %>Public/JavaScript/Contrib/flatpickr/locales.js'],
          '<%= paths.core %>Public/JavaScript/Contrib/luxon.js': ['<%= paths.core %>Public/JavaScript/Contrib/luxon.js'],
          '<%= paths.core %>Public/JavaScript/Contrib/nprogress.js': ['<%= paths.core %>Public/JavaScript/Contrib/nprogress.js'],
          '<%= paths.core %>Public/JavaScript/Contrib/sortablejs.js': ['<%= paths.core %>Public/JavaScript/Contrib/sortablejs.js'],
          '<%= paths.core %>Public/JavaScript/Contrib/taboverride.js': ['<%= paths.core %>Public/JavaScript/Contrib/taboverride.js']
        }
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
        files: [
          {
            expand: true,
            src: [
              '<%= paths.root %>Build/JavaScript/**/*.js',
              '!<%= paths.root %>Build/JavaScript/*/tests/**/*',
            ],
            dest: '<%= paths.root %>Build',
            cwd: '.',
          }
        ]
      }
    },
    concurrent: {
      npmcopy: ['npmcopy:backend', 'npmcopy:umdToEs6', 'npmcopy:dashboardToEs6', 'npmcopy:all'],
      lint: ['eslint', 'stylelint', 'exec:lintspaces'],
      compile_assets: ['scripts', 'css'],
      compile_flags: ['flags-build'],
      minify_assets: ['terser:thirdparty'],
      copy_static: ['copy:core_icons', 'copy:install_icons', 'copy:module_icons', 'copy:extension_icons', 'copy:fonts', 'copy-lit', 'copy:t3editor'],
      build: ['copy:core_icons', 'copy:install_icons', 'copy:module_icons', 'copy:extension_icons', 'copy:fonts', 'copy:t3editor'],
    },
  });

  // Register tasks
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-npmcopy');
  grunt.loadNpmTasks('grunt-terser');
  grunt.loadNpmTasks('@lodder/grunt-postcss');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-exec');
  grunt.loadNpmTasks('grunt-eslint');
  grunt.loadNpmTasks('grunt-stylelint');
  grunt.loadNpmTasks('grunt-concurrent');

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
  grunt.registerTask('lint', ['concurrent:lint']);

  /**
   * grunt css task
   *
   * call "$ grunt css"
   *
   * this task does the following things:
   * - exec:stylefix
   * - sass
   * - postcss
   */
  grunt.registerTask('css', ['exec:stylefix', 'sass', 'postcss']);

  /**
   * grunt update task
   *
   * call "$ grunt update"
   *
   * this task does the following things:
   * - copy some components to a specific destinations because they need to be included via PHP
   */
  grunt.registerTask('update', ['ckeditor:compile-locales', 'exec:rollup', 'concurrent:npmcopy']);

  /**
   * grunt compile-typescript task
   *
   * call "$ grunt compile-typescript"
   *
   * This task does the following things:
   * - 1) Remove previously built JS files from local JavaScript directory
   * - 2) Check all TypeScript files (*.ts) with ESLint which are located in Sources/TypeScript/<EXTKEY>/*.ts
   * - 3) Compiles all TypeScript files (*.ts) which are located in Sources/TypeScript/<EXTKEY>/*.ts
   */
  grunt.registerTask('compile-typescript', ['clear-built-js', 'tsconfig', 'eslint', 'exec:ts']);

  grunt.registerTask('copy-lit', ['es-module-lexer-init', 'copy:lit']);

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
  grunt.registerTask('scripts', ['compile-typescript', 'terser:typescript', 'es-module-lexer-init', 'copy:ts_files']);

  /**
   * grunt clear-build task
   *
   * call "$ grunt clear-build"
   *
   * Removes all build-related assets, e.g. cache and built files
   */
  grunt.registerTask('clear-build', ['clear-build-cache', 'clear-built-js']);

  /**
   * Removes build-related caching information
   */
  grunt.registerTask('clear-build-cache', function () {
    if (grunt.file.isDir('.cache')) {
      grunt.file.delete('.cache');
    }
  });

  /**
   * Removes build JavaScript files (incorporated altogether with TypeScript compilations)
   */
  grunt.registerTask('clear-built-js', function () {
    if (grunt.file.isDir('JavaScript')) {
      grunt.file.delete('JavaScript');
    }
  });

  /**
   * grunt tsconfig task
   *
   * call "$ grunt tsconfig"
   *
   * this task updates the tsconfig.json file with modules paths for all sysexts
   */
  grunt.task.registerTask('tsconfig', function () {
    const config = grunt.file.readJSON('tsconfig.json');
    const typescriptPath = grunt.config.get('paths.typescript');
    config.compilerOptions.paths = {};
    grunt.file.expand(typescriptPath + '*/').map(dir => dir.replace(typescriptPath, '')).forEach((path) => {
      const extname = path.match(/^([^/]+?)\//)[1].replace(/_/g, '-')
      config.compilerOptions.paths['@typo3/' + extname + '/*'] = [path + '*'];
    });

    grunt.file.write('tsconfig.json', JSON.stringify(config, null, 4) + '\n');
  });

  /**
   * @internal
   */
  grunt.task.registerTask('es-module-lexer-init', function() {
    const done = this.async();

    esModuleLexer.init
      .then(() => done(true))
      .catch((e) => done(e));
  });

  /**
   * @internal
   */
  grunt.registerTask('copy-lit', ['es-module-lexer-init', 'copy:lit']);

  /**
   * Outputs a "bell" character. When output, modern terminals flash shortly or produce a notification (usually configurable).
   * This Grunt config uses it after the "watch" task finished compiling, signaling to the developer that her/his changes
   * are now compiled.
   */
  grunt.registerTask('bell', () => console.log('\u0007'));

  /**
   * grunt default task
   *
   * call "$ grunt default"
   *
   * this task does the following things:
   * - execute update task
   * - execute copy task
   * - compile sass files
   * - uglify js files
   * - minifies svg files
   * - compiles TypeScript files
   */
  grunt.registerTask('default', ['clear-build', 'clean', 'update', 'concurrent:copy_static', 'concurrent:compile_flags', 'concurrent:compile_assets', 'concurrent:minify_assets']);

  /**
   * grunt build task (legacy, for those used to it). Use `grunt default` instead.
   *
   * call "$ grunt build"
   *
   * this task does the following things:
   * - execute exec:npm-install task
   * - execute all task
   */
  grunt.registerTask('build', ['exec:npm-install', 'default']);
};
