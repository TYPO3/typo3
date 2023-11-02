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

import { ScaffoldIdentifierEnum } from '../enum/viewport/scaffold-identifier';
import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';

class Toolbar {
  public registerEvent(callback: () => void): void {
    DocumentService.ready().then(() => {
      callback();
    });
    new RegularEvent('t3-topbar-update', callback).bindTo(document.querySelector(ScaffoldIdentifierEnum.header));
  }
}

export default Toolbar;
