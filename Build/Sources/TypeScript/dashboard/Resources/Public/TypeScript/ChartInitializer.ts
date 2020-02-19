/*
* This file is part of the TYPO3 CMS project.
*
* It is free software; you can redistribute it and/or modify it under
* the terms of the GNU General Public License, either version 2
* of the License, or any later version.
*
* For the full copyright and license information, please read the
* LICENSE.txt file that was distributed with this source code.
*
* The TYPO3 project - inspiring people to share!
*/

import * as $ from 'jquery';
let Chart: any = require('TYPO3/CMS/Dashboard/Contrib/chartjs');

class ChartInitializer {

  private selector: string = '.dashboard-item--chart';

  constructor() {
    $((): void => {
      this.initialize();
    });
  }

  public initialize(): void {
    const me = this;
    $(document).on('widgetContentRendered', me.selector, (e: JQueryEventObject, config: any): void => {
      e.preventDefault();
      const $me = $(e.currentTarget);

      if (typeof undefined === config.graphConfig) {
        return;
      }

      let _canvas: any = $me.find('canvas:first');
      let context;

      if (_canvas.length > 0) {
        context = _canvas[0].getContext('2d');
      }

      if (typeof undefined === context) {
        return;
      }

      new Chart(context, config.graphConfig)
    });
  }
}

export = new ChartInitializer();
