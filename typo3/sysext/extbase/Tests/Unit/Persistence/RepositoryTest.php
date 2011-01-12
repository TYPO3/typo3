<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Bastian Waidelich <bastian@typo3.org>
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

class Tx_Extbase_Tests_Unit_Persistence_RepositoryTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Persistence_Repository
	 */
	protected $repository;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var Tx_Extbase_Persistence_IdentityMap
	 */
	protected $mockIdentityMap;

	/**
	 * @var Tx_Extbase_Persistence_QueryFactory
	 */
	protected $mockQueryFactory;

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @var Tx_Extbase_Persistence_QueryInterface
	 */
	protected $mockQuery;

	/**
	 * @var Tx_Extbase_Persistence_QuerySettingsInterface
	 */
	protected $querySettings;

	public function setUp() {
		$this->mockIdentityMap = $this->getMock('Tx_Extbase_Persistence_IdentityMap');
		$this->mockQueryFactory = $this->getMock('Tx_Extbase_Persistence_QueryFactory');
		$this->mockQuery = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$this->mockQuerySettings = $this->getMock('Tx_Extbase_Persistence_QuerySettingsInterface');
		$this->mockQuery->expects($this->any())->method('getQuerySettings')->will($this->returnValue($this->mockQuerySettings));
		$this->mockQueryFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockQuery));
		$this->mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$this->mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$this->repository = $this->getAccessibleMock('Tx_Extbase_Persistence_Repository', array('dummy'), array($this->mockObjectManager));
		$this->repository->injectIdentityMap($this->mockIdentityMap);
		$this->repository->injectQueryFactory($this->mockQueryFactory);
		$this->repository->injectPersistenceManager($this->mockPersistenceManager);
	}

	/**
	 * @test
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		$this->assertTrue($this->repository instanceof Tx_Extbase_Persistence_RepositoryInterface);
	}

	/**
	 * @test
	 */
	public function addActuallyAddsAnObjectToTheInternalObjectsArray() {
		$someObject = new stdClass();
		$this->repository->_set('objectType', get_class($someObject));
		$this->repository->add($someObject);

		$this->assertTrue($this->repository->getAddedObjects()->contains($someObject));
	}

	/**
	 * @test
	 */
	public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray() {
		$object1 = new stdClass();
		$object2 = new stdClass();
		$object3 = new stdClass();

		$this->repository->_set('objectType', get_class($object1));
		$this->repository->add($object1);
		$this->repository->add($object2);
		$this->repository->add($object3);

		$this->repository->remove($object2);

		$this->assertTrue($this->repository->getAddedObjects()->contains($object1));
		$this->assertFalse($this->repository->getAddedObjects()->contains($object2));
		$this->assertTrue($this->repository->getAddedObjects()->contains($object3));
	}

	/**
	 * @test
	 */
	public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition() {
		$object1 = new ArrayObject(array('val' => '1'));
		$object2 = new ArrayObject(array('val' => '2'));
		$object3 = new ArrayObject(array('val' => '3'));

		$this->repository->_set('objectType', get_class($object1));
		$this->repository->add($object1);
		$this->repository->add($object2);
		$this->repository->add($object3);

		$object2['foo'] = 'bar';
		$object3['val'] = '2';

		$this->repository->remove($object2);

		$this->assertTrue($this->repository->getAddedObjects()->contains($object1));
		$this->assertFalse($this->repository->getAddedObjects()->contains($object2));
		$this->assertTrue($this->repository->getAddedObjects()->contains($object3));
	}

	/**
	 * Make sure we remember the objects that are not currently add()ed
	 * but might be in persistent storage.
	 *
	 * @test
	 */
	public function removeRetainsObjectForObjectsNotInCurrentSession() {
		$object = new ArrayObject(array('val' => '1'));
		$this->repository->_set('objectType', get_class($object));
		$this->repository->remove($object);

		$this->assertTrue($this->repository->getRemovedObjects()->contains($object));
	}

	/**
	 * dataProvider for createQueryCallsQueryFactoryWithExpectedType
	 */
	public function modelAndRepositoryClassNames() {
		return array(
			array('Tx_BlogExample_Domain_Repository_BlogRepository', 'Tx_BlogExample_Domain_Model_Blog'),
			array('﻿_Domain_Repository_Content_PageRepository', '﻿_Domain_Model_Content_Page')
		);
	}

	/**
	 * @test
	 * @dataProvider modelAndRepositoryClassNames
	 */
	public function constructSetsObjectTypeFromClassName($repositoryClassName, $modelClassName) {
		$mockClassName = 'MockRepository' . uniqid();
		eval('class ' . $mockClassName . ' extends Tx_Extbase_Persistence_Repository {
			protected function getRepositoryClassName() {
				return \'' . $repositoryClassName . '\';
			}
			public function _getObjectType() {
				return $this->objectType;
			}
		}');

		$this->repository = new $mockClassName($this->mockObjectManager);
		$this->assertEquals($modelClassName, $this->repository->_getObjectType());
	}

	/**
	 * @test
	 */
	public function createQueryCallsQueryFactoryWithExpectedClassName() {
		$this->mockQueryFactory->expects($this->once())->method('create')->with('ExpectedType');
		$this->repository->_set('objectType', 'ExpectedType');
		$this->repository->createQuery();
	}

	/**
	 * @test
	 */
	public function findAllCreatesQueryAndReturnsResultOfExecuteCall() {
		$expectedResult = $this->getMock('Tx_Extbase_Persistence_QueryResultInterface');
		$this->mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));
		$this->assertSame($expectedResult, $this->repository->findAll());
	}

	/**
	 * @test
	 */
	public function findByUidReturnsResultOfGetObjectByIdentifierCall() {
		$fakeUid = '123';
		$object = new stdClass();
		$this->repository->_set('objectType', 'someObjectType');

		$this->mockIdentityMap->expects($this->once())->method('hasIdentifier')->with($fakeUid, 'someObjectType')->will($this->returnValue(TRUE));
		$this->mockIdentityMap->expects($this->once())->method('getObjectByIdentifier')->with($fakeUid)->will($this->returnValue($object));

		$expectedResult = $object;
		$actualResult = $this->repository->findByUid($fakeUid);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function findByUidQueriesObjectAndRegistersItIfItWasNotFoundInIdentityMap() {
		$fakeUid = '123';
		$object = new stdClass();
		$this->repository->_set('objectType', 'someObjectType');

		$mockQuerySettings = $this->getMock('Tx_Extbase_Persistence_QuerySettingsInterface');
		$this->mockQuery->expects($this->atLeastOnce())->method('getQuerySettings')->will($this->returnValue($mockQuerySettings));

		$mockQueryResult = $this->getMock('Tx_Extbase_Persistence_QueryResultInterface');

		$this->mockQuery->expects($this->once())->method('equals')->with('uid', $fakeUid)->will($this->returnValue('matchingConstraint'));
		$this->mockQuery->expects($this->once())->method('matching')->with('matchingConstraint')->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));
		$mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));

		$this->mockIdentityMap->expects($this->once())->method('hasIdentifier')->with($fakeUid, 'someObjectType')->will($this->returnValue(FALSE));
		$this->mockIdentityMap->expects($this->once())->method('registerObject')->with($object, $fakeUid);
		$this->mockQueryFactory->expects($this->once())->method('create')->with('someObjectType')->will($this->returnValue($this->mockQuery));

		$expectedResult = $object;
		$actualResult = $this->repository->findByUid($fakeUid);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * Replacing a reconstituted object (which has a uuid) by a new object
	 * will ask the persistence backend to replace them accordingly in the
	 * identity map.
	 *
	 * @test
	 * @return void
	 */
	public function replaceReplacesReconstitutedEntityByNewObject() {
		$existingObject = $this->getMock('Tx_Extbase_DomainObject_DomainObjectInterface');
		$newObject = $this->getMock('Tx_Extbase_DomainObject_DomainObjectInterface');

		$mockBackend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$this->mockPersistenceManager->expects($this->once())->method('getBackend')->will($this->returnValue($mockBackend));
		$mockBackend->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('123'));
		$mockBackend->expects($this->once())->method('replaceObject')->with($existingObject, $newObject);

		$mockSession = $this->getMock('Tx_Extbase_Persistence_Session');
		$this->mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockSession));

		$this->repository->_set('objectType', get_class($newObject));
		$this->repository->replace($existingObject, $newObject);
	}

	/**
	 * Replacing a reconstituted object which during this session has been
	 * marked for removal (by calling the repository's remove method)
	 * additionally registers the "newObject" for removal and removes the
	 * "existingObject" from the list of removed objects.
	 *
	 * @test
	 * @return void
	 */
	public function replaceRemovesReconstitutedObjectWhichIsMarkedToBeRemoved() {
		$existingObject = $this->getMock('Tx_Extbase_DomainObject_DomainObjectInterface');
		$newObject = $this->getMock('Tx_Extbase_DomainObject_DomainObjectInterface');

		$removedObjects = new SplObjectStorage;
		$removedObjects->attach($existingObject);

		$mockBackend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$this->mockPersistenceManager->expects($this->once())->method('getBackend')->will($this->returnValue($mockBackend));
		$mockBackend->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('123'));
		$mockBackend->expects($this->once())->method('replaceObject')->with($existingObject, $newObject);

		$mockSession = $this->getMock('Tx_Extbase_Persistence_Session');
		$this->mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockSession));

		$this->repository->_set('objectType', get_class($newObject));
		$this->repository->_set('removedObjects', $removedObjects);
		$this->repository->replace($existingObject, $newObject);

		$this->assertFalse($removedObjects->contains($existingObject));
		$this->assertTrue($removedObjects->contains($newObject));
	}

	/**
	 * Replacing a new object which has not yet been persisted by another
	 * new object will just replace them in the repository's list of added
	 * objects.
	 *
	 * @test
	 * @return void
	 */
	public function replaceAddsNewObjectToAddedObjects() {
		$existingObject = $this->getMock('Tx_Extbase_DomainObject_DomainObjectInterface');
		$newObject = $this->getMock('Tx_Extbase_DomainObject_DomainObjectInterface');

		$addedObjects = new SplObjectStorage;
		$addedObjects->attach($existingObject);

		$mockBackend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$this->mockPersistenceManager->expects($this->once())->method('getBackend')->will($this->returnValue($mockBackend));
		$mockBackend->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue(NULL));
		$mockBackend->expects($this->never())->method('replaceObject');

		$mockSession = $this->getMock('Tx_Extbase_Persistence_Session');
		$this->mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockSession));

		$this->repository->_set('objectType', get_class($newObject));
		$this->repository->_set('addedObjects', $addedObjects);
		$this->repository->replace($existingObject, $newObject);

		$this->assertFalse($addedObjects->contains($existingObject));
		$this->assertTrue($addedObjects->contains($newObject));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_IllegalObjectType
	 */
	public function replaceChecksObjectType() {
		$this->repository->_set('objectType', 'ExpectedObjectType');

		$this->repository->replace(new stdClass(), new stdClass());
	}

	/**
	 * @test
	 */
	public function updateReplacesAnObjectWithTheSameUuidByTheGivenObject() {
		$existingObject = new stdClass;
		$modifiedObject = $this->getMock('Tx_Extbase_DomainObject_DomainObjectInterface');
		$modifiedObject->expects($this->once())->method('getUid')->will($this->returnValue('123'));

		$repository = $this->getAccessibleMock('Tx_Extbase_Persistence_Repository', array('findByUid', 'replace'), array($this->mockObjectManager));
		$repository->expects($this->once())->method('findByUid')->with('123')->will($this->returnValue($existingObject));
		$repository->expects($this->once())->method('replace')->with($existingObject, $modifiedObject);
		$repository->_set('objectType', get_class($modifiedObject));
		$repository->update($modifiedObject);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_UnknownObject
	 */
	public function updateRejectsUnknownObjects() {
		$someObject = $this->getMock('Tx_Extbase_DomainObject_DomainObjectInterface');
		$someObject->expects($this->once())->method('getUid')->will($this->returnValue(NULL));

		$this->repository->_set('objectType', get_class($someObject));

		$this->repository->update($someObject);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_IllegalObjectType
	 */
	public function updateRejectsObjectsOfWrongType() {
		$this->repository->_set('objectType', 'Foo');
		$this->repository->update(new stdClass());
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('Tx_Extbase_Persistence_QueryResultInterface');
		$this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$this->assertSame($mockQueryResult, $this->repository->findByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$object = new stdClass();
		$mockQueryResult = $this->getMock('Tx_Extbase_Persistence_QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));
		$this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('setLimit')->with(1)->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$this->assertSame($object, $this->repository->findOneByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('Tx_Extbase_Persistence_QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('count')->will($this->returnValue(2));
		$this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$this->assertSame(2, $this->repository->countByFoo('bar'));
	}

	/**
	 * @test
	 * @expectedException Exception
	 */
	public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled() {
		$this->repository->__call('foo', array());
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_IllegalObjectType
	 */
	public function addChecksObjectType() {
		$this->repository->_set('objectType', 'ExpectedObjectType');

		$this->repository->add(new stdClass());
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_IllegalObjectType
	 */
	public function removeChecksObjectType() {
		$this->repository->_set('objectType', 'ExpectedObjectType');

		$this->repository->remove(new stdClass());
	}

}
?>