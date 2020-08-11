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

import Chart from 'TYPO3/CMS/Dashboard/Contrib/chartjs';
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');

class ChartInitializer {

  private readonly selector: string = '.dashboard-item';

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    new RegularEvent('widgetContentRendered', function (this: HTMLElement, e: CustomEvent): void {
      e.preventDefault();
      const config: any = e.detail;

      if (undefined === config || undefined === config.graphConfig) {
        return;
      }

      let _canvas: any = this.querySelector('canvas');
      let context;

      if (_canvas !== null) {
        context = _canvas.getContext('2d');
      }

      if (undefined === context) {
        return;
      }

      new Chart(context, config.graphConfig)
    }).delegateTo(document, this.selector)
  }
}

export = new ChartInitializer();
