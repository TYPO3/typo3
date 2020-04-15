<?php

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
    public function anObjectCanBeAttached()
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        self::assertEquals($objectStorage[$object1], null);
        self::assertEquals($objectStorage[$object2], 'foo');
    }

    /**
     * @test
     */
    public function anObjectCanBeDetached()
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        self::assertEquals(count($objectStorage), 2);
        $objectStorage->detach($object1);
        self::assertEquals(count($objectStorage), 1);
        $objectStorage->detach($object2);
        self::assertEquals(count($objectStorage), 0);
    }

    /**
     * @test
     */
    public function offsetSetAssociatesDataToAnObjectInTheStorage()
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->offsetSet($object1, 'foo');
        self::assertEquals(count($objectStorage), 1);
        $objectStorage[$object2] = 'bar';
        self::assertEquals(count($objectStorage), 2);
    }

    /**
     * @test
     */
    public function offsetUnsetRemovesAnObjectFromTheStorage()
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        self::assertEquals(count($objectStorage), 2);
        $objectStorage->offsetUnset($object2);
        self::assertEquals(count($objectStorage), 1);
        $objectStorage->offsetUnset($object1);
        self::assertEquals(count($objectStorage), 0);
    }

    /**
     * @test
     */
    public function offsetUnsetKeyRemovesAnObjectFromTheStorage()
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        self::assertEquals(count($objectStorage), 2);
        $objectStorage->offsetUnset(0);
        self::assertEquals(count($objectStorage), 1);
        $objectStorage->offsetUnset(0);
        self::assertEquals(count($objectStorage), 0);
    }

    /**
     * @test
     */
    public function offsetGetReturnsTheDataAssociatedWithAnObject()
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage[$object1] = 'foo';
        $objectStorage->attach($object2);
        self::assertEquals($objectStorage->offsetGet($object1), 'foo');
        self::assertEquals($objectStorage->offsetGet($object2), null);
    }

    /**
     * @test
     */
    public function offsetGetKeyReturnsTheObject()
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
    public function offsetExistsChecksWhetherAnObjectExistsInTheStorage()
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorage->attach($object1);
        self::assertEquals($objectStorage->offsetExists($object1), true);
        self::assertEquals($objectStorage->offsetExists($object2), false);
    }

    /**
     * @test
     */
    public function offsetExistsChecksWhetherKeyExistsInTheStorage()
    {
        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new \stdClass());
        self::assertTrue($objectStorage->offsetExists(0));
        self::assertFalse($objectStorage->offsetExists(1));
    }

    /**
     * @test
     */
    public function offsetExistsWorksWithEmptyStorageAndIntegerKey()
    {
        $objectStorage = new ObjectStorage();
        self::assertEquals($objectStorage->offsetExists(0), false);
    }

    /**
     * @test
     */
    public function offsetExistsWorksWithEmptyStorageAndStringKey()
    {
        $objectStorage = new ObjectStorage();
        self::assertEquals($objectStorage->offsetExists('0'), false);
    }

    /**
     * @test
     */
    public function getInfoReturnsTheDataAssociatedWithTheCurrentIteratorEntry()
    {
        $objectStorage = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $object3 = new \stdClass();
        $objectStorage->attach($object1, 42);
        $objectStorage->attach($object2, 'foo');
        $objectStorage->attach($object3, ['bar', 'baz']);
        $objectStorage->rewind();
        self::assertEquals($objectStorage->getInfo(), 42);
        $objectStorage->next();
        self::assertEquals($objectStorage->getInfo(), 'foo');
        $objectStorage->next();
        self::assertEquals($objectStorage->getInfo(), ['bar', 'baz']);
    }

    /**
     * @test
     */
    public function setInfoSetsTheDataAssociatedWithTheCurrentIteratorEntry()
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
        self::assertEquals($objectStorage[$object1], 42);
        self::assertEquals($objectStorage[$object2], 'bar');
    }

    /**
     * @test
     */
    public function removeAllRemovesObjectsContainedInAnotherStorageFromTheCurrentStorage()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorageA = new ObjectStorage();
        $objectStorageA->attach($object1, 'foo');
        $objectStorageB = new ObjectStorage();
        $objectStorageB->attach($object1, 'bar');
        $objectStorageB->attach($object2, 'baz');
        self::assertEquals(count($objectStorageB), 2);
        $objectStorageB->removeAll($objectStorageA);
        self::assertEquals(count($objectStorageB), 1);
    }

    /**
     * @test
     */
    public function addAllAddsAllObjectsFromAnotherStorage()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $objectStorageA = new ObjectStorage();
        // It might be better to mock this
        $objectStorageA->attach($object1, 'foo');
        $objectStorageB = new ObjectStorage();
        $objectStorageB->attach($object2, 'baz');
        self::assertEquals($objectStorageB->offsetExists($object1), false);
        $objectStorageB->addAll($objectStorageA);
        self::assertEquals($objectStorageB[$object1], 'foo');
        self::assertEquals($objectStorageB[$object2], 'baz');
    }

    /**
     * @test
     */
    public function theStorageCanBeRetrievedAsArray()
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
    public function allRelationsAreNotDirtyOnAttaching()
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
    public function allRelationsAreNotDirtyOnAttachingAndRemoving()
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
    public function theRelationsAreNotDirtyOnReAddingAtSamePosition()
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
    public function theRelationsAreDirtyOnReAddingAtOtherPosition()
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
