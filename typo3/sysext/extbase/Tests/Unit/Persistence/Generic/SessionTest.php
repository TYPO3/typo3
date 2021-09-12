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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SessionTest extends UnitTestCase
{
    protected function createContainer(): Container
    {
        $psrContainer = $this->getMockBuilder(ContainerInterface::class)->onlyMethods(['has', 'get'])->getMock();
        $psrContainer->method('has')->willReturn(false);
        return new Container($psrContainer);
    }

    /**
     * @test
     */
    public function objectRegisteredWithRegisterReconstitutedEntityCanBeRetrievedWithGetReconstitutedEntities(): void
    {
        $someObject = new \ArrayObject([]);
        $session = new Session($this->createContainer());
        $session->registerReconstitutedEntity($someObject);

        $ReconstitutedEntities = $session->getReconstitutedEntities();
        self::assertTrue($ReconstitutedEntities->contains($someObject));
    }

    /**
     * @test
     */
    public function unregisterReconstitutedEntityRemovesObjectFromSession(): void
    {
        $someObject = new \ArrayObject([]);
        $session = new Session($this->createContainer());
        $session->registerObject($someObject, 'fakeUuid');
        $session->registerReconstitutedEntity($someObject);
        $session->unregisterReconstitutedEntity($someObject);

        $ReconstitutedEntities = $session->getReconstitutedEntities();
        self::assertFalse($ReconstitutedEntities->contains($someObject));
    }

    /**
     * @test
     */
    public function hasObjectReturnsTrueForRegisteredObject(): void
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $session = new Session($this->createContainer());
        $session->registerObject($object1, 12345);

        self::assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        self::assertFalse($session->hasObject($object2), 'Session claims it does have unregistered object.');
    }

    /**
     * @test
     */
    public function hasIdentifierReturnsTrueForRegisteredObject(): void
    {
        $session = new Session($this->createContainer());
        $session->registerObject(new \stdClass(), 12345);

        self::assertTrue($session->hasIdentifier('12345', 'stdClass'), 'Session claims it does not have registered object.');
        self::assertFalse($session->hasIdentifier('67890', 'stdClass'), 'Session claims it does have unregistered object.');
    }

    /**
     * @test
     */
    public function getIdentifierByObjectReturnsRegisteredUUIDForObject(): void
    {
        $object = new \stdClass();
        $session = new Session($this->createContainer());
        $session->registerObject($object, 12345);

        self::assertEquals(12345, $session->getIdentifierByObject($object), 'Did not get UUID registered for object.');
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsRegisteredObjectForUUID(): void
    {
        $object = new \stdClass();
        $session = new Session($this->createContainer());
        $session->registerObject($object, 12345);

        self::assertSame($session->getObjectByIdentifier('12345', 'stdClass'), $object, 'Did not get object registered for UUID.');
    }

    /**
     * @test
     */
    public function unregisterObjectRemovesRegisteredObject(): void
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $session = new Session($this->createContainer());
        $session->registerObject($object1, 12345);
        $session->registerObject($object2, 67890);

        self::assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        self::assertTrue($session->hasIdentifier('12345', 'stdClass'), 'Session claims it does not have registered object.');
        self::assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        self::assertTrue($session->hasIdentifier('67890', 'stdClass'), 'Session claims it does not have registered object.');

        $session->unregisterObject($object1);

        self::assertFalse($session->hasObject($object1), 'Session claims it does have unregistered object.');
        self::assertFalse($session->hasIdentifier('12345', 'stdClass'), 'Session claims it does not have registered object.');
        self::assertTrue($session->hasObject($object2), 'Session claims it does not have registered object.');
        self::assertTrue($session->hasIdentifier('67890', 'stdClass'), 'Session claims it does not have registered object.');
    }

    /**
     * @test
     */
    public function newSessionIsEmpty(): void
    {
        $persistenceSession = new Session($this->createContainer());
        $reconstitutedObjects = $persistenceSession->getReconstitutedEntities();
        self::assertCount(0, $reconstitutedObjects, 'The reconstituted objects storage was not empty.');
    }

    /**
     * @test
     */
    public function objectCanBeRegisteredAsReconstituted(): void
    {
        $persistenceSession = new Session($this->createContainer());
        $entity = $this->createMock(AbstractEntity::class);
        $persistenceSession->registerReconstitutedEntity($entity);
        $reconstitutedObjects = $persistenceSession->getReconstitutedEntities();
        self::assertTrue($reconstitutedObjects->contains($entity), 'The object was not registered as reconstituted.');
    }

    /**
     * @test
     */
    public function objectCanBeUnregisteredAsReconstituted(): void
    {
        $persistenceSession = new Session($this->createContainer());
        $entity = $this->createMock(AbstractEntity::class);
        $persistenceSession->registerReconstitutedEntity($entity);
        $persistenceSession->unregisterReconstitutedEntity($entity);
        $reconstitutedObjects = $persistenceSession->getReconstitutedEntities();
        self::assertCount(0, $reconstitutedObjects, 'The reconstituted objects storage was not empty.');
    }
}
