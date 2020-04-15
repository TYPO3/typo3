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

use TYPO3\CMS\Core\Locking\SemaphoreLockStrategy;
use TYPO3\CMS\Core\Locking\SimpleLockStrategy;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 *
 * @requires function sem_get
 */
class SemaphoreLockStrategyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function acquireGetsSemaphore()
    {
        $lock = new SemaphoreLockStrategy('99999');
        self::assertTrue($lock->acquire());
        $lock->release();
        $lock->destroy();
    }

    /**
     * @test
     */
    public function getPriorityReturnsDefaultPriority()
    {
        self::assertEquals(SimpleLockStrategy::getPriority(), SimpleLockStrategy::DEFAULT_PRIORITY);
    }

    /**
     * @test
     */
    public function setPriority()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][SemaphoreLockStrategy::class]['priority'] = 10;

        self::assertEquals(10, SemaphoreLockStrategy::getPriority());
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][SemaphoreLockStrategy::class]['priority']);
    }
}
