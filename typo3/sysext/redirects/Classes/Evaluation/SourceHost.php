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
 * Class SourceHost - Used for validation / sanitation of domain values
 */
class SourceHost
{
    /**
     * Server-side removing of protocol on save
     *
     * @param string $value The field value to be evaluated
     * @param string $is_in The "is_in" value of the field configuration from TCA
     * @param bool $set Boolean defining if the value is written to the database or not.
     * @return string Evaluated field value
     */
    public function evaluateFieldValue($value, $isIn, &$set)
    {
        return preg_replace('#(.*?:\/\/)#', '', $value);
    }
}
