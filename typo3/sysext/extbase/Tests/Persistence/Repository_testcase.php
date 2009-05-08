<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
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

class Tx_Extbase_Persistence_Repository_testcase extends Tx_Extbase_Base_testcase {
	
	// public function test_FindDelegatesToObjectRelationalMapperBuildQueryAndFetch() {
	// 	$repository = new Tx_BlogExample_Domain_Model_BlogRepository();
	// 	$repository->dataMapper = $this->getMock('Tx_Extbase_Persistence_Mapper_ObjectRelationalMapper', array('buildQuery', 'fetch'), array(), '', FALSE);
	// 	$repository->dataMapper->expects($this->once())
	// 		->method('buildQuery')
	// 		->with($this->equalTo('Tx_BlogExample_Domain_Model_Blog'), $this->equalTo('foo'))
	// 		->will($this->returnValue('query'));
	// 	$repository->dataMapper->expects($this->once())
	// 		->method('fetch')
	// 		->with($this->equalTo('Tx_BlogExample_Domain_Model_Blog'), $this->equalTo('query'))
	// 		->will($this->returnValue(array()));
	// 	
	// 	$result = $repository->findByConditions('foo');
	// 	$this->assertEquals(array(), $result);
	// }
	// 
	// public function test_MagicFindByPropertyUsesGenericFind() {
	// 	$repository = $this->getMock('Tx_BlogExample_Domain_Model_BlogRepository', array('findByConditions'), array('Tx_BlogExample_Domain_Model_Blog'));
	// 	$repository->expects($this->once())
	// 		->method('findByConditions')
	// 		->with($this->equalTo(array('name' => 'foo')))
	// 		->will($this->returnValue(array()));
	// 	
	// 	$repository->findByName('foo');
	// }
	// 
	// public function test_MagicFindOneByPropertyUsesGenericFind() {
	// 	$repository = $this->getMock('TX_Blogexample_Domain_Model_BlogRepository', array('findByConditions'), array('Tx_BlogExample_Domain_Model_Blog'));
	// 	$repository->expects($this->once())
	// 		->method('findByConditions')
	// 		->with($this->equalTo(array('name' => 'foo')), $this->equalTo(''), $this->equalTo(''), $this->equalTo(1))
	// 		->will($this->returnValue(array()));
	// 	
	// 	$repository->findOneByName('foo');
	// }

	/**
	 * @test
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		eval('class Tx_Aggregate_Root_Class implements Tx_Extbase_DomainObject_DomainObjectInterface {
			public function _reconstituteProperty($propertyName, $value) {}
			public function _memorizeCleanState() {}
			public function _isDirty() {}
			public function _getProperties() {}
			public function _getDirtyProperties() {}
		}');
		$repository = new Tx_Extbase_Persistence_Repository('Tx_Aggregate_Root_Class');
		$this->assertTrue($repository instanceof Tx_Extbase_Persistence_RepositoryInterface);
	}

	// /**
	//  * @test
	//  */
	// public function addActuallyAddsAnObjectToTheInternalObjectsArray() {
	// 	$someObject = new \stdClass();
	// 	$repository = new Tx_Extbase_Persistence_Repository();
	// 	$repository->add($someObject);
	// 
	// 	$this->assertTrue($repository->getAddedObjects()->contains($someObject));
	// }
	// 
	// /**
	//  * @test
	//  */
	// public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray() {
	// 	$object1 = new \stdClass();
	// 	$object2 = new \stdClass();
	// 	$object3 = new \stdClass();
	// 
	// 	$repository = new Tx_Extbase_Persistence_Repository();
	// 	$repository->add($object1);
	// 	$repository->add($object2);
	// 	$repository->add($object3);
	// 
	// 	$repository->remove($object2);
	// 
	// 	$this->assertTrue($repository->getAddedObjects()->contains($object1));
	// 	$this->assertFalse($repository->getAddedObjects()->contains($object2));
	// 	$this->assertTrue($repository->getAddedObjects()->contains($object3));
	// }
	// 
	// /**
	//  * @test
	//  */
	// public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition() {
	// 	$object1 = new \ArrayObject(array('val' => '1'));
	// 	$object2 = new \ArrayObject(array('val' => '2'));
	// 	$object3 = new \ArrayObject(array('val' => '3'));
	// 
	// 	$repository = new Tx_Extbase_Persistence_Repository();
	// 	$repository->add($object1);
	// 	$repository->add($object2);
	// 	$repository->add($object3);
	// 
	// 	$object2['foo'] = 'bar';
	// 	$object3['val'] = '2';
	// 
	// 	$repository->remove($object2);
	// 
	// 	$this->assertTrue($repository->getAddedObjects()->contains($object1));
	// 	$this->assertFalse($repository->getAddedObjects()->contains($object2));
	// 	$this->assertTrue($repository->getAddedObjects()->contains($object3));
	// }
	// 
	// /**
	//  * Make sure we remember the objects that are not currently add()ed
	//  * but might be in persistent storage.
	//  *
	//  * @test
	//  */
	// public function removeRetainsObjectForObjectsNotInCurrentSession() {
	// 	$object = new \ArrayObject(array('val' => '1'));
	// 	$repository = new Tx_Extbase_Persistence_Repository();
	// 	$repository->remove($object);
	// 
	// 	$this->assertTrue($repository->getRemovedObjects()->contains($object));
	// }
	// 
	// /**
	//  * @test
	//  */
	// public function createQueryCallsQueryFactoryWithExpectedType() {
	// 	eval('class Tx_Aggregate_Root_Class implements Tx_Extbase_DomainObject_DomainObjectInterface {
	// 		public function _reconstituteProperty($propertyName, $value) {}
	// 		public function _memorizeCleanState() {}
	// 		public function _isDirty() {}
	// 		public function _getProperties() {}
	// 		public function _getDirtyProperties() {}
	// 	}');
	// 	$fakeRepositoryClassName = 'ExpectedTypeRepository';
	// 	$expectedType = 'ExpectedType';
	// 
	// 	$mockQueryFactory = $this->getMock('Tx_Extbase_Persistence_QueryFactory');
	// 	$mockQueryFactory->expects($this->once())->method('create')->with($expectedType);
	// 
	// 	$repository = $this->getMock('Tx_Extbase_Persistence_Repository', array('FLOW3_AOP_Proxy_getProxyTargetClassName'), array('Tx_Aggregate_Root_Class'));
	// 	$repository->expects($this->once())->method('FLOW3_AOP_Proxy_getProxyTargetClassName')->will($this->returnValue($fakeRepositoryClassName));
	// 
	// 	$repository->createQuery();
	// }
	// 
	// /**
	//  * @test
	//  */
	// public function findAllCreatesQueryAndReturnsResultOfExecuteCall() {
	// 	$expectedResult = array('one', 'two');
	// 
	// 	$mockQuery = $this->getMock('Tx_Extbase_Persistence_Query');
	// 	$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($expectedResult));
	// 
	// 	$repository = $this->getMock('Tx_Extbase_Persistence_Repository', array('createQuery'));
	// 	$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));
	// 
	// 	$this->assertSame($expectedResult, $repository->findAll());
	// }
	// 
	// /**
	//  * @test
	//  */
	// public function findByUUIDCreatesQueryAndReturnsResultOfExecuteCall() {
	// 	$fakeUUID = '123-456';
	// 
	// 	$mockQuery = $this->getMock('Tx_Extbase_Persistence_Query');
	// 	$mockQuery->expects($this->once())->method('withUUID')->with($fakeUUID)->will($this->returnValue('matchCriteria'));
	// 	$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
	// 	$mockQuery->expects($this->once())->method('execute')->will($this->returnValue(array('one', 'two')));
	// 
	// 	$repository = $this->getMock('Tx_Extbase_Persistence_Repository', array('createQuery'));
	// 	$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));
	// 
	// 	$this->assertSame('one', $repository->findByUUID($fakeUUID));
	// }
	// 
	// /**
	//  * Replacing a reconstituted object (which has a uuid) by a new object
	//  * will ask the persistence backend to replace them accordingly in the
	//  * identity map.
	//  *
	//  * @test
	//  * @return void
	//  */
	// public function replaceReconstitutedObjectByNewObject() {
	// 	$existingObject = new \stdClass;
	// 	$newObject = new \stdClass;
	// 
	// 	$mockPersistenceBackend = $this->getMock('F3\FLOW3\Persistence\BackendInterface');
	// 	$mockPersistenceBackend->expects($this->once())->method('getUUIDByObject')->with($existingObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
	// 	$mockPersistenceBackend->expects($this->once())->method('replaceObject')->with($existingObject, $newObject);
	// 
	// 	$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session', array(), array(), '', FALSE);
	// 	$mockPersistenceSession->expects($this->once())->method('unregisterReconstitutedObject')->with($existingObject);
	// 	$mockPersistenceSession->expects($this->once())->method('registerReconstitutedObject')->with($newObject);
	// 
	// 	$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\ManagerInterface');
	// 	$mockPersistenceManager->expects($this->once())->method('getBackend')->will($this->returnValue($mockPersistenceBackend));
	// 	$mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockPersistenceSession));
	// 
	// 	$repository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('dummy'));
	// 	$repository->injectPersistenceManager($mockPersistenceManager);
	// 	$repository->replace($existingObject, $newObject);
	// }
	// 
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
	// 	$repository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('dummy'));
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
	// 	$repository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('dummy'));
	// 	$repository->injectPersistenceManager($mockPersistenceManager);
	// 	$repository->_set('addedObjects', $addedObjects);
	// 	$repository->replace($existingObject, $newObject);
	// 
	// 	$this->assertFalse($addedObjects->contains($existingObject));
	// 	$this->assertTrue($addedObjects->contains($newObject));
	// }
	// 
	// /**
	//  * @test
	//  */
	// public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria() {
	// 	$mockQuery = $this->getMock('Tx_Extbase_Persistence_Query');
	// 	$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
	// 	$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
	// 	$mockQuery->expects($this->once())->method('execute')->will($this->returnValue(array('baz', 'quux')));
	// 
	// 	$repository = $this->getMock('Tx_Extbase_Persistence_Repository', array('createQuery'));
	// 	$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));
	// 
	// 	$this->assertSame(array('baz', 'quux'), $repository->findByFoo('bar'));
	// }
	// 
	// /**
	//  * @test
	//  */
	// public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria() {
	// 	$mockQuery = $this->getMock('Tx_Extbase_Persistence_Query');
	// 	$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
	// 	$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
	// 	$mockQuery->expects($this->once())->method('execute')->will($this->returnValue(array('baz', 'quux')));
	// 
	// 	$repository = $this->getMock('Tx_Extbase_Persistence_Repository', array('createQuery'));
	// 	$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));
	// 
	// 	$this->assertSame('baz', $repository->findOneByFoo('bar'));
	// }
	// 
	// /**
	//  * @test
	//  * @expectedException F3\FLOW3\Error\Exception
	//  */
	// public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled() {
	// 	$repository = $this->getMock('Tx_Extbase_Persistence_Repository', array('dummy'));
	// 	$repository->__call('foo', array());
	// }
	// 

}
?>