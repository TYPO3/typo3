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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Tests\Unit\Persistence\Fixture\Domain\Model\Entity;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ObjectStorageTest extends UnitTestCase
{
    #[Test]
    public function currentForEmptyStorageReturnsNull(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        $result = $objectStorage->current();

        self::assertNull($result);
    }

    #[Test]
    public function countForEmptyStorageIsZero(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        self::assertCount(0, $objectStorage);
    }

    #[Test]
    public function getInfoForEmptyStorageReturnsNull(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        $result = $objectStorage->getInfo();

        self::assertNull($result);
    }

    #[Test]
    public function attachWithInformationMakesAttachedInformationAvailableUsingTheObjectAsKey(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $information = 'foo';

        $objectStorage->attach($object, $information);

        self::assertSame($information, $objectStorage[$object]);
    }

    #[Test]
    public function attachForEmptyStorageIncreasesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        self::assertCount(0, $objectStorage);

        $objectStorage->attach(new Entity());

        self::assertCount(1, $objectStorage);
    }

    #[Test]
    public function attachForNonEmptyStorageIncreasesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new Entity());
        self::assertCount(1, $objectStorage);

        $objectStorage->attach(new Entity());

        self::assertCount(2, $objectStorage);
    }

    #[Test]
    public function attachingAnObjectUsingArrayAssignmentWithInformationIncreasesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new Entity());
        self::assertCount(1, $objectStorage);

        $objectStorage[new Entity()] = 'bar';

        self::assertCount(2, $objectStorage);
    }

    #[Test]
    public function detachForAttachedObjectReducesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new Entity());

        $object = new Entity();
        $objectStorage->attach($object);
        self::assertCount(2, $objectStorage);

        $objectStorage->detach($object);

        self::assertCount(1, $objectStorage);
    }

    #[Test]
    public function offsetSetIncreasesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new Entity());
        self::assertCount(1, $objectStorage);

        $objectStorage->offsetSet(new Entity(), 'foo');

        self::assertCount(2, $objectStorage);
    }

    #[Test]
    public function offsetUnsetWithObjectReducesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new Entity());
        $object = new Entity();
        $objectStorage->attach($object);
        self::assertCount(2, $objectStorage);

        $objectStorage->offsetUnset($object);

        self::assertCount(1, $objectStorage);
    }

    #[Test]
    public function offsetUnsetWithIntegerKeyReducesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new Entity());
        $object = new Entity();
        $objectStorage->attach($object);
        self::assertCount(2, $objectStorage);

        $objectStorage->offsetUnset(0);

        self::assertCount(1, $objectStorage);
    }

    #[Test]
    public function offsetGetForNoneExistingIntegerEntryReturnsNull(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        self::assertNull($objectStorage->offsetGet(1));
    }

    #[Test]
    public function offsetGetForNoneExistingObjectEntryReturnsNull(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();

        self::assertNull($objectStorage->offsetGet($object));
    }

    #[Test]
    public function offsetGetForObjectAttachedWithoutWithoutInformationReturnsNull(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object);

        self::assertNull($objectStorage->offsetGet($object));
    }

    #[Test]
    public function offsetGetForObjectWithInformationAttachedUsingArrayAssignmentReturnsTheAssociatedInformation(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object1 = new Entity();
        $information = 'foo';
        $objectStorage[$object1] = $information;

        self::assertSame($information, $objectStorage->offsetGet($object1));
    }

    #[Test]
    public function offsetGetForObjectWithInformationAttachedUsingAttachReturnsTheAssociatedInformation(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object1 = new Entity();
        $information = 'foo';
        $objectStorage->attach($object1, $information);

        self::assertSame($information, $objectStorage->offsetGet($object1));
    }

    #[Test]
    public function offsetGetWithIntegerKeyReturnsTheAssociatedObject(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object);

        self::assertSame($object, $objectStorage->offsetGet(0));
    }

    #[Test]
    public function offsetExistsWithObjectAddedToStorageReturnsTrue(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object);

        self::assertTrue($objectStorage->offsetExists($object));
    }

    #[Test]
    public function offsetExistsWithObjectNotAddedToStorageReturnsFalse(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        self::assertFalse($objectStorage->offsetExists(new Entity()));
    }

    #[Test]
    public function offsetExistsWithIntegerKeyInStorageReturnsTrue(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object);

        self::assertTrue($objectStorage->offsetExists(0));
    }

    #[Test]
    public function offsetExistsWithIntegerKeyNotInStorageReturnsFalse(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        self::assertFalse($objectStorage->offsetExists(0));
    }

    #[Test]
    public function offsetExistsWithNumericStringKeyNotInStorageReturnsFalse(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        self::assertFalse($objectStorage->offsetExists('0'));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function informationDataProvider(): array
    {
        return [
            'integer' => [42],
            'string' => ['foo'],
            'array of strings' => [['bar', 'baz']],
        ];
    }

    #[DataProvider('informationDataProvider')]
    #[Test]
    public function getInfoReturnsTheInformationAssociatedWithTheCurrentIteratorEntry(mixed $information): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object, $information);
        $objectStorage->rewind();

        self::assertSame($information, $objectStorage->getInfo());
    }

    #[DataProvider('informationDataProvider')]
    #[Test]
    public function setInfoSetsTheInformationAssociatedWithTheCurrentIteratorEntry(mixed $information): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object);

        $objectStorage->setInfo($information);

        self::assertSame($information, $objectStorage[$object]);
    }

    #[DataProvider('informationDataProvider')]
    #[Test]
    public function setInfoOverwritesTheInformationAssociatedWithTheCurrentIteratorEntry(mixed $information): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object, 'bar');

        $objectStorage->setInfo($information);

        self::assertSame($information, $objectStorage[$object]);
    }

    #[Test]
    public function removeAllRemovesObjectsContainedInAnotherStorageFromTheCurrentStorage(): void
    {
        $object1 = new Entity();
        /** @var ObjectStorage<Entity> $otherObjectStorage */
        $otherObjectStorage = new ObjectStorage();
        $otherObjectStorage->attach($object1);
        /** @var ObjectStorage<Entity> $objectStorageToRemoveFrom */
        $objectStorageToRemoveFrom = new ObjectStorage();
        $objectStorageToRemoveFrom->attach($object1);
        self::assertCount(1, $objectStorageToRemoveFrom);

        $objectStorageToRemoveFrom->removeAll($otherObjectStorage);

        self::assertCount(0, $objectStorageToRemoveFrom);
    }

    #[Test]
    public function removeAllRemovesRemovesObjectWithDifferentInformationFromTheCurrentStorage(): void
    {
        $object1 = new Entity();
        /** @var ObjectStorage<Entity> $otherObjectStorage */
        $otherObjectStorage = new ObjectStorage();
        $otherObjectStorage->attach($object1, 'foo');
        /** @var ObjectStorage<Entity> $objectStorageToRemoveFrom */
        $objectStorageToRemoveFrom = new ObjectStorage();
        $objectStorageToRemoveFrom->attach($object1, 'bar');
        self::assertCount(1, $objectStorageToRemoveFrom);

        $objectStorageToRemoveFrom->removeAll($otherObjectStorage);

        self::assertCount(0, $objectStorageToRemoveFrom);
    }

    #[Test]
    public function removeAllKeepsObjectsNotContainedInTheOtherStorage(): void
    {
        $object1 = new Entity();
        /** @var ObjectStorage<Entity> $otherObjectStorage */
        $otherObjectStorage = new ObjectStorage();
        $otherObjectStorage->attach($object1);
        /** @var ObjectStorage<Entity> $objectStorageToRemoveFrom */
        $objectStorageToRemoveFrom = new ObjectStorage();
        $objectStorageToRemoveFrom->attach($object1);
        $objectStorageToRemoveFrom->attach(new Entity());
        self::assertCount(2, $objectStorageToRemoveFrom);

        $objectStorageToRemoveFrom->removeAll($otherObjectStorage);

        self::assertCount(1, $objectStorageToRemoveFrom);
    }

    #[Test]
    public function removeAlIgnoresAdditionsObjectsContainedInOtherStorage(): void
    {
        $object1 = new Entity();
        /** @var ObjectStorage<Entity> $otherObjectStorage */
        $otherObjectStorage = new ObjectStorage();
        $otherObjectStorage->attach($object1);
        /** @var ObjectStorage<Entity> $objectStorageToRemoveFrom */
        $objectStorageToRemoveFrom = new ObjectStorage();
        $objectStorageToRemoveFrom->attach($object1);
        $objectStorageToRemoveFrom->attach(new Entity());
        self::assertCount(2, $objectStorageToRemoveFrom);

        $objectStorageToRemoveFrom->removeAll($otherObjectStorage);

        self::assertCount(1, $objectStorageToRemoveFrom);
    }

    #[Test]
    public function addAllAddsAllObjectsFromAnotherStorage(): void
    {
        $object = new Entity();
        /** @var ObjectStorage<Entity> $storageToAddFrom */
        $storageToAddFrom = new ObjectStorage();
        $storageToAddFrom->attach($object, 'foo');
        /** @var ObjectStorage<Entity> $storageToAddTo */
        $storageToAddTo = new ObjectStorage();
        self::assertFalse($storageToAddTo->contains($object));

        $storageToAddTo->addAll($storageToAddFrom);

        self::assertTrue($storageToAddTo->contains($object));
    }

    #[Test]
    public function addAllAlsoAddsInformationOfTheAddedObjects(): void
    {
        $object = new Entity();
        /** @var ObjectStorage<Entity> $storageToAddFrom */
        $storageToAddFrom = new ObjectStorage();
        $information = 'foo';
        $storageToAddFrom->attach($object, $information);
        /** @var ObjectStorage<Entity> $storageToAddTo */
        $storageToAddTo = new ObjectStorage();
        self::assertFalse($storageToAddTo->offsetExists($object));

        $storageToAddTo->addAll($storageToAddFrom);

        self::assertSame($information, $storageToAddTo[$object]);
    }

    #[Test]
    public function toArrayReturnsObjectsInStorageUsingIntegerKeys(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object1 = new Entity();
        $objectStorage->attach($object1, 'foo');
        $object2 = new Entity();
        $objectStorage->attach($object2, 'bar');

        self::assertSame([0 => $object1, 1 => $object2], $objectStorage->toArray());
    }

    #[Test]
    public function getArrayReturnsObjectsInStorageUsingIntegerKeys(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object1 = new Entity();
        $objectStorage->attach($object1, 'foo');
        $object2 = new Entity();
        $objectStorage->attach($object2, 'bar');

        self::assertSame([0 => $object1, 1 => $object2], $objectStorage->getArray());
    }

    #[Test]
    public function relationsAreNotDirtyOnAttaching(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();

        $objectStorage->attach($object);

        self::assertFalse($objectStorage->isRelationDirty($object));
    }

    #[Test]
    public function relationsAreNotDirtyOnAttachingAndRemoving(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object1 = new Entity();

        $objectStorage->attach($object1);
        $objectStorage->detach($object1);

        self::assertFalse($objectStorage->isRelationDirty($object1));
    }

    #[Test]
    public function relationsAreNotDirtyOnReAddingAtSamePosition(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object1 = new Entity();
        $object2 = new Entity();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);

        $clonedStorage = clone $objectStorage;
        $objectStorage->removeAll($clonedStorage);
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);

        self::assertFalse($objectStorage->isRelationDirty($object1));
        self::assertFalse($objectStorage->isRelationDirty($object2));
    }

    #[Test]
    public function relationsAreDirtyOnReAddingAtOtherPosition(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object1 = new Entity();
        $object2 = new Entity();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);

        $clonedStorage = clone $objectStorage;
        $objectStorage->removeAll($clonedStorage);
        $objectStorage->attach($object2);
        $objectStorage->attach($object1);

        self::assertTrue($objectStorage->isRelationDirty($object1));
        self::assertTrue($objectStorage->isRelationDirty($object2));
    }
}
