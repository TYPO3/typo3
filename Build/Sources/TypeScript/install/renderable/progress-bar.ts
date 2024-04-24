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
 * Module: @typo3/install/module/progress-bar
 */
class ProgressBar {
  private readonly template: JQuery = $(
    '<div class="t3js-progressbar progress" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">' +
      '<div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>' +
    '</div>');

  public render(severity: number, title: string, progress: string): JQuery {
    const progressWrapper = this.template.clone();
    const progressBar = progressWrapper.find('.progress-bar');

    progressWrapper.attr('aria-label', title);
    progressBar.addClass('progress-bar-' + Severity.getCssClass(severity));
    if (progress) {
      progressBar.css('width', progress + '%');
      progressWrapper.attr('aria-valuenow', progress);
    }
    if (title) {
      progressBar.text(title);
    }
    return progressWrapper;
  }
}

export default new ProgressBar();
