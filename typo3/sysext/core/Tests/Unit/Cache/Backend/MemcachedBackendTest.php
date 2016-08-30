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

use TYPO3\CMS\Core\Cache\Backend\MemcachedBackend;

/**
 * Testcase for the cache to memcached backend
 *
 * This file is a backport from FLOW3
 */
class MemcachedBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Sets up this testcase
     *
     * @return void
     */
    protected function setUp()
    {
        if (!extension_loaded('memcache')) {
            $this->markTestSkipped('memcache extension was not available');
        }
        try {
            if (!@fsockopen('localhost', 11211)) {
                $this->markTestSkipped('memcached not reachable');
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('memcached not reachable');
        }
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $backendOptions = ['servers' => ['localhost:11211']];
        $backend = new MemcachedBackend('Testing', $backendOptions);
        $backend->initializeObject();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function initializeObjectThrowsExceptionIfNoMemcacheServerIsConfigured()
    {
        $backend = new MemcachedBackend('Testing');
        $backend->initializeObject();
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $inCache = $backend->has($identifier);
        $this->assertTrue($inCache, 'Memcache failed to set and check entry');
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetEntry()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $fetchedData = $backend->get($identifier);
        $this->assertEquals($data, $fetchedData, 'Memcache failed to set and retrieve data');
    }

    /**
     * @test
     */
    public function itIsPossibleToRemoveEntryFromCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        $inCache = $backend->has($identifier);
        $this->assertFalse($inCache, 'Failed to set and remove data from Memcache');
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $otherData = 'some other data';
        $backend->set($identifier, $otherData);
        $fetchedData = $backend->get($identifier);
        $this->assertEquals($otherData, $fetchedData, 'Memcache failed to overwrite and retrieve data');
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
        $this->assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        $this->assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
    }

    /**
     * @test
     */
    public function setRemovesTagsFromPreviousSet()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
        $backend->set($identifier, $data, ['UnitTestTag%tag3']);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tagX');
        $this->assertEquals([], $retrieved, 'Found entry which should no longer exist.');
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = $this->getUniqueId('NonExistingIdentifier');
        $inCache = $backend->has($identifier);
        $this->assertFalse($inCache, '"has" did not return FALSE when checking on non existing identifier');
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = $this->getUniqueId('NonExistingIdentifier');
        $inCache = $backend->remove($identifier);
        $this->assertFalse($inCache, '"remove" did not return FALSE when checking on non existing identifier');
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('BackendMemcacheTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendMemcacheTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendMemcacheTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTag('UnitTestTag%special');
        $this->assertTrue($backend->has('BackendMemcacheTest1'), 'BackendMemcacheTest1');
        $this->assertFalse($backend->has('BackendMemcacheTest2'), 'BackendMemcacheTest2');
        $this->assertTrue($backend->has('BackendMemcacheTest3'), 'BackendMemcacheTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('BackendMemcacheTest1', $data);
        $backend->set('BackendMemcacheTest2', $data);
        $backend->set('BackendMemcacheTest3', $data);
        $backend->flush();
        $this->assertFalse($backend->has('BackendMemcacheTest1'), 'BackendMemcacheTest1');
        $this->assertFalse($backend->has('BackendMemcacheTest2'), 'BackendMemcacheTest2');
        $this->assertFalse($backend->has('BackendMemcacheTest3'), 'BackendMemcacheTest3');
    }

    /**
     * @test
     */
    public function flushRemovesOnlyOwnEntries()
    {
        $backendOptions = ['servers' => ['localhost:11211']];
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $thisCache */
        $thisCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
        $thisBackend = new MemcachedBackend('Testing', $backendOptions);
        $thisBackend->setCache($thisCache);
        $thisBackend->initializeObject();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $thatCache */
        $thatCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
        $thatBackend = new MemcachedBackend('Testing', $backendOptions);
        $thatBackend->setCache($thatCache);
        $thatBackend->initializeObject();
        $thisBackend->set('thisEntry', 'Hello');
        $thatBackend->set('thatEntry', 'World!');
        $thatBackend->flush();
        $this->assertEquals('Hello', $thisBackend->get('thisEntry'));
        $this->assertFalse($thatBackend->has('thatEntry'));
    }

    /**
     * Check if we can store ~5 MB of data, this gives some headroom for the
     * reflection data.
     *
     * @test
     */
    public function largeDataIsStored()
    {
        $backend = $this->setUpBackend();
        $data = str_repeat('abcde', 1024 * 1024);
        $backend->set('tooLargeData', $data);
        $this->assertTrue($backend->has('tooLargeData'));
        $this->assertEquals($backend->get('tooLargeData'), $data);
    }

    /**
     * @test
     */
    public function setTagsOnlyOnceToIdentifier()
    {
        $backendOptions = ['servers' => ['localhost:11211']];
        $identifier = $this->getUniqueId('MyIdentifier');
        $tags = ['UnitTestTag%test', 'UnitTestTag%boring'];

        $backend = $this->setUpBackend($backendOptions, true);
        $backend->_call('addIdentifierToTags', $identifier, $tags);
        $this->assertSame(
            $tags,
            $backend->_call('findTagsByIdentifier', $identifier)
        );

        $backend->_call('addIdentifierToTags', $identifier, $tags);
        $this->assertSame(
            $tags,
            $backend->_call('findTagsByIdentifier', $identifier)
        );
    }

    /**
     * Sets up the memcached backend used for testing
     *
     * @param array $backendOptions Options for the memcache backend
     * @param bool $accessible TRUE if backend should be encapsulated in accessible proxy otherwise FALSE.
     * @return \TYPO3\CMS\Core\Tests\AccessibleObjectInterface|MemcachedBackend
     */
    protected function setUpBackend(array $backendOptions = [], $accessible = false)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache */
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface::class, [], [], '', false);
        if ($backendOptions == []) {
            $backendOptions = ['servers' => ['localhost:11211']];
        }
        if ($accessible) {
            $accessibleClassName = $this->buildAccessibleProxy(MemcachedBackend::class);
            $backend = new $accessibleClassName('Testing', $backendOptions);
        } else {
            $backend = new MemcachedBackend('Testing', $backendOptions);
        }
        $backend->setCache($cache);
        $backend->initializeObject();
        return $backend;
    }
}
