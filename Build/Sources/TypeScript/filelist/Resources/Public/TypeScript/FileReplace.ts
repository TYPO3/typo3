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

import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');

class FileReplace {
  constructor() {
    this.registerEvents();
  }

  private registerEvents(): void {
    new RegularEvent('click', function (this: HTMLElement): void {
      const targetSelector = this.dataset.filelistClickTarget;
      (document.querySelector(targetSelector) as HTMLElement).click();
    }).delegateTo(document.body, '[data-filelist-click-target]:not([data-filelist-click-target=""]');

    new RegularEvent('change', function (this: HTMLInputElement): void {
      const targetSelector = this.dataset.filelistChangeTarget;
      (document.querySelector(targetSelector) as HTMLInputElement).value = this.value;
    }).delegateTo(document.body, '[data-filelist-change-target]:not([data-filelist-change-target=""])');
  }
}

export = new FileReplace();
