<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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

class Tx_ExtBase_Persistence_ObjectStorage_testcase extends Tx_ExtBase_Base_testcase {
	
	public function setUp() {
	}
	
	public function test_AnObjectCanBeAttached() {
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$objectStorage->attach($object);
		$result = $objectStorage->offsetGet($object);
		$this->assertEquals($result, $object, 'The retrieved object differs from the attached object.');		
	}
	
	public function test_AttachingSomethingElseThanAnObjectThrowsAnException() {
		$this->setExpectedException('Tx_ExtBase_Exception_InvalidArgumentType');
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$objectStorage->attach('foo');
	}
	
	public function test_AnObjectCanBeDetached() {
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$objectStorage->offsetSet($object, $object);
		$resultBeforeDetaching = $objectStorage->offsetGet($object);
		$this->assertEquals($resultBeforeDetaching, $object, 'The object could not be set via offsetSet().');		
		$objectStorage->detach($object);
		$resultAfterDetaching = $objectStorage->offsetGet($object);
		$this->assertEquals($resultAfterDetaching, NULL, 'The object could not be detached.');		
	}
	
	public function test_DetachingSomethingElseThanAnObjectThrowsAnException() {
		$this->setExpectedException('Tx_ExtBase_Exception_InvalidArgumentType');
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$objectStorage->detach('foo');
	}
	
	public function test_AddingAnObjectWithoutAnObjectAsOffsetThrowsAnException() {
		$this->setExpectedException('Tx_ExtBase_Exception_InvalidArgumentType');
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$objectStorage[] = $object;
	}
		
	public function test_AddingAValueOtherThanAnObjectThrowsAnException() {
		$this->setExpectedException('Tx_ExtBase_Exception_InvalidArgumentType');
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$objectStorage[$object] = 'foo';
	}
	
	public function test_IfTheOffsetAndTheValueAreNotEqualAnExceptionIsThrown() {
		$this->setExpectedException('Tx_ExtBase_Exception_InvalidArgumentType');
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$object1 = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$object2 = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$objectStorage[$object1] = $object2;
	}	
	
	public function test_AnObjectCouldBeSetViaAnOffset() {
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$objectStorage[$object] = $object;
		$result = $objectStorage->offsetGet($object);
		$this->assertEquals($result, $object, 'The retrieved object differs from the attached object.');
	}
	
	public function test_ItCanBeTestedIfTheStorageContainsAnObject() {
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$objectStorage->attach($object);
		$result = $objectStorage->contains($object);
		$this->assertEquals($result, TRUE, 'The method object differs from the attached object.');		
	}
	
	public function test_UnsettingSomethingElseThanAnObjectThrowsAnException() {
		$this->setExpectedException('Tx_ExtBase_Exception_InvalidArgumentType');
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		// $object = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		// $objectStorage->offsetSet($object, $object);
		$objectStorage->offsetUnset('foo');
	}

	public function test_AnObjectCanBeUnset() {
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$objectStorage->offsetSet($object, $object);
		$resultBeforeDetaching = $objectStorage->offsetGet($object);
		$this->assertEquals($resultBeforeDetaching, $object, 'The object could not be set via offsetSet().');		
		$objectStorage->offsetUnset($object);
		$resultAfterDetaching = $objectStorage->offsetGet($object);
		$this->assertEquals($resultAfterDetaching, NULL, 'The object could not be unsetted.');		
	}

	public function test_TheStorageCanBeRetrievedAsArray() {
		$objectStorage = new Tx_ExtBase_Persistence_ObjectStorage();
		$object1 = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$objectStorage->offsetSet($object1, $object1);
		$object2 = $this->getMock('Tx_ExtBase_DomainObject_AbstractEntity');
		$objectStorage->offsetSet($object2, $object2);
		$result = $objectStorage->toArray();
		$this->assertEquals(is_array($result), TRUE, 'The result was not an array as expected.');		
		$this->assertEquals(count($result), 2, 'The retrieved array did not contain two elements.');		
	}

}
?>