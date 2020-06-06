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

import {Resizable} from './Modifier/Resizable';
import {Tabbable} from './Modifier/Tabbable';
import DocumentService = require('TYPO3/CMS/Core/DocumentService');

class TextTableElement {
  private element: HTMLTextAreaElement = null;

  constructor(elementId: string) {
    DocumentService.ready().then((): void => {
      this.element = <HTMLTextAreaElement>document.getElementById(elementId);

      Resizable.enable(this.element);
      Tabbable.enable(this.element);
    });
  }
}

export = TextTableElement;
