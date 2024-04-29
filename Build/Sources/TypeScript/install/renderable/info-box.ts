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

import $ from 'jquery';
import Severity from './severity';

/**
 * Module: @typo3/install/module/info-box
 */
class InfoBox {
  private readonly template: JQuery = $(
    '<div class="t3js-infobox callout callout-sm">' +
      '<div class="callout-title"></div>' +
      '<div class="callout-body"></div>' +
    '</div>',
  );

  public render(severity: number, title: string, message?: string): JQuery {
    const infoBox: JQuery = this.template.clone();
    infoBox.addClass('callout-' + Severity.getCssClass(severity));
    if (title) {
      infoBox.find('.callout-title').text(title);
    }
    if (message) {
      infoBox.find('.callout-body').text(message);
    } else {
      infoBox.find('.callout-body').remove();
    }
    return infoBox;
  }
}

export default new InfoBox();
