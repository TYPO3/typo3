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

/**
 * Action used when an operation is executed immediately.
 */
class ImmediateAction extends AbstractAction {
  protected callback: () => void;

  public execute(): Promise<any> {
    return this.executeCallback();
  }

  private async executeCallback(): Promise<any> {
    return Promise.resolve(this.callback());
  }
}

export default ImmediateAction;
