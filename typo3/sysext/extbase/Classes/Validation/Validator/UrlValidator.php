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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Validator for url.
 */
final class UrlValidator extends AbstractValidator
{
    protected string $message = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validator.url.notvalid';

    protected $supportedOptions = [
        'message' => [null, 'Translation key or message for invalid value', 'string'],
    ];

    /**
     * Checks if the given value is a valid url.
     */
    public function isValid(mixed $value): void
    {
        if (!is_string($value) || !GeneralUtility::isValidUrl($value)) {
            $this->addError($this->translateErrorMessage($this->message), 1238108078);
        }
    }
}
