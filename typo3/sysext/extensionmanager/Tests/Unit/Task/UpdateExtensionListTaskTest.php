<?php

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Task;

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

use TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask;
use TYPO3\CMS\Extensionmanager\Utility\Repository\Helper;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class UpdateExtensionListTaskTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function updateExtensionListTaskIsInstanceOfAbstractTask()
    {
        $taskMock = $this->getMockBuilder(UpdateExtensionListTask::class)->disableOriginalConstructor()->getMock();
        self::assertInstanceOf(AbstractTask::class, $taskMock);
    }

    /**
     * @test
     */
    public function executeCallsUpdateExtListOfRepositoryHelper()
    {
        $repositoryHelper = $this->createMock(Helper::class);
        $repositoryHelper
                ->expects(self::once())
                ->method('updateExtList');

        $objectManagerMock = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $objectManagerMock
                ->expects(self::at(0))
                ->method('get')
                ->with(Helper::class)
                ->willReturn($repositoryHelper);

        $persistenceManagerMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->disableOriginalConstructor()->getMock();
        $objectManagerMock
                ->expects(self::at(1))
                ->method('get')
                ->willReturn($persistenceManagerMock);

        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class, $objectManagerMock);

        $task = $this->getMockBuilder(UpdateExtensionListTask::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->execute();
    }

    /**
     * @test
     */
    public function executeCallsPersistAllOnPersistenceManager()
    {
        $repositoryHelper = $this->createMock(Helper::class);
        $objectManagerMock = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $objectManagerMock
            ->expects(self::at(0))
            ->method('get')
            ->with(Helper::class)
            ->willReturn($repositoryHelper);

        $persistenceManagerMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->disableOriginalConstructor()->getMock();
        $persistenceManagerMock
            ->expects(self::once())
            ->method('persistAll');

        $objectManagerMock
                ->expects(self::at(1))
                ->method('get')
                ->willReturn($persistenceManagerMock);

        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class, $objectManagerMock);

        /** @var UpdateExtensionListTask|PHPUnit_Framework_MockObject_MockObject $task */
        $task = $this->getMockBuilder(UpdateExtensionListTask::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->execute();
    }
}
