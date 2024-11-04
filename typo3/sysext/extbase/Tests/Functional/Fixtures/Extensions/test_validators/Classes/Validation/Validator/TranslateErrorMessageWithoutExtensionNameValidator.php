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

namespace TYPO3Tests\TestValidators\Validation\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Fixture to verify that validator translates the error message when the default message is used or overridden
 * using validator options.
 */
class TranslateErrorMessageWithoutExtensionNameValidator extends AbstractValidator
{
    protected string $message = 'LLL:EXT:test_validators/Resources/Private/Language/locallang.xlf:validator.translateerrormessagewithoutextensionname.message';

    protected $supportedOptions = [
        'message' => [null, 'Translation key or message for invalid value', 'string'],
    ];

    protected function isValid(mixed $value): void
    {
        $this->addError($this->translateErrorMessage($this->message), 1730722912);
    }
}
