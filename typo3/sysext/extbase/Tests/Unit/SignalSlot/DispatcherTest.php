<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Felix Oertel <f@oer.tel>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Testcase for the Signal Dispatcher Class
 *
 * @package Extbase
 * @subpackage Tests
 */
class Tx_Extbase_Tests_Unit_SignalSlot_DispatcherTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_SignalSlot_Dispatcher
	 */
	protected $signalSlotDispatcher;

	public function setUp() {
		$this->signalSlotDispatcher = new Tx_Extbase_SignalSlot_Dispatcher();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function connectAllowsForConnectingASlotWithASignal() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod', TRUE);

		$expectedSlots = array(
			array('class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => NULL, 'omitSignalInformation' => TRUE)
		);
		$this->assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function connectAlsoAcceptsObjectsInPlaceOfTheClassName() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'someSlotMethod', TRUE);

		$expectedSlots = array(
			array('class' => NULL, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'omitSignalInformation' => TRUE)
		);
		$this->assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function connectAlsoAcceptsClosuresActingAsASlot() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = function() { };

		$this->signalSlotDispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'foo', TRUE);

		$expectedSlots = array(
			array('class' => NULL, 'method' => '__invoke', 'object' => $mockSlot, 'omitSignalInformation' => TRUE)
		);
		$this->assertSame($expectedSlots, $this->signalSlotDispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchPassesTheSignalArgumentsToTheSlotMethod() {
		$arguments = array();
		$mockSlot = function() use (&$arguments) { $arguments =  func_get_args(); };

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');

		$this->signalSlotDispatcher->connect('Foo', 'bar', $mockSlot, NULL, TRUE);
		$this->signalSlotDispatcher->injectObjectManager($mockObjectManager);

		$this->signalSlotDispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux'), $arguments);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchRetrievesSlotInstanceFromTheObjectManagerIfOnlyAClassNameWasSpecified() {
		$slotClassName = uniqid('Mock_');
		eval ('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
		$mockSlot = new $slotClassName();

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));

		$this->signalSlotDispatcher->injectObjectManager($mockObjectManager);
		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $slotClassName, 'slot', TRUE);

		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame($mockSlot->arguments, array('bar', 'quux'));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_SignalSlot_Exception_InvalidSlotException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchThrowsAnExceptionIfTheSpecifiedClassOfASlotIsUnknown() {
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with('NonExistingClassName')->will($this->returnValue(FALSE));

		$this->signalSlotDispatcher->injectObjectManager($mockObjectManager);
		$this->signalSlotDispatcher->connect('Foo', 'emitBar', 'NonExistingClassName', 'slot', TRUE);
		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array());
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_SignalSlot_Exception_InvalidSlotException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchThrowsAnExceptionIfTheSpecifiedSlotMethodDoesNotExist() {
		$slotClassName = uniqid('Mock_');
		eval ('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
		$mockSlot = new $slotClassName();

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));

		$this->signalSlotDispatcher->injectObjectManager($mockObjectManager);
		$this->signalSlotDispatcher->connect('Foo', 'emitBar', $slotClassName, 'unknownMethodName', TRUE);

		$this->signalSlotDispatcher->dispatch('Foo', 'emitBar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame($mockSlot->arguments, array('bar', 'quux'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchPassesFirstArgumentContainingSlotInformationIfTheConnectionStatesSo() {
		$arguments = array();
		$mockSlot = function() use (&$arguments) { $arguments =  func_get_args(); };

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');

		$this->signalSlotDispatcher->connect('SignalClassName', 'methodName', $mockSlot, NULL, FALSE);
		$this->signalSlotDispatcher->injectObjectManager($mockObjectManager);

		$this->signalSlotDispatcher->dispatch('SignalClassName', 'methodName', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux', 'SignalClassName::methodName'), $arguments);
	}
}
?>