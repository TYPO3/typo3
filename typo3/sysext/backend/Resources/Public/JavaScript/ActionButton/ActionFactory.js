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
define(["require","exports","./ActionEnum","./DeferredAction","./ImmediateAction"],function(require,exports,ActionEnum,DeferredAction,ImmediateAction){"use strict";class ActionFactory{static createAction(e){if(e.type===ActionEnum.DEFERRED)return new DeferredAction(ActionFactory.regenerateCallback(e.callback));if(e.type===ActionEnum.IMMEDIATE)return new ImmediateAction(ActionFactory.regenerateCallback(e.callback));throw new Error("Unknown action type "+e.type+" passed")}static regenerateCallback(callback){return eval(callback.toString())}}return ActionFactory});