<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Scheduler;

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
 * Testcase for the Task object
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TaskTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Scheduler\Task|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $task;

	/**
	 * @var \TYPO3\CMS\Extbase\Scheduler\TaskExecutor|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $taskExecutor;

	public function setUp() {
		$this->taskExecutor = $this->getMock('TYPO3\\CMS\\Extbase\\Scheduler\\TaskExecutor', array('execute'));
		$this->task = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Scheduler\\Task', array('logException'), array(), '', FALSE);
		$this->task->_set('objectManager', \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager'));
		$this->task->_set('commandManager', \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\CommandManager'));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function executeCallsLogExceptionOnCaughtException() {
		$this->taskExecutor->expects($this->once())->method('execute')->will($this->throwException(new \Exception()));
		$this->task->_set('taskExecutor', $this->taskExecutor);
		$this->task->expects($this->once())->method('logException');
		$this->task->execute();
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function executeReturnsTrueIfNoExceptionIsCaught() {
		$this->task->_set('taskExecutor', $this->taskExecutor);
		$this->assertTrue($this->task->execute());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function setCommandIdentifierSetsCommandIdentifierCorrectly() {
		$this->task->setCommandIdentifier('Foo');
		$this->assertSame('Foo', $this->task->_get('commandIdentifier'));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function getCommandIdentifierReturnsCorrectCommandIdentifier() {
		$this->task->_set('commandIdentifier', 'Foo');
		$this->assertSame('Foo', $this->task->getCommandIdentifier());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function setArgumentsSetsArgumentsCorrectly() {
		$this->task->setArguments(array('Foo'));
		$this->assertSame(array('Foo'), $this->task->_get('arguments'));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function getArgumentsReturnsCorrectArguments() {
		$this->task->_set('arguments', array('Foo'));
		$this->assertSame(array('Foo'), $this->task->getArguments());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function setDefaultsSetsDefaultsCorrectly() {
		$this->task->setDefaults(array('Foo'));
		$this->assertSame(array('Foo'), $this->task->_get('defaults'));
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function getDefaultsReturnsCorrectDefaults() {
		$this->task->_set('defaults', array('Foo'));
		$this->assertSame(array('Foo'), $this->task->getDefaults());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function addDefaultValueAddsDefaultToDefaults() {
		$defaults = array('foo' => 'bar');
		$this->task->_set('defaults', $defaults);

		$defaults['baz'] = 'qux';
		$this->task->addDefaultValue('baz', 'qux');

		$this->assertSame($defaults, $this->task->getDefaults());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function addDefaultValueConvertsBooleanValuesToInteger() {
		$defaults = array('foo' => 'bar');
		$this->task->_set('defaults', $defaults);

		$defaults['baz'] = 1;
		$this->task->addDefaultValue('baz', TRUE);

		$this->assertSame($defaults, $this->task->getDefaults());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function getAdditionalInformationRespectsArguments() {
		$this->task->_set('commandIdentifier', 'foo');
		$this->task->_set('defaults', array('bar' => 'baz'));
		$this->task->_set('arguments', array('qux' => 'quux'));

		$this->assertSame('foo qux=quux', $this->task->getAdditionalInformation());
	}
}

?>