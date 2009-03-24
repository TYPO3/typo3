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

require_once(PATH_tslib . 'class.tslib_content.php');

class TX_EXTMVC_Persistence_Session_testcase extends tx_phpunit_testcase {
	
	public function setUp() {
	}
	
	public function test_NewSessionIsEmpty() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_ObjectCanBeRegisteredAsAdded() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(1, count($addedObjects), 'The added objects storage holds 0 or more than 1 objects.');
		$this->assertSame($entity, $addedObjects[0], 'The returned object was not the same as the registered one.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_ObjectCanBeRegisteredAsRemoved() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerRemovedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(1, count($removedObjects), 'The removed objects storage holds 0 or more than 1 objects.');
		$this->assertSame($entity, $removedObjects[0], 'The returned object was not the same as the registered one.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_ObjectCanBeRegisteredAsReconstituted() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerReconstitutedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(1, count($reconstitutedObjects), 'The reconstituted objects storage holds 0 or more than 1 objects.');
		$this->assertSame($entity, $reconstitutedObjects[0], 'The returned object was not the same as the registered one.');
	}

	public function test_ObjectCanBeUnregisteredAsAdded() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity);
		$persistenceSession->unregisterAddedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_ObjectCanBeUnregisteredAsRemoved() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerRemovedObject($entity);
		$persistenceSession->unregisterRemovedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_ObjectCanBeUnregisteredAsReconstituted() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerReconstitutedObject($entity);
		$persistenceSession->unregisterReconstitutedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}
	public function test_ObjectCanBeRemovedAfterBeingAdded() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity);
		$persistenceSession->registerRemovedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_AnObjectCanBeRemovedAfterBeingAdded() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity);
		$persistenceSession->registerRemovedObject($entity);
		$addedObjects = $persistenceSession->getAddedObjects();
		$removedObjects = $persistenceSession->getRemovedObjects();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_TryingToRegisterReconstitutedObjectsAsAddedResultsInAnException() {
		$this->setExpectedException('InvalidArgumentException');
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerReconstitutedObject($entity);
		$persistenceSession->registerAddedObject($entity);
	}

	public function test_TryingToRegisterAddedObjectsAsReconstitutedResultsInAnException() {
		$this->setExpectedException('InvalidArgumentException');
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity1 = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity1);
		$persistenceSession->registerReconstitutedObject($entity1);
	}

	public function test_SessionCanBeCleared() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity1 = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$entity2 = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$entity3 = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$persistenceSession->registerAddedObject($entity1);
		$persistenceSession->registerRemovedObject($entity2);
		$persistenceSession->registerReconstitutedObject($entity3);
		$persistenceSession->registerAggregateRootClassName('TX_EXTMVC_DomainObject_AbstractEntity');
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

	public function test_ObjectCanBeUnregistered() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity1 = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$entity2 = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$entity3 = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
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

	public function test_DirtyEntitiesAreReturned() {
		$persistenceSession = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_AbstractEntity');
		$entity->expects($this->any())
			->method('_isDirty')
			->will($this->returnValue(TRUE));
		$persistenceSession->registerReconstitutedObject($entity);
		$dirtyObjects = $persistenceSession->getDirtyObjects();
		$this->assertEquals(1, count($dirtyObjects), 'There is more than one dirty object.');
		$this->assertEquals($entity, $dirtyObjects[0], 'The entity doesn\'t equal to the dirty object retrieved from the persistenceSession.');
	}

	
}
?>