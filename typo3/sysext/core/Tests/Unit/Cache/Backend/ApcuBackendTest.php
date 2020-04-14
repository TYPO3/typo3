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

use TYPO3\CMS\Core\Cache\Backend\ApcuBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the APCu cache backend.
 *
 * NOTE: If you want to execute these tests you need to enable apc in
 * cli context (apc.enable_cli = 1) and disable slam defense (apc.slam_defense = 0)
 */
class ApcuBackendTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        // APCu module is called apcu, but options are prefixed with apc
        if (!extension_loaded('apcu') || !(bool)ini_get('apc.enabled') || !(bool)ini_get('apc.enable_cli')) {
            self::markTestSkipped('APCu extension was not available, or it was disabled for CLI.');
        }
        if ((bool)ini_get('apc.slam_defense')) {
            self::markTestSkipped('This testcase can only be executed with apc.slam_defense = 0');
        }
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $backend = new ApcuBackend('Testing');
        $data = 'Some data';
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1232986118);
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
        self::assertTrue($backend->has($identifier));
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
        self::assertEquals($data, $fetchedData);
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
        self::assertFalse($backend->has($identifier));
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
        self::assertEquals($otherData, $fetchedData);
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
        self::assertEquals($identifier, $retrieved[0]);
        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        self::assertEquals($identifier, $retrieved[0]);
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
        self::assertEquals([], $retrieved);
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesNotExist()
    {
        $backend = $this->setUpBackend();
        $identifier = StringUtility::getUniqueId('NonExistingIdentifier');
        self::assertFalse($backend->has($identifier));
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = StringUtility::getUniqueId('NonExistingIdentifier');
        self::assertFalse($backend->remove($identifier));
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('BackendAPCUTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendAPCUTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendAPCUTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTag('UnitTestTag%special');
        self::assertTrue($backend->has('BackendAPCUTest1'));
        self::assertFalse($backend->has('BackendAPCUTest2'));
        self::assertTrue($backend->has('BackendAPCUTest3'));
    }

    /**
     * @test
     */
    public function flushByTagsRemovesCacheEntriesWithSpecifiedTags()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('BackendAPCUTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendAPCUTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendAPCUTest3', $data, ['UnitTestTag%test']);
        $backend->flushByTags(['UnitTestTag%special', 'UnitTestTag%boring']);
        self::assertFalse($backend->has('BackendAPCUTest1'), 'BackendAPCTest1');
        self::assertFalse($backend->has('BackendAPCUTest2'), 'BackendAPCTest2');
        self::assertTrue($backend->has('BackendAPCUTest3'), 'BackendAPCTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $backend = $this->setUpBackend();
        $data = 'some data' . microtime();
        $backend->set('BackendAPCUTest1', $data);
        $backend->set('BackendAPCUTest2', $data);
        $backend->set('BackendAPCUTest3', $data);
        $backend->flush();
        self::assertFalse($backend->has('BackendAPCUTest1'));
        self::assertFalse($backend->has('BackendAPCUTest2'));
        self::assertFalse($backend->has('BackendAPCUTest3'));
    }

    /**
     * @test
     */
    public function flushRemovesOnlyOwnEntries()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FrontendInterface $thisCache */
        $thisCache = $this->createMock(FrontendInterface::class);
        $thisCache->expects(self::any())->method('getIdentifier')->willReturn('thisCache');
        $thisBackend = new ApcuBackend('Testing');
        $thisBackend->setCache($thisCache);

        /** @var \PHPUnit\Framework\MockObject\MockObject|FrontendInterface $thatCache */
        $thatCache = $this->createMock(FrontendInterface::class);
        $thatCache->expects(self::any())->method('getIdentifier')->willReturn('thatCache');
        $thatBackend = new ApcuBackend('Testing');
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
     * Sets up the APCu backend used for testing
     *
     * @param bool $accessible TRUE if backend should be encapsulated in accessible proxy otherwise FALSE.
     * @return AccessibleObjectInterface|ApcuBackend
     */
    protected function setUpBackend($accessible = false)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FrontendInterface $cache */
        $cache = $this->createMock(FrontendInterface::class);
        if ($accessible) {
            $backend = $this->getAccessibleMock(ApcuBackend::class, ['dummy'], ['Testing']);
        } else {
            $backend = new ApcuBackend('Testing');
        }
        $backend->setCache($cache);
        return $backend;
    }
}
