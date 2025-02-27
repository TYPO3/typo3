import { bundle } from './bundle.js';
import { ckeditorPackages } from './ckeditor.js';

export default [
  bundle('bootstrap', {
    imports: { '@popperjs/core': 'node_modules/@popperjs/core/dist/esm/index.js' },
  }),

  bundle('flatpickr'),
  bundle('flatpickr/locales', { src: 'node_modules/flatpickr/dist/esm/l10n/index.js' }),

  bundle('lodash-es', { extension: 'backend' }),

  bundle('mark.js', { extension: 'backend', src: 'node_modules/mark.js/src/lib/mark.js' }),

  bundle('select-pure', {
    extension: 'backend',
    imports: { 'autobind-decorator': 'node_modules/autobind-decorator/lib/esm/index.js' },
    external: [ 'lit', /^lit\//, /^lit-html\// ],
  }),

  bundle('chart.js', {
    extension: 'dashboard',
    imports: { '@kurkle/color': 'node_modules/@kurkle/color/dist/color.esm.js' },
  }),

  ...ckeditorPackages,
]
