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

namespace TYPO3\CMS\Core\Tests\Unit\Cache\Frontend;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\BackendInterface;
use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class VariableFrontendTest extends UnitTestCase
{
    public static function constructAcceptsValidIdentifiersDataProvider(): array
    {
        return [
            ['x'],
            ['someValue'],
            ['123fivesixseveneight'],
            ['some&'],
            ['ab_cd%'],
            [rawurlencode('resource://some/äöü$&% sadf')],
            [str_repeat('x', 250)],
        ];
    }

    #[Test]
    #[DataProvider('constructAcceptsValidIdentifiersDataProvider')]
    #[DoesNotPerformAssertions]
    public function constructAcceptsValidIdentifiers(string $identifier): void
    {
        new VariableFrontend($identifier, $this->createMock(BackendInterface::class));
    }

    public static function constructRejectsInvalidIdentifiersDataProvider(): array
    {
        return [
            [''],
            ['abc def'],
            ['foo!'],
            ['bar:'],
            ['some/'],
            ['bla*'],
            ['one+'],
            ['äöü'],
            [str_repeat('x', 251)],
            ['x$'],
            ['\\a'],
            ['b#'],
        ];
    }

    #[Test]
    #[DataProvider('constructRejectsInvalidIdentifiersDataProvider')]
    public function constructRejectsInvalidIdentifiers(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1203584729);
        new VariableFrontend($identifier, $this->createMock(BackendInterface::class));
    }

    #[Test]
    public function flushCallsBackend(): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects(self::once())->method('flush');
        $cache = new VariableFrontend('someCacheIdentifier', $backend);
        $cache->flush();
    }

    #[Test]
    public function flushByTagRejectsInvalidTags(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1233057359);
        $backend = $this->createMock(TaggableBackendInterface::class);
        $backend->expects(self::never())->method('flushByTag');
        $cache = new VariableFrontend('someCacheIdentifier', $backend);
        $cache->flushByTag('SomeInvalid\\Tag');
    }

    #[Test]
    public function flushByTagCallsBackendIfItIsATaggableBackend(): void
    {
        $tag = 'someTag';
        $backend = $this->createMock(TaggableBackendInterface::class);
        $backend->expects(self::once())->method('flushByTag')->with($tag);
        $cache = new VariableFrontend('someCacheIdentifier', $backend);
        $cache->flushByTag($tag);
    }

    #[Test]
    public function flushByTagsCallsBackendIfItIsATaggableBackend(): void
    {
        $tag = 'someTag';
        $backend = $this->createMock(TaggableBackendInterface::class);
        $backend->expects(self::once())->method('flushByTags')->with([$tag]);
        $cache = new VariableFrontend('someCacheIdentifier', $backend);
        $cache->flushByTags([$tag]);
    }

    #[Test]
    public function collectGarbageCallsBackend(): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects(self::once())->method('collectGarbage');
        $cache = new VariableFrontend('someCacheIdentifier', $backend);
        $cache->collectGarbage();
    }

    public static function isValidEntryIdentifierReturnsFalseWithValidIdentifierDataProvider(): array
    {
        return [
            [''],
            ['abc def'],
            ['foo!'],
            ['bar:'],
            ['some/'],
            ['bla*'],
            ['one+'],
            ['äöü'],
            [str_repeat('x', 251)],
            ['x$'],
            ['\\a'],
            ['b#'],
        ];
    }

    #[Test]
    #[DataProvider('isValidEntryIdentifierReturnsFalseWithValidIdentifierDataProvider')]
    public function isValidEntryIdentifierReturnsFalseWithValidIdentifier(string $identifier): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $cache = new VariableFrontend('someCacheIdentifier', $backend);
        self::assertFalse($cache->isValidEntryIdentifier($identifier));
    }

    public static function isValidEntryIdentifierReturnsTrueWithValidIdentifierDataProvider(): array
    {
        return [
            ['_'],
            ['abcdef'],
            ['foo'],
            ['bar123'],
            ['3some'],
            ['_bl_a'],
            ['some&'],
            ['one%TWO'],
            [str_repeat('x', 250)],
        ];
    }

    #[Test]
    #[DataProvider('isValidEntryIdentifierReturnsTrueWithValidIdentifierDataProvider')]
    public function isValidEntryIdentifierReturnsTrueWithValidIdentifier(string $identifier): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $cache = new VariableFrontend('someCacheIdentifier', $backend);
        self::assertTrue($cache->isValidEntryIdentifier($identifier));
    }

    public static function isValidTagReturnsFalseWithInvalidTagDataProvider(): array
    {
        return [
            [''],
            ['abc def'],
            ['foo!'],
            ['bar:'],
            ['some/'],
            ['bla*'],
            ['one+'],
            ['äöü'],
            [str_repeat('x', 251)],
            ['x$'],
            ['\\a'],
            ['b#'],
        ];
    }

    #[Test]
    #[DataProvider('isValidTagReturnsFalseWithInvalidTagDataProvider')]
    public function isValidTagReturnsFalseWithInvalidTag(string $tag): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $cache = new VariableFrontend('someCacheIdentifier', $backend);
        self::assertFalse($cache->isValidTag($tag));
    }

    public static function isValidTagReturnsTrueWithValidTagDataProvider(): array
    {
        return [
            ['abcdef'],
            ['foo-bar'],
            ['foo_baar'],
            ['bar123'],
            ['3some'],
            ['file%Thing'],
            ['some&'],
            ['%x%'],
            [str_repeat('x', 250)],
        ];
    }

    #[Test]
    #[DataProvider('isValidTagReturnsTrueWithValidTagDataProvider')]
    public function isValidTagReturnsTrueWithValidTag(string $tag): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $cache = new VariableFrontend('someCacheIdentifier', $backend);
        self::assertTrue($cache->isValidTag($tag));
    }

    #[Test]
    public function setChecksIfTheIdentifierIsValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1233058264);
        $cache = new VariableFrontend('someCacheIdentifier', $this->createMock(BackendInterface::class));
        $cache->set('invalid identifier', 'bar');
    }

    #[Test]
    public function setPassesSerializedStringToBackend(): void
    {
        $theString = 'Just some value';
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects(self::once())->method('set')->with('VariableCacheTest', serialize($theString));
        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString);
    }

    #[Test]
    public function setPassesSerializedArrayToBackend(): void
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects(self::once())->method('set')->with('VariableCacheTest', serialize($theArray));
        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theArray);
    }

    #[Test]
    public function setPassesLifetimeToBackend(): void
    {
        $theString = 'Just some value';
        $theLifetime = 1234;
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects(self::once())->method('set')->with('VariableCacheTest', serialize($theString), [], $theLifetime);
        $cache = new VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString, [], $theLifetime);
    }

    #[Test]
    public function getFetchesStringValueFromBackend(): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects(self::once())->method('get')->willReturn(serialize('Just some value'));
        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertEquals('Just some value', $cache->get('VariableCacheTest'));
    }

    #[Test]
    public function getFetchesArrayValueFromBackend(): void
    {
        $theArray = ['Just some value', 'and another one.'];
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects(self::once())->method('get')->willReturn(serialize($theArray));
        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertEquals($theArray, $cache->get('VariableCacheTest'));
    }

    #[Test]
    public function getFetchesFalseBooleanValueFromBackend(): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects(self::once())->method('get')->willReturn(serialize(false));
        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertFalse($cache->get('VariableCacheTest'));
    }

    #[Test]
    public function hasReturnsResultFromBackend(): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects(self::once())->method('has')->with(self::equalTo('VariableCacheTest'))->willReturn(true);
        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertTrue($cache->has('VariableCacheTest'));
    }

    #[Test]
    public function removeCallsBackend(): void
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects(self::once())->method('remove')->with(self::equalTo($cacheIdentifier))->willReturn(true);
        $cache = new VariableFrontend('VariableFrontend', $backend);
        self::assertTrue($cache->remove($cacheIdentifier));
    }
}
