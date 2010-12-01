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

class Tx_Extbase_Persistence_Repository_testcase extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Persistence_Repository
	 */
	protected $repository;

	/**
	 * @var Tx_Extbase_Persistence_IdentityMap
	 */
	protected $identityMap;

	/**
	 * @var Tx_Extbase_Persistence_QueryFactory
	 */
	protected $queryFactory;

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var Tx_Extbase_Persistence_QueryInterface
	 */
	protected $query;

	/**
	 * @var Tx_Extbase_Persistence_QuerySettingsInterface
	 */
	protected $querySettings;

	public function setUp() {
		$this->identityMap = $this->getMock('Tx_Extbase_Persistence_IdentityMap');
		$this->queryFactory = $this->getMock('Tx_Extbase_Persistence_QueryFactory');
		$this->query = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$this->querySettings = $this->getMock('Tx_Extbase_Persistence_QuerySettingsInterface');
		$this->query->expects($this->any())->method('getQuerySettings')->will($this->returnValue($this->querySettings));
		$this->queryFactory->expects($this->any())->method('create')->will($this->returnValue($this->query));
		$this->persistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$this->repository = $this->getAccessibleMock('Tx_Extbase_Persistence_Repository', array('dummy'), array($this->identityMap, $this->queryFactory, $this->persistenceManager));
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

		$this->repository = new $mockClassName($this->identityMap, $this->queryFactory, $this->persistenceManager);
		$this->assertEquals($modelClassName, $this->repository->_getObjectType());
	}

	/**
	 * @test
	 */
	public function createQueryCallsQueryFactoryWithExpectedClassName() {
		$this->queryFactory->expects($this->once())->method('create')->with('ExpectedType');
		$this->repository->_set('objectType', 'ExpectedType');
		$this->repository->createQuery();
	}

	/**
	 * @test
	 */
	public function findAllCreatesQueryAndReturnsResultOfExecuteCall() {
		$expectedResult = $this->getMock('Tx_Extbase_Persistence_QueryResultInterface');
		$this->query->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));
		$this->assertSame($expectedResult, $this->repository->findAll());
	}

	/**
	 * @test
	 */
	public function findByUidReturnsResultOfGetObjectByIdentifierCall() {
		$fakeUid = '123';
		$object = new stdClass();

		$this->persistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($fakeUid)->will($this->returnValue($object));
		$this->repository->_set('objectType', 'stdClass');

		$this->assertSame($object, $this->repository->findByUid($fakeUid));
	}

	/**
	 * @test
	 */
	public function findByUidReturnsNullIfObjectOfMismatchingTypeWasFoundByGetObjectByIdentifierCall() {
		$fakeUUID = '123';
		$object = new stdClass();

		$this->persistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($fakeUUID)->will($this->returnValue($object));
		$this->repository->_set('objectType', 'otherExpectedClass');

		$this->assertNULL($this->repository->findByUuid($fakeUUID));
	}

	/**
	 * Replacing a reconstituted object (which has a uuid) by a new object
	 * will ask the persistence backend to replace them accordingly in the
	 * identity map.
	 *
	 * @test
	 * @return void
	 */
	public function replaceReconstitutedEntityByNewObject() {
		$existingObject = new stdClass;
		$newObject = new stdClass;

		$this->persistenceManager->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
		$this->persistenceManager->expects($this->once())->method('replaceObject')->with($existingObject, $newObject);

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
	public function replaceReconstitutedObjectWhichIsMarkedToBeRemoved() {
		$existingObject = new stdClass;
		$newObject = new stdClass;

		$removedObjects = new SplObjectStorage;
		$removedObjects->attach($existingObject);

		$this->persistenceManager->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
		$this->persistenceManager->expects($this->once())->method('replaceObject')->with($existingObject, $newObject);

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
	public function replaceNewObjectByNewObject() {
		$existingObject = new stdClass;
		$newObject = new stdClass;

		$addedObjects = new SplObjectStorage;
		$addedObjects->attach($existingObject);

		$this->persistenceManager->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue(NULL));
		$this->persistenceManager->expects($this->never())->method('replaceObject');

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
		$modifiedObject = $this->getMock('FooBar' . uniqid(), array('FLOW3_Persistence_isClone'));
		$modifiedObject->expects($this->once())->method('FLOW3_Persistence_isClone')->will($this->returnValue(TRUE));

		$this->persistenceManager->expects($this->once())->method('getIdentifierByObject')->with($modifiedObject)->will($this->returnValue('86ea8820-19f6-11de-8c30-0800200c9a66'));
		$this->persistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('86ea8820-19f6-11de-8c30-0800200c9a66')->will($this->returnValue($existingObject));

		$this->repository->expects($this->once())->method('replaceObject')->with($existingObject, $modifiedObject);

		$this->repository->_set('objectType', get_class($modifiedObject));
		$this->repository->update($modifiedObject);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_IllegalObjectType
	 */
	public function updateRejectsNonClonedObjects() {
		$someObject = $this->getMock('FooBar' . uniqid(), array('FLOW3_Persistence_isClone'));
		$someObject->expects($this->once())->method('FLOW3_Persistence_isClone')->will($this->returnValue(FALSE));

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
		$mockQuery = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($mockQueryResult));

		$this->repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($mockQueryResult, $this->repository->findByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$object = new stdClass();
		$mockQueryResult = $this->getMock('Tx_Extbase_Persistence_QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));
		$mockQuery = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$this->repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($object, $this->repository->findOneByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('Tx_Extbase_Persistence_QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('count')->will($this->returnValue(2));
		$mockQuery = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$this->repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

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