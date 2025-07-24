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

namespace TYPO3\CMS\Form\Evaluation;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Triggered from DataHandler as TCA formevals hook for validation / sanitization of domain values.
 *
 * @internal
 */
class EmailOrFormElementIdentifier
{
    /**
     * Server-side removing of invalid email value on save
     *
     * @param string $value The field value to be evaluated
     * @return string Evaluated field value
     */
    public function evaluateFieldValue(string $value): string
    {
        $isValidEmail = GeneralUtility::validEmail($value);
        if (!$isValidEmail && !preg_match('/^\{[^\}]+\}$/', $value)) {
            return '';
        }
        return $value;
    }
}
