<?php

declare(strict_types=1);

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
final class RegularExpressionValidator extends AbstractValidator
{
    protected string $message = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validator.regularexpression.nomatch';

    /**
     * @var array
     */
    protected $supportedOptions = [
        'regularExpression' => ['', 'The regular expression to use for validation, used as given', 'string', true],
        'message' => [null, 'Translation key or message when regular expression results in a no match', 'string'],
    ];

    /**
     * Checks if the given value matches the specified regular expression.
     *
     * @throws InvalidValidationOptionsException
     */
    public function isValid(mixed $value): void
    {
        $result = preg_match($this->options['regularExpression'], $value);
        if ($result === 0) {
            $this->addError(
                $this->translateErrorMessage($this->message),
                1221565130
            );
        }
        if ($result === false) {
            throw new InvalidValidationOptionsException('regularExpression "' . $this->options['regularExpression'] . '" in RegularExpressionValidator contained an error.', 1298273089);
        }
    }
}
