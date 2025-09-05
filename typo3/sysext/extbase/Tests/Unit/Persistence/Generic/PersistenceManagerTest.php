<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Extbase\Tests\Unit\Persistence\Fixture\Model\Entity2;
use TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Fixtures\TearDownableBackendInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PersistenceManagerTest extends UnitTestCase
{
    #[Test]
    public function persistAllPassesAddedObjectsToBackend(): void
    {
        $entity2 = new Entity2();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($entity2);
        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects($this->once())->method('setAggregateRootObjects')->with($objectStorage);

        $manager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $mockBackend,
            $this->createMock(Session::class)
        );
        $manager->add($entity2);

        $manager->persistAll();
    }

    #[Test]
    public function persistAllPassesRemovedObjectsToBackend(): void
    {
        $entity2 = new Entity2();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($entity2);
        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects($this->once())->method('setDeletedEntities')->with($objectStorage);

        $manager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $mockBackend,
            $this->createMock(Session::class)
        );
        $manager->remove($entity2);

        $manager->persistAll();
    }

    #[Test]
    public function getIdentifierByObjectReturnsIdentifierFromBackend(): void
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects($this->once())->method('getIdentifierByObject')->with($object)->willReturn($fakeUuid);

        $manager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $mockBackend,
            $this->createMock(Session::class)
        );

        self::assertEquals($manager->getIdentifierByObject($object), $fakeUuid);
    }

    #[Test]
    public function getObjectByIdentifierReturnsObjectFromSessionIfAvailable(): void
    {
        $fakeUuid = 'fakeUuid';
        $object = new \stdClass();

        $mockSession = $this->createMock(Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid, \stdClass::class)->willReturn(true);
        $mockSession->expects($this->once())->method('getObjectByIdentifier')->with($fakeUuid)->willReturn($object);

        $manager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $this->createMock(BackendInterface::class),
            $mockSession
        );

        self::assertEquals($manager->getObjectByIdentifier($fakeUuid, $object::class), $object);
    }

    #[Test]
    public function getObjectByIdentifierReturnsObjectFromPersistenceIfAvailable(): void
    {
        $fakeUuid = '42';
        $object = new \stdClass();
        $fakeEntityType = get_class($object);

        $mockSession = $this->createMock(Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with($fakeUuid)->willReturn(false);

        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects($this->once())->method('getObjectByIdentifier')->with(
            $fakeUuid,
            $fakeEntityType
        )->willReturn($object);

        $manager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $mockBackend,
            $mockSession
        );

        self::assertEquals($manager->getObjectByIdentifier($fakeUuid, $fakeEntityType), $object);
    }

    #[Test]
    public function getObjectByIdentifierReturnsNullForUnknownObject(): void
    {
        $fakeUuid = '42';
        $fakeEntityType = 'foobar';

        $mockSession = $this->createMock(Session::class);
        $mockSession->expects($this->once())->method('hasIdentifier')->with(
            $fakeUuid,
            $fakeEntityType
        )->willReturn(false);

        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects($this->once())->method('getObjectByIdentifier')->with(
            $fakeUuid,
            $fakeEntityType
        )->willReturn(null);

        $manager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $mockBackend,
            $mockSession
        );

        self::assertNull($manager->getObjectByIdentifier($fakeUuid, $fakeEntityType));
    }

    #[Test]
    public function addActuallyAddsAnObjectToTheInternalObjectsArray(): void
    {
        $someObject = new \stdClass();
        $backend = $this->createMock(BackendInterface::class);
        $persistenceManager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $backend,
            $this->createMock(Session::class)
        );
        $persistenceManager->add($someObject);

        $expectedAddedObjects = new ObjectStorage();
        $expectedAddedObjects->attach($someObject);

        // this is the actual assertion
        $backend->expects($this->atLeastOnce())->method('setAggregateRootObjects')->with($expectedAddedObjects);

        $backend->expects($this->atLeastOnce())->method('setChangedEntities')->with(self::anything());
        $backend->expects($this->atLeastOnce())->method('setDeletedEntities')->with(self::anything());
        $backend->expects($this->atLeastOnce())->method('commit');
        $persistenceManager->persistAll();
    }

    #[Test]
    public function removeActuallyRemovesAnObjectFromTheInternalObjectsArray(): void
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $object3 = new \stdClass();

        $backend = $this->createMock(BackendInterface::class);
        $persistenceManager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $backend,
            $this->createMock(Session::class)
        );
        $persistenceManager->add($object1);
        $persistenceManager->add($object2);
        $persistenceManager->add($object3);

        $persistenceManager->remove($object2);

        $expectedAddedObjects = new ObjectStorage();
        $expectedAddedObjects->attach($object1);
        $expectedAddedObjects->attach($object2);
        $expectedAddedObjects->attach($object3);
        $expectedAddedObjects->detach($object2);

        // this is the actual assertion
        $backend->expects($this->atLeastOnce())->method('setAggregateRootObjects')->with($expectedAddedObjects);

        $backend->expects($this->atLeastOnce())->method('setChangedEntities')->with(self::anything());
        $backend->expects($this->atLeastOnce())->method('setDeletedEntities')->with(self::anything());
        $backend->expects($this->atLeastOnce())->method('commit');

        $persistenceManager->persistAll();
    }

    #[Test]
    public function removeRemovesTheRightObjectEvenIfItHasBeenModifiedSinceItsAddition(): void
    {
        $object1 = new \ArrayObject(['val' => '1']);
        $object2 = new \ArrayObject(['val' => '2']);
        $object3 = new \ArrayObject(['val' => '3']);

        $backend = $this->createMock(BackendInterface::class);
        $persistenceManager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $backend,
            $this->createMock(Session::class)
        );
        $persistenceManager->add($object1);
        $persistenceManager->add($object2);
        $persistenceManager->add($object3);

        $object2['foo'] = 'bar';
        $object3['val'] = '2';

        $persistenceManager->remove($object2);

        // replay the actual sequence of actions, that makes the objectStorages comparable
        $expectedAddedObjects = new ObjectStorage();
        $expectedAddedObjects->attach($object1);
        $expectedAddedObjects->attach($object2);
        $expectedAddedObjects->attach($object3);
        $expectedAddedObjects->detach($object2);

        // this is the actual assertion
        $backend->expects($this->atLeastOnce())->method('setAggregateRootObjects')->with($expectedAddedObjects);

        $backend->expects($this->atLeastOnce())->method('setChangedEntities')->with(self::anything());
        $backend->expects($this->atLeastOnce())->method('setDeletedEntities')->with(self::anything());
        $backend->expects($this->atLeastOnce())->method('commit');
        $persistenceManager->persistAll();
    }

    /**
     * Make sure we remember the objects that are not currently add()ed
     * but might be in persistent storage.
     */
    #[Test]
    public function removeRetainsObjectForObjectsNotInCurrentSession(): void
    {
        $object = new \ArrayObject(['val' => '1']);
        $backend = $this->createMock(BackendInterface::class);
        $persistenceManager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $backend,
            $this->createMock(Session::class)
        );
        $persistenceManager->remove($object);

        $expectedDeletedObjects = new ObjectStorage();
        $expectedDeletedObjects->attach($object);
        $backend->expects($this->atLeastOnce())->method('setAggregateRootObjects');
        $backend->expects($this->atLeastOnce())->method('setChangedEntities')->with(self::anything());

        // this is the actual assertion
        $backend->expects($this->atLeastOnce())->method('setDeletedEntities')->with($expectedDeletedObjects);

        $backend->expects($this->atLeastOnce())->method('commit');
        $persistenceManager->persistAll();
    }

    #[Test]
    public function updateSchedulesAnObjectForPersistence(): void
    {
        $className = StringUtility::getUniqueId('BazFixture');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Model;
			class ' . $className . ' extends \\' . AbstractEntity::class . ' {
				protected ?int $uid = 42;
			}
		');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Repository;
			class  ' . $className . 'Repository extends \\TYPO3\\CMS\\Extbase\\Persistence\\Repository {}
		');
        $classNameWithNamespace = __NAMESPACE__ . '\\Domain\\Model\\' . $className;
        /** @var class-string<RepositoryInterface> $repositoryClassNameWithNamespace */
        $repositoryClassNameWithNamespace = __NAMESPACE__ . '\\Domain\\Repository\\' . $className . 'Repository';

        $psrContainer = $this->getMockBuilder(ContainerInterface::class)
            ->onlyMethods(['has', 'get'])
            ->disableOriginalConstructor()
            ->getMock();
        $psrContainer->method('has')->willReturn(false);
        $session = new Session();
        $changedEntities = new ObjectStorage();
        $entity1 = new $classNameWithNamespace();

        $repository = $this->getAccessibleMock($repositoryClassNameWithNamespace, null);
        $repository->_set('objectType', get_class($entity1));

        $mockBackend = $this->getMockBuilder(Backend::class)
            ->onlyMethods(['commit', 'setChangedEntities'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockBackend->expects($this->once())
            ->method('setChangedEntities')
            ->with(self::equalTo($changedEntities));

        $persistenceManager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $mockBackend,
            $session
        );

        $repository->_set('persistenceManager', $persistenceManager);
        $session->registerObject($entity1, '42');
        $changedEntities->attach($entity1);
        $repository->update($entity1);
        $persistenceManager->persistAll();
    }

    #[Test]
    public function tearDownWithBackendSupportingTearDownDelegatesCallToBackend(): void
    {
        $mockBackend = $this->createMock(TearDownableBackendInterface::class);
        $mockBackend->expects($this->once())->method('tearDown');
        $persistenceManager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $mockBackend,
            $this->createMock(Session::class)
        );
        $persistenceManager->tearDown();
    }

    #[Test]
    public function persistAllAddsNamespacedReconstitutedObjectFromSessionToBackendsAggregateRootObjects(): void
    {
        $className = StringUtility::getUniqueId('BazFixture');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Model;
			class ' . $className . ' extends \\' . AbstractEntity::class . ' {}
		');
        eval('
			namespace ' . __NAMESPACE__ . '\\Domain\\Repository;
			class  ' . $className . 'Repository {}
		');
        $aggregateRootObjects = new ObjectStorage();
        $classNameWithNamespace = __NAMESPACE__ . '\\Domain\\Model\\' . $className;
        $entity1 = new $classNameWithNamespace();
        $aggregateRootObjects->attach($entity1);

        $mockBackend = $this->getMockBuilder(Backend::class)
            ->onlyMethods(['commit', 'setAggregateRootObjects', 'setDeletedEntities'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockBackend->expects($this->once())
            ->method('setAggregateRootObjects')
            ->with(self::equalTo($aggregateRootObjects));

        $persistenceManager = new PersistenceManager(
            $this->createMock(QueryFactoryInterface::class),
            $mockBackend,
            $this->createMock(Session::class)
        );

        $persistenceManager->add($entity1);
        $persistenceManager->persistAll();
    }
}
