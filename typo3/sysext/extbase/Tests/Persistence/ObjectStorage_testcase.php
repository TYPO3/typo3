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
		$object1 = new StdClass;
		$object2 = new StdClass;
		$objectStorage->attach($object1);
		$objectStorage->attach($object2, 'foo');
		$this->assertEquals($objectStorage[$object1], NULL);
		$this->assertEquals($objectStorage[$object2], 'foo');
	}
	
	/**
	 * @test
	 */
	public function anObjectCanBeDetached() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object1 = new StdClass;
		$object2 = new StdClass;
		$objectStorage->attach($object1);
		$objectStorage->attach($object2, 'foo');
		$this->assertEquals(count($objectStorage), 2);
		$objectStorage->detach($object1);
		$this->assertEquals(count($objectStorage), 1);
		$objectStorage->detach($object2);
		$this->assertEquals(count($objectStorage), 0);
	}
	
	/**
	 * @test
	 */
	public function offsetSetAssociatesDataToAnObjectInTheStorage() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object1 = new StdClass;
		$object2 = new StdClass;
		$objectStorage->offsetSet($object1, 'foo');
		$this->assertEquals(count($objectStorage), 1);
		$objectStorage[$object2] = 'bar';
		$this->assertEquals(count($objectStorage), 2);
	}
	
	/**
	 * @test
	 */
	public function offsetUnsetRemovesAnObjectFromTheStorage() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object1 = new StdClass;
		$object2 = new StdClass;
		$objectStorage->attach($object1);
		$objectStorage->attach($object2, 'foo');
		$this->assertEquals(count($objectStorage), 2);
		$objectStorage->offsetUnset($object2);
		$this->assertEquals(count($objectStorage), 1);
		$objectStorage->offsetUnset($object1);
		$this->assertEquals(count($objectStorage), 0);
	}
	
	/**
	 * @test
	 */
	public function offsetGetReturnsTheDataAssociatedWithAnObject() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object1 = new StdClass;
		$object2 = new StdClass;
		$objectStorage[$object1] = 'foo';
		$objectStorage->attach($object2);
		$this->assertEquals($objectStorage->offsetGet($object1), 'foo');
		$this->assertEquals($objectStorage->offsetGet($object2), NULL);
	}
	
	/**
	 * @test
	 */
	public function offsetExistsChecksWhetherAnObjectExistsInTheStorage() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object1 = new StdClass;
		$object2 = new StdClass;
		$objectStorage->attach($object1);
		$this->assertEquals($objectStorage->offsetExists($object1), TRUE);
		$this->assertEquals($objectStorage->offsetExists($object2), FALSE);
	}
	
	/**
	 * @test
	 */
	public function getInfoReturnsTheDataAssociatedWithTheCurrentIteratorEntry() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object1 = new StdClass;
		$object2 = new StdClass;
		$object3 = new StdClass;
		$objectStorage->attach($object1, 42);
		$objectStorage->attach($object2, 'foo');
		$objectStorage->attach($object3, array('bar', 'baz'));
		$objectStorage->rewind();
		$this->assertEquals($objectStorage->getInfo(), 42);
		$objectStorage->next();
		$this->assertEquals($objectStorage->getInfo(), 'foo');
		$objectStorage->next();
		$this->assertEquals($objectStorage->getInfo(), array('bar', 'baz'));
	}
	
	/**
	 * @test
	 */
	public function setInfoSetsTheDataAssociatedWithTheCurrentIteratorEntry() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object1 = new StdClass;
		$object2 = new StdClass;
		$objectStorage->attach($object1);
		$objectStorage->attach($object2, 'foo');
		$objectStorage->rewind();
		$objectStorage->setInfo(42);
		$objectStorage->next();
		$objectStorage->setInfo('bar');
		$this->assertEquals($objectStorage[$object1], 42);
		$this->assertEquals($objectStorage[$object2], 'bar');
	}
	
	/**
	 * @test
	 */
	public function removeAllRemovesObjectsContainedInAnotherStorageFromTheCurrentStorage() {
		$object1 = new StdClass;
		$object2 = new StdClass;
		$objectStorageA = new Tx_Extbase_Persistence_ObjectStorage();
		$objectStorageA->attach($object1, 'foo');
		$objectStorageB = new Tx_Extbase_Persistence_ObjectStorage();
		$objectStorageB->attach($object1, 'bar');
		$objectStorageB->attach($object2, 'baz');
		$this->assertEquals(count($objectStorageB), 2);
		$objectStorageB->removeAll($objectStorageA);
		$this->assertEquals(count($objectStorageB), 1);
	}

	/**
	 * @test
	 */
	public function addAllAddsAllObjectsFromAnotherStorage() {
		$object1 = new StdClass;
		$object2 = new StdClass;
		$objectStorageA = new Tx_Extbase_Persistence_ObjectStorage(); // It might be better to mock this
		$objectStorageA->attach($object1, 'foo');
		$objectStorageB = new Tx_Extbase_Persistence_ObjectStorage();
		$objectStorageB->attach($object2, 'baz');
		$this->assertEquals($objectStorageB->offsetExists($object1), FALSE);
		$objectStorageB->addAll($objectStorageA);
		$this->assertEquals($objectStorageB[$object1], 'foo');
		$this->assertEquals($objectStorageB[$object2], 'baz');
	}

	
	/**
	 * @test
	 */
	public function theStorageCanBeRetrievedAsArray() {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$object1 = new StdClass;
		$object2 = new StdClass;
		$objectStorage->attach($object1, 'foo');
		$objectStorage->attach($object2, 'bar');
		$this->assertEquals($objectStorage->toArray(), array($object1, $object2));
	}

}
?>