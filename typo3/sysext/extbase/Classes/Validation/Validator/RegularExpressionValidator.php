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

use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;

/**
 * Validator based on regular expressions.
 */
class RegularExpressionValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'regularExpression' => ['', 'The regular expression to use for validation, used as given', 'string', true],
        'errorMessage' => ['', 'Error Message to show when validation fails', 'string', false],
    ];

    /**
     * Checks if the given value matches the specified regular expression.
     *
     * @param mixed $value The value that should be validated
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
     */
    public function isValid($value)
    {
        $result = preg_match($this->options['regularExpression'], $value);
        if ($result === 0) {
            $errorMessage = $this->getErrorMessage();
            $this->addError(
                $errorMessage,
                1221565130
            );
        }
        if ($result === false) {
            throw new InvalidValidationOptionsException('regularExpression "' . $this->options['regularExpression'] . '" in RegularExpressionValidator contained an error.', 1298273089);
        }
    }

    protected function getErrorMessage(): string
    {
        $errorMessage = (string)($this->options['errorMessage'] ?? '');
        // if custom message is no locallang reference
        if ($errorMessage !== '' && strpos($errorMessage, 'LLL') !== 0) {
            return $errorMessage;
        }
        if ($errorMessage === '') {
            // fallback to default message
            $errorMessage = 'validator.regularexpression.nomatch';
        }
        return $this->translateErrorMessage(
            $errorMessage,
            'extbase'
        );
    }
}
