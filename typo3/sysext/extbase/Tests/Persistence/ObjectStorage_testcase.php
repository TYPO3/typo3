<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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

class Tx_Extbase_Persistence_ObjectStorage_testcase extends Tx_Extbase_BaseTestCase {
	
	/**
	 * @test
	 */
	public function anObjectCanBeAttached() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$objectStorage->attach($object);
		$result = $objectStorage->offsetGet($object);

		$this->assertEquals($result, $object, 'The retrieved object differs from the attached object.');		
	}
	
	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_InvalidArgumentType
	 */
	public function attachingSomethingElseThanAnObjectThrowsAnException() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$objectStorage->attach('foo');
	}
	
	/**
	 * @test
	 */
	public function anObjectCanBeDetached() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$objectStorage->offsetSet($object, $object);
		$resultBeforeDetaching = $objectStorage->offsetGet($object);

		$this->assertEquals($resultBeforeDetaching, $object, 'The object could not be set via offsetSet().');		

		$objectStorage->detach($object);
		$resultAfterDetaching = $objectStorage->offsetGet($object);

		$this->assertEquals($resultAfterDetaching, NULL, 'The object could not be detached.');		
	}
	
	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_InvalidArgumentType
	 */
	public function detachingSomethingElseThanAnObjectThrowsAnException() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$objectStorage->detach('foo');
	}
	
	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_InvalidArgumentType
	 */
	public function addingAnObjectWithoutAnObjectAsOffsetThrowsAnException() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$objectStorage[] = $object;
	}
		
	/**
	 * @test
	 */
	public function anObjectCouldBeSetViaAnOffset() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$objectStorage[$object] = $object;
		$result = $objectStorage->offsetGet($object);

		$this->assertEquals($result, $object, 'The retrieved object differs from the attached object.');
	}
	
	/**
	 * @test
	 */
	public function itCanBeTestedIfTheStorageContainsAnObject() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$objectStorage->attach($object);
		$result = $objectStorage->contains($object);

		$this->assertEquals($result, TRUE, 'The method object differs from the attached object.');		
	}
	
	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_InvalidArgumentType
	 */
	public function unsettingSomethingElseThanAnObjectThrowsAnException() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		// $object = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		// $objectStorage->offsetSet($object, $object);
		$objectStorage->offsetUnset('foo');
	}

	/**
	 * @test
	 */
	public function anObjectCanBeUnset() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$objectStorage->offsetSet($object, $object);
		$resultBeforeDetaching = $objectStorage->offsetGet($object);

		$this->assertEquals($resultBeforeDetaching, $object, 'The object could not be set via offsetSet().');		

		$objectStorage->offsetUnset($object);
		$resultAfterDetaching = $objectStorage->offsetGet($object);

		$this->assertEquals($resultAfterDetaching, NULL, 'The object could not be unsetted.');		
	}

	/**
	 * @test
	 */
	public function theStorageCanBeRetrievedAsArray() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object1 = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$objectStorage->offsetSet($object1, $object1);
		$object2 = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$objectStorage->offsetSet($object2, $object2);
		$result = $objectStorage->toArray();

		$this->assertEquals(is_array($result), TRUE, 'The result was not an array as expected.');		
		$this->assertEquals(count($result), 2, 'The retrieved array did not contain two elements.');		
	}

}
?>