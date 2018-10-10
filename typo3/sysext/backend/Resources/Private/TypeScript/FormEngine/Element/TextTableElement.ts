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
import * as $ from 'jquery';

class TextTableElement {
  private element: HTMLTextAreaElement = null;

  constructor(elementId: string) {
    $((): void => {
      this.element = <HTMLTextAreaElement>document.querySelector('#' + elementId);

      Resizable.enable(this.element);
      Tabbable.enable(this.element);
    });
  }
}

export = TextTableElement;
