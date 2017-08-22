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
 * Validator for integers.
 *
 * @api
 */
class IntegerValidator extends AbstractValidator
{
    /**
     * Checks if the given value is a valid integer.
     *
     * @param mixed $value The value that should be validated
     * @api
     */
    public function isValid($value)
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError(
                $this->translateErrorMessage(
                'validator.integer.notvalid',
                'extbase'
                ),
                1221560494
            );
        }
    }
}
