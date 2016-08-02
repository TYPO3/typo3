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

use TYPO3\CMS\Core\Locking\SemaphoreLockStrategy;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Locking\SemaphoreLockStrategy
 */
class SemaphoreLockStrategyTest extends UnitTestCase
{
    /**
     * Set up the tests
     */
    protected function setUp()
    {
        if (!SemaphoreLockStrategy::getCapabilities()) {
            $this->markTestSkipped('The system does not support semaphore locking.');
        }
    }

    /**
     * @test
     */
    public function acquireGetsSemaphore()
    {
        $lock = new SemaphoreLockStrategy('99999');
        $this->assertTrue($lock->acquire());
        $lock->release();
        $lock->destroy();
    }
}
