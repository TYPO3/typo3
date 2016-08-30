<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case
 */
class PersistenceManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    protected function setUp()
    {
        $this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
    }

    /**
     * @test
     */
    public function persistAllPassesAddedObjectsToBackend()
    {
        $entity2 = new \TYPO3\CMS\Extbase\Tests\Unit\Persistence\Fixture\Model\Entity2();
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorage->attach($entity2);
        $mockBackend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class);
        $mockBackend->expects($this->once())->method('setAggregateRootObjects')->with($objectStorage);

        $manager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $manager->_set('backend', $mockBackend);
        $manager->add($entity2);

        $manager->persistAll();
    }

    /**
     * @test
     */
    public function persistAllPassesRemovedObjectsToBackend()
    {
        $entity2 = new \TYPO3\CMS\Extbase\Tests\Unit\Persistence\Fixture\Model\Entity2();
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorage->attach($entity2);
        $mockBackend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class);
        $mockBackend->expects($this->once())->method('setDeletedEntities')->with($objectStorage);

        $manager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $manager->_set('backend', $mockBackend);
        $manager->remove($entity2);

        $manager->persistAll();
    }

    /**
     * @test
     */
    public function getIdentifierByObjectReturnsIdentifierFromBackend()
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockBackend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class);
        $mockBackend->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue($fakeUuid));

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $manager */
        $manager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $manager->_set('backend', $mockBackend);

        $this->assertEquals($manager->getIdentifierByObject($object), $fakeUuid);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsObjectFromSessionIfAvailable()
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockSession = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(true));
        $mockSession->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid)->will($this->returnValue($object));

        $manager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $manager->_set('persistenceSession', $mockSession);

        $this->assertEquals($manager->getObjectByIdentifier($fakeUuid), $object);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsObjectFromPersistenceIfAvailable()
    {
        $fakeUuid = '42';
        $object = new \stdClass();
        $fakeEntityType = get_class($object);

        $mockSession = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(false));

        $mockBackend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class);
        $mockBackend->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid, $fakeEntityType)->will($this->returnValue($object));

        $manager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $manager->_set('persistenceSession', $mockSession);
        $manager->_set('backend', $mockBackend);

        $this->assertEquals($manager->getObjectByIdentifier($fakeUuid, $fakeEntityType), $object);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsNullForUnknownObject()
    {
        $fakeUuid = '42';
        $fakeEntityType = 'foobar';

        $mockSession = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid, $fakeEntityType)->will($this->returnValue(false));

        $mockBackend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class);
        $mockBackend->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid, $fakeEntityType)->will($this->returnValue(null));

        $manager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $manager->_set('persistenceSession', $mockSession);
        $manager->_set('backend', $mockBackend);

        $this->assertNull($manager->getObjectByIdentifier($fakeUuid, $fakeEntityType));
    }

    /**
     * @test
     */
    public function addActuallyAddsAnObjectToTheInternalObjectsArray()
    {
        $someObject = new \stdClass();
        $persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
        $persistenceManager->add($someObject);

        $this->assertAttributeContains($someObject, 'addedObjects', $persistenceManager);
    }

    /**
     * @test
     */
    public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray()
    {
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
    public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition()
    {
        $object1 = new \ArrayObject(['val' => '1']);
        $object2 = new \ArrayObject(['val' => '2']);
        $object3 = new \ArrayObject(['val' => '3']);

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
    public function removeRetainsObjectForObjectsNotInCurrentSession()
    {
        $object = new \ArrayObject(['val' => '1']);
        $persistenceManager = new \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager();
        $persistenceManager->remove($object);

        $this->assertAttributeContains($object, 'removedObjects', $persistenceManager);
    }

    /**
     * @test
     */
    public function updateSchedulesAnObjectForPersistence()
    {
        $className = $this->getUniqueId('BazFixture');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Model;
			class ' . $className . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {
				protected $uid = 42;
			}
		');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Repository;
			class  ' . $className . 'Repository extends \\TYPO3\\CMS\\Extbase\\Persistence\\Repository {}
		');
        $classNameWithNamespace = __NAMESPACE__ . '\\Domain\\Model\\' . $className;
        $repositorClassNameWithNamespace = __NAMESPACE__ . '\\Domain\\Repository\\' . $className . 'Repository';

        $persistenceManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $session = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $changedEntities = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $entity1 = new $classNameWithNamespace();
        $repository = $this->getAccessibleMock($repositorClassNameWithNamespace, ['dummy'], [$this->mockObjectManager]);
        $repository->_set('objectType', get_class($entity1));
        $mockBackend = $this->getMock($this->buildAccessibleProxy(\TYPO3\CMS\Extbase\Persistence\Generic\Backend::class), ['commit', 'setChangedEntities'], [], '', false);
        $mockBackend->expects($this->once())
            ->method('setChangedEntities')
            ->with($this->equalTo($changedEntities));

        $persistenceManager->_set('backend', $mockBackend);
        $persistenceManager->_set('persistenceSession', $session);

        $repository->_set('persistenceManager', $persistenceManager);
        $session->registerObject($entity1, 42);
        $changedEntities->attach($entity1);
        $repository->update($entity1);
        $persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function clearStateForgetsAboutNewObjects()
    {
        $mockObject = $this->getMock('TYPO3\CMS\Extbase\Persistence\Aspect\PersistenceMagicInterface');
        $mockObject->Persistence_Object_Identifier = 'abcdefg';

        $mockSession = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Session::class);
        $mockSession->expects($this->any())->method('hasIdentifier')->will($this->returnValue(false));
        $mockBackend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class);
        $mockBackend->expects($this->any())->method('getObjectDataByIdentifier')->will($this->returnValue(false));

        $persistenceManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $persistenceManager->_set('persistenceSession', $mockSession);
        $persistenceManager->_set('backend', $mockBackend);

        $persistenceManager->registerNewObject($mockObject);
        $persistenceManager->clearState();

        $object = $persistenceManager->getObjectByIdentifier('abcdefg');
        $this->assertNull($object);
    }

    /**
     * @test
     */
    public function tearDownWithBackendNotSupportingTearDownDoesNothing()
    {
        $mockBackend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class);
        $mockBackend->expects($this->never())->method('tearDown');

        $persistenceManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $persistenceManager->_set('backend', $mockBackend);

        $persistenceManager->tearDown();
    }

    /**
     * @test
     */
    public function tearDownWithBackendSupportingTearDownDelegatesCallToBackend()
    {
        $methods = array_merge(get_class_methods(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class), ['tearDown']);
        $mockBackend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class, $methods);
        $mockBackend->expects($this->once())->method('tearDown');

        $persistenceManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $persistenceManager->_set('backend', $mockBackend);

        $persistenceManager->tearDown();
    }

    /**
     * @test
     *
     * This test and the related Fixtures TxDomainModelTestEntity and
     * TxDomainRepositoryTestEntityRepository can be removed if we do not need to support
     * underscore class names instead of namespaced class names
     */
    public function persistAllAddsReconstitutedObjectFromSessionToBackendsAggregateRootObjects()
    {
        $className = $this->getUniqueId('BazFixture');
        eval('
			class Foo_Bar_Domain_Model_' . $className . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {}
		');
        eval('
			class Foo_Bar_Domain_Repository_' . $className . 'Repository {}
		');
        $persistenceManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $aggregateRootObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $fullClassName = 'Foo_Bar_Domain_Model_' . $className;
        $entity1 = new $fullClassName();
        $aggregateRootObjects->attach($entity1);
        $mockBackend = $this->getMock($this->buildAccessibleProxy(\TYPO3\CMS\Extbase\Persistence\Generic\Backend::class), ['commit', 'setAggregateRootObjects', 'setDeletedEntities'], [], '', false);
        $mockBackend->expects($this->once())
            ->method('setAggregateRootObjects')
            ->with($this->equalTo($aggregateRootObjects));
        $persistenceManager->_set('backend', $mockBackend);
        $persistenceManager->add($entity1);
        $persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function persistAllAddsNamespacedReconstitutedObjectFromSessionToBackendsAggregateRootObjects()
    {
        $className = $this->getUniqueId('BazFixture');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Model;
			class ' . $className . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {}
		');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Repository;
			class  ' . $className . 'Repository {}
		');
        $persistenceManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['dummy']);
        $aggregateRootObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $classNameWithNamespace = __NAMESPACE__ . '\\Domain\\Model\\' . $className;
        $entity1 = new $classNameWithNamespace();
        $aggregateRootObjects->attach($entity1);
        $mockBackend = $this->getMock($this->buildAccessibleProxy(\TYPO3\CMS\Extbase\Persistence\Generic\Backend::class), ['commit', 'setAggregateRootObjects', 'setDeletedEntities'], [], '', false);
        $mockBackend->expects($this->once())
            ->method('setAggregateRootObjects')
            ->with($this->equalTo($aggregateRootObjects));
        $persistenceManager->_set('backend', $mockBackend);
        $persistenceManager->add($entity1);
        $persistenceManager->persistAll();
    }
}
