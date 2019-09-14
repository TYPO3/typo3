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
import ActionEnum = require('./ActionEnum');
import DeferredAction = require('./DeferredAction');
import ImmediateAction = require('./ImmediateAction');

class ActionFactory {
  public static createAction(options: any): AbstractAction {
    if (options.type === ActionEnum.DEFERRED) {
      return new DeferredAction(ActionFactory.regenerateCallback(options.callback));
    }

    if (options.type === ActionEnum.IMMEDIATE) {
      return new ImmediateAction(ActionFactory.regenerateCallback(options.callback));
    }

    throw new Error('Unknown action type ' + options.type + ' passed');
  }

  private static regenerateCallback(callback: Function): any {
    // tslint:disable-next-line:no-eval
    return eval(callback.toString());
  }
}

export = ActionFactory;
