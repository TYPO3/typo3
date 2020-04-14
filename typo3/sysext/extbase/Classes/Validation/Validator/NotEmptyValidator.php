<?php

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

namespace TYPO3\CMS\Extbase\Validation\Validator;

/**
 * Validator for not empty values.
 */
class NotEmptyValidator extends AbstractValidator
{
    /**
     * This validator always needs to be executed even if the given value is empty.
     * See AbstractValidator::validate()
     *
     * @var bool
     */
    protected $acceptsEmptyValues = false;

    /**
     * Checks if the given property ($propertyValue) is not empty (NULL, empty string, empty array or empty object).
     *
     * @param mixed $value The value that should be validated
     */
    public function isValid($value)
    {
        if ($value === null) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.notempty.null',
                    'extbase'
                ),
                1221560910
            );
        }
        if ($value === '') {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.notempty.empty',
                    'extbase'
                ),
                1221560718
            );
        }
        if (is_array($value) && empty($value)) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.notempty.empty',
                    'extbase'
                ),
                1347992400
            );
        }
        if (is_object($value) && $value instanceof \Countable && $value->count() === 0) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.notempty.empty',
                    'extbase'
                ),
                1347992453
            );
        }
    }
}
