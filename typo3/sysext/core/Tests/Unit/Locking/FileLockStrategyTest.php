<?php
namespace TYPO3\CMS\Core\Tests\Unit\Locking;

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

use TYPO3\CMS\Core\Locking\FileLockStrategy;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        GeneralUtility::rmdir(PATH_site . FileLockStrategy::FILE_LOCK_FOLDER, true);
        new FileLockStrategy('999999999');
        $this->assertTrue(is_dir(PATH_site . FileLockStrategy::FILE_LOCK_FOLDER));
    }

    /**
     * @test
     */
    public function constructorSetsFilePathToExpectedValue()
    {
        $lock = $this->getAccessibleMock(FileLockStrategy::class, ['dummy'], ['999999999']);
        $this->assertSame(PATH_site . FileLockStrategy::FILE_LOCK_FOLDER . 'flock_' . md5('999999999'), $lock->_get('filePath'));
    }
}
