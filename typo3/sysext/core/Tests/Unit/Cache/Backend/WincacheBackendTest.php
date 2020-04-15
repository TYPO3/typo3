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

use TYPO3\CMS\Core\Cache\Backend\WincacheBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the WinCache cache backend
 *
 * @requires extension wincache
 */
class WincacheBackendTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $this->expectException(Exception::class);
        //@todo Add exception code with wincache extension

        $backend = new WincacheBackend('Testing');
        $data = 'Some data';
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $inCache = $backend->has($identifier);
        self::assertTrue($inCache, 'WinCache backend failed to set and check entry');
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetEntry()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $fetchedData = $backend->get($identifier);
        self::assertEquals($data, $fetchedData, 'Wincache backend failed to set and retrieve data');
    }

    /**
     * @test
     */
    public function itIsPossibleToRemoveEntryFromCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        $inCache = $backend->has($identifier);
        self::assertFalse($inCache, 'Failed to set and remove data from WinCache backend');
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $otherData = 'some other data';
        $backend->set($identifier, $otherData);
        $fetchedData = $backend->get($identifier);
        self::assertEquals($otherData, $fetchedData, 'WinCache backend failed to overwrite and retrieve data');
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsSetEntries()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $backend->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
        self::assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        self::assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
    }

    /**
     * @test
     */
    public function setRemovesTagsFromPreviousSet()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $backend->set($identifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tagX']);
        $backend->set($identifier, $data, ['UnitTestTag%tag3']);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tagX');
        self::assertEquals([], $retrieved, 'Found entry which should no longer exist.');
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = StringUtility::getUniqueId('NonExistingIdentifier');
        $inCache = $backend->has($identifier);
        self::assertFalse($inCache, '"has" did not return FALSE when checking on non existing identifier');
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = StringUtility::getUniqueId('NonExistingIdentifier');
        $inCache = $backend->remove($identifier);
        self::assertFalse($inCache, '"remove" did not return FALSE when checking on non existing identifier');
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
        self::assertTrue($backend->has('BackendWincacheTest1'), 'BackendWincacheTest1');
        self::assertFalse($backend->has('BackendWincacheTest2'), 'BackendWincacheTest2');
        self::assertTrue($backend->has('BackendWincacheTest3'), 'BackendWincacheTest3');
    }

    /**
     * @test
     */
    public function flushByTagsRemovesCacheEntriesWithSpecifiedTags()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('BackendWincacheTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendWincacheTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendWincacheTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTag('UnitTestTag%special', 'UnitTestTag%boring');
        self::assertTrue($backend->has('BackendWincacheTest1'), 'BackendWincacheTest1');
        self::assertFalse($backend->has('BackendWincacheTest2'), 'BackendWincacheTest2');
        self::assertTrue($backend->has('BackendWincacheTest3'), 'BackendWincacheTest3');
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
        self::assertFalse($backend->has('BackendWincacheTest1'), 'BackendWincacheTest1');
        self::assertFalse($backend->has('BackendWincacheTest2'), 'BackendWincacheTest2');
        self::assertFalse($backend->has('BackendWincacheTest3'), 'BackendWincacheTest3');
    }

    /**
     * @test
     */
    public function flushRemovesOnlyOwnEntries()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $thisCache */
        $thisCache = $this->createMock(FrontendInterface::class);
        $thisCache->expects(self::any())->method('getIdentifier')->willReturn('thisCache');
        $thisBackend = new WincacheBackend('Testing');
        $thisBackend->setCache($thisCache);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $thatCache */
        $thatCache = $this->createMock(FrontendInterface::class);
        $thatCache->expects(self::any())->method('getIdentifier')->willReturn('thatCache');
        $thatBackend = new WincacheBackend('Testing');
        $thatBackend->setCache($thatCache);
        $thisBackend->set('thisEntry', 'Hello');
        $thatBackend->set('thatEntry', 'World!');
        $thatBackend->flush();
        self::assertEquals('Hello', $thisBackend->get('thisEntry'));
        self::assertFalse($thatBackend->has('thatEntry'));
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
        $identifier = StringUtility::getUniqueId('tooLargeData');
        $backend->set($identifier, $data);
        self::assertTrue($backend->has($identifier));
        self::assertEquals($backend->get($identifier), $data);
    }

    /**
     * @test
     */
    public function setTagsOnlyOnceToIdentifier()
    {
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $tags = ['UnitTestTag%test', 'UnitTestTag%boring'];

        $backend = $this->setUpBackend(true);
        $backend->_call('addIdentifierToTags', $identifier, $tags);
        self::assertSame(
            $tags,
            $backend->_call('findTagsByIdentifier', $identifier)
        );

        $backend->_call('addIdentifierToTags', $identifier, $tags);
        self::assertSame(
            $tags,
            $backend->_call('findTagsByIdentifier', $identifier)
        );
    }

    /**
     * Sets up the WinCache backend used for testing
     *
     * @param bool $accessible TRUE if backend should be encapsulated in accessible proxy otherwise FALSE.
     * @return \TYPO3\TestingFramework\Core\AccessibleObjectInterface|WincacheBackend
     */
    protected function setUpBackend($accessible = false)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache */
        $cache = $this->createMock(FrontendInterface::class);
        if ($accessible) {
            $backend = $this->getAccessibleMock(WincacheBackend::class, ['dummy'], ['Testing']);
        } else {
            $backend = new WincacheBackend('Testing');
        }
        $backend->setCache($cache);
        return $backend;
    }
}
