<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence;

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
class RepositoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Repository
	 */
	protected $repository;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap
	 */
	protected $mockIdentityMap;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory
	 */
	protected $mockQueryFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface
	 */
	protected $mockBackend;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
	 */
	protected $mockSession;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	protected $mockQuery;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
	 */
	protected $querySettings;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
	 */
	protected $mockQuerySettings;

	public function setUp() {
		$this->mockIdentityMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\IdentityMap');
		$this->mockQueryFactory = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactory');
		$this->mockQuery = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryInterface');
		$this->mockQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$this->mockQuery->expects($this->any())->method('getQuerySettings')->will($this->returnValue($this->mockQuerySettings));
		$this->mockQueryFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockQuery));
		$this->mockBackend = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\BackendInterface');
		$this->mockSession = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Session');
		$this->mockPersistenceManager = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface');
		$this->mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->repository = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Repository', array('dummy'), array($this->mockObjectManager));
		$this->repository->injectIdentityMap($this->mockIdentityMap);
		$this->repository->injectQueryFactory($this->mockQueryFactory);
		$this->repository->injectPersistenceManager($this->mockPersistenceManager);
		$this->repository->injectBackend($this->mockBackend);
		$this->repository->injectSession($this->mockSession);
	}

	/**
	 * @test
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		$this->assertTrue($this->repository instanceof \TYPO3\CMS\Extbase\Persistence\RepositoryInterface);
	}

	/**
	 * @test
	 */
	public function addActuallyAddsAnObjectToTheInternalObjectsArray() {
		$someObject = new \stdClass();
		$this->repository->_set('objectType', get_class($someObject));
		$this->repository->add($someObject);
		$this->assertTrue($this->repository->getAddedObjects()->contains($someObject));
	}

	/**
	 * @test
	 */
	public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray() {
		$object1 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$object2 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$object3 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
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
		$object1 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$object2 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$object3 = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		$this->repository->_set('objectType', get_class($object1));
		$this->repository->add($object1);
		$this->repository->add($object2);
		$this->repository->add($object3);
		$object2->setPid(1);
		$object3->setPid(2);
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
		$object = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
		// if the object is not currently add()ed, it is not new
		$object->expects($this->once())->method('_isNew')->will($this->returnValue(FALSE));
		$this->repository->_set('objectType', get_class($object));
		$this->repository->remove($object);
		$this->assertTrue($this->repository->getRemovedObjects()->contains($object));
	}

	/**
	 * dataProvider for createQueryCallsQueryFactoryWithExpectedType
	 *
	 * @return array
	 */
	public function modelAndRepositoryClassNames() {
		return array(
			array('Tx_BlogExample_Domain_Repository_BlogRepository', 'Tx_BlogExample_Domain_Model_Blog'),
			array('﻿_Domain_Repository_Content_PageRepository', '﻿_Domain_Model_Content_Page'),
			array('Tx_RepositoryExample_Domain_Repository_SomeModelRepository', 'Tx_RepositoryExample_Domain_Model_SomeModel'),
			array('Tx_RepositoryExample_Domain_Repository_RepositoryRepository', 'Tx_RepositoryExample_Domain_Model_Repository'),
			array('Tx_Repository_Domain_Repository_RepositoryRepository', 'Tx_Repository_Domain_Model_Repository')
		);
	}

	/**
	 * @test
	 * @dataProvider modelAndRepositoryClassNames
	 * @param string $repositoryClassName
	 * @param string $modelClassName
	 */
	public function constructSetsObjectTypeFromClassName($repositoryClassName, $modelClassName) {
		$mockClassName = 'MockRepository' . uniqid();
		eval('class ' . $mockClassName . ' extends TYPO3\\CMS\\Extbase\\Persistence\\Repository {
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
	public function createQueryReturnsQueryWithUnmodifiedDefaultQuerySettings() {
		$mockQueryFactory = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactory');
		$mockQuery = new \TYPO3\CMS\Extbase\Persistence\Generic\Query('foo');
		$mockDefaultQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$this->repository->injectQueryFactory($mockQueryFactory);
		$this->repository->setDefaultQuerySettings($mockDefaultQuerySettings);
		$mockQueryFactory->expects($this->once())->method('create')->will($this->returnValue($mockQuery));
		$this->repository->createQuery();
		$instanceQuerySettings = $mockQuery->getQuerySettings();
		$this->assertEquals($mockDefaultQuerySettings, $instanceQuerySettings);
		$this->assertNotSame($mockDefaultQuerySettings, $instanceQuerySettings);
	}

	/**
	 * @test
	 */
	public function findAllCreatesQueryAndReturnsResultOfExecuteCall() {
		$expectedResult = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface');
		$this->mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));
		$this->assertSame($expectedResult, $this->repository->findAll());
	}

	/**
	 * @test
	 */
	public function findByUidReturnsResultOfGetObjectByIdentifierCall() {
		$fakeUid = '123';
		$object = new \stdClass();
		$this->repository->_set('objectType', 'someObjectType');
		$this->mockIdentityMap->expects($this->once())->method('hasIdentifier')->with($fakeUid, 'someObjectType')->will($this->returnValue(TRUE));
		$this->mockIdentityMap->expects($this->once())->method('getObjectByIdentifier')->with($fakeUid)->will($this->returnValue($object));
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
		$existingObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$newObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('123'));
		$this->mockBackend->expects($this->once())->method('replaceObject')->with($existingObject, $newObject);
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
		$existingObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$newObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$removedObjects = new \SplObjectStorage();
		$removedObjects->attach($existingObject);
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue('123'));
		$this->mockBackend->expects($this->once())->method('replaceObject')->with($existingObject, $newObject);
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
		$existingObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$newObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$addedObjects = new \SplObjectStorage();
		$addedObjects->attach($existingObject);
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($existingObject)->will($this->returnValue(NULL));
		$this->mockBackend->expects($this->never())->method('replaceObject');
		$this->repository->_set('objectType', get_class($newObject));
		$this->repository->_set('addedObjects', $addedObjects);
		$this->repository->replace($existingObject, $newObject);
		$this->assertFalse($addedObjects->contains($existingObject));
		$this->assertTrue($addedObjects->contains($newObject));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function replaceChecksObjectType() {
		$this->repository->_set('objectType', 'ExpectedObjectType');
		$this->repository->replace(new \stdClass(), new \stdClass());
	}

	/**
	 * @test
	 */
	public function updateReplacesAnObjectWithTheSameUuidByTheGivenObject() {
		$existingObject = new \stdClass();
		$modifiedObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$modifiedObject->expects($this->once())->method('getUid')->will($this->returnValue('123'));
		$repository = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Repository', array('findByUid', 'replace'), array($this->mockObjectManager));
		$repository->expects($this->once())->method('findByUid')->with('123')->will($this->returnValue($existingObject));
		$repository->expects($this->once())->method('replace')->with($existingObject, $modifiedObject);
		$repository->_set('objectType', get_class($modifiedObject));
		$repository->update($modifiedObject);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
	 */
	public function updateRejectsUnknownObjects() {
		$someObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\DomainObjectInterface');
		$someObject->expects($this->once())->method('getUid')->will($this->returnValue(NULL));
		$this->repository->_set('objectType', get_class($someObject));
		$this->repository->update($someObject);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function updateRejectsObjectsOfWrongType() {
		$this->repository->_set('objectType', 'Foo');
		$this->repository->update(new \stdClass());
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface');
		$this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));
		$this->assertSame($mockQueryResult, $this->repository->findByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$object = new \stdClass();
		$mockQueryResult = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface');
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
		$mockQueryResult = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('count')->will($this->returnValue(2));
		$this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));
		$this->assertSame(2, $this->repository->countByFoo('bar'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException
	 */
	public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled() {
		$this->repository->__call('foo', array());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function addChecksObjectType() {
		$this->repository->_set('objectType', 'ExpectedObjectType');
		$this->repository->add(new \stdClass());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function removeChecksObjectType() {
		$this->repository->_set('objectType', 'ExpectedObjectType');
		$this->repository->remove(new \stdClass());
	}

}


?>