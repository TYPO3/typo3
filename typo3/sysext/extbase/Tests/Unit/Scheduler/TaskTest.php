<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Scheduler;

/**
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
 */
class TaskTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Scheduler\Task|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $task;

	/**
	 * @var \TYPO3\CMS\Extbase\Scheduler\TaskExecutor|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $taskExecutor;

	public function setUp() {
		$this->taskExecutor = $this->getMock('TYPO3\\CMS\\Extbase\\Scheduler\\TaskExecutor', array('execute'), array(), '', FALSE);
		$this->task = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Scheduler\\Task', array('logException', '__wakeup'), array(), '', FALSE);
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function executeCallsLogExceptionOnCaughtExceptionAndRethrowsException() {
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
