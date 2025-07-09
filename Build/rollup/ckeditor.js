import { nodeResolve } from '@rollup/plugin-node-resolve';
import MagicString from 'magic-string';
import commonjs from '@rollup/plugin-commonjs';
import postcss from 'postcss';
import cssnano from 'cssnano';
import svg from 'rollup-plugin-svg';
import { minify } from 'rollup-plugin-esbuild';
import { styles } from '@ckeditor/ckeditor5-dev-utils';
import { resolve } from 'path';
import { readdirSync, readFileSync, statSync, existsSync } from 'fs';
import { translations } from './ckeditor/translations.js';

const postCssConfig = styles.getPostCssConfig({
  themeImporter: {
    themePath: new URL(import.meta.resolve('@ckeditor/ckeditor5-theme-lark')).pathname
  },
});

const postCssPocessor = postcss([
  ...postCssConfig.plugins,
  cssnano({
    preset: 'default',
  }),
]);

const packages = readdirSync('node_modules/@ckeditor')
  .filter(dir =>
    statSync(`node_modules/@ckeditor/${dir}`).isDirectory() &&
    existsSync(`node_modules/@ckeditor/${dir}/package.json`) &&
    !['ckeditor5-dev-translations', 'ckeditor5-dev-utils'].includes(dir)
  );

export default [
  ...packages.map(pkg => {
    const packageName = `@ckeditor/${pkg}`;
    const packageJson = `node_modules/${packageName}/package.json`;
    const entryPoint = JSON.parse(readFileSync(packageJson, 'utf8')).main
    let input = `./node_modules/${packageName}/${entryPoint}`;
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
        plugins: [
          minify({ target: 'es2023' }),
        ]
      },
      external: [
        'lodash-es',
      ],
      plugins: [
        {
          name: 'resolve imports',
          resolveId: (source, from) => {
            if (source.startsWith('@ckeditor/') && !source.startsWith(packageName) && !source.endsWith('.svg') && !source.endsWith('.css')) {
              if (source.split('/').length > 2) {
                throw new Error(`Non package-entry point was imported: ${source}`);
              }
              return { id: source.replace(/.js$/, ''), external: true }
            }
            if (source.startsWith('ckeditor5/src/')) {
              return { id: '@ckeditor/ckeditor5-' + source.substring(14).replace(/.js$/, ''), external: true };
            }
            if (source.startsWith('@ckeditor/') && source.endsWith('.js') && source.split('/').length === 2) {
              throw new Error(`JS File with suffix: ${source} import from ${from}`);
            }
            if (
              !source.startsWith('@ckeditor/') &&
              !source.startsWith('.') &&
              !source.startsWith('/') &&
              !source.startsWith('Sources/JavaScript/rte_ckeditor/contrib') &&
              source !== 'es-toolkit/compat' &&
              source !== 'vanilla-colorful/hex-color-picker.js' &&
              source !== 'vanilla-colorful/lib/entrypoints/hex' &&
              source !== 'color-convert' &&
              source !== 'color-name' &&
              source !== 'color-parse'
            ) {
              throw new Error(`HEADS UP: New CKEditor 5 import "${source}" (import from ${from}). Please decide whether to bundle or package separately and adapt Build/rollup/ckeditor.js accordingly.`);
            }
            return null
          }
        },
        {
          name: 'patchLinkEditing',
          transform(code, id) {
            if (!id.endsWith('@ckeditor/ckeditor5-link/src/linkediting.js')) {
              return null;
            }
            const ms = new MagicString(code);
            // Workaround a CKEditor 5 bug where a link without an `href` attribute is created
            // when the cursor is placed at the end of a link containing a class attribute.
            // @todo: Fix this upstream: htmlA should theoretically be removed automatically
            // when linkHref is removed as it is defined to be a coupledAttribute with linkHref.
            // (see @ckeditor/ckeditor5-html-support/src/schemadefinitions.js)
            const source = "return textAttributes.filter(attribute => attribute.startsWith('link'));";
            const target = "return textAttributes.filter(attribute => attribute.startsWith('link') || attribute === 'htmlA');";
            if (!code.includes(source)) {
              throw new Error(`Expected to find "${search}" in "${id}". Please adapt the rollup plugin "patchLinkEditing".`);
            }
            ms.replace(source, target);
            return { code: ms.toString(), map: ms.generateMap({ id, includeContent: true, hires: true }) }
          }
        },
        {
          name: 'css inject',
          async transform(code, id) {
            if (!id.endsWith('.css')) {
              return;
            }
            const { css } = await postCssPocessor.process(code, { from: id });
            const importPath = resolve('./rollup/shim/style-inject.js');
            return {
              code: `
                import styleInject from '${importPath}';
                styleInject(${JSON.stringify(css)});
              `,
              map: { mappings: '' }
            }
          }
        },
        nodeResolve({
          extensions: ['.js']
        }),
        commonjs(),
        svg(),
      ]
    }
  }),
  ...translations()
];

