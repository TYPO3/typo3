<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence;

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
class ObjectStorageTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function anObjectCanBeAttached()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        $this->assertEquals($objectStorage[$object1], null);
        $this->assertEquals($objectStorage[$object2], 'foo');
    }

    /**
     * @test
     */
    public function anObjectCanBeDetached()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        $this->assertEquals(count($objectStorage), 2);
        $objectStorage->detach($object1);
        $this->assertEquals(count($objectStorage), 1);
        $objectStorage->detach($object2);
        $this->assertEquals(count($objectStorage), 0);
    }

    /**
     * @test
     */
    public function offsetSetAssociatesDataToAnObjectInTheStorage()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $objectStorage->offsetSet($object1, 'foo');
        $this->assertEquals(count($objectStorage), 1);
        $objectStorage[$object2] = 'bar';
        $this->assertEquals(count($objectStorage), 2);
    }

    /**
     * @test
     */
    public function offsetUnsetRemovesAnObjectFromTheStorage()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        $this->assertEquals(count($objectStorage), 2);
        $objectStorage->offsetUnset($object2);
        $this->assertEquals(count($objectStorage), 1);
        $objectStorage->offsetUnset($object1);
        $this->assertEquals(count($objectStorage), 0);
    }

    /**
     * @test
     */
    public function offsetGetReturnsTheDataAssociatedWithAnObject()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $objectStorage[$object1] = 'foo';
        $objectStorage->attach($object2);
        $this->assertEquals($objectStorage->offsetGet($object1), 'foo');
        $this->assertEquals($objectStorage->offsetGet($object2), null);
    }

    /**
     * @test
     */
    public function offsetExistsChecksWhetherAnObjectExistsInTheStorage()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $objectStorage->attach($object1);
        $this->assertEquals($objectStorage->offsetExists($object1), true);
        $this->assertEquals($objectStorage->offsetExists($object2), false);
    }

    /**
     * @test
     */
    public function offsetExistsWorksWithEmptyStorageAndIntegerKey()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->assertEquals($objectStorage->offsetExists(0), false);
    }

    /**
     * @test
     */
    public function offsetExistsWorksWithEmptyStorageAndStringKey()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->assertEquals($objectStorage->offsetExists('0'), false);
    }

    /**
     * @test
     */
    public function getInfoReturnsTheDataAssociatedWithTheCurrentIteratorEntry()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $object3 = new \StdClass();
        $objectStorage->attach($object1, 42);
        $objectStorage->attach($object2, 'foo');
        $objectStorage->attach($object3, ['bar', 'baz']);
        $objectStorage->rewind();
        $this->assertEquals($objectStorage->getInfo(), 42);
        $objectStorage->next();
        $this->assertEquals($objectStorage->getInfo(), 'foo');
        $objectStorage->next();
        $this->assertEquals($objectStorage->getInfo(), ['bar', 'baz']);
    }

    /**
     * @test
     */
    public function setInfoSetsTheDataAssociatedWithTheCurrentIteratorEntry()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2, 'foo');
        $objectStorage->rewind();
        $objectStorage->setInfo(42);
        $objectStorage->next();
        $objectStorage->setInfo('bar');
        $this->assertEquals($objectStorage[$object1], 42);
        $this->assertEquals($objectStorage[$object2], 'bar');
    }

    /**
     * @test
     */
    public function removeAllRemovesObjectsContainedInAnotherStorageFromTheCurrentStorage()
    {
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $objectStorageA = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageA->attach($object1, 'foo');
        $objectStorageB = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageB->attach($object1, 'bar');
        $objectStorageB->attach($object2, 'baz');
        $this->assertEquals(count($objectStorageB), 2);
        $objectStorageB->removeAll($objectStorageA);
        $this->assertEquals(count($objectStorageB), 1);
    }

    /**
     * @test
     */
    public function addAllAddsAllObjectsFromAnotherStorage()
    {
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $objectStorageA = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        // It might be better to mock this
        $objectStorageA->attach($object1, 'foo');
        $objectStorageB = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageB->attach($object2, 'baz');
        $this->assertEquals($objectStorageB->offsetExists($object1), false);
        $objectStorageB->addAll($objectStorageA);
        $this->assertEquals($objectStorageB[$object1], 'foo');
        $this->assertEquals($objectStorageB[$object2], 'baz');
    }

    /**
     * @test
     */
    public function theStorageCanBeRetrievedAsArray()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $objectStorage->attach($object1, 'foo');
        $objectStorage->attach($object2, 'bar');
        $this->assertEquals($objectStorage->toArray(), [$object1, $object2]);
    }

    /**
     * @test
     */
    public function allRelationsAreNotDirtyOnAttaching()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass();
        $object2 = new \StdClass();
        $object3 = new \StdClass();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);
        $objectStorage->attach($object3);
        $this->assertFalse($objectStorage->isRelationDirty($object1));
        $this->assertFalse($objectStorage->isRelationDirty($object2));
        $this->assertFalse($objectStorage->isRelationDirty($object3));
    }

    /**
     * @test
     */
    public function allRelationsAreNotDirtyOnAttachingAndRemoving()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass;
        $object2 = new \StdClass;
        $object3 = new \StdClass;
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);
        $objectStorage->detach($object2);
        $objectStorage->attach($object3);
        $this->assertFalse($objectStorage->isRelationDirty($object1));
        $this->assertFalse($objectStorage->isRelationDirty($object3));
    }

    /**
     * @test
     */
    public function theRelationsAreNotDirtyOnReAddingAtSamePosition()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass;
        $object2 = new \StdClass;
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);
        $clonedStorage = clone $objectStorage;
        $objectStorage->removeAll($clonedStorage);
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);
        $this->assertFalse($objectStorage->isRelationDirty($object1));
        $this->assertFalse($objectStorage->isRelationDirty($object2));
    }

    /**
     * @test
     */
    public function theRelationsAreDirtyOnReAddingAtOtherPosition()
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $object1 = new \StdClass;
        $object2 = new \StdClass;
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);
        $clonedStorage = clone $objectStorage;
        $objectStorage->removeAll($clonedStorage);
        $objectStorage->attach($object2);
        $objectStorage->attach($object1);
        $this->assertTrue($objectStorage->isRelationDirty($object1));
        $this->assertTrue($objectStorage->isRelationDirty($object2));
    }
}
