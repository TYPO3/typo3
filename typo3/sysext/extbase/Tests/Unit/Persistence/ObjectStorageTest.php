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

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Tests\Unit\Persistence\Fixture\Domain\Model\Entity;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ObjectStorageTest extends UnitTestCase
{
    /**
     * @test
     */
    public function currentForEmptyStorageReturnsNull(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        $result = $objectStorage->current();

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function countForEmptyStorageIsZero(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        self::assertCount(0, $objectStorage);
    }

    /**
     * @test
     */
    public function getInfoForEmptyStorageReturnsNull(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        $result = $objectStorage->getInfo();

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function attachWithInformationMakesAttachedInformationAvailableUsingTheObjectAsKey(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $information = 'foo';

        $objectStorage->attach($object, $information);

        self::assertSame($information, $objectStorage[$object]);
    }

    /**
     * @test
     */
    public function attachForEmptyStorageIncreasesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        self::assertCount(0, $objectStorage);

        $objectStorage->attach(new Entity());

        self::assertCount(1, $objectStorage);
    }

    /**
     * @test
     */
    public function attachForNonEmptyStorageIncreasesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new Entity());
        self::assertCount(1, $objectStorage);

        $objectStorage->attach(new Entity());

        self::assertCount(2, $objectStorage);
    }

    /**
     * @test
     */
    public function attachingAnObjectUsingArrayAssignmentWithInformationIncreasesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new Entity());
        self::assertCount(1, $objectStorage);

        $objectStorage[new Entity()] = 'bar';

        self::assertCount(2, $objectStorage);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function offsetSetIncreasesCountByOne(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new Entity());
        self::assertCount(1, $objectStorage);

        $objectStorage->offsetSet(new Entity(), 'foo');

        self::assertCount(2, $objectStorage);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function offsetGetForNoneExistingIntegerEntryReturnsNull(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        self::assertNull($objectStorage->offsetGet(1));
    }

    /**
     * @test
     */
    public function offsetGetForNoneExistingObjectEntryReturnsNull(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();

        self::assertNull($objectStorage->offsetGet($object));
    }

    /**
     * @test
     */
    public function offsetGetForObjectAttachedWithoutWithoutInformationReturnsNull(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object);

        self::assertNull($objectStorage->offsetGet($object));
    }

    /**
     * @test
     */
    public function offsetGetForObjectWithInformationAttachedUsingArrayAssignmentReturnsTheAssociatedInformation(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object1 = new Entity();
        $information = 'foo';
        $objectStorage[$object1] = $information;

        self::assertSame($information, $objectStorage->offsetGet($object1));
    }

    /**
     * @test
     */
    public function offsetGetForObjectWithInformationAttachedUsingAttachReturnsTheAssociatedInformation(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object1 = new Entity();
        $information = 'foo';
        $objectStorage->attach($object1, $information);

        self::assertSame($information, $objectStorage->offsetGet($object1));
    }

    /**
     * @test
     */
    public function offsetGetWithIntegerKeyReturnsTheAssociatedObject(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object);

        self::assertSame($object, $objectStorage->offsetGet(0));
    }

    /**
     * @test
     */
    public function offsetExistsWithObjectAddedToStorageReturnsTrue(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object);

        self::assertTrue($objectStorage->offsetExists($object));
    }

    /**
     * @test
     */
    public function offsetExistsWithObjectNotAddedToStorageReturnsFalse(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        self::assertFalse($objectStorage->offsetExists(new Entity()));
    }

    /**
     * @test
     */
    public function offsetExistsWithIntegerKeyInStorageReturnsTrue(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object);

        self::assertTrue($objectStorage->offsetExists(0));
    }

    /**
     * @test
     */
    public function offsetExistsWithIntegerKeyNotInStorageReturnsFalse(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        self::assertFalse($objectStorage->offsetExists(0));
    }

    /**
     * @test
     */
    public function offsetExistsWithNumericStringKeyNotInStorageReturnsFalse(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();

        // @phpstan-ignore-next-line We're explicitly testing with a key that lies outside the contract.
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

    /**
     * @test
     * @dataProvider informationDataProvider
     */
    public function getInfoReturnsTheInformationAssociatedWithTheCurrentIteratorEntry(mixed $information): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object, $information);
        $objectStorage->rewind();

        self::assertSame($information, $objectStorage->getInfo());
    }

    /**
     * @test
     * @dataProvider informationDataProvider
     */
    public function setInfoSetsTheInformationAssociatedWithTheCurrentIteratorEntry(mixed $information): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object);

        $objectStorage->setInfo($information);

        self::assertSame($information, $objectStorage[$object]);
    }

    /**
     * @test
     * @dataProvider informationDataProvider
     */
    public function setInfoOverwritesTheInformationAssociatedWithTheCurrentIteratorEntry(mixed $information): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();
        $objectStorage->attach($object, 'bar');

        $objectStorage->setInfo($information);

        self::assertSame($information, $objectStorage[$object]);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function relationsAreNotDirtyOnAttaching(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object = new Entity();

        $objectStorage->attach($object);

        self::assertFalse($objectStorage->isRelationDirty($object));
    }

    /**
     * @test
     */
    public function relationsAreNotDirtyOnAttachingAndRemoving(): void
    {
        /** @var ObjectStorage<Entity> $objectStorage */
        $objectStorage = new ObjectStorage();
        $object1 = new Entity();

        $objectStorage->attach($object1);
        $objectStorage->detach($object1);

        self::assertFalse($objectStorage->isRelationDirty($object1));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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
