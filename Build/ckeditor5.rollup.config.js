import resolve from '@rollup/plugin-node-resolve';
import postcss from 'rollup-plugin-postcss';
import svg from 'rollup-plugin-svg';

const { styles } = require( '@ckeditor/ckeditor5-dev-utils' );
const postCssConfig = styles.getPostCssConfig({
  themeImporter: {
    themePath: require.resolve('@ckeditor/ckeditor5-theme-lark')
  },
  minify: true
});

export default [{
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
        postcss({
          ...postCssConfig,
          // @todo unsure whether we might give up a stand alone style file
          // style information is bundled inside the JavaScript bundle, that's how it's documented as well
          // extract: path.resolve('../typo3/sysext/rte_ckeditor/Resources/Public/Contrib/ckeditor5.css')
        }),
        resolve({
            extensions: ['.js']
        }),
				svg(),
    ]
}];

