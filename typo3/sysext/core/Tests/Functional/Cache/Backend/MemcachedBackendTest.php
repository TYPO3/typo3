<?php
namespace TYPO3\CMS\Core\Tests\Functional\Cache\Backend;

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
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class MemcachedBackendTest extends FunctionalTestCase
{

    /**
     * Sets up this test case
     */
    protected function setUp()
    {
        parent::setUp();
        if (!extension_loaded('memcache') && !extension_loaded('memcached')) {
            $this->markTestSkipped('Neither "memcache" nor "memcached" extension was available');
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
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1207149215);

        $subject->set($this->getUniqueId('MyIdentifier'), 'some data');
    }

    /**
     * @test
     */
    public function initializeObjectThrowsExceptionIfNoMemcacheServerIsConfigured()
    {
        $subject = new MemcachedBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1213115903);
        $subject->initializeObject();
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $identifier = $this->getUniqueId('MyIdentifier');
        $subject->set($identifier, 'Some data');
        $this->assertTrue($subject->has($identifier));
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $subject->set($identifier, $data);
        $this->assertEquals($data, $subject->get($identifier));
    }

    /**
     * @test
     */
    public function getReturnsPreviouslySetDataWithVariousTypes()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $data = [
            'string' => 'Serialize a string',
            'integer' => 0,
            'anotherIntegerValue' => 123456,
            'float' => 12.34,
            'bool' => true,
            'array' => [
                0 => 'test',
                1 => 'another test',
            ],
        ];

        $subject->set('myIdentifier', $data);
        $this->assertSame($data, $subject->get('myIdentifier'));
    }

    /**
     * Check if we can store ~5 MB of data.
     *
     * @test
     */
    public function largeDataIsStored()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $data = str_repeat('abcde', 1024 * 1024);
        $subject->set('tooLargeData', $data);
        $this->assertTrue($subject->has('tooLargeData'));
        $this->assertEquals($subject->get('tooLargeData'), $data);
    }

    /**
     * @test
     */
    public function itIsPossibleToRemoveEntryFromCache()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $subject->set($identifier, $data);
        $subject->remove($identifier);
        $this->assertFalse($subject->has($identifier));
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $subject->set($identifier, $data);
        $otherData = 'some other data';
        $subject->set($identifier, $otherData);
        $this->assertEquals($otherData, $subject->get($identifier));
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $subject->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
        $retrieved = $subject->findIdentifiersByTag('UnitTestTag%tag1');
        $this->assertEquals($identifier, $retrieved[0]);
        $retrieved = $subject->findIdentifiersByTag('UnitTestTag%tag2');
        $this->assertEquals($identifier, $retrieved[0]);
    }

    /**
     * @test
     */
    public function setRemovesTagsFromPreviousSet()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $subject->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
        $subject->set($identifier, $data, ['UnitTestTag%tag3']);
        $this->assertEquals([], $subject->findIdentifiersByTag('UnitTestTag%tagX'));
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $identifier = $this->getUniqueId('NonExistingIdentifier');
        $this->assertFalse($subject->has($identifier));
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $identifier = $this->getUniqueId('NonExistingIdentifier');
        $this->assertFalse($subject->remove($identifier));
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $data = 'some data' . microtime();
        $subject->set('BackendMemcacheTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $subject->set('BackendMemcacheTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $subject->set('BackendMemcacheTest3', $data, ['UnitTestTag%test']);
        $subject->flushByTag('UnitTestTag%special');
        $this->assertTrue($subject->has('BackendMemcacheTest1'));
        $this->assertFalse($subject->has('BackendMemcacheTest2'));
        $this->assertTrue($subject->has('BackendMemcacheTest3'));
    }

    /**
     * @test
     */
    public function flushByTagsRemovesCacheEntriesWithSpecifiedTags()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $data = 'some data' . microtime();
        $subject->set('BackendMemcacheTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $subject->set('BackendMemcacheTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $subject->set('BackendMemcacheTest3', $data, ['UnitTestTag%test']);
        $subject->flushByTags(['UnitTestTag%special', 'UnitTestTag%boring']);
        $this->assertFalse($subject->has('BackendMemcacheTest1'));
        $this->assertFalse($subject->has('BackendMemcacheTest2'));
        $this->assertTrue($subject->has('BackendMemcacheTest3'));
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $subject->initializeObject();
        $subject->setCache($frontendProphecy->reveal());

        $data = 'some data' . microtime();
        $subject->set('BackendMemcacheTest1', $data);
        $subject->set('BackendMemcacheTest2', $data);
        $subject->set('BackendMemcacheTest3', $data);
        $subject->flush();
        $this->assertFalse($subject->has('BackendMemcacheTest1'));
        $this->assertFalse($subject->has('BackendMemcacheTest2'));
        $this->assertFalse($subject->has('BackendMemcacheTest3'));
    }

    /**
     * @test
     */
    public function flushRemovesOnlyOwnEntries()
    {
        $thisFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $thisFrontendProphecy->getIdentifier()->willReturn('thisCache');
        $thisBackend = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $thisBackend->initializeObject();
        $thisBackend->setCache($thisFrontendProphecy->reveal());

        $thatFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $thatFrontendProphecy->getIdentifier()->willReturn('thatCache');
        $thatBackend = new MemcachedBackend('Testing', [ 'servers' => ['localhost:11211'] ]);
        $thatBackend->initializeObject();
        $thatBackend->setCache($thatFrontendProphecy->reveal());

        $thisBackend->set('thisEntry', 'Hello');
        $thatBackend->set('thatEntry', 'World!');
        $thatBackend->flush();

        $this->assertEquals('Hello', $thisBackend->get('thisEntry'));
        $this->assertFalse($thatBackend->has('thatEntry'));
    }
}
