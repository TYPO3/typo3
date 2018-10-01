<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Mvc\Validation;

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

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validator for empty values.
 *
 * Scope: frontend
 */
class EmptyValidator extends AbstractValidator
{
    /**
     * This validator always needs to be executed even if the given value is empty.
     * See AbstractValidator::validate()
     *
     * @var bool
     */
    protected $acceptsEmptyValues = true;

    /**
     * Checks if the given property ($propertyValue) is empty (NULL, empty string, empty array or empty object).
     *
     * @param mixed $value The value that should be validated
     */
    public function isValid($value)
    {
        if (!empty($value)) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1476396435',
                    'form'
                ),
                1476396435
            );
        }
    }
}
