import terser from '@rollup/plugin-terser';

export default [
  {
    input: 'node_modules/d3-selection/src/index.js',
    output: {
      file: '../typo3/sysext/core/Resources/Public/JavaScript/Contrib/d3-selection.js',
      format: 'esm',
    },
    plugins: [
      terser({ ecma: 8 }),
    ],
  },
  {
    input: 'node_modules/d3-dispatch/src/index.js',
    output: {
      file: '../typo3/sysext/core/Resources/Public/JavaScript/Contrib/d3-dispatch.js',
      format: 'esm',
    },
    plugins: [
      terser({ ecma: 8 }),
    ],
  },
  {
    input: 'node_modules/d3-drag/src/index.js',
    output: {
      file: '../typo3/sysext/core/Resources/Public/JavaScript/Contrib/d3-drag.js',
      format: 'esm',
    },
    plugins: [
      terser({ ecma: 8 }),
      {
        name: 'externals',
        resolveId: (source) => {
          if (source === 'd3-selection') {
            return { id: 'd3-selection', external: true }
          }
          if (source === 'd3-dispatch') {
            return { id: 'd3-dispatch', external: true }
          }
          return null
        }
      }
    ],
  },
];
