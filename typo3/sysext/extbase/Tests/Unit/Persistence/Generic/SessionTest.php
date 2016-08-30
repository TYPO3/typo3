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
class SessionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function objectRegisteredWithRegisterReconstitutedEntityCanBeRetrievedWithGetReconstitutedEntities()
    {
        $someObject = new \ArrayObject([]);
        $session = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $session->registerReconstitutedEntity($someObject, ['identifier' => 'fakeUuid']);

        $ReconstitutedEntities = $session->getReconstitutedEntities();
        $this->assertTrue($ReconstitutedEntities->contains($someObject));
    }

    /**
     * @test
     */
    public function unregisterReconstitutedEntityRemovesObjectFromSession()
    {
        $someObject = new \ArrayObject([]);
        $session = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $session->registerObject($someObject, 'fakeUuid');
        $session->registerReconstitutedEntity($someObject, ['identifier' => 'fakeUuid']);
        $session->unregisterReconstitutedEntity($someObject);

        $ReconstitutedEntities = $session->getReconstitutedEntities();
        $this->assertFalse($ReconstitutedEntities->contains($someObject));
    }

    /**
     * @test
     */
    public function hasObjectReturnsTrueForRegisteredObject()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $session = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $session->registerObject($object1, 12345);

        $this->assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        $this->assertFalse($session->hasObject($object2), 'Session claims it does have unregistered object.');
    }

    /**
     * @test
     */
    public function hasIdentifierReturnsTrueForRegisteredObject()
    {
        $session = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $session->registerObject(new \stdClass(), 12345);

        $this->assertTrue($session->hasIdentifier('12345', 'stdClass'), 'Session claims it does not have registered object.');
        $this->assertFalse($session->hasIdentifier('67890', 'stdClass'), 'Session claims it does have unregistered object.');
    }

    /**
     * @test
     */
    public function getIdentifierByObjectReturnsRegisteredUUIDForObject()
    {
        $object = new \stdClass();
        $session = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $session->registerObject($object, 12345);

        $this->assertEquals($session->getIdentifierByObject($object), 12345, 'Did not get UUID registered for object.');
    }

    /**
     * @test
     */
    public function getObjectByIdentifierReturnsRegisteredObjectForUUID()
    {
        $object = new \stdClass();
        $session = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $session->registerObject($object, 12345);

        $this->assertSame($session->getObjectByIdentifier('12345', 'stdClass'), $object, 'Did not get object registered for UUID.');
    }

    /**
     * @test
     */
    public function unregisterObjectRemovesRegisteredObject()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $session = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $session->registerObject($object1, 12345);
        $session->registerObject($object2, 67890);

        $this->assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        $this->assertTrue($session->hasIdentifier('12345', 'stdClass'), 'Session claims it does not have registered object.');
        $this->assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        $this->assertTrue($session->hasIdentifier('67890', 'stdClass'), 'Session claims it does not have registered object.');

        $session->unregisterObject($object1);

        $this->assertFalse($session->hasObject($object1), 'Session claims it does have unregistered object.');
        $this->assertFalse($session->hasIdentifier('12345', 'stdClass'), 'Session claims it does not have registered object.');
        $this->assertTrue($session->hasObject($object2), 'Session claims it does not have registered object.');
        $this->assertTrue($session->hasIdentifier('67890', 'stdClass'), 'Session claims it does not have registered object.');
    }

    /**
     * @test
     */
    public function newSessionIsEmpty()
    {
        $persistenceSession = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $reconstitutedObjects = $persistenceSession->getReconstitutedEntities();
        $this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
    }

    /**
     * @test
     */
    public function objectCanBeRegisteredAsReconstituted()
    {
        $persistenceSession = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $entity = $this->getMock(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class);
        $persistenceSession->registerReconstitutedEntity($entity);
        $reconstitutedObjects = $persistenceSession->getReconstitutedEntities();
        $this->assertTrue($reconstitutedObjects->contains($entity), 'The object was not registered as reconstituted.');
    }

    /**
     * @test
     */
    public function objectCanBeUnregisteredAsReconstituted()
    {
        $persistenceSession = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
        $entity = $this->getMock(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class);
        $persistenceSession->registerReconstitutedEntity($entity);
        $persistenceSession->unregisterReconstitutedEntity($entity);
        $reconstitutedObjects = $persistenceSession->getReconstitutedEntities();
        $this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
    }
}
