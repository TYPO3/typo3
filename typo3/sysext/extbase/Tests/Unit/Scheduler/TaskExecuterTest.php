<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Scheduler;

require_once __DIR__ . '/Fixtures/MockACommandController.php';

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * TaskExecutor Test Class
 */
class TaskExecutorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\CommandController|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject
	 */
	protected $controller;

	/**
	 * @var \TYPO3\CMS\Core\Cache\CacheManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $commandManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Scheduler\TaskExecutor|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject
	 */
	protected $taskExecuter;

	public function setUp() {
		$this->controller = $this->getAccessibleMock('TYPO3\CMS\Extbase\Tests\MockACommandController', array('dummy'));
		$this->controller->injectReflectionService($this->objectManager->get('TYPO3\CMS\Extbase\Reflection\ReflectionService'));
		$this->controller->injectObjectManager($this->objectManager);

		$command = new \TYPO3\CMS\Extbase\Mvc\Cli\Command('TYPO3\CMS\Extbase\Tests\MockACommandController', 'funcA');
		$nullBackend = new \TYPO3\CMS\Core\Cache\Backend\NullBackend('production');
		$variableFrontend = new \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend('foo', $nullBackend);

		$this->cacheManager = $this->getMock('TYPO3\CMS\Core\Cache\CacheManager', array('dummy', 'getCache'));
		$this->cacheManager->expects($this->any())->method('getCache')->will($this->returnValue($variableFrontend));
		$GLOBALS['typo3CacheManager'] = $this->cacheManager;

		$this->objectManager = $this->getMock('TYPO3\CMS\Extbase\Object\ObjectManager', array('dummy'));
		$this->commandManager = $this->getMock('TYPO3\CMS\Extbase\Mvc\Cli\CommandManager', array('dummy', 'getCommandByIdentifier'));
		$this->configurationManager = $this->getAccessibleMock('TYPO3\CMS\Extbase\Configuration\ConfigurationManager', array('dummy', 'getConfiguration', 'setContentObject', 'setConfiguration'));

		$this->configurationManager
			->expects($this->once())
			->method('getConfiguration')
			->with(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
			->will($this->returnValue(array()));

		$this->commandManager
			->expects($this->any())
			->method('getCommandByIdentifier')
			->will($this->returnValue($command));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function executeDispatchesTheRightCommandControllerAndCommandAction() {
		$dispatcher = $this->getMock('TYPO3\CMS\Extbase\Mvc\Dispatcher', array('resolveController'), array($this->objectManager));
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($this->controller));
		$dispatcher->injectSignalSlotDispatcher($this->objectManager->get('TYPO3\CMS\Extbase\SignalSlot\Dispatcher'));

		$this->taskExecuter = $this->getAccessibleMock('TYPO3\CMS\Extbase\Scheduler\TaskExecutor', array('dummy', 'shutdown', 'getDispatcher'));
		$this->taskExecuter->expects($this->any())->method('getDispatcher')->will($this->returnValue($dispatcher));
		$this->taskExecuter->injectObjectManager($this->objectManager);
		$this->taskExecuter->injectCommandManager($this->commandManager);
		$this->taskExecuter->injectConfigurationManager($this->configurationManager);
		$this->taskExecuter->initializeObject();

		/** @var $task \TYPO3\CMS\Extbase\Scheduler\Task|\PHPUnit_Framework_MockObject_MockObject */
		$task = $this->getMock('TYPO3\CMS\Extbase\Scheduler\Task', array('dummy', 'getCommandIdentifier'));
		$task->expects($this->any())->method('getCommandIdentifier')->will($this->returnValue('extbase:controller:command'));
		$this->taskExecuter->execute($task);

		$this->assertSame('Foo', $this->taskExecuter->_get('response')->getContent());
	}
}

?>