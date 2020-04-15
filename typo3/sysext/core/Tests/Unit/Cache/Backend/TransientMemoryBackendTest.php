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

namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the TransientMemory cache backend
 */
class TransientMemoryBackendTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1238244992);

        $backend = new TransientMemoryBackend('Testing');
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend('Testing');
        $backend->setCache($cache);
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $inCache = $backend->has($identifier);
        self::assertTrue($inCache);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetEntry()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend('Testing');
        $backend->setCache($cache);
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $fetchedData = $backend->get($identifier);
        self::assertEquals($data, $fetchedData);
    }

    /**
     * @test
     */
    public function itIsPossibleToRemoveEntryFromCache()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend('Testing');
        $backend->setCache($cache);
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        $inCache = $backend->has($identifier);
        self::assertFalse($inCache);
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend('Testing');
        $backend->setCache($cache);
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $otherData = 'some other data';
        $backend->set($identifier, $otherData);
        $fetchedData = $backend->get($identifier);
        self::assertEquals($otherData, $fetchedData);
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend('Testing');
        $backend->setCache($cache);
        $data = 'Some data';
        $entryIdentifier = 'MyIdentifier';
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
        self::assertEquals($entryIdentifier, $retrieved[0]);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        self::assertEquals($entryIdentifier, $retrieved[0]);
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend('Testing');
        $backend->setCache($cache);
        $identifier = 'NonExistingIdentifier';
        $inCache = $backend->has($identifier);
        self::assertFalse($inCache);
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend('Testing');
        $backend->setCache($cache);
        $identifier = 'NonExistingIdentifier';
        $inCache = $backend->remove($identifier);
        self::assertFalse($inCache);
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend('Testing');
        $backend->setCache($cache);
        $data = 'some data' . microtime();
        $backend->set('TransientMemoryBackendTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('TransientMemoryBackendTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('TransientMemoryBackendTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTag('UnitTestTag%special');
        self::assertTrue($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
        self::assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
        self::assertTrue($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
    }

    /**
     * @test
     */
    public function flushByTagsRemovesCacheEntriesWithSpecifiedTags()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend('Testing');
        $backend->setCache($cache);
        $data = 'some data' . microtime();
        $backend->set('TransientMemoryBackendTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('TransientMemoryBackendTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('TransientMemoryBackendTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTags(['UnitTestTag%special', 'UnitTestTag%boring']);
        self::assertFalse($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
        self::assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
        self::assertTrue($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $backend = new TransientMemoryBackend('Testing');
        $backend->setCache($cache);
        $data = 'some data' . microtime();
        $backend->set('TransientMemoryBackendTest1', $data);
        $backend->set('TransientMemoryBackendTest2', $data);
        $backend->set('TransientMemoryBackendTest3', $data);
        $backend->flush();
        self::assertFalse($backend->has('TransientMemoryBackendTest1'), 'TransientMemoryBackendTest1');
        self::assertFalse($backend->has('TransientMemoryBackendTest2'), 'TransientMemoryBackendTest2');
        self::assertFalse($backend->has('TransientMemoryBackendTest3'), 'TransientMemoryBackendTest3');
    }
}
