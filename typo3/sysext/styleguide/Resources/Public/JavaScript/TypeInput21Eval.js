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

/**
 * @exports TYPO3/CMS/Styleguide/TypeInput21Eval
 */
define(
  ['require', 'exports', 'TYPO3/CMS/Backend/FormEngineValidation'],
  function(require, exports, FormEngineValidation) {
    'use strict';

    class TypeInput21Eval {
      static registerCustomEvaluation(name) {
        FormEngineValidation.registerCustomEvaluation(name, TypeInput21Eval.appendJSfoo);
      }

      static appendJSfoo(value) {
        return value + 'JSfoo';
      }
    }

    return TypeInput21Eval;
});
