<?php
declare(strict_types = 1);

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

use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the File cache backend
 */
class FileBackendTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * Sets up this testcase
     * @throws \org\bovigo\vfs\vfsStreamException
     */
    protected function setUp(): void
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('Foo'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function setCacheDirectoryThrowsExceptionOnNonWritableDirectory(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1303669848);

        $mockCache = $this->createMock(AbstractFrontend::class);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('http://localhost/');

        $backend->setCache($mockCache);
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsAbsolutePathWithoutTrailingSlash(): void
    {
        $backend = $this->getAccessibleMock(FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('/tmp/foo');
        $this->assertEquals('/tmp/foo/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsAbsolutePathWithTrailingSlash(): void
    {
        $backend = $this->getAccessibleMock(FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('/tmp/foo/');
        $this->assertEquals('/tmp/foo/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsRelativePathWithoutTrailingSlash(): void
    {
        $backend = $this->getAccessibleMock(FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('tmp/foo');
        $path = Environment::getProjectPath();
        $this->assertEquals($path . '/tmp/foo/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsRelativePathWithTrailingSlash(): void
    {
        $backend = $this->getAccessibleMock(FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('tmp/foo/');
        $path = Environment::getProjectPath();
        $this->assertEquals($path . '/tmp/foo/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsRelativeDottedPathWithoutTrailingSlash(): void
    {
        $backend = $this->getAccessibleMock(FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('../tmp/foo');
        $path = Environment::getProjectPath();
        $this->assertEquals($path . '/../tmp/foo/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsRelativeDottedPathWithTrailingSlash(): void
    {
        $backend = $this->getAccessibleMock(FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('../tmp/foo/');
        $path = Environment::getProjectPath();
        $this->assertEquals($path . '/../tmp/foo/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsAbsoluteDottedPathWithoutTrailingSlash(): void
    {
        $backend = $this->getAccessibleMock(FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('/tmp/../foo');
        $this->assertEquals('/tmp/../foo/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsAbsoluteDottedPathWithTrailingSlash(): void
    {
        $backend = $this->getAccessibleMock(FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('/tmp/../foo/');
        $this->assertEquals('/tmp/../foo/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getCacheDirectoryReturnsTheCurrentCacheDirectory(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $this->assertEquals('vfs://Foo/cache/data/SomeCache/', $backend->getCacheDirectory());
    }

    /**
     * @test
     * @throws Exception
     */
    public function aDedicatedCacheDirectoryIsUsedForCodeCaches(): void
    {
        $mockCache = $this->createMock(PhpFrontend::class);
        $mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $this->assertEquals('vfs://Foo/cache/code/SomeCache/', $backend->getCacheDirectory());
    }

    /**
     * @test
     * @throws Exception
     */
    public function setThrowsExceptionIfDataIsNotAString(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1204481674);

        $mockCache = $this->createMock(AbstractFrontend::class);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set('some identifier', ['not a string']);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setReallySavesToTheSpecifiedDirectory(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = 'vfs://Foo/cache/data/UnitTestCache/' . $entryIdentifier;

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data);

        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, false, null, 0, \strlen($data));
        $this->assertEquals($data, $retrievedData);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data1 = 'some data' . microtime();
        $data2 = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data1, [], 500);
        $backend->set($entryIdentifier, $data2, [], 200);

        $pathAndFilename = 'vfs://Foo/cache/data/UnitTestCache/' . $entryIdentifier;
        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, false, null, 0, \strlen($data2));
        $this->assertEquals($data2, $retrievedData);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setAlsoSavesSpecifiedTags(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data, ['Tag1', 'Tag2']);

        $pathAndFilename = 'vfs://Foo/cache/data/UnitTestCache/' . $entryIdentifier;
        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents(
            $pathAndFilename,
            false,
            null,
            \strlen($data) + FileBackend::EXPIRYTIME_LENGTH,
            9
        );
        $this->assertEquals('Tag1 Tag2', $retrievedData);
    }

    /**
     * @test
     * @throws Exception
     */
    public function setCacheDetectsAndLoadsAFrozenCache(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data, ['Tag1', 'Tag2']);

        $backend->freeze();

        unset($backend);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $this->assertTrue($backend->isFrozen());
        $this->assertEquals($data, $backend->get($entryIdentifier));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getReturnsContentOfTheCorrectCacheFile(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['setTag'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $entryIdentifier = 'BackendFileTest';

        $data = 'some data' . microtime();
        $backend->set($entryIdentifier, $data, [], 500);

        $data = 'some other data' . microtime();
        $backend->set($entryIdentifier, $data, [], 100);

        $loadedData = $backend->get($entryIdentifier);
        $this->assertEquals($data, $loadedData);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getReturnsFalseForExpiredEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['isCacheFileExpired'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->once())->method('isCacheFileExpired')->with('vfs://Foo/cache/data/UnitTestCache/ExpiredEntry')->will($this->returnValue(true));
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $this->assertFalse($backend->get('ExpiredEntry'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function getDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['isCacheFileExpired'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->expects($this->once())->method('isCacheFileExpired');

        $backend->set('foo', 'some data');
        $backend->freeze();
        $this->assertEquals('some data', $backend->get('foo'));
        $this->assertFalse($backend->get('bar'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function hasReturnsTrueIfAnEntryExists(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $entryIdentifier = 'BackendFileTest';

        $data = 'some data' . microtime();
        $backend->set($entryIdentifier, $data);

        $this->assertTrue($backend->has($entryIdentifier), 'has() did not return TRUE.');
        $this->assertFalse($backend->has($entryIdentifier . 'Not'), 'has() did not return FALSE.');
    }

    /**
     * @test
     */
    public function hasReturnsFalseForExpiredEntries(): void
    {
        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['isCacheFileExpired'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(
            true,
            false
        ));

        $this->assertFalse($backend->has('foo'));
        $this->assertTrue($backend->has('bar'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function hasDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['isCacheFileExpired'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->expects($this->once())->method('isCacheFileExpired'); // Indirectly called by freeze() -> get()

        $backend->set('foo', 'some data');
        $backend->freeze();
        $this->assertTrue($backend->has('foo'));
        $this->assertFalse($backend->has('bar'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function removeReallyRemovesACacheEntry(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = 'vfs://Foo/cache/data/UnitTestCache/' . $entryIdentifier;

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data);
        $this->assertFileExists($pathAndFilename);

        $backend->remove($entryIdentifier);
        $this->assertFileNotExists($pathAndFilename);
    }

    /**
     */
    public function invalidEntryIdentifiers(): array
    {
        return [
            'trailing slash' => ['/myIdentifer'],
            'trailing dot and slash' => ['./myIdentifer'],
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

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @param string $identifier
     * @throws Exception
     * @throws InvalidDataException
     */
    public function setThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1282073032);

        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->setConstructorArgs(['test'])
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($identifier, 'cache data', []);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @param string $identifier
     * @throws Exception
     */
    public function getThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1282073033);

        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->get($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @param string $identifier
     */
    public function hasThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1282073034);

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();

        $backend->has($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @param string $identifier
     * @throws Exception
     */
    public function removeThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1282073035);

        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->remove($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @param string $identifier
     * @throws Exception
     */
    public function requireOnceThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1282073036);

        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->requireOnce($identifier);
    }

    /**
     * @test
     * @throws Exception
     */
    public function requireOnceIncludesAndReturnsResultOfIncludedPhpFile(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntry';

        $data = '<?php return "foo"; ?>';
        $backend->set($entryIdentifier, $data);

        $loadedData = $backend->requireOnce($entryIdentifier);
        $this->assertEquals('foo', $loadedData);
    }

    /**
     * @test
     * @throws Exception
     */
    public function requireOnceDoesNotCheckExpiryTimeIfBackendIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['isCacheFileExpired'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->expects($this->once())->method('isCacheFileExpired'); // Indirectly called by freeze() -> get()

        $data = '<?php return "foo"; ?>';
        $backend->set('FooEntry', $data);

        $backend->freeze();

        $loadedData = $backend->requireOnce('FooEntry');
        $this->assertEquals('foo', $loadedData);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @param string $identifier
     */
    public function requireThrowsExceptionForInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1532528246);
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->require($identifier);
    }

    /**
     * @test
     */
    public function requireIncludesAndReturnsResultOfIncludedPhpFile(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));
        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $entryIdentifier = 'SomePhpEntry2';

        $data = '<?php return "foo2"; ?>';
        $backend->set($entryIdentifier, $data);

        $loadedData = $backend->require($entryIdentifier);
        $this->assertEquals('foo2', $loadedData);
    }

    /**
     * @test
     */
    public function requireDoesNotCheckExpiryTimeIfBackendIsFrozen(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));
        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['isCacheFileExpired'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->expects($this->once())->method('isCacheFileExpired'); // Indirectly called by freeze() -> get()

        $data = '<?php return "foo"; ?>';
        $backend->set('FooEntry2', $data);

        $backend->freeze();

        $loadedData = $backend->require('FooEntry2');
        $this->assertEquals('foo', $loadedData);
    }

    /**
     * @test
     */
    public function requireCanLoadSameEntryMultipleTimes(): void
    {
        $frontendProphecy = $this->prophesize(AbstractFrontend::class);
        $frontendProphecy->getIdentifier()->willReturn('UnitTestCache');
        $subject = new FileBackend('Testing');
        $subject->setCacheDirectory('vfs://Foo/');
        $subject->setCache($frontendProphecy->reveal());
        $subject->set('BarEntry', '<?php return "foo"; ?>');
        $loadedData = $subject->require('BarEntry');
        $this->assertEquals('foo', $loadedData);
        $loadedData = $subject->require('BarEntry');
        $this->assertEquals('foo', $loadedData);
    }

    /**
     * @test
     * @throws Exception
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $data = 'some data' . microtime();
        $backend->set('BackendFileTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendFileTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('BackendFileTest3', $data, ['UnitTestTag%test']);

        $expectedEntry = 'BackendFileTest2';

        $actualEntries = $backend->findIdentifiersByTag('UnitTestTag%special');
        $this->assertInternalType('array', $actualEntries);
        $this->assertEquals($expectedEntry, array_pop($actualEntries));
    }

    /**
     * @test
     * @throws Exception
     */
    public function findIdentifiersByTagDoesNotReturnExpiredEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('BackendFileTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special'], -100);
        $backend->set('BackendFileTest3', $data, ['UnitTestTag%test']);

        $this->assertSame([], $backend->findIdentifiersByTag('UnitTestTag%special'));
        $this->assertSame(['BackendFileTest1', 'BackendFileTest3'], $backend->findIdentifiersByTag('UnitTestTag%test'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function flushRemovesAllCacheEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data);
        $backend->set('BackendFileTest2', $data);

        $this->assertFileExists('vfs://Foo/cache/data/UnitTestCache/BackendFileTest1');
        $this->assertFileExists('vfs://Foo/cache/data/UnitTestCache/BackendFileTest2');

        $backend->flush();

        $this->assertFileNotExists('vfs://Foo/cache/data/UnitTestCache/BackendFileTest1');
        $this->assertFileNotExists('vfs://Foo/cache/data/UnitTestCache/BackendFileTest2');
    }

    /**
     * @test
     * @throws Exception
     */
    public function flushCreatesCacheDirectoryAgain(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->flush();
        $this->assertFileExists('vfs://Foo/cache/data/UnitTestCache/');
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag(): void
    {
        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['findIdentifiersByTag', 'remove'])
            ->disableOriginalConstructor()
            ->getMock();

        $backend->expects($this->once())->method('findIdentifiersByTag')->with('UnitTestTag%special')->will($this->returnValue([
            'foo',
            'bar',
            'baz'
        ]));
        $backend->expects($this->at(1))->method('remove')->with('foo');
        $backend->expects($this->at(2))->method('remove')->with('bar');
        $backend->expects($this->at(3))->method('remove')->with('baz');

        $backend->flushByTag('UnitTestTag%special');
    }

    /**
     * @test
     * @throws Exception
     */
    public function collectGarbageRemovesExpiredCacheEntries(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['isCacheFileExpired'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->expects($this->exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(
            true,
            false
        ));
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data);
        $backend->set('BackendFileTest2', $data);

        $this->assertFileExists('vfs://Foo/cache/data/UnitTestCache/BackendFileTest1');
        $this->assertFileExists('vfs://Foo/cache/data/UnitTestCache/BackendFileTest2');

        $backend->collectGarbage();
        $this->assertFileNotExists('vfs://Foo/cache/data/UnitTestCache/BackendFileTest1');
        $this->assertFileExists('vfs://Foo/cache/data/UnitTestCache/BackendFileTest2');
    }

    /**
     * @test
     * @throws Exception
     */
    public function flushUnfreezesTheCache(): void
    {
        $mockCache = $this->createMock(AbstractFrontend::class);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMockBuilder(FileBackend::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->freeze();

        $this->assertTrue($backend->isFrozen());
        $backend->flush();
        $this->assertFalse($backend->isFrozen());
    }
}
