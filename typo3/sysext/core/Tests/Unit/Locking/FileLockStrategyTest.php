<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Locking;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Locking\FileLockStrategy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Locking\FileLockStrategy
 */
class FileLockStrategyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorCreatesLockDirectoryIfNotExisting()
    {
        GeneralUtility::rmdir(Environment::getVarPath() . '/' . FileLockStrategy::FILE_LOCK_FOLDER, true);
        new FileLockStrategy('999999999');
        self::assertTrue(is_dir(Environment::getVarPath() . '/' . FileLockStrategy::FILE_LOCK_FOLDER));
    }

    /**
     * @test
     */
    public function constructorSetsFilePathToExpectedValue()
    {
        $lock = $this->getAccessibleMock(FileLockStrategy::class, ['dummy'], ['999999999']);
        self::assertSame(Environment::getVarPath() . '/' . FileLockStrategy::FILE_LOCK_FOLDER . 'flock_' . md5('999999999'), $lock->_get('filePath'));
    }

    /**
     * @test
     */
    public function getPriorityReturnsDefaultPriority()
    {
        self::assertEquals(FileLockStrategy::getPriority(), FileLockStrategy::DEFAULT_PRIORITY);
    }

    /**
     * @test
     */
    public function setPriority()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][FileLockStrategy::class]['priority'] = 10;

        self::assertEquals(10, FileLockStrategy::getPriority());
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][FileLockStrategy::class]['priority']);
    }
}
