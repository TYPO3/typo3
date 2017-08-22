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
 * Validator for general numbers.
 *
 * @api
 */
class NumberValidator extends AbstractValidator
{
    /**
     * Checks if the given value is a valid number.
     *
     * @param mixed $value The value that should be validated
     */
    public function isValid($value)
    {
        if (!is_numeric($value)) {
            $this->addError(
            $this->translateErrorMessage(
                'validator.number.notvalid',
                'extbase'
            ),
                1221563685
            );
        }
    }
}
