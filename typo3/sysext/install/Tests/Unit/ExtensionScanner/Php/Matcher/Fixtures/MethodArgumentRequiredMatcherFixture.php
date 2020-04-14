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
class MethodArgumentRequiredMatcherFixture
{
    public function aMethod()
    {
        // Match: searchWhere() needs at least 3 arguments
        $foo->searchWhere('arg1', 'arg2');
        $this->searchWhere('arg1', 'arg2');

        // No match: Correct minimum number of args given
        $foo->searchWhere('arg1', 'arg2', 'arg3');
        // No match: 4 args given, but searchWhere() always had only 3
        $foo->searchWhere('arg1', 'arg2', 'arg3', 'arg4');
        // No match: Argument unpacking used
        $arg1 = ['arg1', 'arg2'];
        $foo->searchWhere(...$arg1);
        // No match: Only 2 args given, but called statically
        $foo::searchWhere('arg1', 'arg2');
        // No match: Called statically
        $foo::searchWhere('arg1', 'arg2', 'arg3', 'arg4');
        // No match: Called statically
        $foo::searchWhere('arg1', 'arg2', 'arg3', 'arg4', 'arg5');
        // @extensionScannerIgnoreLine
        $this->searchWhere('arg1', 'arg2');
    }
}
