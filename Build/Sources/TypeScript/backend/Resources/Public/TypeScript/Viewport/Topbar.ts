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

import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {ScaffoldIdentifierEnum} from '../Enum/Viewport/ScaffoldIdentifier';
import Toolbar = require('./Toolbar');
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');

class Topbar {
  public static readonly topbarSelector: string = ScaffoldIdentifierEnum.header;
  public readonly Toolbar: Toolbar;

  public constructor() {
    this.Toolbar = new Toolbar();
  }

  public refresh(): void {
    new AjaxRequest(TYPO3.settings.ajaxUrls.topbar).get().then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      const topbar = document.querySelector(Topbar.topbarSelector);
      if (topbar !== null) {
        topbar.innerHTML = data.topbar;
        topbar.dispatchEvent(new Event('t3-topbar-update'));
      }
    });
  }
}

export = Topbar;
