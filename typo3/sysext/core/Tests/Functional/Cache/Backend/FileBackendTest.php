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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileBackendTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/Foo/', true);
        parent::tearDown();
    }

    #[Test]
    public function setCacheDirectoryThrowsExceptionOnNonWritableDirectory(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1303669848);
        $subject = new FileBackend('');
        $subject->setCacheDirectory('http://localhost/');
        $subject->setCache(new NullFrontend('foo'));
    }

    #[Test]
    public function setCacheDirectoryAllowsAbsolutePathWithoutTrailingSlash(): void
    {
        $subject = $this->getAccessibleMock(FileBackend::class, null, [], '', false);
        $subject->setCacheDirectory('/tmp/foo');
        self::assertEquals('/tmp/foo/', $subject->_get('temporaryCacheDirectory'));
    }

    #[Test]
    public function setCacheDirectoryAllowsAbsolutePathWithTrailingSlash(): void
    {
        $subject = $this->getAccessibleMock(FileBackend::class, null, [], '', false);
        $subject->setCacheDirectory('/tmp/foo/');
        self::assertEquals('/tmp/foo/', $subject->_get('temporaryCacheDirectory'));
    }

    #[Test]
    public function setCacheDirectoryAllowsRelativePathWithoutTrailingSlash(): void
    {
        $subject = $this->getAccessibleMock(FileBackend::class, null, [], '', false);
        $subject->setCacheDirectory('tmp/foo');
        $path = Environment::getProjectPath();
        self::assertEquals($path . '/tmp/foo/', $subject->_get('temporaryCacheDirectory'));
    }

    #[Test]
    public function setCacheDirectoryAllowsRelativePathWithTrailingSlash(): void
    {
        $subject = $this->getAccessibleMock(FileBackend::class, null, [], '', false);
        $subject->setCacheDirectory('tmp/foo/');
        $path = Environment::getProjectPath();
        self::assertEquals($path . '/tmp/foo/', $subject->_get('temporaryCacheDirectory'));
    }

    #[Test]
    public function setCacheDirectoryAllowsRelativeDottedPathWithoutTrailingSlash(): void
    {
        $subject = $this->getAccessibleMock(FileBackend::class, null, [], '', false);
        $subject->setCacheDirectory('../tmp/foo');
        $path = Environment::getProjectPath();
        self::assertEquals($path . '/../tmp/foo/', $subject->_get('temporaryCacheDirectory'));
    }

    #[Test]
    public function setCacheDirectoryAllowsRelativeDottedPathWithTrailingSlash(): void
    {
        $subject = $this->getAccessibleMock(FileBackend::class, null, [], '', false);
        $subject->setCacheDirectory('../tmp/foo/');
        $path = Environment::getProjectPath();
        self::assertEquals($path . '/../tmp/foo/', $subject->_get('temporaryCacheDirectory'));
    }

    #[Test]
    public function setCacheDirectoryAllowsAbsoluteDottedPathWithoutTrailingSlash(): void
    {
        $subject = $this->getAccessibleMock(FileBackend::class, null, [], '', false);
        $subject->setCacheDirectory('/tmp/../foo');
        self::assertEquals('/tmp/../foo/', $subject->_get('temporaryCacheDirectory'));
    }

    #[Test]
    public function setCacheDirectoryAllowsAbsoluteDottedPathWithTrailingSlash(): void
    {
        $subject = $this->getAccessibleMock(FileBackend::class, null, [], '', false);
        $subject->setCacheDirectory('/tmp/../foo/');
        self::assertEquals('/tmp/../foo/', $subject->_get('temporaryCacheDirectory'));
    }

    #[Test]
    public function getCacheDirectoryReturnsTheCurrentCacheDirectory(): void
    {
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache(new NullFrontend('SomeCache'));
        self::assertEquals($this->instancePath . '/Foo/cache/code/SomeCache/', $subject->getCacheDirectory());
    }

    #[Test]
    public function aDedicatedCacheDirectoryIsUsedForCodeCaches(): void
    {
        $mockCache = $this->createMock(PhpFrontend::class);
        $mockCache->method('getIdentifier')->willReturn('SomeCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        self::assertEquals($this->instancePath . '/Foo/cache/code/SomeCache/', $subject->getCacheDirectory());
    }

    #[Test]
    public function setThrowsExceptionIfDataIsNotAString(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1204481674);
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache(new NullFrontend('SomeCache'));
        $subject->set('some identifier', ['not a string']);
    }

    #[Test]
    public function setReallySavesToTheSpecifiedDirectory(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = $this->instancePath . '/Foo/cache/data/UnitTestCache/' . $entryIdentifier;
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->set($entryIdentifier, $data);
        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, false, null, 0, \strlen($data));
        self::assertEquals($data, $retrievedData);
    }

    #[Test]
    public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $data1 = 'some data' . microtime();
        $data2 = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->set($entryIdentifier, $data1, [], 500);
        $subject->set($entryIdentifier, $data2, [], 200);
        $pathAndFilename = $this->instancePath . '/Foo/cache/data/UnitTestCache/' . $entryIdentifier;
        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, false, null, 0, \strlen($data2));
        self::assertEquals($data2, $retrievedData);
    }

    #[Test]
    public function setAlsoSavesSpecifiedTags(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->set($entryIdentifier, $data, ['Tag1', 'Tag2']);
        $pathAndFilename = $this->instancePath . '/Foo/cache/data/UnitTestCache/' . $entryIdentifier;
        self::assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, false, null, \strlen($data) + FileBackend::EXPIRYTIME_LENGTH, 9);
        self::assertEquals('Tag1 Tag2', $retrievedData);
    }

    #[Test]
    public function setCacheDetectsAndLoadsAFrozenCache(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->set($entryIdentifier, $data, ['Tag1', 'Tag2']);
        $subject->freeze();
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        self::assertTrue($subject->isFrozen());
        self::assertEquals($data, $subject->get($entryIdentifier));
    }

    #[Test]
    public function getReturnsContentOfTheCorrectCacheFile(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $entryIdentifier = 'BackendFileTest';
        $data = 'some data' . microtime();
        $subject->set($entryIdentifier, $data, [], 500);
        $data = 'some other data' . microtime();
        $subject->set($entryIdentifier, $data, [], 100);
        $loadedData = $subject->get($entryIdentifier);
        self::assertEquals($data, $loadedData);
    }

    #[Test]
    public function getReturnsFalseForExpiredEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = $this->getMockBuilder(FileBackend::class)->onlyMethods(['isCacheFileExpired'])->disableOriginalConstructor()->getMock();
        $subject->expects($this->once())->method('isCacheFileExpired')->with($this->instancePath . '/Foo/cache/data/UnitTestCache/ExpiredEntry')->willReturn(true);
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        self::assertFalse($subject->get('ExpiredEntry'));
    }

    #[Test]
    public function getDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = $this->getMockBuilder(FileBackend::class)->onlyMethods(['isCacheFileExpired'])->disableOriginalConstructor()->getMock();
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->expects($this->once())->method('isCacheFileExpired');
        $subject->set('foo', 'some data');
        $subject->freeze();
        self::assertEquals('some data', $subject->get('foo'));
        self::assertFalse($subject->get('bar'));
    }

    #[Test]
    public function hasReturnsTrueIfAnEntryExists(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $entryIdentifier = 'BackendFileTest';
        $data = 'some data' . microtime();
        $subject->set($entryIdentifier, $data);
        self::assertTrue($subject->has($entryIdentifier), 'has() did not return TRUE.');
        self::assertFalse($subject->has($entryIdentifier . 'Not'), 'has() did not return FALSE.');
    }

    #[Test]
    public function hasReturnsFalseForExpiredEntries(): void
    {
        $subject = $this->getMockBuilder(FileBackend::class)->onlyMethods(['isCacheFileExpired'])->disableOriginalConstructor()->getMock();
        $subject->expects($this->exactly(2))->method('isCacheFileExpired')->willReturn(true, false);
        self::assertFalse($subject->has('foo'));
        self::assertTrue($subject->has('bar'));
    }

    #[Test]
    public function hasDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = $this->getMockBuilder(FileBackend::class)->onlyMethods(['isCacheFileExpired'])->disableOriginalConstructor()->getMock();
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->expects($this->once())->method('isCacheFileExpired'); // Indirectly called by freeze() -> get()
        $subject->set('foo', 'some data');
        $subject->freeze();
        self::assertTrue($subject->has('foo'));
        self::assertFalse($subject->has('bar'));
    }

    #[Test]
    public function removeReallyRemovesACacheEntry(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = $this->instancePath . '/Foo/cache/data/UnitTestCache/' . $entryIdentifier;
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->set($entryIdentifier, $data);
        self::assertFileExists($pathAndFilename);
        $subject->remove($entryIdentifier);
        self::assertFileDoesNotExist($pathAndFilename);
    }

    public static function invalidEntryIdentifiers(): array
    {
        return [
            'trailing slash' => ['/myIdentifier'],
            'trailing dot and slash' => ['./myIdentifier'],
            'trailing two dots and slash' => ['../myIdentifier'],
            'trailing with multiple dots and slashes' => ['.././../myIdentifier'],
            'slash in middle part' => ['my/Identifier'],
            'dot and slash in middle part' => ['my./Identifier'],
            'two dots and slash in middle part' => ['my../Identifier'],
            'multiple dots and slashes in middle part' => ['my.././../Identifier'],
            'pending slash' => ['myIdentifier/'],
            'pending dot and slash' => ['myIdentifier./'],
            'pending dots and slash' => ['myIdentifier../'],
            'pending multiple dots and slashes' => ['myIdentifier.././../'],
        ];
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function setThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1282073032);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->set($identifier, 'cache data', []);
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function getThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1282073033);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->get($identifier);
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function hasThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1282073034);
        $subject = new FileBackend('');
        $subject->has($identifier);
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function removeThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1334756960);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->remove($identifier);
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function requireOnceThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1282073036);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->requireOnce($identifier);
    }

    #[Test]
    public function requireOnceIncludesAndReturnsResultOfIncludedPhpFile(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $entryIdentifier = 'SomePhpEntry';
        $data = '<?php return "foo"; ?>';
        $subject->set($entryIdentifier, $data);
        $loadedData = $subject->requireOnce($entryIdentifier);
        self::assertEquals('foo', $loadedData);
    }

    #[Test]
    public function requireOnceDoesNotCheckExpiryTimeIfBackendIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = $this->getMockBuilder(FileBackend::class)->onlyMethods(['isCacheFileExpired'])->disableOriginalConstructor()->getMock();
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->expects($this->once())->method('isCacheFileExpired'); // Indirectly called by freeze() -> get()
        $data = '<?php return "foo"; ?>';
        $subject->set('FooEntry', $data);
        $subject->freeze();
        $loadedData = $subject->requireOnce('FooEntry');
        self::assertEquals('foo', $loadedData);
    }

    #[DataProvider('invalidEntryIdentifiers')]
    #[Test]
    public function requireThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1532528246);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->require($identifier);
    }

    #[Test]
    public function requireIncludesAndReturnsResultOfIncludedPhpFile(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $entryIdentifier = 'SomePhpEntry2';
        $data = '<?php return "foo2"; ?>';
        $subject->set($entryIdentifier, $data);
        $loadedData = $subject->require($entryIdentifier);
        self::assertEquals('foo2', $loadedData);
    }

    #[Test]
    public function requireDoesNotCheckExpiryTimeIfBackendIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = $this->getMockBuilder(FileBackend::class)->onlyMethods(['isCacheFileExpired'])->disableOriginalConstructor()->getMock();
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->expects($this->once())->method('isCacheFileExpired'); // Indirectly called by freeze() -> get()
        $data = '<?php return "foo"; ?>';
        $subject->set('FooEntry2', $data);
        $subject->freeze();
        $loadedData = $subject->require('FooEntry2');
        self::assertEquals('foo', $loadedData);
    }

    #[Test]
    public function requireCanLoadSameEntryMultipleTimes(): void
    {
        $frontendMock = $this->getMockBuilder(AbstractFrontend::class)->disableOriginalConstructor()->getMock();
        $frontendMock->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('Testing');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($frontendMock);
        $subject->set('BarEntry', '<?php return "foo"; ?>');
        $loadedData = $subject->require('BarEntry');
        self::assertEquals('foo', $loadedData);
        $loadedData = $subject->require('BarEntry');
        self::assertEquals('foo', $loadedData);
    }

    #[Test]
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $data = 'some data' . microtime();
        $subject->set('BackendFileTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $subject->set('BackendFileTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $subject->set('BackendFileTest3', $data, ['UnitTestTag%test']);
        $expectedEntry = 'BackendFileTest2';
        $actualEntries = $subject->findIdentifiersByTag('UnitTestTag%special');
        self::assertEquals($expectedEntry, array_pop($actualEntries));
    }

    #[Test]
    public function findIdentifiersByTagDoesNotReturnExpiredEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $data = 'some data';
        $subject->set('BackendFileTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $subject->set('BackendFileTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special'], -100);
        $subject->set('BackendFileTest3', $data, ['UnitTestTag%test']);
        self::assertSame([], $subject->findIdentifiersByTag('UnitTestTag%special'));
        $actualEntries = $subject->findIdentifiersByTag('UnitTestTag%test');
        self::assertContains('BackendFileTest1', $actualEntries);
        self::assertContains('BackendFileTest3', $actualEntries);
    }

    #[Test]
    public function flushRemovesAllCacheEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $data = 'some data';
        $subject->set('BackendFileTest1', $data);
        $subject->set('BackendFileTest2', $data);
        self::assertFileExists($this->instancePath . '/Foo/cache/data/UnitTestCache/BackendFileTest1');
        self::assertFileExists($this->instancePath . '/Foo/cache/data/UnitTestCache/BackendFileTest2');
        $subject->flush();
        self::assertFileDoesNotExist($this->instancePath . '/Foo/cache/data/UnitTestCache/BackendFileTest1');
        self::assertFileDoesNotExist($this->instancePath . '/Foo/cache/data/UnitTestCache/BackendFileTest2');
    }

    #[Test]
    public function flushCreatesCacheDirectoryAgain(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->flush();
        self::assertFileExists($this->instancePath . '/Foo/cache/data/UnitTestCache/');
    }

    #[Test]
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag(): void
    {
        $subject = $this->getMockBuilder(FileBackend::class)->onlyMethods(['findIdentifiersByTag', 'remove'])->disableOriginalConstructor()->getMock();
        $subject->expects($this->once())->method('findIdentifiersByTag')->with('UnitTestTag%special')->willReturn([
            'foo',
            'bar',
            'baz',
        ]);
        $series = [
            ['foo'],
            ['bar'],
            ['baz'],
        ];
        $subject->expects($this->exactly(3))->method('remove')
            ->willReturnCallback(function (string $value) use (&$series): void {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $value);
            });
        $subject->flushByTag('UnitTestTag%special');
    }

    #[Test]
    public function collectGarbageRemovesExpiredCacheEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = $this->getMockBuilder(FileBackend::class)->onlyMethods(['isCacheFileExpired'])->disableOriginalConstructor()->getMock();
        $subject->expects($this->exactly(2))->method('isCacheFileExpired')->willReturnMap([
            [$this->instancePath . '/Foo/cache/data/UnitTestCache/BackendFileTest1', false],
            [$this->instancePath . '/Foo/cache/data/UnitTestCache/BackendFileTest2', true],
        ]);
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $data = 'some data';
        $subject->set('BackendFileTest1', $data);
        $subject->set('BackendFileTest2', $data);
        self::assertFileExists($this->instancePath . '/Foo/cache/data/UnitTestCache/BackendFileTest1');
        self::assertFileExists($this->instancePath . '/Foo/cache/data/UnitTestCache/BackendFileTest2');
        $subject->collectGarbage();
        self::assertFileExists($this->instancePath . '/Foo/cache/data/UnitTestCache/BackendFileTest1');
        self::assertFileDoesNotExist($this->instancePath . '/Foo/cache/data/UnitTestCache/BackendFileTest2');
    }

    #[Test]
    public function flushUnfreezesTheCache(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->willReturn('UnitTestCache');
        $subject = new FileBackend('');
        $subject->setCacheDirectory($this->instancePath . '/Foo/');
        $subject->setCache($mockCache);
        $subject->freeze();
        self::assertTrue($subject->isFrozen());
        $subject->flush();
        self::assertFalse($subject->isFrozen());
    }
}
