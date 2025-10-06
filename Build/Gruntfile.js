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

  const generateSourcemaps = grunt.option('sourcemaps') || false;

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
    postcss: {
      options: {
        map: generateSourcemaps ? { inline: true } : false,
        syntax: require('postcss-scss'),
        processors: () => [
          require('@csstools/postcss-sass')({
            sass: require('sass'),
            outputStyle: 'expanded',
            precision: 8
          }),
          require('autoprefixer')(),
          require('cssnano')({
            preset: [
              'default',
            ],
          }),
          {
            postcssPlugin: 'keep line breaks',
            // Use "OnceExit" event instead of "Rule"/"AtRule" to postprocess the minification
            // performed by postcss-normalize-whitespace (as part of cssnano)
            OnceExit(css) {
              css.walk(node => {
                if (['rule', 'atrule'].includes(node.type)) {
                  node.raws.before = "\n";
                }
              })
            },
          },
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
          }),
        ]
      },
      adminpanel: {
        src: '<%= paths.sass %>adminpanel.scss',
        dest: '<%= paths.adminpanel %>Public/Css/adminpanel.css',
      },
      backend: {
        src: '<%= paths.sass %>backend.scss',
        dest: '<%= paths.backend %>Public/Css/backend.css',
      },
      dashboard: {
        src: '<%= paths.sass %>dashboard.scss',
        dest: '<%= paths.dashboard %>Public/Css/dashboard.css',
      },
      dashboard_modal: {
        src: '<%= paths.sass %>dashboard_modal.scss',
        dest: '<%= paths.dashboard %>Public/Css/Modal/style.css',
      },
      form: {
        src: '<%= paths.sass %>form.scss',
        dest: '<%= paths.form %>Public/Css/form.css',
      },
      styleguide: {
        src: '<%= paths.sass %>styleguide-frontend.scss',
        dest: '<%= paths.styleguide %>Public/Css/styleguide-frontend.css'
      },
      webfonts: {
        src: '<%= paths.sass %>webfonts.scss',
        dest: '<%= paths.backend %>Public/Css/webfonts.css',
      },
      workspaces: {
        src: '<%= paths.sass %>workspace.scss',
        dest: '<%= paths.workspaces %>Public/Css/preview.css',
      },
    },
    exec: {
      ts: ((process.platform === 'win32') ? 'node_modules\\.bin\\tsc.cmd' : './node_modules/.bin/tsc') + ' --project tsconfig.json' + (generateSourcemaps ? ' --inlineSources --sourceMap' : ''),
      rollup: ((process.platform === 'win32') ? 'node_modules\\.bin\\rollup.cmd' : './node_modules/.bin/rollup') + ' -c rollup/config.js' + (generateSourcemaps ? ' --sourcemap="inline"' : ''),
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
    'process-javascript': {
      ts: {
        src: [
          '<%= paths.root %>Build/JavaScript/**/*.js',
          '!<%= paths.root %>Build/JavaScript/*/tests/**/*.js',
        ],
        dest: '<%= paths.sysext %>',
        pathmap: srcpath => require('path')
          .relative(`${grunt.config.get('paths.root')}Build/JavaScript/`, srcpath)
          .replace(
            /^([^\/]+)\//,
            (match, extension) => `${extension.replace(/-/g, '_')}/Resources/Public/JavaScript/`
          ),
        banner: '/*\n' +
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
      }
    },
    copy: {
      options: {
        punctuation: ''
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
            const { code } = require('./lib/map-import.js').mapImports.transform(source, srcpath);

            if (generateSourcemaps) {
              const { readFileSync } = require('fs');
              const { resolve, dirname } = require('path');
              // transform into an inline sourcemap
              return code.replace(
                /(\/\/# sourceMappingURL=)([^ ]+)/,
                (match, prefix, path) => `${prefix}data:application/json;charset=utf-8;base64,${
                  Buffer.from(readFileSync(resolve(dirname(srcpath), path.trim()))).toString('base64')
                }`
              );
            } else {
              return code.replace(/\/\/# sourceMappingURL=[^ ]+/, '');
            }
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
            '@lit-labs/motion/*.js',
          ],
        }]
      },
    },
    clean: {
      lit: {
        options: {
          force: true
        },
        src: [
          '<%= paths.core %>Public/JavaScript/Contrib/lit',
          '<%= paths.core %>Public/JavaScript/Contrib/lit-html',
          '<%= paths.core %>Public/JavaScript/Contrib/lit-element',
          '<%= paths.core %>Public/JavaScript/Contrib/@lit',
          '<%= paths.core %>Public/JavaScript/Contrib/@lit-labs',
        ]
      }
    },
    esbuild: Object.fromEntries(Object.entries({
      core: [
        'autosize',
        { name: 'bootstrap', bundle: true },
        'cropperjs',
        { name: 'css-tree', bundle: true },
        'dompurify',
        { name: 'flatpickr', bundle: true },
        { name: 'flatpickr/dist/l10n', src: 'node_modules/flatpickr/dist/esm/l10n/index.js', bundle: true },
        'interactjs',
        'jquery',
        { name: 'luxon', src: 'node_modules/luxon/build/es6/luxon.js' },
        'marked',
        'nprogress',
        'shortcut-buttons-flatpickr',
        { name: 'sortablejs', src: 'node_modules/sortablejs/modular/sortable.complete.esm.js' },
        {
          name: 'tablesort',
          src: 'Sources/JavaScript/tablesort.js',
          bundle: true,
          options: {
            inject: [ 'Sources/JavaScript/tablesort.inject.js' ],
          },
        },
        'taboverride',
      ],
      backend: [
        { name: 'alwan', src: 'node_modules/alwan/dist/js/esm/alwan.min.js' },
        'crelt',
        { name: 'lodash-es', bundle: true },
        'mark.js',
        {
          name: 'select-pure',
          bundle: true,
          options: {
            external: [
              'lit',
              'lit/*',
              'lit-html/*',
            ]
          }
        },
        'style-mod',
        'w3c-keyname',
        '@codemirror/autocomplete',
        '@codemirror/commands',
        '@codemirror/lang-css',
        '@codemirror/lang-html',
        '@codemirror/lang-javascript',
        '@codemirror/lang-json',
        '@codemirror/lang-php',
        '@codemirror/lang-sql',
        '@codemirror/language',
        '@codemirror/lang-xml',
        '@codemirror/lint',
        '@codemirror/search',
        '@codemirror/state',
        '@codemirror/theme-one-dark',
        '@codemirror/view',
        '@lezer/common',
        '@lezer/css',
        '@lezer/highlight',
        '@lezer/html',
        '@lezer/javascript',
        '@lezer/json',
        '@lezer/lr',
        '@lezer/php',
        '@lezer/xml',
      ],
      dashboard: [
        { name: 'chart.js', bundle: true },
      ],
    }).flatMap(
      ([extension, modules]) => modules.map(
        module => [
          module?.name ?? module,
          {
            entryPoints: [module?.src ?? module?.name ?? module],
            format: 'esm',
            outfile: `<%= paths.${extension} %>Public/JavaScript/Contrib/${(module?.name ?? module).replace(/\.js$/, 'js')}.js`,
            minify: true,
            bundle: module?.bundle ?? false,
            sourcemap: generateSourcemaps ? 'inline' : false,
            ...(module?.options ?? {}),
          }
        ]
      )
    )),
    concurrent: {
      lint: ['eslint', 'stylelint', 'exec:lintspaces'],
      eslint_ts: ['eslint', 'ts'],
      build: ['scripts', 'css', 'thirdparty']
    },
  });

  // Register tasks
  grunt.loadNpmTasks('grunt-contrib-watch');
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
  grunt.registerTask('css', ['exec:stylefix', 'postcss']);

  /**
   * grunt scripts task
   *
   * call "$ grunt scripts"
   *
   * this task does the following things:
   * - 1) Remove previously built JS files from local JavaScript directory
   * - 2) Check all TypeScript files (*.ts) with ESLint which are located in Sources/TypeScript/<EXTKEY>/*.ts
   * - 3) Compiles all TypeScript files (*.ts) which are located in Sources/TypeScript/<EXTKEY>/*.ts
   * - 4) Process, minify and copy all generated JavaScript files to public folders
   */
  grunt.registerTask('scripts', ['clear-built-js', 'concurrent:eslint_ts']);
  grunt.registerTask('ts', ['exec:ts', 'process-javascript:ts']);

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

  grunt.task.registerMultiTask('process-javascript', function () {
    const done = this.async();
    const { src, dest, pathmap, banner } = this.data;
    const { rollup } = require('rollup')
    const { litnano } = require('litnano/rollup');
    const { mapImports } = require('./lib/map-import.js');
    const { minify } = require('rollup-plugin-esbuild');

    const process = async (src, dest) => {
      const input = grunt.file.expand(src);
      if (input.length === 0) {
        return;
      }

      const loadSource = {
        name: 'load typescript with sourcemap',
        load(id) {
          const code = grunt.file.read(id);
          const ast = this.parse(code);
          const map = generateSourcemaps ? JSON.parse(grunt.file.read(id + '.map')) : null;
          return { code, ast, map };
        },
      };

      const fixDecorate = {
        name: 'fix __decorate',
        transform(code, file) {
          const MagicString = require('magic-string');
          const ms = new MagicString(code)
          // Workaround for https://github.com/microsoft/TypeScript/issues/35802
          // > The 'this' keyword is equivalent to 'undefined' at the top level of an ES module
          ms.replace('__decorate = (this && this.__decorate) || function', '__decorate=function');
          return { code: ms.toString(), map: ms.generateMap({ file, includeContent: true, hires: true }) }
        },
      };

      const skipEmptyModules = {
        name: 'skip empty modules',
        generateBundle(options, bundle, isWrite) {
          for (const [fileName, outputAsset] of Object.entries(bundle)) {
            const ast = this.parse(outputAsset.code)
            if (ast?.type === 'Program' && ast?.body.length === 0) {
              delete bundle[fileName];
            }
          }
        }
      };

      const modules = await rollup({
        input,
        external: () => true,
        treeshake: false,
        makeAbsoluteExternalsRelative: false,
        plugins: [
          loadSource,
          fixDecorate,
          mapImports,
          litnano(),
          skipEmptyModules,
          minify({
            sourceMap: generateSourcemaps,
            target: 'es2023',
            banner,
          })
        ],
      })

      const { output } = await modules.generate({
        format: 'es',
        compact: true,
        entryFileNames: (chunk) => pathmap(chunk.facadeModuleId),
        sourcemap: generateSourcemaps ? 'inline' : false,
      })

      for (const file of output) {
        grunt.file.write(dest + file.fileName, file.code);
      }
    };

    process(src, dest)
      .then(done)
      .catch(e => {
        console.error(e)
        done(false)
      });
  });

  grunt.registerMultiTask('esbuild', 'Runs esbuild', async function () {
    const done = this.async()
    const { build } = require('esbuild');

    build(this.data)
      .then(done)
      .catch(e => {
        console.error(e);
        done(false);
      })
  })

  /**
   * Outputs a "bell" character. When output, modern terminals flash shortly or produce a notification (usually configurable).
   * This Grunt config uses it after the "watch" task finished compiling, signaling to the developer that her/his changes
   * are now compiled.
   */
  grunt.registerTask('bell', () => console.log('\u0007'));

  /**
   * grunt thirdparty task
   *
   * this task executes all tasks (except rollup-ckeditor, as it is slow) that copy/process thirdparty files
   */
  grunt.registerTask('thirdparty', ['clean', 'copy', 'esbuild', 'flags-build', 'exec:rollup']);

  /**
   * grunt default task
   *
   * call "$ grunt default"
   *
   * this task executes all tasks in with parallelization
   */
  grunt.registerTask('default', ['clear-build-cache', 'concurrent:build']);

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
