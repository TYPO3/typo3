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

import { AbstractAction } from './abstract-action';
import Icons from '../icons';

/**
 * Action used when an operation execution time is unknown.
 */
class DeferredAction extends AbstractAction {
  protected override callback: () => Promise<void>;

  public async execute(el: HTMLAnchorElement|HTMLButtonElement): Promise<void> {
    el.dataset.actionLabel = el.innerText;
    el.classList.add('disabled');

    Icons.getIcon('spinner-circle', Icons.sizes.small).then((spinner: string): void => {
      el.innerHTML = spinner;
    });
    return await this.executeCallback(el);
  }

  private async executeCallback(el: HTMLElement): Promise<void> {
    return await Promise.resolve(this.callback()).finally(() => {
      el.innerText = el.dataset.actionLabel;
      el.classList.remove('disabled');
    });
  }
}

export default DeferredAction;
