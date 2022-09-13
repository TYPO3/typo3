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

namespace TYPO3\CMS\Core\Authentication;

interface MimicServiceInterface
{
    /**
     * Mimics user authentication for known invalid authentication requests. This method can be used
     * to mitigate timing discrepancies for invalid authentication attempts, which can be used for
     * user enumeration.
     *
     * Authentication services can implement this method to simulate(!) corresponding processes that
     * would be processed during valid requests - e.g. perform password hashing (timing) or call
     * remote services (network latency).
     *
     * @return bool whether other services shall continue
     * @link https://cwe.mitre.org/data/definitions/208.html: CWE-208: Observable Timing Discrepancy
     */
    public function mimicAuthUser(): bool;
}
