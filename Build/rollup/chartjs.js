import terser from '@rollup/plugin-terser';

export default {
  input: 'node_modules/chart.js/dist/chart.js',
  output: {
    file: '../typo3/sysext/dashboard/Resources/Public/JavaScript/Contrib/chartjs.js',
    format: 'esm',
  },
  plugins: [
    terser({ ecma: 8 }),
    {
      name: 'externals',
      resolveId: (source) => {
        if (source === 'chunks/helpers.segment.js') {
          return { id: 'node_modules/chart.js/dist/chunks/helpers.segment.js' }
        }
        if (source === '@kurkle/color') {
          return { id: 'node_modules/@kurkle/color/dist/color.esm.js' }
        }
        return null
      }
    }
  ],
};
