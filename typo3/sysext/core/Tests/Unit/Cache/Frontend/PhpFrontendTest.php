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
use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PhpFrontendTest extends UnitTestCase
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
        new PhpFrontend($identifier, $this->createMock(PhpCapableBackendInterface::class));
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
        new PhpFrontend($identifier, $this->createMock(PhpCapableBackendInterface::class));
    }

    #[Test]
    public function flushCallsBackend(): void
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects($this->once())->method('flush');
        $cache = new PhpFrontend('someCacheIdentifier', $backend);
        $cache->flush();
    }

    #[Test]
    public function flushByTagRejectsInvalidTags(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1233057359);
        $backend = $this->createMock(FileBackend::class);
        $backend->expects($this->never())->method('flushByTag');
        $cache = new PhpFrontend('someCacheIdentifier', $backend);
        $cache->flushByTag('SomeInvalid\\Tag');
    }

    #[Test]
    public function flushByTagCallsBackendIfItIsATaggableBackend(): void
    {
        $tag = 'someTag';
        $backend = $this->createMock(FileBackend::class);
        $backend->expects($this->once())->method('flushByTag')->with($tag);
        $cache = new PhpFrontend('someCacheIdentifier', $backend);
        $cache->flushByTag($tag);
    }

    #[Test]
    public function flushByTagsCallsBackendIfItIsATaggableBackend(): void
    {
        $tag = 'someTag';
        $backend = $this->createMock(FileBackend::class);
        $backend->expects($this->once())->method('flushByTags')->with([$tag]);
        $cache = new PhpFrontend('someCacheIdentifier', $backend);
        $cache->flushByTags([$tag]);
    }

    #[Test]
    public function collectGarbageCallsBackend(): void
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects($this->once())->method('collectGarbage');
        $cache = new PhpFrontend('someCacheIdentifier', $backend);
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
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $cache = new PhpFrontend('someCacheIdentifier', $backend);
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
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $cache = new PhpFrontend('someCacheIdentifier', $backend);
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
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $cache = new PhpFrontend('someCacheIdentifier', $backend);
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
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $cache = new PhpFrontend('someCacheIdentifier', $backend);
        self::assertTrue($cache->isValidTag($tag));
    }

    #[Test]
    public function setChecksIfTheIdentifierIsValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1264023823);
        $cache = new PhpFrontend('someCacheIdentifier', $this->createMock(PhpCapableBackendInterface::class));
        $cache->set('invalid identifier', 'bar');
    }

    #[Test]
    public function setPassesPhpSourceCodeTagsAndLifetimeToBackend(): void
    {
        $originalSourceCode = 'return "hello world!";';
        $modifiedSourceCode = '<?php' . chr(10) . $originalSourceCode . chr(10) . '#';
        $mockBackend = $this->createMock(PhpCapableBackendInterface::class);
        $mockBackend->expects($this->once())->method('set')->with('Foo-Bar', $modifiedSourceCode, ['tags'], 1234);
        $cache = new PhpFrontend('someCacheIdentifier', $mockBackend);
        $cache->set('Foo-Bar', $originalSourceCode, ['tags'], 1234);
    }

    #[Test]
    public function setThrowsInvalidDataExceptionOnNonStringValues(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1264023824);
        $cache = new PhpFrontend('someCacheIdentifier', $this->createMock(PhpCapableBackendInterface::class));
        $cache->set('Foo-Bar', []);
    }

    #[Test]
    public function requireOnceCallsTheBackendsRequireOnceMethod(): void
    {
        $mockBackend = $this->createMock(PhpCapableBackendInterface::class);
        $mockBackend->expects($this->once())->method('requireOnce')->with('Foo-Bar')->willReturn('hello world!');
        $cache = new PhpFrontend('someCacheIdentifier', $mockBackend);
        $result = $cache->requireOnce('Foo-Bar');
        self::assertSame('hello world!', $result);
    }

    #[Test]
    public function requireCallsTheBackendsRequireMethod(): void
    {
        $mockBackend = $this->createMock(SimpleFileBackend::class);
        $mockBackend->expects($this->once())->method('require')->with('Foo-Bar')->willReturn('hello world!');
        $cache = new PhpFrontend('someCacheIdentifier', $mockBackend);
        $result = $cache->require('Foo-Bar');
        self::assertSame('hello world!', $result);
    }
}
