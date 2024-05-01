import terser from '@rollup/plugin-terser';
import { createRequire } from 'node:module';

export default {
  input: 'node_modules/select-pure/lib/index.js',
  output: {
    file: '../typo3/sysext/backend/Resources/Public/JavaScript/Contrib/select-pure.js',
    format: 'esm',
  },
  plugins: [
    terser({ ecma: 8 }),
    {
      name: 'externals',
      resolveId: (source, importer) => {
        if (source === 'autobind-decorator') {
          return { id: 'node_modules/autobind-decorator/lib/esm/index.js' }
        }
        if (source === 'lit' || source.startsWith('lit/')) {
          return { id: source, external: true }
        }
        if (source.startsWith('lit-html/')) {
          return { id: source.replace('lit-html/', 'lit/'), external: true }
        }
        if (source.startsWith('.') && importer) {
          const require = createRequire(import.meta.url);
          const path = require('path');
          return require.resolve(path.resolve(path.dirname(importer), source))
        }
        return null
      }
    }
  ],
};
