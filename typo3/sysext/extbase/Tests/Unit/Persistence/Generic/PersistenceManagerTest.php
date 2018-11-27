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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Extbase\Tests\Unit\Persistence\Fixture\Model\Entity2;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PersistenceManagerTest extends UnitTestCase
{
    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
    }

    /**
     * @test
     */
    public function persistAllPassesAddedObjectsToBackend(): void
    {
        $entity2 = new Entity2();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($entity2);
        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects($this->once())->method('setAggregateRootObjects')->with($objectStorage);

        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $manager */
        $manager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
        $manager->_set('backend', $mockBackend);
        $manager->add($entity2);

        $manager->persistAll();
    }

    /**
     * @test
     */
    public function persistAllPassesRemovedObjectsToBackend(): void
    {
        $entity2 = new Entity2();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($entity2);
        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects($this->once())->method('setDeletedEntities')->with($objectStorage);

        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $manager */
        $manager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
        $manager->_set('backend', $mockBackend);
        $manager->remove($entity2);

        $manager->persistAll();
    }

    /**
     * @test
     */
    public function getIdentifierByObjectReturnsIdentifierFromBackend(): void
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue($fakeUuid));

        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $manager */
        $manager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
        $manager->_set('backend', $mockBackend);

        $this->assertEquals($manager->getIdentifierByObject($object), $fakeUuid);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsObjectFromSessionIfAvailable(): void
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockSession = $this->createMock(Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(true));
        $mockSession->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid)->will($this->returnValue($object));

        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $manager */
        $manager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
        $manager->_set('persistenceSession', $mockSession);

        $this->assertEquals($manager->getObjectByIdentifier($fakeUuid), $object);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsObjectFromPersistenceIfAvailable(): void
    {
        $fakeUuid = '42';
        $object = new \stdClass();
        $fakeEntityType = get_class($object);

        $mockSession = $this->createMock(Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->will($this->returnValue(false));

        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects($this->once())->method('getObjectByIdentifier')->with(
            $fakeUuid,
            $fakeEntityType
        )->will($this->returnValue($object));

        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $manager */
        $manager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
        $manager->_set('persistenceSession', $mockSession);
        $manager->_set('backend', $mockBackend);

        $this->assertEquals($manager->getObjectByIdentifier($fakeUuid, $fakeEntityType), $object);
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsNullForUnknownObject(): void
    {
        $fakeUuid = '42';
        $fakeEntityType = 'foobar';

        $mockSession = $this->createMock(Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with(
            $fakeUuid,
            $fakeEntityType
        )->will($this->returnValue(false));

        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects($this->once())->method('getObjectByIdentifier')->with(
            $fakeUuid,
            $fakeEntityType
        )->will($this->returnValue(null));

        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $manager */
        $manager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
        $manager->_set('persistenceSession', $mockSession);
        $manager->_set('backend', $mockBackend);

        $this->assertNull($manager->getObjectByIdentifier($fakeUuid, $fakeEntityType));
    }

    /**
     * @test
     */
    public function addActuallyAddsAnObjectToTheInternalObjectsArray(): void
    {
        $someObject = new \stdClass();
        $persistenceManager = new PersistenceManager();
        $persistenceManager->add($someObject);

        $this->assertAttributeContains($someObject, 'addedObjects', $persistenceManager);
    }

    /**
     * @test
     */
    public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray(): void
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $object3 = new \stdClass();

        $persistenceManager = new PersistenceManager();
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
    public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition(): void
    {
        $object1 = new \ArrayObject(['val' => '1']);
        $object2 = new \ArrayObject(['val' => '2']);
        $object3 = new \ArrayObject(['val' => '3']);

        $persistenceManager = new PersistenceManager();
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
    public function removeRetainsObjectForObjectsNotInCurrentSession(): void
    {
        $object = new \ArrayObject(['val' => '1']);
        $persistenceManager = new PersistenceManager();
        $persistenceManager->remove($object);

        $this->assertAttributeContains($object, 'removedObjects', $persistenceManager);
    }

    /**
     * @test
     */
    public function updateSchedulesAnObjectForPersistence(): void
    {
        $className = $this->getUniqueId('BazFixture');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Model;
			class ' . $className . ' extends \\' . AbstractEntity::class . ' {
				protected $uid = 42;
			}
		');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Repository;
			class  ' . $className . 'Repository extends \\TYPO3\\CMS\\Extbase\\Persistence\\Repository {}
		');
        $classNameWithNamespace = __NAMESPACE__ . '\\Domain\\Model\\' . $className;
        $repositorClassNameWithNamespace = __NAMESPACE__ . '\\Domain\\Repository\\' . $className . 'Repository';

        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $persistenceManager */
        $persistenceManager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
        $session = new Session(new Container());
        $changedEntities = new ObjectStorage();
        $entity1 = new $classNameWithNamespace();
        /** @var RepositoryInterface|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $repository */
        $repository = $this->getAccessibleMock($repositorClassNameWithNamespace, ['dummy'], [$this->mockObjectManager]);
        $repository->_set('objectType', get_class($entity1));
        $mockBackend = $this->getMockBuilder($this->buildAccessibleProxy(Backend::class))
            ->setMethods(['commit', 'setChangedEntities'])
            ->disableOriginalConstructor()
            ->getMock();
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
    public function clearStateForgetsAboutNewObjects(): void
    {
        /** @var PersistenceManagerInterface|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $mockObject */
        $mockObject = $this->createMock(PersistenceManagerInterface::class);
        $mockObject->Persistence_Object_Identifier = 'abcdefg';

        $mockSession = $this->createMock(Session::class);
        $mockSession->expects($this->any())->method('hasIdentifier')->will($this->returnValue(false));
        $mockBackend = $this->createMock(BackendInterface::class);

        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $persistenceManager */
        $persistenceManager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
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
    public function tearDownWithBackendSupportingTearDownDelegatesCallToBackend(): void
    {
        $methods = array_merge(
            get_class_methods(BackendInterface::class),
            ['tearDown']
        );
        $mockBackend = $this->getMockBuilder(BackendInterface::class)
            ->setMethods($methods)
            ->getMock();
        $mockBackend->expects($this->once())->method('tearDown');

        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $persistenceManager */
        $persistenceManager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
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
    public function persistAllAddsReconstitutedObjectFromSessionToBackendsAggregateRootObjects(): void
    {
        $className = $this->getUniqueId('BazFixture');
        eval('
			class Foo_Bar_Domain_Model_' . $className . ' extends \\' . AbstractEntity::class . ' {}
		');
        eval('
			class Foo_Bar_Domain_Repository_' . $className . 'Repository {}
		');
        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $persistenceManager */
        $persistenceManager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
        $aggregateRootObjects = new ObjectStorage();
        $fullClassName = 'Foo_Bar_Domain_Model_' . $className;
        $entity1 = new $fullClassName();
        $aggregateRootObjects->attach($entity1);
        $mockBackend = $this->getMockBuilder($this->buildAccessibleProxy(Backend::class))
            ->setMethods(['commit', 'setAggregateRootObjects', 'setDeletedEntities'])
            ->disableOriginalConstructor()
            ->getMock();
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
    public function persistAllAddsNamespacedReconstitutedObjectFromSessionToBackendsAggregateRootObjects(): void
    {
        $className = $this->getUniqueId('BazFixture');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Model;
			class ' . $className . ' extends \\' . AbstractEntity::class . ' {}
		');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Repository;
			class  ' . $className . 'Repository {}
		');
        /** @var PersistenceManager|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $persistenceManager */
        $persistenceManager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['dummy']
        );
        $aggregateRootObjects = new ObjectStorage();
        $classNameWithNamespace = __NAMESPACE__ . '\\Domain\\Model\\' . $className;
        $entity1 = new $classNameWithNamespace();
        $aggregateRootObjects->attach($entity1);
        $mockBackend = $this->getMockBuilder($this->buildAccessibleProxy(Backend::class))
            ->setMethods(['commit', 'setAggregateRootObjects', 'setDeletedEntities'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockBackend->expects($this->once())
            ->method('setAggregateRootObjects')
            ->with($this->equalTo($aggregateRootObjects));
        $persistenceManager->_set('backend', $mockBackend);
        $persistenceManager->add($entity1);
        $persistenceManager->persistAll();
    }
}
