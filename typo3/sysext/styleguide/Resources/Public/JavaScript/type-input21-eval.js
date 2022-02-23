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

import FormEngineValidation from '@typo3/backend/form-engine-validation.js';

class TypeInput21Eval {
  static registerCustomEvaluation(name) {
    FormEngineValidation.registerCustomEvaluation(name, TypeInput21Eval.appendJSfoo);
  }

  static appendJSfoo(value) {
    return value + 'JSfoo';
  }
}

export default TypeInput21Eval;
