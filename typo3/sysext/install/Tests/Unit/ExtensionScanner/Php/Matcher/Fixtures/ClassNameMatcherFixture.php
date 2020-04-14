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

use TYPO3\CMS\Backend\Console\Application;
use TYPO3\CMS\Backend\Console\Application as App1;
use TYPO3\CMS\Backend\Console\Application as App2;
use TYPO3\CMS\Backend\Console\Application as App3;
use TYPO3\CMS\Backend\Console as Con;
use TYPO3\CMS\Backend\Console as Con2;

/**
 * Fixture file
 */
class ClassNameMatcherFixture extends App2 implements App3, Con\Application
{
    public function aMethod(Con2\Application $app)
    {
        // Matches
        $foo = new \RemoveXSS();
        $foo = new \RemoveXSS();
        (new \RemoveXSS())->foo();
        $foo = new \TYPO3\CMS\Backend\Console\Application();
        (new \TYPO3\CMS\Backend\Console\Application())->foo();
        Application::foo();
        App1::bar();
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(App2::class)->foo();
        $bar = \RemoveXSS::class;
        if ($baz instanceof App3) {
            $foo = 'dummy';
        }
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Console\\Application')->foo();
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\Console\Application')->foo();

        // No matches:
        // Not a matching name - mind the "2" at end
        \RemoveXSS2::class;
        // Prefixing with \ is not allowed and would throw exception in makeInstance anyway
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\\CMS\\Backend\\Console\\Application')->foo();
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\\TYPO3\CMS\Backend\Console\Application')->foo();
        // @extensionScannerIgnoreLine
        $bar = \RemoveXSS::class;
    }
}
