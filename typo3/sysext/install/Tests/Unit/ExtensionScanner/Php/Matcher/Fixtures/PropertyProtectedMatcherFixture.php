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
class PropertyProtectedMatcherFixture
{
    public function aMethod()
    {
        // Match
        $foo->recUpdateAccessCache;

        // No match
        $this->recUpdateAccessCache;
        $foo->foo;
        $foo->$recUpdateAccessCache;
        $foo->recUpdateAccessCache();
        $this->foo;
        $this->$recUpdateAccessCache;
        $this->$recUpdateAccessCache();
        $foo::$recUpdateAccessCache;
        $foo::$recUpdateAccessCache();
        $foo::recUpdateAccessCache;
        $foo::recUpdateAccessCache();
        self::$foo;
        self::$foo();
        static::$foo;
        static::$foo();
        // @extensionScannerIgnoreLine
        $foo->recUpdateAccessCache;
    }
}
