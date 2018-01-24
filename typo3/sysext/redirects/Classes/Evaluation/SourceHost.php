<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\Evaluation;

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
 * Class SourceHost
 * Triggered from DataHandler as TCA formevals hook for validation / sanitation of domain values.
 */
class SourceHost
{
    /**
     * Server-side removing of protocol on save
     *
     * @param string $value The field value to be evaluated
     * @return string Evaluated field value
     */
    public function evaluateFieldValue(string $value): string
    {
        return preg_replace('#(.*?:\/\/)#', '', $value) ?? '';
    }
}
