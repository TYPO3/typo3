import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import postcss from 'rollup-plugin-postcss';
import svg from 'rollup-plugin-svg';
import * as path from 'path';
import { buildConfigForTranslations } from './ckeditor5.rollup.helpers';

const { styles } = require( '@ckeditor/ckeditor5-dev-utils' );
const postCssConfig = styles.getPostCssConfig({
  themeImporter: {
    themePath: require.resolve('@ckeditor/ckeditor5-theme-lark')
  },
  minify: true
});

export default [
  {
    input: [
      './Sources/JavaScript/rte_ckeditor/contrib/ckeditor5-bundle.js'
    ],
    output: {
      compact: true,
      file: '../typo3/sysext/rte_ckeditor/Resources/Public/Contrib/ckeditor5-bundle.js',
      format: 'es',
      name: 'ckeditor5',
    },
    plugins: [
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
          const importPath = path.resolve('./ckeditor5.rollup.functions.js');
          return `import styleInject from '${importPath}';\n` + `styleInject(${cssVariableName});`;
        },
        // @todo unsure whether we might give up a stand alone style file
        // style information is bundled inside the JavaScript bundle, that's how it's documented as well
        // extract: path.resolve('../typo3/sysext/rte_ckeditor/Resources/Public/Contrib/ckeditor5.css')
      }),
      resolve({
        extensions: ['.js']
      }),
      commonjs(),
      svg(),
    ]
  },
  {
    input: [
      './Sources/JavaScript/rte_ckeditor/contrib/ckeditor5-inspector.js'
    ],
    output: {
      compact: true,
      file: '../typo3/sysext/rte_ckeditor/Resources/Public/Contrib/ckeditor5-inspector.js',
      format: 'es',
      name: 'ckeditor5-inspector',
    },
    plugins: [
      resolve({
        extensions: ['.js']
      }),
      commonjs(),
    ]
  },
  ...buildConfigForTranslations()
];

