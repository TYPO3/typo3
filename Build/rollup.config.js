import bootstrap from './rollup/bootstrap';
import chartjs from './rollup/chartjs';
import ckeditor from './rollup/ckeditor';
import lodash from './rollup/lodash';
import selectPure from './rollup/select-pure';

export default [
  ...ckeditor,
  bootstrap,
  chartjs,
  lodash,
  selectPure,
]
