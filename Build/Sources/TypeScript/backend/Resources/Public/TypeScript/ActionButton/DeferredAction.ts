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

import {AbstractAction} from './AbstractAction';
import Icons = require('../Icons');

/**
 * Action used when an operation execution time is unknown.
 */
class DeferredAction extends AbstractAction {
  protected callback: () => Promise<any>;

  public async execute(el: HTMLElement): Promise<any> {
    Icons.getIcon('spinner-circle-light', Icons.sizes.small).then((spinner: string): void => {
      el.innerHTML = spinner;
    });
    return await this.executeCallback();
  }

  private async executeCallback(): Promise<any> {
    return await this.callback();
  }
}

export = DeferredAction;
