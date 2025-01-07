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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures;

/**
 * Fixture file
 */
class ScalarStringMatcherFixture
{
    public function aMethod(): void
    {
        // Matches
        $foo = 'TYPO3_MODE';
        define('TYPO3_MODE', $foo);
        $concatenatedString = 'no' . 'TYPO3_MODE';
        if (defined('TYPO3_MODE')) {
            $bar = 'baz';
        }
        printf('TYPO3_MODE');

        // No match
        $baz = 'TYPO3_' . 'MODE';
        $baz = ' TYPO3_MODE other ';
        $baz = ' TYPO3_MODE';
        $baz = 'TYPO3_MODE ';
        $TYPO3_MODE = 'nope';
        // TYPO3_MODE
        /* TYPO3_MODE */

        // @extensionScannerIgnoreLine
        $foo = 'TYPO3_MODE';

        // Match (again). No longer ignored.
        $foo = 'TYPO3_MODE';
    }

    // No match
    public function TYPO3_MODE(): void
    {
        $foo = 'bar';
    }
}

class TYPO3_MODE
{
    public function __construct()
    {
        $foo = 'bar';
    }
}
