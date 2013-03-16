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
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class UpdateExtensionListTaskTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * Set up
	 */
	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}

	/**
	 * @test
	 */
	public function updateExtensionListTaskIsInstanceOfAbstractTask() {
		$taskMock = $this->getMock('TYPO3\CMS\Extensionmanager\Task\\UpdateExtensionListTask');
		$this->assertInstanceOf('TYPO3\\CMS\\Scheduler\\Task\\AbstractTask', $taskMock);
	}

	/**
	 * @test
	 */
	public function executeCallsUpdateExtListOfRepositoryHelper() {
		$repositoryHelperMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper');
		$repositoryHelperMock
				->expects($this->once())
				->method('updateExtList');

		$objectManagerMock = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$objectManagerMock
				->expects($this->once())
				->method('get')
				->with('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper')
				->will($this->returnValue($repositoryHelperMock));

		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', $objectManagerMock);

		$task = new \TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask();
		$task->execute();
	}
}
?>