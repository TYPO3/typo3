<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\SignalSlot;

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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Test case
 */
class DispatcherTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject
	 */
	protected $signalSlotDispatcher;

	public function setUp() {
		$accessibleClassName = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher', array('dummy'));
		$this->signalSlotDispatcher = new $accessibleClassName();
	}

	/**
	 * @test
	 */
	public function connectAllowsForConnectingASlotWithASignal() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));
		$this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod', TRUE);
		$expectedSlots = array(
			array('class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => NULL, 'passSignalInformation' => TRUE)
		);
		$this->assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
	}

	/**
	 * @test
	 */
	public function connectAlsoAcceptsObjectsInPlaceOfTheClassName() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));
		$this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'someSlotMethod', TRUE);
		$expectedSlots = array(
			array('class' => NULL, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'passSignalInformation' => TRUE)
		);
		$this->assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
	}

	/**
	 * @test
	 */
	public function connectAlsoAcceptsClosuresActingAsASlot() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = function () {
		};
		$this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'foo', TRUE);
		$expectedSlots = array(
			array('class' => NULL, 'method' => '__invoke', 'object' => $mockSlot, 'passSignalInformation' => TRUE)
		);
		$this->assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
	}

	/**
	 * @test
	 */
	public function dispatchPassesTheSignalArgumentsToTheSlotMethod() {
		$arguments = array();
		$mockSlot = function () use (&$arguments) {
			($arguments = func_get_args());
		};
		$this->signalSlotDispatcher->connect('Foo', 'bar', $mockSlot, NULL, FALSE);
		$this->signalSlotDispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux'), $arguments);
	}

	/**
	 * @test
	 */
	public function dispatchRetrievesSlotInstanceFromTheObjectManagerIfOnlyAClassNameWasSpecified() {
		$slotClassName = uniqid('Mock_');
		eval('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
		$mockSlot = new $slotClassName();
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));
		$this->signalSlotDispatcher->_set('objectManager', $mockObjectManager);
		$this->signalSlotDispatcher->_set('isInitialized', TRUE);
		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $slotClassName, 'slot', FALSE);
		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame($mockSlot->arguments, array('bar', 'quux'));
	}

	/**
	 * @test
	 */
	public function dispatchHandsOverArgumentsReturnedByAFormerSlot() {
		$this->signalSlotDispatcher->_set('isInitialized', TRUE);

		$firstMockSlot = $this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\SlotFixture');
		$firstMockSlot->expects($this->once())
			->method('slot')
			->will($this->returnCallback(
						function($foo, $baz) {
							return array('modified_' . $foo, 'modified_' . $baz);}
					));

		$secondMockSlot = $this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\SlotFixture');
		$secondMockSlot->expects($this->once())
			->method('slot')
			->with('modified_bar', 'modified_quux');


		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $firstMockSlot, 'slot', FALSE);
		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $secondMockSlot, 'slot', FALSE);

		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array('bar', 'quux'));
	}

	/**
	 * @test
	 */
	public function dispatchHandsOverArgumentsReturnedByAFormerSlotWithoutInterferingWithSignalSlotInformation() {
		$this->signalSlotDispatcher->_set('isInitialized', TRUE);

		$firstMockSlot = $this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\SlotFixture');
		$firstMockSlot->expects($this->once())
			->method('slot')
			->will($this->returnCallback(
						function($foo, $baz) {
							return array('modified_' . $foo, 'modified_' . $baz);}
					));

		$secondMockSlot = $this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\SlotFixture');
		$secondMockSlot->expects($this->once())
			->method('slot')
			->with('modified_bar', 'modified_quux');

		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $firstMockSlot, 'slot');
		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $secondMockSlot, 'slot');

		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array('bar', 'quux'));
	}

	/**
	 * @test
	 */
	public function dispatchHandsOverFormerArgumentsIfPreviousSlotDoesNotReturnAnything() {
		$this->signalSlotDispatcher->_set('isInitialized', TRUE);

		$firstMockSlot = $this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\SlotFixture');
		$firstMockSlot->expects($this->once())
			->method('slot')
			->will($this->returnCallback(
						function($foo, $baz) {
							return array('modified_' . $foo, 'modified_' . $baz);}
					));

		$secondMockSlot = $this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\SlotFixture');
		$secondMockSlot->expects($this->once())
			->method('slot');

		$thirdMockSlot = $this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\SlotFixture');
		$thirdMockSlot->expects($this->once())
			->method('slot')
			->with('modified_bar', 'modified_quux');


		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $firstMockSlot, 'slot');
		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $secondMockSlot, 'slot');
		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $thirdMockSlot, 'slot');

		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array('bar', 'quux'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
	 */
	public function dispatchThrowsAnExceptionIfTheSlotReturnsNonArray() {
		$this->signalSlotDispatcher->_set('isInitialized', TRUE);

		$mockSlot = $this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\SlotFixture');
		$mockSlot->expects($this->once())
			->method('slot')
			->will($this->returnCallback(
						function() {
							return 'string';}
					));

		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $mockSlot, 'slot', FALSE);
		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array('foo' => 'bar', 'baz' => 'quux'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
	 */
	public function dispatchThrowsAnExceptionIfTheSlotReturnsDifferentNumberOfItems() {
		$this->signalSlotDispatcher->_set('isInitialized', TRUE);

		$mockSlot = $this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\SlotFixture');
		$mockSlot->expects($this->once())
			->method('slot')
			->will($this->returnCallback(
						function() {
							return array(1, 2, 3);}
					));

		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $mockSlot, 'slot', FALSE);
		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array('bar', 'quux'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
	 */
	public function dispatchThrowsAnExceptionIfTheSpecifiedClassOfASlotIsUnknown() {
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with('NonExistingClassName')->will($this->returnValue(FALSE));
		$this->signalSlotDispatcher->_set('objectManager', $mockObjectManager);
		$this->signalSlotDispatcher->_set('isInitialized', TRUE);
		$this->signalSlotDispatcher->connect('Foo', 'emitBar', 'NonExistingClassName', 'slot', TRUE);
		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
	 */
	public function dispatchThrowsAnExceptionIfTheSpecifiedSlotMethodDoesNotExist() {
		$slotClassName = uniqid('Mock_');
		eval('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
		$mockSlot = new $slotClassName();
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));
		$this->signalSlotDispatcher->_set('objectManager', $mockObjectManager);
		$this->signalSlotDispatcher->_set('isInitialized', TRUE);
		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $slotClassName, 'unknownMethodName', TRUE);
		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame($mockSlot->arguments, array('bar', 'quux'));
	}

	/**
	 * @test
	 */
	public function dispatchPassesFirstArgumentContainingSlotInformationIfTheConnectionStatesSo() {
		$arguments = array();
		$mockSlot = function () use (&$arguments) {
			($arguments = func_get_args());
		};
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->signalSlotDispatcher->connect('SignalClassName', 'methodName', $mockSlot, NULL, TRUE);
		$this->signalSlotDispatcher->_set('objectManager', $mockObjectManager);
		$this->signalSlotDispatcher->_set('isInitialized', TRUE);
		$this->signalSlotDispatcher->dispatch('SignalClassName', 'methodName', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux', 'SignalClassName::methodName'), $arguments);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function connectThrowsInvalidArgumentExceptionIfSlotMethodNameIsEmptyAndSlotClassNameIsNoClosure() {
		$this->signalSlotDispatcher->connect('ClassA', 'emitSomeSignal', 'ClassB', '');
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function dispatchReturnsEmptyArrayIfSignalNameAndOrSignalClassNameIsNotRegistered() {
		$this->assertSame(array(), $this->signalSlotDispatcher->dispatch('ClassA', 'someNotRegisteredSignalName'));
	}

	/**
	 * @test
	 */
	public function dispatchReturnsEmptyArrayIfSignalDoesNotProvideAnyArguments() {
		$this->assertSame(array(), $this->signalSlotDispatcher->dispatch('ClassA', 'emitSomeSignal'));
	}

	/**
	 * @test
	 */
	public function dispatchReturnsArgumentsArrayAsIsIfSignalIsNotRegistered() {
		$arguments = array(
			'argument1' => 42,
			'argument2' => 'a string',
			'argument3' => new \stdClass()
		);
		$this->assertSame($arguments, $this->signalSlotDispatcher->dispatch('ClassA', 'emitSomeSignal', $arguments));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function dispatchThrowsInvalidSlotExceptionIfObjectManagerOfSignalSlotDispatcherIsNotSet() {
		$this->signalSlotDispatcher->_set('isInitialized', TRUE);
		$this->signalSlotDispatcher->_set('objectManager', NULL);
		$this->signalSlotDispatcher->_set('slots', array('ClassA' => array('emitSomeSignal' => array(array()))));

		$this->assertSame(NULL, $this->signalSlotDispatcher->dispatch('ClassA', 'emitSomeSignal'));
	}
}
