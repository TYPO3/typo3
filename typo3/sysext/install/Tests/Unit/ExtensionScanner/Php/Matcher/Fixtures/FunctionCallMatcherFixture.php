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
class FunctionCallMatcherFixture
{
    public function aMethod()
    {
        // Matches
        \debugBegin();

        // No match: Not a global call
        debugBegin();
        // No match: Only 1 arg is too much
        debugBegin('foo');
        // No match: Class context
        $foo->debugBegin();
        // No match: Line ignored
        // @extensionScannerIgnoreLine
        debugBegin();
        // @extensionScannerIgnoreLine
        $bar->bar(\debugBegin());
    }
}
