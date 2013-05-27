<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nico de Haen
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

require_once __DIR__ . '/../Fixture/Model/Entity3.php';
require_once __DIR__ . '/../Fixture/Model/Entity2.php';

/**
 * A PersistenceManager Test
 */
class PersistenceManagerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function persistAllPassesAddedObjectsToBackend() {
		$entity2 = new \TYPO3\CMS\Extbase\Tests\Persistence\Fixture\Model\Entity2();
		$objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$objectStorage->attach($entity2);
		$mockBackend = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface');
		$mockBackend->expects($this->once())->method('setAggregateRootObjects')->with($objectStorage);

		$manager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$manager->injectBackend($mockBackend);
		$manager->add($entity2);

		$manager->persistAll();
	}

	/**
	 * @test
	 */
	public function persistAllPassesRemovedObjectsToBackend() {
		$entity2 = new \TYPO3\CMS\Extbase\Tests\Persistence\Fixture\Model\Entity2();
		$objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$objectStorage->attach($entity2);
		$mockBackend = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface');
		$mockBackend->expects($this->once())->method('setDeletedEntities')->with($objectStorage);

		$manager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$manager->injectBackend($mockBackend);
		$manager->remove($entity2);

		$manager->persistAll();
	}

	/**
	 * @test
	 */
	public function getIdentifierByObjectReturnsIdentifierFromSession() {
		$fakeUuid = 'fakeUuid';
		$object = new \stdClass();

		$mockSession = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\Session');
		$mockSession->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue($fakeUuid));

		$manager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);

		$this->assertEquals($manager->getIdentifierByObject($object), $fakeUuid);
	}

	/**
	 * @test
	 */
	public function getObjectByIdentifierReturnsObjectFromSessionIfAvailable() {
		$fakeUuid = 'fakeUuid';
		$object = new \stdClass();

		$mockSession = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\Session');
		$mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(TRUE));
		$mockSession->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid)->will($this->returnValue($object));

		$manager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);

		$this->assertEquals($manager->getObjectByIdentifier($fakeUuid), $object);
	}

	/**
	 * @test
	 */
	public function getObjectByIdentifierReturnsObjectFromPersistenceIfAvailable() {
		$fakeUuid = '42';
		$object = new \stdClass();
		$fakeEntityType = get_class($object);

		$mockSession = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\Session');
		$mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(FALSE));

		$mockBackend = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface');
		$mockBackend->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid, $fakeEntityType)->will($this->returnValue($object));

		$manager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);
		$manager->injectBackend($mockBackend);

		$this->assertEquals($manager->getObjectByIdentifier($fakeUuid, $fakeEntityType), $object);
	}

	/**
	 * @test
	 */
	public function getObjectByIdentifierReturnsNullForUnknownObject() {
		$fakeUuid = '42';
		$fakeEntityType = 'foobar';

		$mockSession = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\Session');
		$mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid, $fakeEntityType)->will($this->returnValue(FALSE));

		$mockBackend = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface');
		$mockBackend->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid, $fakeEntityType)->will($this->returnValue(NULL));

		$manager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$manager->injectPersistenceSession($mockSession);
		$manager->injectBackend($mockBackend);

		$this->assertNull($manager->getObjectByIdentifier($fakeUuid, $fakeEntityType));
	}

	/**
	 * @test
	 */
	public function addActuallyAddsAnObjectToTheInternalObjectsArray() {
		$someObject = new \stdClass();
		$persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$persistenceManager->add($someObject);

		$this->assertAttributeContains($someObject, 'addedObjects', $persistenceManager);
	}

	/**
	 * @test
	 */
	public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray() {
		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$object3 = new \stdClass();

		$persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$persistenceManager->add($object1);
		$persistenceManager->add($object2);
		$persistenceManager->add($object3);

		$persistenceManager->remove($object2);

		$this->assertAttributeContains($object1, 'addedObjects', $persistenceManager);
		$this->assertAttributeNotContains($object2, 'addedObjects', $persistenceManager);
		$this->assertAttributeContains($object3, 'addedObjects', $persistenceManager);
	}

	/**
	 * @test
	 */
	public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition() {
		$object1 = new \ArrayObject(array('val' => '1'));
		$object2 = new \ArrayObject(array('val' => '2'));
		$object3 = new \ArrayObject(array('val' => '3'));

		$persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$persistenceManager->add($object1);
		$persistenceManager->add($object2);
		$persistenceManager->add($object3);

		$object2['foo'] = 'bar';
		$object3['val'] = '2';

		$persistenceManager->remove($object2);

		$this->assertAttributeContains($object1, 'addedObjects', $persistenceManager);
		$this->assertAttributeNotContains($object2, 'addedObjects', $persistenceManager);
		$this->assertAttributeContains($object3, 'addedObjects', $persistenceManager);
	}

	/**
	 * Make sure we remember the objects that are not currently add()ed
	 * but might be in persistent storage.
	 *
	 * @test
	 */
	public function removeRetainsObjectForObjectsNotInCurrentSession() {
		$object = new \ArrayObject(array('val' => '1'));
		$persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$persistenceManager->remove($object);

		$this->assertAttributeContains($object, 'removedObjects', $persistenceManager);
	}

	/**
	 * @test
	 */
	public function updateSchedulesAnObjectForPersistence() {
		$className = uniqid('BazFixture');
		eval ('
			namespace Foo\\Bar\\Domain\\Model;
			class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {
				protected $uid = 42;
			}
		');
		eval ('
			namespace Foo\\Bar\\Domain\\Repository;
			class  ' . $className . 'Repository extends \\TYPO3\\CMS\\Extbase\\Persistence\\Repository {}
		');
		$classNameWithNamespace = 'Foo\\Bar\\Domain\\Model\\' . $className;
		$repositorClassNameWithNamespace = 'Foo\\Bar\\Domain\\Repository\\' . $className . 'Repository';

		$persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$session = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
		$changedEntities = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$entity1 = new $classNameWithNamespace();
		$repository = new $repositorClassNameWithNamespace;
		$mockBackend = $this->getMock($this->buildAccessibleProxy('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend'), array('commit', 'setChangedEntities'), array(), '', FALSE);
		$mockBackend->expects($this->once())
			->method('setChangedEntities')
			->with($this->equalTo($changedEntities));

		$persistenceManager->injectBackend($mockBackend);
		$persistenceManager->injectPersistenceSession($session);
		$repository->injectPersistenceManager($persistenceManager);

		$session->registerObject($entity1, 42);
		$changedEntities->attach($entity1);
		$repository->update($entity1);
		$persistenceManager->persistAll();

	}

	/**
	 * @test
	 */
	public function clearStateForgetsAboutNewObjects() {
		$mockObject = $this->getMock('TYPO3\CMS\Extbase\Persistence\Aspect\PersistenceMagicInterface');
		$mockObject->Persistence_Object_Identifier = 'abcdefg';

		$mockSession = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\Session');
		$mockSession->expects($this->any())->method('hasIdentifier')->will($this->returnValue(FALSE));
		$mockBackend = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface');
		$mockBackend->expects($this->any())->method('getObjectDataByIdentifier')->will($this->returnValue(FALSE));

		$persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$persistenceManager->injectPersistenceSession($mockSession);
		$persistenceManager->injectBackend($mockBackend);

		$persistenceManager->registerNewObject($mockObject);
		$persistenceManager->clearState();

		$object = $persistenceManager->getObjectByIdentifier('abcdefg');
		$this->assertNull($object);
	}

	/**
	 * @test
	 */
	public function tearDownWithBackendNotSupportingTearDownDoesNothing() {
		$mockBackend = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface');
		$mockBackend->expects($this->never())->method('tearDown');

		$persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$persistenceManager->injectBackend($mockBackend);

		$persistenceManager->tearDown();
	}

	/**
	 * @test
	 */
	public function tearDownWithBackendSupportingTearDownDelegatesCallToBackend() {
		$methods = array_merge(get_class_methods('TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface'), array('tearDown'));
		$mockBackend = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface', $methods);
		$mockBackend->expects($this->once())->method('tearDown');

		$persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$persistenceManager->injectBackend($mockBackend);

		$persistenceManager->tearDown();
	}

	/**
	 * @test
	 *
	 * This test and the related Fixtures TxDomainModelTestEntity and
	 * TxDomainRepositoryTestEntityRepository can be removed if we do not need to support
	 * underscore class names instead of namespaced class names
	 */
	public function persistAllAddsReconstitutedObjectFromSessionToBackendsAggregateRootObjects() {
		$className = uniqid('BazFixture');
		eval ('
			class Foo_Bar_Domain_Model_' . $className . ' extends \\TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {}
		');
		eval ('
			class Foo_Bar_Domain_Repository_' . $className . 'Repository {}
		');
		$persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$aggregateRootObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$fullClassName = 'Foo_Bar_Domain_Model_' . $className;
		$entity1 = new $fullClassName();
		$aggregateRootObjects->attach($entity1);
		$mockBackend = $this->getMock($this->buildAccessibleProxy('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend'), array('commit', 'setAggregateRootObjects', 'setDeletedEntities'), array(), '', FALSE);
		$mockBackend->expects($this->once())
			->method('setAggregateRootObjects')
			->with($this->equalTo($aggregateRootObjects));
		$persistenceManager->injectBackend($mockBackend);
		$persistenceManager->add($entity1);
		$persistenceManager->persistAll();
	}

	/**
	 * @test
	 */
	public function persistAllAddsNamespacedReconstitutedObjectFromSessionToBackendsAggregateRootObjects() {
		$className = uniqid('BazFixture');
		eval ('
			namespace Foo\\Bar\\Domain\\Model;
			class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {}
		');
		eval ('
			namespace Foo\\Bar\\Domain\\Repository;
			class  ' . $className . 'Repository {}
		');
		$persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
		$aggregateRootObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$classNameWithNamespace = 'Foo\\Bar\\Domain\\Model\\' . $className;
		$entity1 = new $classNameWithNamespace();
		$aggregateRootObjects->attach($entity1);
		$mockBackend = $this->getMock($this->buildAccessibleProxy('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend'), array('commit', 'setAggregateRootObjects', 'setDeletedEntities'), array(), '', FALSE);
		$mockBackend->expects($this->once())
			->method('setAggregateRootObjects')
			->with($this->equalTo($aggregateRootObjects));
		$persistenceManager->injectBackend($mockBackend);
		$persistenceManager->add($entity1);
		$persistenceManager->persistAll();
	}

}
?>