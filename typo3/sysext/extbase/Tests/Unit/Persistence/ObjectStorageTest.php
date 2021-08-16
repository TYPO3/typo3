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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ObjectStorageTest extends UnitTestCase
{
    /**
     * @test
     */
    public function anObjectCanBeAttached(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        self::assertNull($objectStorage[$object1]);
        self::assertEquals('foo', $objectStorage[$object2]);
    }

    /**
     * @test
     */
    public function anObjectCanBeDetached(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        self::assertCount(2, $objectStorage);
        $objectStorage->detach($object1);
        self::assertCount(1, $objectStorage);
        $objectStorage->detach($object2);
        self::assertCount(0, $objectStorage);
    }

    /**
     * @test
     */
    public function offsetSetAssociatesDataToAnObjectInTheStorage(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->offsetSet($object1, 'foo');
        self::assertCount(1, $objectStorage);
        $objectStorage[$object2] = 'bar';
        self::assertCount(2, $objectStorage);
    }

    /**
     * @test
     */
    public function offsetUnsetRemovesAnObjectFromTheStorage(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        self::assertCount(2, $objectStorage);
        $objectStorage->offsetUnset($object2);
        self::assertCount(1, $objectStorage);
        $objectStorage->offsetUnset($object1);
        self::assertCount(0, $objectStorage);
    }

    /**
     * @test
     */
    public function offsetUnsetKeyRemovesAnObjectFromTheStorage(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        self::assertCount(2, $objectStorage);
        $objectStorage->offsetUnset(0);
        self::assertCount(1, $objectStorage);
        $objectStorage->offsetUnset(0);
        self::assertCount(0, $objectStorage);
    }

    /**
     * @test
     */
    public function offsetGetReturnsTheDataAssociatedWithAnObject(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage[$object1] = 'foo';
        $objectStorage->attach($object2);
        self::assertEquals('foo', $objectStorage->offsetGet($object1));
        self::assertNull($objectStorage->offsetGet($object2));
    }

    /**
     * @test
     */
    public function offsetGetKeyReturnsTheObject(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);
        self::assertSame($object1, $objectStorage->offsetGet(0));
        self::assertSame($object2, $objectStorage->offsetGet(1));
    }

    /**
     * @test
     */
    public function offsetExistsChecksWhetherAnObjectExistsInTheStorage(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        self::assertTrue($objectStorage->offsetExists($object1));
        self::assertFalse($objectStorage->offsetExists($object2));
    }

    /**
     * @test
     */
    public function offsetExistsChecksWhetherKeyExistsInTheStorage(): void
    {
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new \stdClass());
        self::assertTrue($objectStorage->offsetExists(0));
        self::assertFalse($objectStorage->offsetExists(1));
    }

    /**
     * @test
     */
    public function offsetExistsWorksWithEmptyStorageAndIntegerKey(): void
    {
        $objectStorage = new ObjectStorage();
        self::assertFalse($objectStorage->offsetExists(0));
    }

    /**
     * @test
     */
    public function offsetExistsWorksWithEmptyStorageAndStringKey(): void
    {
        $objectStorage = new ObjectStorage();
        self::assertFalse($objectStorage->offsetExists('0'));
    }

    /**
     * @test
     */
    public function getInfoReturnsTheDataAssociatedWithTheCurrentIteratorEntry(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $object3 = new \stdClass();
        $objectStorage->attach($object1, 42);
        $objectStorage->attach($object2, 'foo');
        $objectStorage->attach($object3, ['bar', 'baz']);
        $objectStorage->rewind();
        self::assertEquals(42, $objectStorage->getInfo());
        $objectStorage->next();
        self::assertEquals('foo', $objectStorage->getInfo());
        $objectStorage->next();
        self::assertEquals(['bar', 'baz'], $objectStorage->getInfo());
    }

    /**
     * @test
     */
    public function setInfoSetsTheDataAssociatedWithTheCurrentIteratorEntry(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        $objectStorage->rewind();
        $objectStorage->setInfo(42);
        $objectStorage->next();
        $objectStorage->setInfo('bar');
        self::assertEquals(42, $objectStorage[$object1]);
        self::assertEquals('bar', $objectStorage[$object2]);
    }

    /**
     * @test
     */
    public function removeAllRemovesObjectsContainedInAnotherStorageFromTheCurrentStorage(): void
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorageA = new ObjectStorage();
        $objectStorageA->attach($object1, 'foo');
        $objectStorageB = new ObjectStorage();
        $objectStorageB->attach($object1, 'bar');
        $objectStorageB->attach($object2, 'baz');
        self::assertCount(2, $objectStorageB);
        $objectStorageB->removeAll($objectStorageA);
        self::assertCount(1, $objectStorageB);
    }

    /**
     * @test
     */
    public function addAllAddsAllObjectsFromAnotherStorage(): void
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorageA = new ObjectStorage();
        // It might be better to mock this
        $objectStorageA->attach($object1, 'foo');
        $objectStorageB = new ObjectStorage();
        $objectStorageB->attach($object2, 'baz');
        self::assertFalse($objectStorageB->offsetExists($object1));
        $objectStorageB->addAll($objectStorageA);
        self::assertEquals('foo', $objectStorageB[$object1]);
        self::assertEquals('baz', $objectStorageB[$object2]);
    }

    /**
     * @test
     */
    public function theStorageCanBeRetrievedAsArray(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1, 'foo');
        $objectStorage->attach($object2, 'bar');
        self::assertEquals([$object1, $object2], $objectStorage->toArray());
        self::assertEquals([$object1, $object2], $objectStorage->getArray());
    }

    /**
     * @test
     */
    public function allRelationsAreNotDirtyOnAttaching(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $object3 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);
        $objectStorage->attach($object3);
        self::assertFalse($objectStorage->isRelationDirty($object1));
        self::assertFalse($objectStorage->isRelationDirty($object2));
        self::assertFalse($objectStorage->isRelationDirty($object3));
    }

    /**
     * @test
     */
    public function allRelationsAreNotDirtyOnAttachingAndRemoving(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $object3 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);
        $objectStorage->detach($object2);
        $objectStorage->attach($object3);
        self::assertFalse($objectStorage->isRelationDirty($object1));
        self::assertFalse($objectStorage->isRelationDirty($object3));
    }

    /**
     * @test
     */
    public function theRelationsAreNotDirtyOnReAddingAtSamePosition(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
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
    public function theRelationsAreDirtyOnReAddingAtOtherPosition(): void
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
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
