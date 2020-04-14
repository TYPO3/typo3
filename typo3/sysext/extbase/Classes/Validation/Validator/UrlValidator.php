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
class UrlValidator extends AbstractValidator
{
    /**
     * Checks if the given value is a valid url.
     *
     * @param mixed $value The value that should be validated
     */
    public function isValid($value)
    {
        if (!is_string($value) || !GeneralUtility::isValidUrl($value)) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.url.notvalid',
                    'extbase'
                ),
                1238108078
            );
        }
    }
}
