<?php
namespace TYPO3\CMS\Extbase\Validation\Validator;

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
 * Validator for floats.
 *
 * @api
 */
class FloatValidator extends AbstractValidator
{
    /**
     * The given value is valid if it is of type float or a string matching the regular expression [0-9.e+-]
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    public function isValid($value)
    {
        if (is_float($value)) {
            return;
        }

        if (!is_string($value) || strpos($value, '.') === false || preg_match('/^[0-9.e+-]+$/', $value) !== 1) {
            $this->addError(
                $this->translateErrorMessage(
                'validator.float.notvalid',
                'extbase'
                ), 1221560288);
        }
    }
}
