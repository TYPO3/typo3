<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Scheduler;

require_once __DIR__ . '/Fixtures/MockACommandController.php';
require_once __DIR__ . '/Fixtures/MockBCommandController.php';
require_once __DIR__ . '/Fixtures/MockCCommandController.php';

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
 * FieldProvider Test Class
 */
class FieldProviderTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Command|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $command1;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Command|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $command2;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Command|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $command3;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $commandManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Scheduler\FieldProvider|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject
	 */
	protected $fieldProvider;

	public function setUp() {
		$this->objectManager = $this->getMock('TYPO3\CMS\Extbase\Object\ObjectManager', array('dummy'));
		$this->commandManager = $this->getMock('TYPO3\CMS\Extbase\Mvc\Cli\CommandManager', array('getAvailableCommands'));

		$this->fieldProvider = $this->getAccessibleMock(
			'\TYPO3\CMS\Extbase\Scheduler\FieldProvider',
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$this->command1 = $this->getMock('TYPO3\CMS\Extbase\Mvc\Cli\Command', array('isInternal'), array('TYPO3\\CMS\\Extbase\\Tests\\MockACommandController', 'FuncA'));
		$this->command1->injectObjectManager($this->objectManager);
		$this->command1->injectReflectionService($this->objectManager->get('TYPO3\CMS\Extbase\Reflection\ReflectionService'));
		$this->command1->expects($this->any())->method('isInternal')->will($this->returnValue(FALSE));

		$this->command2 = $this->getMock('TYPO3\CMS\Extbase\Mvc\Cli\Command', array('isInternal'), array('Acme\\Mypkg\\Command\\MockBCommandController', 'FuncB'));
		$this->command2->injectObjectManager($this->objectManager);
		$this->command2->injectReflectionService($this->objectManager->get('TYPO3\CMS\Extbase\Reflection\ReflectionService'));
		$this->command2->expects($this->any())->method('isInternal')->will($this->returnValue(FALSE));

		$this->command3 = $this->getMock('TYPO3\CMS\Extbase\Mvc\Cli\Command', array('isInternal'), array('Tx_Extbase_Command_MockCCommandController', 'FuncC'));
		$this->command3->injectObjectManager($this->objectManager);
		$this->command3->injectReflectionService($this->objectManager->get('TYPO3\CMS\Extbase\Reflection\ReflectionService'));
		$this->command3->expects($this->any())->method('isInternal')->will($this->returnValue(FALSE));

		$this->commandManager->expects($this->any())->method('getAvailableCommands')->will($this->returnValue(array($this->command1, $this->command2, $this->command3)));

		$this->fieldProvider->_set('objectManager', $this->objectManager);
		$this->fieldProvider->_set('commandManager', $this->commandManager);
		$this->fieldProvider->_set('reflectionService', $this->objectManager->get('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService'));
	}

	/**
	 * @test
	 * @author Stefan Neufeind <info@speedpartner.de>
	 */
	public function getCommandControllerActionFieldFetchesCorrectClassNames() {
		$actualResult = $this->fieldProvider->_call('getCommandControllerActionField', array());
		$this->assertContains('<option title="test" value="extbase:mocka:funca">Extbase MockA: FuncA</option>', $actualResult['code']);
		$this->assertContains('<option title="test" value="mypkg:mockb:funcb">Mypkg MockB: FuncB</option>', $actualResult['code']);
		$this->assertContains('<option title="test" value="extbase:mockc:funcc">Extbase MockC: FuncC</option>', $actualResult['code']);
	}

	/**
	 * @test
	 * @author Stefan Neufeind <info@speedpartner.de>
	 */
	public function constructResolvesExtensionnameFromNamespaced() {
		$className = uniqid('DummyController');
		eval('namespace ' . __NAMESPACE__ . '; class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController { function getExtensionName() { return $this->extensionName; } }');
		$classNameNamespaced = __NAMESPACE__ . '\\' . $className;
		$mockController = new $classNameNamespaced();
		$expectedResult = 'Extbase';
		$actualResult = $mockController->getExtensionName();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function validateAdditionalFieldsReturnsTrue() {
		$submittedData = array();
		$this->assertTrue($this->fieldProvider->validateAdditionalFields($submittedData, new \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController()));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function getAdditionalFieldsRendersRightHtml() {
		$expectedAdditionalFields = array(
			'action' => array(
				'code' => '<select name="tx_scheduler[task_extbase][action]">' . LF
					. '<option title="test" value="extbase:mocka:funca" selected="selected">Extbase MockA: FuncA</option>' . LF
					. '<option title="test" value="mypkg:mockb:funcb">Mypkg MockB: FuncB</option>' . LF
					. '<option title="test" value="extbase:mockc:funcc">Extbase MockC: FuncC</option>' . LF
					. '</select>',
				'label' => 'CommandController Command. <em>Save and reopen to define command arguments</em>'
			),
			'description' => array(
				'code' => '',
				'label' => '<strong></strong>'
			),
			'arg' => array(
				'code' => '<input type="text" name="tx_scheduler[task_extbase][arguments][arg]" value="1" /> ',
				'label' => 'Argument: arg. <em>A not required argument</em>'
			)
		);

		$taskInfo = array();
		$task = new \TYPO3\CMS\Extbase\Scheduler\Task();
		$task->setCommandIdentifier($this->command1->getCommandIdentifier());
		$schedulerModule = new \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController();

		$this->assertEquals($expectedAdditionalFields, $this->fieldProvider->getAdditionalFields($taskInfo, $task, $schedulerModule));
	}
}

?>