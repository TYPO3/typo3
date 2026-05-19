import { nodeResolve } from '@rollup/plugin-node-resolve';
import MagicString from 'magic-string';
import commonjs from '@rollup/plugin-commonjs';
import postcss from 'postcss';
import cssnano from 'cssnano';
import svg from 'rollup-plugin-svg';
import { minify } from 'rollup-plugin-esbuild';
import { relative, resolve } from 'path';
import { readdirSync, readFileSync } from 'fs';
import { translations } from './ckeditor/translations.js';

const postCssProcessor = postcss([
  cssnano({
    preset: 'default',
  }),
]);

const packages = [
  'ckeditor5-inspector',
  'ckeditor5-alignment',
  'ckeditor5-autoformat',
  'ckeditor5-basic-styles',
  'ckeditor5-block-quote',
  'ckeditor5-clipboard',
  'ckeditor5-code-block',
  'ckeditor5-core',
  'ckeditor5-editor-classic',
  'ckeditor5-editor-decoupled',
  'ckeditor5-editor-multi-root',
  'ckeditor5-engine',
  'ckeditor5-enter',
  'ckeditor5-essentials',
  'ckeditor5-find-and-replace',
  'ckeditor5-font',
  'ckeditor5-fullscreen',
  'ckeditor5-heading',
  'ckeditor5-highlight',
  'ckeditor5-horizontal-line',
  'ckeditor5-html-support',
  'ckeditor5-icons',
  'ckeditor5-image',
  'ckeditor5-indent',
  'ckeditor5-language',
  'ckeditor5-link',
  'ckeditor5-list',
  'ckeditor5-mention',
  'ckeditor5-paragraph',
  'ckeditor5-paste-from-office',
  'ckeditor5-remove-format',
  'ckeditor5-select-all',
  'ckeditor5-show-blocks',
  'ckeditor5-source-editing',
  'ckeditor5-special-characters',
  'ckeditor5-style',
  'ckeditor5-table',
  'ckeditor5-typing',
  'ckeditor5-ui',
  'ckeditor5-undo',
  'ckeditor5-upload',
  'ckeditor5-utils',
  'ckeditor5-watchdog',
  'ckeditor5-widget',
  'ckeditor5-word-count',
];

export default [
  ...packages.map(pkg => {
    const packageName = `@ckeditor/${pkg}`;
    return {
      input: [
        packageName,
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
            if (source.startsWith('@ckeditor/ckeditor5-') && source.endsWith('/dist/index.js')) {
              return { id: source.replace('/dist/index.js', ''), external: true }
            }
            if (source.startsWith('@ckeditor/') && !source.startsWith(packageName) && !source.endsWith('.svg') && !source.endsWith('.css')) {
              if (source.split('/').length > 2) {
                throw new Error(`Non package-entry point was imported: ${source}`);
              }
              return { id: source.replace(/.js$/, ''), external: true }
            }
            if (source.startsWith('@ckeditor/') && source.endsWith('.js') && source.split('/').length === 2) {
              throw new Error(`JS File with suffix: ${source} import from ${from}`);
            }
            if (
              !source.startsWith('@ckeditor/') &&
              !source.startsWith('.') &&
              !source.startsWith('/') &&
              source !== 'es-toolkit/compat' &&
              source !== 'es-toolkit/compat/isEqual' &&
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
            if (!id.endsWith('@ckeditor/ckeditor5-link/dist/index.js')) {
              return null;
            }
            const ms = new MagicString(code);
            // Workaround a CKEditor 5 bug where a link without an `href` attribute is created
            // when the cursor is placed at the end of a link containing a class attribute.
            // @todo: Fix this upstream: htmlA should theoretically be removed automatically
            // when linkHref is removed as it is defined to be a coupledAttribute with linkHref.
            // (see @ckeditor/ckeditor5-html-support/src/schemadefinitions.js)
            const source = 'return schema.getDefinition("$text").allowAttributes.filter((attribute) => attribute.startsWith("link"));';
            const target = 'return schema.getDefinition("$text").allowAttributes.filter((attribute) => attribute.startsWith("link")||attribute==="htmlA");';
            if (!code.includes(source)) {
              throw new Error(`Expected to find "${source}" in "${id}". Please adapt the rollup plugin "patchLinkEditing".`);
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
            const { css } = await postCssProcessor.process(code, { from: id });
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
  ...translations(packages)
];

