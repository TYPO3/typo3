<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

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
 * Testcase for the PDO cache backend
 */
class PdoBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Sets up this testcase
     */
    protected function setUp()
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension was not available');
        }
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $backend = new \TYPO3\CMS\Core\Cache\Backend\PdoBackend('Testing');
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
        $this->assertTrue($backend->has($identifier));
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
        $this->assertEquals($data, $fetchedData);
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
        $this->assertFalse($backend->has($identifier));
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
        $this->assertEquals($otherData, $fetchedData);
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
        $this->assertEquals($entryIdentifier, $retrieved[0]);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        $this->assertEquals($entryIdentifier, $retrieved[0]);
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
        $this->assertEquals([], $retrieved);
    }

    /**
     * @test
     */
    public function setOverwritesExistingEntryThatExceededItsLifetimeWithNewData()
    {
        $backend = $this->setUpBackend();
        $data1 = 'data1';
        $entryIdentifier = $this->getUniqueId('test');
        $backend->set($entryIdentifier, $data1, [], 1);
        $data2 = 'data2';
        $GLOBALS['EXEC_TIME'] += 2;
        $backend->set($entryIdentifier, $data2, [], 10);
        $this->assertEquals($data2, $backend->get($entryIdentifier));
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier';
        $this->assertFalse($backend->has($identifier));
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier';
        $this->assertFalse($backend->remove($identifier));
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
        $this->assertTrue($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
        $this->assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
        $this->assertTrue($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
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
        $this->assertFalse($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
        $this->assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
        $this->assertFalse($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
    }

    /**
     * @test
     */
    public function flushRemovesOnlyOwnEntries()
    {
        $thisCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface::class, [], [], '', false);
        $thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
        $thisBackend = $this->setUpBackend();
        $thisBackend->setCache($thisCache);
        $thatCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface::class, [], [], '', false);
        $thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
        $thatBackend = $this->setUpBackend();
        $thatBackend->setCache($thatCache);
        $thisBackend->set('thisEntry', 'Hello');
        $thatBackend->set('thatEntry', 'World!');
        $thatBackend->flush();
        $this->assertEquals('Hello', $thisBackend->get('thisEntry'));
        $this->assertFalse($thatBackend->has('thatEntry'));
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
        $this->assertTrue($backend->has($entryIdentifier));
        $GLOBALS['EXEC_TIME'] += 2;
        $backend->collectGarbage();
        $this->assertFalse($backend->has($entryIdentifier));
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
        $this->assertTrue($backend->has($entryIdentifier . 'A'));
        $this->assertTrue($backend->has($entryIdentifier . 'B'));
        $this->assertTrue($backend->has($entryIdentifier . 'C'));
        $this->assertTrue($backend->has($entryIdentifier . 'D'));
        $GLOBALS['EXEC_TIME'] += 2;
        $backend->collectGarbage();
        $this->assertTrue($backend->has($entryIdentifier . 'A'));
        $this->assertTrue($backend->has($entryIdentifier . 'B'));
        $this->assertFalse($backend->has($entryIdentifier . 'C'));
        $this->assertFalse($backend->has($entryIdentifier . 'D'));
    }

    /**
     * Sets up the PDO backend used for testing
     *
     * @return \TYPO3\CMS\Core\Cache\Backend\PdoBackend
     */
    protected function setUpBackend()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface::class, [], [], '', false);
        $mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('TestCache'));
        $backend = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\Backend\PdoBackend::class, 'Testing');
        $backend->setCache($mockCache);
        $backend->setDataSourceName('sqlite::memory:');
        $backend->initializeObject();
        return $backend;
    }
}
