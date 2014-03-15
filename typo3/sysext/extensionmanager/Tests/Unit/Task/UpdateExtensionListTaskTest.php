<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 *
 */
class UpdateExtensionListTaskTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
	 */
	protected $repositoryHelper;

	/**
	 * Set up
	 */
	public function setUp() {
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('scheduler')) {
			$this->markTestSkipped('Tests need EXT:scheduler loaded.');
		}
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$this->repositoryHelper = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper', array(), array(), '', FALSE);
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function updateExtensionListTaskIsInstanceOfAbstractTask() {
		$taskMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Task\\UpdateExtensionListTask');
		$this->assertInstanceOf('TYPO3\\CMS\\Scheduler\\Task\\AbstractTask', $taskMock);
	}

	/**
	 * @test
	 */
	public function executeCallsUpdateExtListOfRepositoryHelper() {
		$this->repositoryHelper
				->expects($this->once())
				->method('updateExtList');

		$objectManagerMock = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$objectManagerMock
				->expects($this->at(0))
				->method('get')
				->with('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper')
				->will($this->returnValue($this->repositoryHelper));

		$persistenceManagerMock = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
		$objectManagerMock
				->expects($this->at(1))
				->method('get')
				->will($this->returnValue($persistenceManagerMock));

		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', $objectManagerMock);

		$task = new \TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask();
		$task->execute();
	}

	/**
	 * @test
	 */
	public function executeCallsPersistAllOnPersistenceManager() {
		$objectManagerMock = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$objectManagerMock
			->expects($this->at(0))
			->method('get')
			->with('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper')
			->will($this->returnValue($this->repositoryHelper));

		$persistenceManagerMock = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
		$persistenceManagerMock
			->expects($this->once())
			->method('persistAll');

		$objectManagerMock
				->expects($this->at(1))
				->method('get')
				->will($this->returnValue($persistenceManagerMock));

		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', $objectManagerMock);

		$task = new \TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask();
		$task->execute();
	}
}
