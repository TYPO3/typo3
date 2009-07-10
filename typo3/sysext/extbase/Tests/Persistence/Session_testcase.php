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

require_once(PATH_tslib . 'class.tslib_content.php');

class Tx_Extbase_Persistence_Session_testcase extends tx_phpunit_testcase {
	
	public function setUp() {
		for ($i=1; $i < 4; $i++) {
			$name = 'entity' . $i;
			$this->$name = uniqid('Tx_Extbase_Tests_Entity_');
			eval('class ' . $this->$name . ' implements Tx_Extbase_DomainObject_DomainObjectInterface {
				public function _memorizeCleanState() {}
				public function _isNew() {}
				public function _isDirty() {}
				public function _setProperty($propertyName, $propertyValue) {}
				public function _getProperty($propertyName) {}
				public function _getProperties() {}
				public function _getDirtyProperties() {}
				public function getUid() { return 123; }
			}');			
		}
	}
	
	/**
	 * @test
	 */
	public function newSessionIsEmpty() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	/**
	 * @test
	 */
	public function objectCanBeRegisteredAsAdded() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();

		$this->assertTrue(!empty($addedObjects[$entity]), 'The object was not added.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	/**
	 * @test
	 */
	public function objectCanBeRegisteredAsRemoved() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerRemovedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertTrue(!empty($removedObjects[$entity]), 'The object was not registered as removed.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	/**
	 * @test
	 */
	public function objectCanBeRegisteredAsReconstituted() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerReconstitutedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertTrue(!empty($reconstitutedObjects[$entity]), 'The object was not registered as reconstituted.');
	}

	/**
	 * @test
	 */
	public function objectCanBeUnregisteredAsAdded() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity);
		$persistenceSession->unregisterAddedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	/**
	 * @test
	 */
	public function objectCanBeUnregisteredAsRemoved() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerRemovedObject($entity);
		$persistenceSession->unregisterRemovedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	/**
	 * @test
	 */
	public function objectCanBeUnregisteredAsReconstituted() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerReconstitutedObject($entity);
		$persistenceSession->unregisterReconstitutedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	/**
	 * @test
	 */
	public function objectCanBeRemovedAfterBeingAdded() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity);
		$persistenceSession->registerRemovedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	/**
	 * @test
	 */
	public function anObjectCanBeRemovedAfterBeingAdded() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity);
		$persistenceSession->registerRemovedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	/**
	 * @test
	 */
	public function tryingToRegisterReconstitutedObjectsAsAddedResultsInAnException() {
		$this->setExpectedException('InvalidArgumentException');
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerReconstitutedObject($entity);
		$persistenceSession->registerAddedObject($entity);
	}

	/**
	 * @test
	 */
	public function tryingToRegisterAddedObjectsAsReconstitutedResultsInAnException() {
		$this->setExpectedException('InvalidArgumentException');
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity1 = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity1);
		$persistenceSession->registerReconstitutedObject($entity1);
	}

	/**
	 * @test
	 */
	public function sessionCanBeCleared() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity1 = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$entity2 = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$entity3 = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity1);
		$persistenceSession->registerRemovedObject($entity2);
		$persistenceSession->registerReconstitutedObject($entity3);
		$persistenceSession->registerAggregateRootClassName('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->clear();
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		$aggregateRootClassNames = $persistenceSession->getAggregateRootClassNames();

		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
		$this->assertEquals(0, count($aggregateRootClassName), 'The aggregate root class name was not empty.');
	}

	/**
	 * @test
	 */
	public function objectCanBeUnregistered() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity1 = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$entity2 = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$entity3 = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity1);
		$persistenceSession->registerRemovedObject($entity2);
		$persistenceSession->registerReconstitutedObject($entity3);
		$persistenceSession->unregisterObject($entity1);
		$persistenceSession->unregisterObject($entity2);
		$persistenceSession->unregisterObject($entity3);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();

		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	/**
	 * @test
	 */
	public function dirtyEntitiesAreReturned() {
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$entity = $this->getMock('Tx_Extbase_DomainObject_AbstractEntity');
		$entity->expects($this->any())
			->method('_isDirty')
			->will($this->returnValue(TRUE));
		$persistenceSession->registerReconstitutedObject($entity);
		$dirtyObjects = $persistenceSession->getDirtyObjects();
		$this->assertEquals(1, count($dirtyObjects), 'There is more than one dirty object.');
		$this->assertTrue(!empty($dirtyObjects[$entity]), 'The entity doesn\'t equal to the dirty object retrieved from the persistenceSession.');
	}
	
	
	
	/**
	 * @test
	 */
	public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition() {
		$entity1 = new $this->entity1;
		$entity2 = new $this->entity2;
		$entity3 = new $this->entity3;
	
		$persistenceSession = new Tx_Extbase_Persistence_Session;
		$persistenceSession->registerAddedObject($entity1);
		$persistenceSession->registerAddedObject($entity2);
		$persistenceSession->registerAddedObject($entity3);
	
		$entity2->foo = 'bar';
		$entity3->val = '2';
	
		$persistenceSession->registerRemovedObject($entity2);
	
		$this->assertTrue($persistenceSession->getAddedObjects()->contains($entity1));
		$this->assertFalse($persistenceSession->getAddedObjects()->contains($entity2));
		$this->assertTrue($persistenceSession->getAddedObjects()->contains($entity3));
	}
	
	// /**
	//  * Replacing a reconstituted object which during this session has been
	//  * marked for removal (by calling the repository's remove method)
	//  * additionally registers the "newObject" for removal and removes the
	//  * "existingObject" from the list of removed objects.
	//  *
	//  * @test
	//  * @return void
	//  */
	// public function replaceReconstituedObjectWhichIsMarkedToBeRemoved() {
	// 	$existingObject = new \stdClass;
	// 	$newObject = new \stdClass;
	// 
	// 	$removedObjects = new \SPLObjectStorage;
	// 	$removedObjects->attach($existingObject);
	// 
	// 	$mockPersistenceBackend = $this->getMock('F3\FLOW3\Persistence\BackendInterface');
	// 	$mockPersistenceBackend->expects($this->once())->method('getUUIDByObject')->with($existingObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
	// 
	// 	$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session', array(), array(), '', FALSE);
	// 	$mockPersistenceSession->expects($this->once())->method('unregisterReconstitutedObject')->with($existingObject);
	// 	$mockPersistenceSession->expects($this->once())->method('registerReconstitutedObject')->with($newObject);
	// 
	// 	$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\ManagerInterface');
	// 	$mockPersistenceManager->expects($this->once())->method('getBackend')->will($this->returnValue($mockPersistenceBackend));
	// 	$mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockPersistenceSession));
	// 
	// 	$repository = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Repository'), array('dummy'));
	// 	$repository->injectPersistenceManager($mockPersistenceManager);
	// 	$repository->_set('removedObjects', $removedObjects);
	// 	$repository->replace($existingObject, $newObject);
	// 
	// 	$this->assertFalse($removedObjects->contains($existingObject));
	// 	$this->assertTrue($removedObjects->contains($newObject));
	// }
	// 
	// /**
	//  * Replacing a new object which has not yet been persisted by another
	//  * new object will just replace them in the repository's list of added
	//  * objects.
	//  *
	//  * @test
	//  * @return void
	//  */
	// public function replaceNewObjectByNewObject() {
	// 	$existingObject = new \stdClass;
	// 	$newObject = new \stdClass;
	// 
	// 	$addedObjects = new \SPLObjectStorage;
	// 	$addedObjects->attach($existingObject);
	// 
	// 	$mockPersistenceBackend = $this->getMock('F3\FLOW3\Persistence\BackendInterface');
	// 	$mockPersistenceBackend->expects($this->once())->method('getUUIDByObject')->with($existingObject)->will($this->returnValue(NULL));
	// 
	// 	$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session', array(), array(), '', FALSE);
	// 
	// 	$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\ManagerInterface');
	// 	$mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockPersistenceSession));
	// 	$mockPersistenceManager->expects($this->once())->method('getBackend')->will($this->returnValue($mockPersistenceBackend));
	// 
	// 	$repository = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Repository'), array('dummy'));
	// 	$repository->injectPersistenceManager($mockPersistenceManager);
	// 	$repository->_set('addedObjects', $addedObjects);
	// 	$repository->replace($existingObject, $newObject);
	// 
	// 	$this->assertFalse($addedObjects->contains($existingObject));
	// 	$this->assertTrue($addedObjects->contains($newObject));
	// }
	
}
?>