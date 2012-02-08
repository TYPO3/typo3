<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2012 Andreas Wolf <andreas.wolf@ikt-werk.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Testcase for the Signal/Slot dispatcher
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_SignalSlot_DispatcherTest extends Tx_Phpunit_TestCase {

	/**
	 * @var t3lib_SignalSlot_Dispatcher
	 */
	private $fixture;

	/**
	 * Sets up this test case.
	 */
	public function setUp() {
		$this->fixture = new t3lib_SignalSlot_Dispatcher();
	}

	/**
	 * Cleans up this test case.
	 */
	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function connectAllowsForConnectingASlotWithASignal() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$this->fixture->connect(get_class($mockSignal), 'someSignal', get_class($mockSlot), 'someSlotMethod', FALSE);

		$expectedSlots = array(
			array('class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => NULL, 'passSignalInformation' => FALSE)
		);
		$this->assertSame($expectedSlots, $this->fixture->getSlots(get_class($mockSignal), 'someSignal'));
	}

	/**
	 * @test
	 */
	public function connectAlsoAcceptsObjectsInPlaceOfTheClassName() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$this->fixture->connect(get_class($mockSignal), 'someSignal', $mockSlot, 'someSlotMethod', FALSE);

		$expectedSlots = array(
			array('class' => NULL, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'passSignalInformation' => FALSE)
		);
		$this->assertSame($expectedSlots, $this->fixture->getSlots(get_class($mockSignal), 'someSignal'));
	}

	/**
	 * @test
	 */
	public function connectAlsoAcceptsClosuresActingAsASlot() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = function() { };

		$this->fixture->connect(get_class($mockSignal), 'someSignal', $mockSlot, 'foo', FALSE);

		$expectedSlots = array(
			array('class' => NULL, 'method' => '__invoke', 'object' => $mockSlot, 'passSignalInformation' => FALSE)
		);
		$this->assertSame($expectedSlots, $this->fixture->getSlots(get_class($mockSignal), 'someSignal'));
	}

	/**
	 * @test
	 */
	public function dispatchPassesTheSignalArgumentsToTheSlotMethod() {
		$arguments = array();
		$mockSlot = function() use (&$arguments) {
			$arguments = func_get_args();
		};

		$this->fixture->connect('Foo', 'bar', $mockSlot, NULL, FALSE);

		$this->fixture->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux'), $arguments);
	}

	/**
	 * @test
	 */
	public function dispatchCreatesSlotInstanceIfOnlyAClassNameWasSpecified() {
		$slotClassName = 'Mock_' . md5(uniqid(mt_rand(), TRUE));
		eval ('class ' . $slotClassName . ' { static $arguments; function slot($foo, $baz) { self::$arguments = array($foo, $baz); } }');

		$this->fixture->connect('Foo', 'bar', $slotClassName, 'slot', FALSE);

		$this->fixture->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux'), $slotClassName::$arguments);
	}

	/**
	 * @test
	 * @expectedException t3lib_SignalSlot_InvalidSlotException
	 */
	public function dispatchThrowsAnExceptionIfTheSpecifiedClassOfASlotIsUnknown() {
		$this->fixture->connect('Foo', 'bar', 'NonExistingClassName', 'slot', FALSE);
		$this->fixture->dispatch('Foo', 'bar', array());
	}

	/**
	 * @test
	 * @expectedException t3lib_SignalSlot_InvalidSlotException
	 */
	public function dispatchThrowsAnExceptionIfTheSpecifiedSlotMethodDoesNotExist() {
		$slotClassName = 'Mock_' . md5(uniqid(mt_rand(), TRUE));
		eval ('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
		$mockSlot = new $slotClassName();

		$this->fixture->connect('Foo', 'bar', $slotClassName, 'unknownMethodName', TRUE);

		$this->fixture->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame($mockSlot->arguments, array('bar', 'quux'));
	}

	/**
	 * @test
	 */
	public function dispatchPassesArgumentContainingSlotInformationLastIfTheConnectionStatesSo() {
		$arguments = array();
		$mockSlot = function() use (&$arguments) {
			$arguments = func_get_args();
		};

		$this->fixture->connect('SignalClassName', 'methodName', $mockSlot, NULL, TRUE);

		$this->fixture->dispatch('SignalClassName', 'methodName', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux', 'SignalClassName::methodName'), $arguments);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function connectWithSignalNameStartingWithEmitShouldNotBeAllowed() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$this->fixture->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod', FALSE);
	}
}
?>