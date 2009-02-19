<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(PATH_tslib . 'class.tslib_content.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_Session.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/DomainObject/TX_EXTMVC_DomainObject_Entity.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Tests/Fixtures/TX_EXTMVC_Tests_Fixtures_Entity.php');

class TX_EXTMVC_Persistence_Session_testcase extends tx_phpunit_testcase {
	
	public function setUp() {
	}
	
	public function test_NewSessionIsEmpty() {
		$session = new TX_EXTMVC_Persistence_Session;
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_ObjectCanBeRegisteredAsAdded() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerAddedObject($entity);
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();
		
		$this->assertEquals(1, count($addedObjects), 'The added objects storage holds 0 or more than 1 objects.');
		$this->assertSame($entity, $addedObjects[0], 'The returned object was not the same as the registered one.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_ObjectCanBeRegisteredAsRemoved() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerRemovedObject($entity);
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(1, count($removedObjects), 'The removed objects storage holds 0 or more than 1 objects.');
		$this->assertSame($entity, $removedObjects[0], 'The returned object was not the same as the registered one.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_ObjectCanBeRegisteredAsReconstituted() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerReconstitutedObject($entity);
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(1, count($reconstitutedObjects), 'The reconstituted objects storage holds 0 or more than 1 objects.');
		$this->assertSame($entity, $reconstitutedObjects[0], 'The returned object was not the same as the registered one.');
	}

	public function test_ObjectCanBeUnregisteredAsAdded() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerAddedObject($entity);
		$session->unregisterAddedObject($entity);
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_ObjectCanBeUnregisteredAsRemoved() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerRemovedObject($entity);
		$session->unregisterRemovedObject($entity);
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_ObjectCanBeUnregisteredAsReconstituted() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerReconstitutedObject($entity);
		$session->unregisterReconstitutedObject($entity);
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}
	public function test_ObjectCanBeRemovedAfterBeingAdded() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerAddedObject($entity);
		$session->registerRemovedObject($entity);
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_AnObjectCanBeRemovedAfterBeingAdded() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerAddedObject($entity);
		$session->registerRemovedObject($entity);
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();
		
		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_TryingToRegisterReconstitutedObjectsAsAddedResultsInAnException() {
		$this->setExpectedException('InvalidArgumentException');
		$session = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerReconstitutedObject($entity);
		$session->registerAddedObject($entity);
	}

	public function test_TryingToRegisterAddedObjectsAsReconstitutedResultsInAnException() {
		$this->setExpectedException('InvalidArgumentException');
		$session = new TX_EXTMVC_Persistence_Session;
		$entity1 = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerAddedObject($entity1);
		$session->registerReconstitutedObject($entity1);
	}

	public function test_SessionCanBeCleared() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity1 = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$entity2 = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$entity3 = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerAddedObject($entity1);
		$session->registerRemovedObject($entity2);
		$session->registerReconstitutedObject($entity3);
		$session->registerAggregateRootClassName('TX_EXTMVC_DomainObject_Entity');
		$session->clear();
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();
		$aggregateRootClassNames = $session->getAggregateRootClassNames();

		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
		$this->assertEquals(0, count($aggregateRootClassName), 'The aggregate root class name was not empty.');
	}

	public function test_ObjectCanBeUnregistered() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity1 = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$entity2 = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$entity3 = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$session->registerAddedObject($entity1);
		$session->registerRemovedObject($entity2);
		$session->registerReconstitutedObject($entity3);
		$session->unregisterObject($entity1);
		$session->unregisterObject($entity2);
		$session->unregisterObject($entity3);
		$addedObjects = $session->getAddedObjects();
		$removedObjects = $session->getRemovedObjects();
		$reconstitutedObjects = $session->getReconstitutedObjects();

		$this->assertEquals(0, count($addedObjects), 'The added objects storage was not empty.');
		$this->assertEquals(0, count($removedObjects), 'The removed objects storage was not empty.');
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	public function test_DirtyEntitiesAreReturned() {
		$session = new TX_EXTMVC_Persistence_Session;
		$entity = $this->getMock('TX_EXTMVC_DomainObject_Entity');
		$entity->expects($this->any())
			->method('_isDirty')
			->will($this->returnValue(TRUE));
		$session->registerReconstitutedObject($entity);
		$dirtyObjects = $session->getDirtyObjects();
		$this->assertEquals(1, count($dirtyObjects), 'There is more than one dirty object.');
		$this->assertEquals($entity, $dirtyObjects[0], 'The entity doesn\'t equal to the dirty object retrieved from the session.');
	}

	
}
?>