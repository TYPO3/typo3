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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

/**
 * Representation of Content-Security-Policy hash algorithm type
 * see https://www.w3.org/TR/CSP3/#grammardef-hash-algorithm
 */
enum HashType: string
{
    case sha256 = 'sha256';
    case sha384 = 'sha384';
    case sha512 = 'sha512';

    /**
     * @return int length in bytes
     */
    public function length(): int
    {
        return self::lengthMap()[$this];
    }

    private static function lengthMap(): \WeakMap
    {
        $map = new \WeakMap();
        $map[self::sha256] = 32;
        $map[self::sha384] = 48;
        $map[self::sha512] = 64;
        return $map;
    }
}
