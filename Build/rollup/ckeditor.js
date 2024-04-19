import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import postcss from 'rollup-plugin-postcss';
import svg from 'rollup-plugin-svg';
import terser from '@rollup/plugin-terser';
import * as path from 'path';
import { buildConfigForTranslations } from './ckeditor/build-translations-config.js';
import { readdirSync, statSync, existsSync } from 'fs';
import { createRequire } from 'node:module';

const { styles } = require( '@ckeditor/ckeditor5-dev-utils' );
const postCssConfig = styles.getPostCssConfig({
  themeImporter: {
    themePath: require.resolve('@ckeditor/ckeditor5-theme-lark')
  },
  minify: true
});

const packages = readdirSync('node_modules/@ckeditor')
  .filter(dir =>
    statSync(`node_modules/@ckeditor/${dir}`).isDirectory() &&
    existsSync(`node_modules/@ckeditor/${dir}/package.json`) &&
    !['ckeditor5-dev-translations', 'ckeditor5-dev-utils'].includes(dir)
  );

export default [
  ...packages.map(pkg => {
    const packageName = `@ckeditor/${pkg}`;
    const packageJson = `../node_modules/${packageName}/package.json`;
    const require = createRequire(import.meta.url);
    let input = `./node_modules/${packageName}/${require(packageJson).main}`;
    if (packageName === '@ckeditor/ckeditor5-link') {
      input = 'Sources/JavaScript/rte_ckeditor/contrib/ckeditor5-link.js';
    }
    return {
      input: [
        input,
      ],
      output: {
        compact: true,
        file: `../typo3/sysext/rte_ckeditor/Resources/Public/Contrib/${packageName}.js`,
        format: 'es',
        plugins: [terser({ ecma: 8 })],
      },
      plugins: [
        {
          name: 'externals',
          resolveId: (source, from) => {
            if (source === '@ckeditor/ckeditor5-utils/src/version.js' && from.includes('@ckeditor/ckeditor5-engine')) {
              return { id: '@ckeditor/ckeditor5-utils', external: true }
            }
            if (source.startsWith('@ckeditor/') && !source.startsWith(packageName) && !source.endsWith('.svg') && !source.endsWith('.css')) {
              if (source.split('/').length > 2) {
                throw new Error(`Non package-entry point was imported: ${source}`);
              }
              return { id: source.replace(/.js$/, ''), external: true }
            }
            if (source.startsWith('ckeditor5/src/')) {
              return { id: '@ckeditor/ckeditor5-' + source.substring(14).replace(/.js$/, ''), external: true };
            }
            if (source === 'lodash-es') {
              return { id: 'lodash-es', external: true }
            }
            if (source.startsWith('@ckeditor/') && source.endsWith('.js') && source.split('/').length === 2) {
              throw new Error(`JS File with suffix: ${source} import from ${from}`);
            }
            if (
              !source.startsWith('@ckeditor/') &&
              !source.startsWith('.') &&
              !source.startsWith('/') &&
              !source.startsWith('Sources/JavaScript/rte_ckeditor/contrib') &&
              source !== 'vanilla-colorful/hex-color-picker.js' &&
              source !== 'vanilla-colorful/lib/entrypoints/hex' &&
              source !== 'color-convert' &&
              source !== 'color-name' &&
              source !== 'color-parse'
            ) {
              throw new Error(`HEADS UP: New CKEditor5 import "${source}" (import from ${from}). Please decide whether to bundle or package separately and adapt Build/rollup/ckeditor.js accordingly.`);
            }
            return null
          }
        },
        {
          name: 'patchLinkEditing',
          transform(code, id) {
            if (id.endsWith('@ckeditor/ckeditor5-link/src/linkediting.js')) {
              // Workaround a CKEditor5 bug where a link without an `href` attribute is created
              // when the cursor is placed at the end of a link containing a class attribute.
              // @todo: Fix this upstream: htmlA should theoretically be removed automatically
              // when linkHref is removed as it is defined to be a coupledAttribute with linkHref.
              // (see @ckeditor/ckeditor5-html-support/src/schemadefinitions.js)
              const source = "return textAttributes.filter(attribute => attribute.startsWith('link'));";
              const target = "return textAttributes.filter(attribute => attribute.startsWith('link') || attribute === 'htmlA');";
              if (!code.includes(source)) {
                throw new Error(`Expected to find "${search}" in "${id}". Please adapt the rollup plugin "patchLinkEditing".`);
              }
              return code.replace(source, target);
            }
            return code;
          }
        },
        postcss({
          ...postCssConfig,
          inject: function (cssVariableName, fileId) {
            // overrides functionality of native `style-inject` package, now applies `window.litNonce` to `<style>`
            const importPath = path.resolve('./rollup/shim/style-inject.js');
            return `import styleInject from '${importPath}';\n` + `styleInject(${cssVariableName});`;
          },
        }),
        resolve({
          extensions: ['.js']
        }),
        commonjs(),
        svg(),
      ]
    }
  }),
  ...buildConfigForTranslations()
];

