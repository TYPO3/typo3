import { bundle } from './bundle.js';
import { ckeditorPackages } from './ckeditor.js';

export default [
  bundle('bootstrap', {
    src: 'Sources/JavaScript/core/Resources/Public/JavaScript/Contrib/bootstrap.js',
    imports: {
      'bootstrap': 'node_modules/bootstrap/dist/js/bootstrap.esm.js',
      '@popperjs/core': 'node_modules/@popperjs/core/dist/esm/index.js'
    },
    external: [ 'jquery' ],
  }),

  bundle('d3-selection'),
  bundle('d3-dispatch'),
  bundle('d3-drag', { external: [ 'd3-selection', 'd3-dispatch' ] }),

  bundle('flatpickr'),
  bundle('flatpickr/locales', { src: 'node_modules/flatpickr/dist/esm/l10n/index.js' }),

  bundle('lodash-es', { extension: 'backend' }),

  bundle('mark.js', { extension: 'backend', src: 'node_modules/mark.js/src/lib/mark.js' }),

  bundle('chart.js', {
    extension: 'dashboard',
    imports: { '@kurkle/color': 'node_modules/@kurkle/color/dist/color.esm.js' },
  }),

  ...ckeditorPackages,
]
