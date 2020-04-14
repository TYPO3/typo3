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

use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Locking\FileLockStrategy;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Locking\SemaphoreLockStrategy;
use TYPO3\CMS\Core\Locking\SimpleLockStrategy;
use TYPO3\CMS\Core\Tests\Unit\Locking\Fixtures\DummyLock;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Locking\LockFactory
 */
class LockFactoryTest extends UnitTestCase
{
    /**
     * @var LockFactory|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $mockFactory;

    /**
     * @var array
     */
    protected $strategiesConfigBackup = [];

    /**
     * Set up the tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockFactory = $this->getAccessibleMock(LockFactory::class, ['dummy']);

        // backup global configuration
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'])) {
            $this->strategiesConfigBackup = $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'];
        } else {
            $this->strategiesConfigBackup = [];
        }
    }

    protected function tearDown(): void
    {
        // restore global configuration
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'] = $this->strategiesConfigBackup;

        parent::tearDown();
    }

    /**
     * @test
     */
    public function addLockingStrategyAddsTheClassNameToTheInternalArray()
    {
        $this->mockFactory->addLockingStrategy(DummyLock::class);
        self::assertArrayHasKey(DummyLock::class, $this->mockFactory->_get('lockingStrategy'));
    }

    /**
     * @test
     */
    public function addLockingStrategyThrowsExceptionIfInterfaceIsNotImplemented()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1425990198);

        $this->mockFactory->addLockingStrategy(\stdClass::class);
    }

    /**
     * @test
     */
    public function getLockerReturnsExpectedClass()
    {
        $this->mockFactory->_set('lockingStrategy', [FileLockStrategy::class => true, DummyLock::class => true]);
        $locker = $this->mockFactory->createLocker(
            'id',
            LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_SHARED
        );
        self::assertInstanceOf(FileLockStrategy::class, $locker);
    }

    /**
     * @test
     */
    public function getLockerReturnsClassWithHighestPriority()
    {
        $this->mockFactory->_set('lockingStrategy', [SemaphoreLockStrategy::class => true, DummyLock::class => true]);
        $locker = $this->mockFactory->createLocker('id');
        self::assertInstanceOf(DummyLock::class, $locker);
    }

    /**
     * @test
     */
    public function setPriorityGetLockerReturnsClassWithHighestPriority()
    {
        $lowestValue = min([
            FileLockStrategy::DEFAULT_PRIORITY,
            SimpleLockStrategy::DEFAULT_PRIORITY,
            SemaphoreLockStrategy::DEFAULT_PRIORITY
        ]) - 1;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][FileLockStrategy::class]['priority'] = $lowestValue;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][SemaphoreLockStrategy::class]['priority'] = $lowestValue;
        $locker = $this->mockFactory->createLocker('id');
        self::assertInstanceOf(SimpleLockStrategy::class, $locker);

        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][FileLockStrategy::class]['priority']);
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][SemaphoreLockStrategy::class]['priority']);
    }

    /**
     * @test
     */
    public function getLockerThrowsExceptionIfNoMatchFound()
    {
        $this->expectException(LockCreateException::class);
        $this->expectExceptionCode(1425990190);

        $this->mockFactory->createLocker('id', 32);
    }
}
