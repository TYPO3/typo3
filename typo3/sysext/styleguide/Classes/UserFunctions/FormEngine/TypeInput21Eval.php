<?php

namespace TYPO3\CMS\Styleguide\UserFunctions\FormEngine;

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
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
 * A "eval" user function used in input_21
 */
class TypeInput21Eval
{
    public function returnFieldJS(): JavaScriptModuleInstruction
    {
        return JavaScriptModuleInstruction::create('@typo3/styleguide/type-input21-eval.js');
    }

    /**
     * Adds text "PHPfoo-evaluate" at end on saving
     *
     * @param string $value
     * @param string $is_in
     * @param bool $set
     * @return string
     */
    public function evaluateFieldValue($value, $is_in, &$set)
    {
        return $value . 'PHPfoo-evaluate';
    }

    /**
     * Adds text "PHPfoo-deevaluate" at end on opening
     *
     * @param array $parameters
     * @return string
     */
    public function deevaluateFieldValue(array $parameters)
    {
        $value = $parameters['value'];
        return $value . 'PHPfoo-deevaluate';
    }
}
