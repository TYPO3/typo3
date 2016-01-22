<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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

use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\DeprecationUtilityFixture;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\DeprecationUtility
 */
class DeprecationUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function deprecationLogFixesPermissionsOnLogFile()
    {
        if (TYPO3_OS === 'WIN') {
            $this->markTestSkipped('deprecationLogFixesPermissionsOnLogFile() test not available on Windows.');
        }
        $filePath = PATH_site . DeprecationUtilityFixture::DEPRECATION_LOG_PATH;
        @mkdir(dirname($filePath));
        $this->testFilesToDelete[] = $filePath;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0777';
        DeprecationUtilityFixture::logMessage('foo');
        clearstatcache();
        $resultFilePermissions = substr(decoct(fileperms($filePath)), 2);
        $this->assertEquals('0777', $resultFilePermissions);
    }
}
