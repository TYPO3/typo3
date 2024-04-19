import bootstrap from './rollup/bootstrap';
import chartjs from './rollup/chartjs';
import ckeditor from './rollup/ckeditor';
import d3 from './rollup/d3';
import lodash from './rollup/lodash';

export default [
  ...ckeditor,
  bootstrap,
  chartjs,
  ...d3,
  lodash,
]
