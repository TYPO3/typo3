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
 * Validator for strings.
 *
 * @api
 */
class StringValidator extends AbstractValidator
{
    /**
     * Checks if the given value is a string.
     *
     * @param mixed $value The value that should be validated
     * @api
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.string.notvalid',
                    'extbase'
                ),
                1238108067
            );
        }
    }
}
