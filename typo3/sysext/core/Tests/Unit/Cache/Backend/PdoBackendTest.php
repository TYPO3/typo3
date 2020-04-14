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

namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

use TYPO3\CMS\Core\Cache\Backend\PdoBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the PDO cache backend
 *
 * @requires extension pdo_sqlite
 */
class PdoBackendTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1259515600);

        $backend = new PdoBackend('Testing');
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        self::assertTrue($backend->has($identifier));
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetEntry()
    {
        $backend = $this->setUpBackend();
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
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        self::assertFalse($backend->has($identifier));
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
        $backend = $this->setUpBackend();
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
    public function findIdentifiersByTagFindsSetEntries()
    {
        $backend = $this->setUpBackend();
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
    public function setRemovesTagsFromPreviousSet()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $entryIdentifier = 'MyIdentifier';
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag3']);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        self::assertEquals([], $retrieved);
    }

    /**
     * @test
     */
    public function setOverwritesExistingEntryThatExceededItsLifetimeWithNewData()
    {
        $backend = $this->setUpBackend();
        $data1 = 'data1';
        $entryIdentifier = StringUtility::getUniqueId('test');
        $backend->set($entryIdentifier, $data1, [], 1);
        $data2 = 'data2';
        $GLOBALS['EXEC_TIME'] += 2;
        $backend->set($entryIdentifier, $data2, [], 10);
        self::assertEquals($data2, $backend->get($entryIdentifier));
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier';
        self::assertFalse($backend->has($identifier));
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier';
        self::assertFalse($backend->remove($identifier));
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('PdoBackendTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('PdoBackendTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('PdoBackendTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTag('UnitTestTag%special');
        self::assertTrue($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
        self::assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
        self::assertTrue($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
    }

    /**
     * @test
     */
    public function flushByTagsRemovesCacheEntriesWithSpecifiedTags()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('PdoBackendTest1', $data, ['UnitTestTag%test', 'UnitTestTags%boring']);
        $backend->set('PdoBackendTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('PdoBackendTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTags(['UnitTestTag%special', 'UnitTestTags%boring']);
        self::assertFalse($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
        self::assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
        self::assertTrue($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('PdoBackendTest1', $data);
        $backend->set('PdoBackendTest2', $data);
        $backend->set('PdoBackendTest3', $data);
        $backend->flush();
        self::assertFalse($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
        self::assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
        self::assertFalse($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
    }

    /**
     * @test
     */
    public function flushRemovesOnlyOwnEntries()
    {
        $thisCache = $this->createMock(FrontendInterface::class);
        $thisCache->expects(self::any())->method('getIdentifier')->willReturn('thisCache');
        $thisBackend = $this->setUpBackend();
        $thisBackend->setCache($thisCache);
        $thatCache = $this->createMock(FrontendInterface::class);
        $thatCache->expects(self::any())->method('getIdentifier')->willReturn('thatCache');
        $thatBackend = $this->setUpBackend();
        $thatBackend->setCache($thatCache);
        $thisBackend->set('thisEntry', 'Hello');
        $thatBackend->set('thatEntry', 'World!');
        $thatBackend->flush();
        self::assertEquals('Hello', $thisBackend->get('thisEntry'));
        self::assertFalse($thatBackend->has('thatEntry'));
    }

    /**
     * @test
     */
    public function collectGarbageReallyRemovesAnExpiredCacheEntry()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendPDORemovalTest';
        $backend->set($entryIdentifier, $data, [], 1);
        self::assertTrue($backend->has($entryIdentifier));
        $GLOBALS['EXEC_TIME'] += 2;
        $backend->collectGarbage();
        self::assertFalse($backend->has($entryIdentifier));
    }

    /**
     * @test
     */
    public function collectGarbageReallyRemovesAllExpiredCacheEntries()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendPDORemovalTest';
        $backend->set($entryIdentifier . 'A', $data, [], null);
        $backend->set($entryIdentifier . 'B', $data, [], 10);
        $backend->set($entryIdentifier . 'C', $data, [], 1);
        $backend->set($entryIdentifier . 'D', $data, [], 1);
        self::assertTrue($backend->has($entryIdentifier . 'A'));
        self::assertTrue($backend->has($entryIdentifier . 'B'));
        self::assertTrue($backend->has($entryIdentifier . 'C'));
        self::assertTrue($backend->has($entryIdentifier . 'D'));
        $GLOBALS['EXEC_TIME'] += 2;
        $backend->collectGarbage();
        self::assertTrue($backend->has($entryIdentifier . 'A'));
        self::assertTrue($backend->has($entryIdentifier . 'B'));
        self::assertFalse($backend->has($entryIdentifier . 'C'));
        self::assertFalse($backend->has($entryIdentifier . 'D'));
    }

    /**
     * Sets up the PDO backend used for testing
     *
     * @return \TYPO3\CMS\Core\Cache\Backend\PdoBackend
     */
    protected function setUpBackend()
    {
        $mockCache = $this->createMock(FrontendInterface::class);
        $mockCache->expects(self::any())->method('getIdentifier')->willReturn('TestCache');
        $backend = GeneralUtility::makeInstance(PdoBackend::class, 'Testing');
        $backend->setCache($mockCache);
        $backend->setDataSourceName('sqlite::memory:');
        $backend->initializeObject();
        return $backend;
    }
}
