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

use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for the File cache backend
 *
 * This file is a backport from FLOW3
 */
class FileBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Sets up this testcase
     *
     * @return void
     */
    protected function setUp()
    {
        if (!class_exists('org\\bovigo\\vfs\\vfsStreamWrapper')) {
            $this->markTestSkipped('File backend tests are not available with this phpunit version.');
        }

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('Foo'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function setCacheDirectoryThrowsExceptionOnNonWritableDirectory()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('http://localhost/');

        $backend->setCache($mockCache);
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsAbsolutePathWithoutTrailingSlash()
    {
        $backend = $this->getAccessibleMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->_set('cacheIdentifier', 'test');
        $backend->setCacheDirectory('/tmp/foo');
        $this->assertEquals('/tmp/foo/test/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsAbsolutePathWithTrailingSlash()
    {
        $backend = $this->getAccessibleMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->_set('cacheIdentifier', 'test');
        $backend->setCacheDirectory('/tmp/foo/');
        $this->assertEquals('/tmp/foo/test/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsRelativePathWithoutTrailingSlash()
    {
        $backend = $this->getAccessibleMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->_set('cacheIdentifier', 'test');
        $backend->setCacheDirectory('tmp/foo');
        // get PATH_site without trailing slash
        $path = GeneralUtility::fixWindowsFilePath(realpath(PATH_site));
        $this->assertEquals($path . '/tmp/foo/test/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsRelativePathWithTrailingSlash()
    {
        $backend = $this->getAccessibleMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->_set('cacheIdentifier', 'test');
        $backend->setCacheDirectory('tmp/foo/');
        // get PATH_site without trailing slash
        $path = GeneralUtility::fixWindowsFilePath(realpath(PATH_site));
        $this->assertEquals($path . '/tmp/foo/test/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsRelativeDottedPathWithoutTrailingSlash()
    {
        $backend = $this->getAccessibleMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->_set('cacheIdentifier', 'test');
        $backend->setCacheDirectory('../tmp/foo');
        // get PATH_site without trailing slash
        $path = GeneralUtility::fixWindowsFilePath(realpath(PATH_site));
        $this->assertEquals($path . '/../tmp/foo/test/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsRelativeDottedPathWithTrailingSlash()
    {
        $backend = $this->getAccessibleMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->_set('cacheIdentifier', 'test');
        $backend->setCacheDirectory('../tmp/foo/');
        // get PATH_site without trailing slash
        $path = GeneralUtility::fixWindowsFilePath(realpath(PATH_site));
        $this->assertEquals($path . '/../tmp/foo/test/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsAbsoluteDottedPathWithoutTrailingSlash()
    {
        $backend = $this->getAccessibleMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->_set('cacheIdentifier', 'test');
        $backend->setCacheDirectory('/tmp/../foo');
        $this->assertEquals('/tmp/../foo/test/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function setCacheDirectoryAllowsAbsoluteDottedPathWithTrailingSlash()
    {
        $backend = $this->getAccessibleMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->_set('cacheIdentifier', 'test');
        $backend->setCacheDirectory('/tmp/../foo/');
        $this->assertEquals('/tmp/../foo/test/', $backend->_get('temporaryCacheDirectory'));
    }

    /**
     * @test
     */
    public function getCacheDirectoryReturnsTheCurrentCacheDirectory()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $this->assertEquals('vfs://Foo/Cache/Data/SomeCache/', $backend->getCacheDirectory());
    }

    /**
     * @test
     */
    public function aDedicatedCacheDirectoryIsUsedForCodeCaches()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class, [], [], '', false);
        $mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $this->assertEquals('vfs://Foo/Cache/Code/SomeCache/', $backend->getCacheDirectory());
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
     */
    public function setThrowsExceptionIfDataIsNotAString()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set('some identifier', ['not a string']);
    }

    /**
     * @test
     */
    public function setReallySavesToTheSpecifiedDirectory()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data);

        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, null, null, 0, strlen($data));
        $this->assertEquals($data, $retrievedData);
    }

    /**
     * @test
     */
    public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data1 = 'some data' . microtime();
        $data2 = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data1, [], 500);
        $backend->set($entryIdentifier, $data2, [], 200);

        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;
        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, null, null, 0, strlen($data2));
        $this->assertEquals($data2, $retrievedData);
    }

    /**
     * @test
     */
    public function setAlsoSavesSpecifiedTags()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileRemoveBeforeSetTest';

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data, ['Tag1', 'Tag2']);

        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;
        $this->assertFileExists($pathAndFilename);
        $retrievedData = file_get_contents($pathAndFilename, null, null, (strlen($data) + \TYPO3\CMS\Core\Cache\Backend\FileBackend::EXPIRYTIME_LENGTH), 9);
        $this->assertEquals('Tag1 Tag2', $retrievedData);
    }

    /**
     * @test
     */
    public function setCacheDetectsAndLoadsAFrozenCache()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data, ['Tag1', 'Tag2']);

        $backend->freeze();

        unset($backend);

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $this->assertTrue($backend->isFrozen());
        $this->assertEquals($data, $backend->get($entryIdentifier));
    }

    /**
     * @test
     */
    public function getReturnsContentOfTheCorrectCacheFile()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['setTag'], [], '', false);
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
     */
    public function getReturnsFalseForExpiredEntries()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['isCacheFileExpired'], [], '', false);
        $backend->expects($this->once())->method('isCacheFileExpired')->with('vfs://Foo/Cache/Data/UnitTestCache/ExpiredEntry')->will($this->returnValue(true));
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $this->assertFalse($backend->get('ExpiredEntry'));
    }

    /**
     * @test
     */
    public function getDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['isCacheFileExpired'], [], '', false);
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
     */
    public function hasReturnsTrueIfAnEntryExists()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
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
    public function hasReturnsFalseForExpiredEntries()
    {
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['isCacheFileExpired'], [], '', false);
        $backend->expects($this->exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(true, false));

        $this->assertFalse($backend->has('foo'));
        $this->assertTrue($backend->has('bar'));
    }

    /**
     * @test
     */
    public function hasDoesNotCheckIfAnEntryIsExpiredIfTheCacheIsFrozen()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['isCacheFileExpired'], [], '', false);
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
     */
    public function removeReallyRemovesACacheEntry()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $data = 'some data' . microtime();
        $entryIdentifier = 'BackendFileTest';
        $pathAndFilename = 'vfs://Foo/Cache/Data/UnitTestCache/' . $entryIdentifier;

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($entryIdentifier, $data);
        $this->assertFileExists($pathAndFilename);

        $backend->remove($entryIdentifier);
        $this->assertFileNotExists($pathAndFilename);
    }

    /**
     */
    public function invalidEntryIdentifiers()
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
     * @expectedException \InvalidArgumentException
     */
    public function setThrowsExceptionForInvalidIdentifier($identifier)
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], ['test'], '', true);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->set($identifier, 'cache data', []);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function getThrowsExceptionForInvalidIdentifier($identifier)
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->get($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function hasThrowsExceptionForInvalidIdentifier($identifier)
    {
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);

        $backend->has($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function removeThrowsExceptionForInvalidIdentifier($identifier)
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->remove($identifier);
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifiers
     * @expectedException \InvalidArgumentException
     */
    public function requireOnceThrowsExceptionForInvalidIdentifier($identifier)
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->requireOnce($identifier);
    }

    /**
     * @test
     */
    public function requireOnceIncludesAndReturnsResultOfIncludedPhpFile()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
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
     */
    public function requireOnceDoesNotCheckExpiryTimeIfBackendIsFrozen()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['isCacheFileExpired'], [], '', false);
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
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
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
     */
    public function findIdentifiersByTagDoesNotReturnExpiredEntries()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
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
     */
    public function flushRemovesAllCacheEntries()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data);
        $backend->set('BackendFileTest2', $data);

        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');

        $backend->flush();

        $this->assertFileNotExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        $this->assertFileNotExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');
    }

    /**
     * @test
     */
    public function flushCreatesCacheDirectoryAgain()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->flush();
        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/');
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['findIdentifiersByTag', 'remove'], [], '', false);

        $backend->expects($this->once())->method('findIdentifiersByTag')->with('UnitTestTag%special')->will($this->returnValue(['foo', 'bar', 'baz']));
        $backend->expects($this->at(1))->method('remove')->with('foo');
        $backend->expects($this->at(2))->method('remove')->with('bar');
        $backend->expects($this->at(3))->method('remove')->with('baz');

        $backend->flushByTag('UnitTestTag%special');
    }

    /**
     * @test
     */
    public function collectGarbageRemovesExpiredCacheEntries()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['isCacheFileExpired'], [], '', false);
        $backend->expects($this->exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(true, false));
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $data = 'some data';
        $backend->set('BackendFileTest1', $data);
        $backend->set('BackendFileTest2', $data);

        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');

        $backend->collectGarbage();
        $this->assertFileNotExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest1');
        $this->assertFileExists('vfs://Foo/Cache/Data/UnitTestCache/BackendFileTest2');
    }

    /**
     * @test
     */
    public function flushUnfreezesTheCache()
    {
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
        $mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\FileBackend::class, ['dummy'], [], '', false);
        $backend->setCacheDirectory('vfs://Foo/');
        $backend->setCache($mockCache);

        $backend->freeze();

        $this->assertTrue($backend->isFrozen());
        $backend->flush();
        $this->assertFalse($backend->isFrozen());
    }
}
