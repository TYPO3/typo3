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

use TYPO3\CMS\Core\Cache\Backend\WincacheBackend;

/**
 * Testcase for the WinCache cache backend
 */
class WincacheBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Sets up this testcase
     *
     * @return void
     */
    protected function setUp()
    {
        if (!extension_loaded('wincache')) {
            $this->markTestSkipped('WinCache extension was not available');
        }
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $backend = new WincacheBackend('Testing');
        $data = 'Some data';
        $identifier = $this->getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
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
        $this->assertTrue($inCache, 'WinCache backend failed to set and check entry');
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
        $this->assertEquals($data, $fetchedData, 'Winache backend failed to set and retrieve data');
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
        $this->assertFalse($inCache, 'Failed to set and remove data from WinCache backend');
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
        $this->assertEquals($otherData, $fetchedData, 'WinCache backend failed to overwrite and retrieve data');
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsSetEntries()
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
        $backend->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tagX']);
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
        $backend->set('BackendWincacheTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendWincacheTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendWincacheTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTag('UnitTestTag%special');
        $this->assertTrue($backend->has('BackendWincacheTest1'), 'BackendWincacheTest1');
        $this->assertFalse($backend->has('BackendWincacheTest2'), 'BackendWincacheTest2');
        $this->assertTrue($backend->has('BackendWincacheTest3'), 'BackendWincacheTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('BackendWincacheTest1', $data);
        $backend->set('BackendWincacheTest2', $data);
        $backend->set('BackendWincacheTest3', $data);
        $backend->flush();
        $this->assertFalse($backend->has('BackendWincacheTest1'), 'BackendWincacheTest1');
        $this->assertFalse($backend->has('BackendWincacheTest2'), 'BackendWincacheTest2');
        $this->assertFalse($backend->has('BackendWincacheTest3'), 'BackendWincacheTest3');
    }

    /**
     * @test
     */
    public function flushRemovesOnlyOwnEntries()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $thisCache */
        $thisCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface::class, [], [], '', false);
        $thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
        $thisBackend = new WincacheBackend('Testing');
        $thisBackend->setCache($thisCache);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $thatCache */
        $thatCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface::class, [], [], '', false);
        $thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
        $thatBackend = new WincacheBackend('Testing');
        $thatBackend->setCache($thatCache);
        $thisBackend->set('thisEntry', 'Hello');
        $thatBackend->set('thatEntry', 'World!');
        $thatBackend->flush();
        $this->assertEquals('Hello', $thisBackend->get('thisEntry'));
        $this->assertFalse($thatBackend->has('thatEntry'));
    }

    /**
     * Check if we can store ~5 MB of data
     *
     * @test
     */
    public function largeDataIsStored()
    {
        $backend = $this->setUpBackend();
        $data = str_repeat('abcde', 1024 * 1024);
        $identifier = $this->getUniqueId('tooLargeData');
        $backend->set($identifier, $data);
        $this->assertTrue($backend->has($identifier));
        $this->assertEquals($backend->get($identifier), $data);
    }

    /**
     * @test
     */
    public function setTagsOnlyOnceToIdentifier()
    {
        $identifier = $this->getUniqueId('MyIdentifier');
        $tags = ['UnitTestTag%test', 'UnitTestTag%boring'];

        $backend = $this->setUpBackend(true);
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
     * Sets up the WinCache backend used for testing
     *
     * @param bool $accessible TRUE if backend should be encapsulated in accessible proxy otherwise FALSE.
     * @return \TYPO3\CMS\Core\Tests\AccessibleObjectInterface|WincacheBackend
     */
    protected function setUpBackend($accessible = false)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache */
        $cache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface::class, [], [], '', false);
        if ($accessible) {
            $accessibleClassName = $this->buildAccessibleProxy(WincacheBackend::class);
            $backend = new $accessibleClassName('Testing');
        } else {
            $backend = new WincacheBackend('Testing');
        }
        $backend->setCache($cache);
        return $backend;
    }
}
