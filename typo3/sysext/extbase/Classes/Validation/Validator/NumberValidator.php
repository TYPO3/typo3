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

/**
 * Validator for general numbers.
 */
final class NumberValidator extends AbstractValidator
{
    protected string $message = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validator.number.notvalid';

    protected $supportedOptions = [
        'message' => [null, 'Translation key or message for invalid value', 'string'],
    ];

    /**
     * Checks if the given value is a valid number.
     */
    public function isValid(mixed $value): void
    {
        if (!is_numeric($value)) {
            $this->addError($this->translateErrorMessage($this->message), 1221563685);
        }
    }
}
