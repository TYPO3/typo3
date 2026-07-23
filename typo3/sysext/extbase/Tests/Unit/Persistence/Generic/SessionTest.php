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
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SessionTest extends UnitTestCase
{
    #[Test]
    public function objectRegisteredWithRegisterReconstitutedEntityCanBeRetrievedWithGetReconstitutedEntities(): void
    {
        $someObject = new class extends AbstractDomainObject {};
        $session = new Session();
        $session->registerReconstitutedEntity($someObject);

        $ReconstitutedEntities = $session->getReconstitutedEntities();
        self::assertTrue($ReconstitutedEntities->contains($someObject));
    }

    #[Test]
    public function unregisterReconstitutedEntityRemovesObjectFromSession(): void
    {
        $someObject = new class extends AbstractDomainObject {};
        $session = new Session();
        $session->registerObject($someObject, 'fakeUuid');
        $session->registerReconstitutedEntity($someObject);
        $session->unregisterReconstitutedEntity($someObject);

        $ReconstitutedEntities = $session->getReconstitutedEntities();
        self::assertFalse($ReconstitutedEntities->contains($someObject));
    }

    #[Test]
    public function hasObjectReturnsTrueForRegisteredObject(): void
    {
        $object1 = new class extends AbstractDomainObject {};
        $object2 = new class extends AbstractDomainObject {};
        $session = new Session();
        $session->registerObject($object1, '12345');

        self::assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        self::assertFalse($session->hasObject($object2), 'Session claims it does have unregistered object.');
    }

    #[Test]
    public function hasIdentifierReturnsTrueForRegisteredObject(): void
    {
        $object = new class extends AbstractDomainObject {};
        $session = new Session();
        $session->registerObject($object, '12345');

        self::assertTrue($session->hasIdentifier('12345', $object::class), 'Session claims it does not have registered object.');
        self::assertFalse($session->hasIdentifier('67890', $object::class), 'Session claims it does have unregistered object.');
    }

    #[Test]
    public function getIdentifierByObjectReturnsRegisteredUUIDForObject(): void
    {
        $object = new class extends AbstractDomainObject {};
        $session = new Session();
        $session->registerObject($object, '12345');

        self::assertEquals('12345', $session->getIdentifierByObject($object), 'Did not get UUID registered for object.');
    }

    #[Test]
    public function getObjectByIdentifierReturnsRegisteredObjectForUUID(): void
    {
        $object = new class extends AbstractDomainObject {};
        $session = new Session();
        $session->registerObject($object, '12345');

        self::assertSame($session->getObjectByIdentifier('12345', $object::class), $object, 'Did not get object registered for UUID.');
    }

    #[Test]
    public function unregisterObjectRemovesRegisteredObject(): void
    {
        $object1 = new class extends AbstractDomainObject {};
        $object2 = new class extends AbstractDomainObject {};
        $session = new Session();
        $session->registerObject($object1, '12345');
        $session->registerObject($object2, '67890');

        self::assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        self::assertTrue($session->hasIdentifier('12345', $object1::class), 'Session claims it does not have registered object.');
        self::assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
        self::assertTrue($session->hasIdentifier('67890', $object2::class), 'Session claims it does not have registered object.');

        $session->unregisterObject($object1);

        self::assertFalse($session->hasObject($object1), 'Session claims it does have unregistered object.');
        self::assertFalse($session->hasIdentifier('12345', $object1::class), 'Session claims it does not have registered object.');
        self::assertTrue($session->hasObject($object2), 'Session claims it does not have registered object.');
        self::assertTrue($session->hasIdentifier('67890', $object2::class), 'Session claims it does not have registered object.');
    }

    #[Test]
    public function newSessionIsEmpty(): void
    {
        $persistenceSession = new Session();
        $reconstitutedObjects = $persistenceSession->getReconstitutedEntities();
        self::assertCount(0, $reconstitutedObjects, 'The reconstituted objects storage was not empty.');
    }

    #[Test]
    public function objectCanBeRegisteredAsReconstituted(): void
    {
        $persistenceSession = new Session();
        $entity = self::createStub(AbstractEntity::class);
        $persistenceSession->registerReconstitutedEntity($entity);
        $reconstitutedObjects = $persistenceSession->getReconstitutedEntities();
        self::assertTrue($reconstitutedObjects->contains($entity), 'The object was not registered as reconstituted.');
    }

    #[Test]
    public function objectCanBeUnregisteredAsReconstituted(): void
    {
        $persistenceSession = new Session();
        $entity = self::createStub(AbstractEntity::class);
        $persistenceSession->registerReconstitutedEntity($entity);
        $persistenceSession->unregisterReconstitutedEntity($entity);
        $reconstitutedObjects = $persistenceSession->getReconstitutedEntities();
        self::assertCount(0, $reconstitutedObjects, 'The reconstituted objects storage was not empty.');
    }

    #[Test]
    public function buildIdentifierProducesUniqueIdentifiersForDifferentContentIds(): void
    {
        $languageAspect1 = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON, [0]);
        $languageAspect2 = new LanguageAspect(1, 2, LanguageAspect::OVERLAYS_ON, [0]);

        $session = new Session();
        $identifier1 = $session->buildIdentifier('1', $languageAspect1);
        $identifier2 = $session->buildIdentifier('1', $languageAspect2);

        self::assertNotEquals($identifier1, $identifier2, 'Identifiers should differ for different content IDs.');
    }

    #[Test]
    public function buildIdentifierProducesUniqueIdentifiersForDifferentOverlayTypes(): void
    {
        $languageAspect1 = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON, [0]);
        $languageAspect2 = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED, [0]);

        $session = new Session();
        $identifier1 = $session->buildIdentifier('1', $languageAspect1);
        $identifier2 = $session->buildIdentifier('1', $languageAspect2);

        self::assertNotEquals($identifier1, $identifier2, 'Identifiers should differ for different overlay types.');
    }

    #[Test]
    public function buildIdentifierProducesUniqueIdentifiersForDifferentFallbackChains(): void
    {
        $languageAspect1 = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON, [0]);
        $languageAspect2 = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON, [2, 0]);

        $session = new Session();
        $identifier1 = $session->buildIdentifier('1', $languageAspect1);
        $identifier2 = $session->buildIdentifier('1', $languageAspect2);

        self::assertNotEquals($identifier1, $identifier2, 'Identifiers should differ for different fallback chains.');
    }

    #[Test]
    public function buildIdentifierProducesSameIdentifierForSameConfiguration(): void
    {
        $languageAspect1 = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON, [0]);
        $languageAspect2 = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON, [0]);

        $session = new Session();
        $identifier1 = $session->buildIdentifier('1', $languageAspect1);
        $identifier2 = $session->buildIdentifier('1', $languageAspect2);

        self::assertEquals($identifier1, $identifier2, 'Identifiers should be identical for the same configuration.');
    }

    #[Test]
    public function buildIdentifierIgnoresLanguageId(): void
    {
        // The language ID is used for menus/links, not for content fetching,
        // so it should not affect the identifier. Only contentId matters.
        $languageAspect1 = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON, [0]);
        $languageAspect2 = new LanguageAspect(2, 1, LanguageAspect::OVERLAYS_ON, [0]);

        $session = new Session();
        $identifier1 = $session->buildIdentifier('1', $languageAspect1);
        $identifier2 = $session->buildIdentifier('1', $languageAspect2);

        self::assertEquals($identifier1, $identifier2, 'Identifiers should be identical when only language ID differs.');
    }

    #[Test]
    public function getBaseIdentifierStripsLanguageContentIdentifier(): void
    {
        $session = new Session();
        self::assertSame('42', $session->getBaseIdentifier('42@1-on-0'));
        self::assertSame('42_13', $session->getBaseIdentifier('42_13@1-on-0'));
    }

    #[Test]
    public function getBaseIdentifierReturnsIdentifierWithoutSuffix(): void
    {
        $session = new Session();
        self::assertSame('42', $session->getBaseIdentifier('42'));
    }

    #[Test]
    public function buildIdentifierFromArrayRow(): void
    {
        $session = new Session();
        $languageAspect = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON, [0]);
        $identifier = $session->buildIdentifier(['uid' => 42, '_LOCALIZED_UID' => 13], $languageAspect);

        self::assertSame('42_13', $session->getBaseIdentifier($identifier));
        self::assertStringStartsWith('42_13@', $identifier);
    }

    #[Test]
    public function buildIdentifierFromArrayRowWithoutLocalizedUid(): void
    {
        $session = new Session();
        $languageAspect = new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON, []);
        $identifier = $session->buildIdentifier(['uid' => 42], $languageAspect);

        self::assertSame('42', $session->getBaseIdentifier($identifier));
        self::assertStringStartsWith('42@', $identifier);
    }

    #[Test]
    public function buildIdentifierUsesDefaultLanguageAspectWhenNoneProvided(): void
    {
        $session = new Session();
        $identifierWithDefault = $session->buildIdentifier('42');
        $identifierWithExplicit = $session->buildIdentifier(
            '42',
            new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [])
        );

        self::assertEquals($identifierWithDefault, $identifierWithExplicit);
    }
}
