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

/**
 * Test case
 *
 */
class UpdateExtensionListTaskTest extends \TYPO3\CMS\Components\TestingFramework\Core\UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
     */
    protected $repositoryHelper;

    /**
     * Set up
     */
    protected function setUp()
    {
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('scheduler')) {
            $this->markTestSkipped('Tests need EXT:scheduler loaded.');
        }
        $this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
        $this->repositoryHelper = $this->createMock(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper::class);
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function updateExtensionListTaskIsInstanceOfAbstractTask()
    {
        $taskMock = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask::class)->getMock();
        $this->assertInstanceOf(\TYPO3\CMS\Scheduler\Task\AbstractTask::class, $taskMock);
    }

    /**
     * @test
     */
    public function executeCallsUpdateExtListOfRepositoryHelper()
    {
        $this->repositoryHelper
                ->expects($this->once())
                ->method('updateExtList');

        $objectManagerMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Object\ObjectManager::class)->getMock();
        $objectManagerMock
                ->expects($this->at(0))
                ->method('get')
                ->with(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper::class)
                ->will($this->returnValue($this->repositoryHelper));

        $persistenceManagerMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->getMock();
        $objectManagerMock
                ->expects($this->at(1))
                ->method('get')
                ->will($this->returnValue($persistenceManagerMock));

        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class, $objectManagerMock);

        $task = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask::class)
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
        $objectManagerMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Object\ObjectManager::class)->getMock();
        $objectManagerMock
            ->expects($this->at(0))
            ->method('get')
            ->with(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper::class)
            ->will($this->returnValue($this->repositoryHelper));

        $persistenceManagerMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->getMock();
        $persistenceManagerMock
            ->expects($this->once())
            ->method('persistAll');

        $objectManagerMock
                ->expects($this->at(1))
                ->method('get')
                ->will($this->returnValue($persistenceManagerMock));

        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class, $objectManagerMock);

        /** @var \TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask|PHPUnit_Framework_MockObject_MockObject $task */
        $task = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->execute();
    }
}
