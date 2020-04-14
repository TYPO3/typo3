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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Fixture file
 */
class MethodArgumentRequiredStaticMatcherFixture
{
    public function aMethod()
    {
        // Match: addNavigationComponent() uses less than three arguments
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent('foo', 'bar');
        // Match: Works with shortened class name, too
        ExtensionManagementUtility::addNavigationComponent('foo', 'bar');
        // Match: Weak match on method name
        $foo::addNavigationComponent('foo', 'bar');

        // No match: Three args are ok
        ExtensionManagementUtility::addNavigationComponent('foo', 'bar', 'baz');
        // No match: Argument unpacking used
        ExtensionManagementUtility::addNavigationComponent('foo', ...'bar');
        // No match: All needed args are given
        $foo::addNavigationComponent('foo', 'bar', 'baz');
        $foo::addNavigationComponent(...'foo');
        // @extensionScannerIgnoreLine
        ExtensionManagementUtility::addNavigationComponent('foo', 'bar');
    }
}
