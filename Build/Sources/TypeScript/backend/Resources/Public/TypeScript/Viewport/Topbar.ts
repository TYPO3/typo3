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

import {ScaffoldIdentifierEnum} from '../Enum/Viewport/ScaffoldIdentifier';
import * as $ from 'jquery';
import Toolbar = require('./Toolbar');

class Topbar {
  public static readonly topbarSelector: string = ScaffoldIdentifierEnum.header;
  public readonly Toolbar: Toolbar;

  public constructor() {
    this.Toolbar = new Toolbar();
  }

  public refresh(): void {
    $.ajax(TYPO3.settings.ajaxUrls.topbar).done((data: { [key: string]: string }): void => {
      $(Topbar.topbarSelector).html(data.topbar);
      $(Topbar.topbarSelector).trigger('t3-topbar-update');
    });
  }
}

export = Topbar;
