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

namespace TYPO3\CMS\Core\Tests\Functional\Cache\Backend;

use TYPO3\CMS\Core\Cache\Backend\ApcuBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * NOTE: If you want to execute these tests you need to enable apc in
 * cli context (apc.enable_cli = 1) and disable slam defense (apc.slam_defense = 0)
 */
class ApcuBackendTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        // APCu module is called apcu, but options are prefixed with apc
        if (!extension_loaded('apcu') || !(bool)ini_get('apc.enabled') || !(bool)ini_get('apc.enable_cli')) {
            self::markTestSkipped('APCu extension was not available, or it was disabled for CLI.');
        }
        if (ini_get('apc.slam_defense')) {
            self::markTestSkipped('This testcase can only be executed with apc.slam_defense = 0');
        }
        parent::setUp();
    }

    protected function tearDown(): void
    {
        apcu_clear_cache();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet(): void
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
    public function itIsPossibleToSetAndCheckExistenceInCache(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
        $data = 'Some data';
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        self::assertTrue($backend->has($identifier));
    }

    public static function itIsPossibleToSetAndGetEntryDataProvider(): iterable
    {
        yield [ 5 ];
        yield [ 5.23 ];
        yield [ 'foo' ];
        yield [ false ];
        yield [ true ];
        yield [ null ];
        yield [ ['foo', 'bar'] ];
    }

    /**
     * @test
     * @dataProvider itIsPossibleToSetAndGetEntryDataProvider
     */
    public function itIsPossibleToSetAndGetEntry(mixed $data): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        self::assertSame($data, $backend->get($identifier));
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetObject(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $object = new \stdClass();
        $object->foo = 'foo';
        $backend->set($identifier, $object);
        $fetchedData = $backend->get($identifier);
        self::assertEquals($object, $fetchedData);
    }

    /**
     * @test
     */
    public function itIsPossibleToRemoveEntryFromCache(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
        $data = 'Some data';
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        self::assertFalse($backend->has($identifier));
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
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
    public function findIdentifiersByTagFindsSetEntries(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
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
    public function setRemovesTagsFromPreviousSet(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
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
    public function hasReturnsFalseIfTheEntryDoesNotExist(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
        $identifier = StringUtility::getUniqueId('NonExistingIdentifier');
        self::assertFalse($backend->has($identifier));
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
        $identifier = StringUtility::getUniqueId('NonExistingIdentifier');
        self::assertFalse($backend->remove($identifier));
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
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
    public function flushByTagsRemovesCacheEntriesWithSpecifiedTags(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
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
    public function flushRemovesAllCacheEntries(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
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
    public function flushRemovesOnlyOwnEntries(): void
    {
        $thisCache = $this->createMock(FrontendInterface::class);
        $thisCache->method('getIdentifier')->willReturn('thisCache');
        $thisBackend = new ApcuBackend('Testing');
        $thisBackend->setCache($thisCache);

        $thatCache = $this->createMock(FrontendInterface::class);
        $thatCache->method('getIdentifier')->willReturn('thatCache');
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
    public function largeDataIsStored(): void
    {
        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
        $data = str_repeat('abcde', 1024 * 1024);
        $identifier = StringUtility::getUniqueId('tooLargeData');
        $backend->set($identifier, $data);
        self::assertTrue($backend->has($identifier));
        self::assertEquals($backend->get($identifier), $data);
    }

    /**
     * @test
     */
    public function setTagsOnlyOnceToIdentifier(): void
    {
        $identifier = StringUtility::getUniqueId('MyIdentifier');
        $tags = ['UnitTestTag%test', 'UnitTestTag%boring'];

        $backend = new ApcuBackend('Testing');
        $backend->setCache($this->createMock(FrontendInterface::class));
        $backend->set($identifier, 'testData', $tags);
        $backend->set($identifier, 'testData', $tags);

        // Expect exactly 5 entries:
        // 1 for data, 3 for tag->identifier, 1 for identifier->tags
        self::assertSame(5, count(apcu_cache_info()['cache_list']));
    }
}
