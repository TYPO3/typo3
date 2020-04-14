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

namespace TYPO3\CMS\Core\Crypto;

/**
 * Crypto safe pseudo-random value generation
 */
class Random
{
    /**
     * Generates cryptographic secure pseudo-random bytes
     *
     * @param int $length
     * @return string
     */
    public function generateRandomBytes(int $length): string
    {
        return random_bytes($length);
    }

    /**
     * Generates cryptographic secure pseudo-random integers
     *
     * @param int $min
     * @param int $max
     * @return int
     */
    public function generateRandomInteger(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /**
     * Generates cryptographic secure pseudo-random hex string
     *
     * @param int $length
     * @return string
     */
    public function generateRandomHexString(int $length): string
    {
        return substr(bin2hex($this->generateRandomBytes((int)(($length + 1) / 2))), 0, $length);
    }
}
