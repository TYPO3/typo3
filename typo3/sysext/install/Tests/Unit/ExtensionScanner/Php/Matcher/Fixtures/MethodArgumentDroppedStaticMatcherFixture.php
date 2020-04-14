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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fixture file
 */
class MethodArgumentDroppedStaticMatcherFixture
{
    public function aMethod()
    {
        // Match: getFileAbsFileName() uses one argument only
        \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('foo', 'bar');
        // Match: Works with shortened class name, too
        GeneralUtility::getFileAbsFileName('foo', 'bar');
        // Match: Weak match on method name
        $foo::getFileAbsFileName('foo', 'bar');

        // No match: One arg is ok
        GeneralUtility::getFileAbsFileName('foo');
        // No match: Argument unpacking used
        GeneralUtility::getFileAbsFileName(...$args1, ...$args2);
        // No match: One arg is ok
        $foo::getFileAbsFileName('foo');
        $foo::getFileAbsFileName(...$args1, ...$args2);
        // @extensionScannerIgnoreLine
        $foo::getFileAbsFileName('foo', 'bar');
    }
}
