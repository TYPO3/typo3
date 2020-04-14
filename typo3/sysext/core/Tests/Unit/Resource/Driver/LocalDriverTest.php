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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase;
use TYPO3\CMS\Core\Tests\Unit\Resource\Driver\Fixtures\LocalDriverFilenameFilter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Test case
 */
class LocalDriverTest extends BaseTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var LocalDriver
     */
    protected $localDriver;

    /**
     * @var array
     */
    protected $testDirs = [];

    /**
     * @var string
     */
    protected $iso88591GreaterThan127 = '';

    /**
     * @var string
     */
    protected $utf8Latin1Supplement = '';

    /**
     * @var string
     */
    protected $utf8Latin1ExtendedA = '';

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        foreach ($this->testDirs as $dir) {
            chmod($dir, 0777);
            GeneralUtility::rmdir($dir, true);
        }
        parent::tearDown();
    }

    /**
     * Creates a "real" directory for doing tests. This is necessary because some file system properties (e.g. permissions)
     * cannot be reflected by vfsStream, and some methods (like touch()) don't work there either.
     *
     * Created directories are automatically destroyed during tearDown()
     *
     * @return string
     */
    protected function createRealTestdir(): string
    {
        $basedir = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('fal-test-');
        mkdir($basedir);
        $this->testDirs[] = $basedir;
        return $basedir;
    }

    /**
     * Create a "real" directory together with a driver configured
     * for this directory.
     *
     * @return array With path to base directory and driver
     */
    protected function prepareRealTestEnvironment(): array
    {
        $basedir = $this->createRealTestdir();
        $subject = $this->createDriver([
            'basePath' => $basedir
        ]);
        return [$basedir, $subject];
    }

    /**
     * Creates a mocked driver object as test subject, optionally using a given mount object.
     *
     * IMPORTANT: Call this only after setting up the virtual file system (with the addTo* methods)!
     *
     * @param array $driverConfiguration
     * @param array $mockedDriverMethods
     * @return LocalDriver
     */
    protected function createDriver(array $driverConfiguration = [], array $mockedDriverMethods = []): LocalDriver
    {
        // it's important to do that here, so vfsContents could have been set before
        if (!isset($driverConfiguration['basePath'])) {
            $this->initializeVfs();
            $driverConfiguration['basePath'] = $this->getMountRootUrl();
        }
        /** @var LocalDriver $driver */
        $mockedDriverMethods[] = 'isPathValid';
        $driver = $this->getAccessibleMock(
            LocalDriver::class,
            $mockedDriverMethods,
            [$driverConfiguration]
        );
        $driver->expects(self::any())
            ->method('isPathValid')
            ->willReturn(
                true
            );

        $driver->setStorageUid(5);
        $driver->processConfiguration();
        $driver->initialize();
        return $driver;
    }

    /**
     * @test
     */
    public function calculatedBasePathRelativeIsSane(): void
    {
        $subject = $this->createDriver();

        // This would cause problems if you fill "/fileadmin/" into the base path field of a sys_file_storage record and select "relative" as path type
        $relativeDriverConfiguration = [
            'pathType' => 'relative',
            'basePath' => '/typo3temp/var/tests/',
        ];
        $basePath = $subject->_call('calculateBasePath', $relativeDriverConfiguration);

        self::assertStringNotContainsString('//', $basePath);
    }

    /**
     * @test
     */
    public function calculatedBasePathAbsoluteIsSane(): void
    {
        $subject = $this->createDriver();

        // This test checks if "/../" are properly filtered out (i.e. from "Base path" field of sys_file_storage)
        $varPath = Environment::getVarPath();
        $projectPath = Environment::getProjectPath();
        $relativeVarPath = str_replace($projectPath, '', $varPath);
        $segments = str_repeat('/..', substr_count($relativeVarPath, '/') + 1);
        $relativeDriverConfiguration = [
            'basePath' => Environment::getVarPath() . '/tests' . $segments . $relativeVarPath . '/tests/',
        ];
        $basePath = $subject->_call('calculateBasePath', $relativeDriverConfiguration);

        self::assertStringNotContainsString('/../', $basePath);
    }

    /**
     * @test
     */
    public function createFolderRecursiveSanitizesFilename(): void
    {
        /** @var LocalDriver|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $driver */
        $driver = $this->createDriver([], ['sanitizeFilename']);
        $driver->expects(self::exactly(2))
            ->method('sanitizeFileName')
            ->willReturn(
                'sanitized'
            );
        $driver->createFolder('newFolder/andSubfolder', '/', true);
        self::assertFileExists($this->getUrlInMount('/sanitized/sanitized/'));
    }

    /**
     * @test
     */
    public function determineBaseUrlUrlEncodesUriParts(): void
    {
        /** @var LocalDriver|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $driver */
        $driver = $this->getAccessibleMock(
            LocalDriver::class,
            ['hasCapability'],
            [],
            '',
            false
        );
        $driver->expects(self::once())
            ->method('hasCapability')
            ->with(ResourceStorage::CAPABILITY_PUBLIC)
            ->willReturn(
                true
            );
        $driver->_set('absoluteBasePath', Environment::getPublicPath() . '/un encö/ded %path/');
        $driver->_call('determineBaseUrl');
        $baseUri = $driver->_get('baseUri');
        self::assertEquals(rawurlencode('un encö') . '/' . rawurlencode('ded %path') . '/', $baseUri);
    }

    /**
     * @test
     */
    public function getDefaultFolderReturnsFolderForUserUploadPath(): void
    {
        $subject = $this->createDriver();
        $folderIdentifier = $subject->getDefaultFolder();
        self::assertEquals('/user_upload/', $folderIdentifier);
    }

    /**
     * @test
     */
    public function defaultLevelFolderFolderIsCreatedIfItDoesntExist(): void
    {
        $subject = $this->createDriver();
        self::assertFileExists($this->getUrlInMount($subject->getDefaultFolder()));
    }

    /**
     * @test
     */
    public function getFolderInFolderReturnsCorrectFolderObject(): void
    {
        $this->addToMount([
            'someDir' => [
                'someSubdir' => []
            ]
        ]);
        $subject = $this->createDriver();
        $folder = $subject->getFolderInFolder('someSubdir', '/someDir/');
        self::assertEquals('/someDir/someSubdir/', $folder);
    }

    /**
     * @test
     */
    public function createFolderCreatesFolderOnDisk(): void
    {
        $this->addToMount(['some' => ['folder' => []]]);
        $subject = $this->createDriver();
        $subject->createFolder('path', '/some/folder/');
        self::assertFileExists($this->getUrlInMount('/some/folder/'));
        self::assertFileExists($this->getUrlInMount('/some/folder/path'));
    }

    /**
     * @test
     */
    public function createFolderReturnsFolderObject(): void
    {
        $this->addToMount(['some' => ['folder' => []]]);
        $subject = $this->createDriver();
        $createdFolder = $subject->createFolder('path', '/some/folder/');
        self::assertEquals('/some/folder/path/', $createdFolder);
    }

    /**
     * @return array
     */
    public static function createFolderSanitizesFolderNameBeforeCreationDataProvider(): array
    {
        return [
            'folder name with NULL character' => [
                'some' . "\0" . 'Folder',
                'some_Folder'
            ],
            'folder name with directory part' => [
                '../someFolder',
                '.._someFolder'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider createFolderSanitizesFolderNameBeforeCreationDataProvider
     * @param string $newFolderName
     * @param string $expectedFolderName
     */
    public function createFolderSanitizesFolderNameBeforeCreation(string $newFolderName, string $expectedFolderName): void
    {
        $this->addToMount(['some' => ['folder' => []]]);
        $subject = $this->createDriver();
        $subject->createFolder($newFolderName, '/some/folder/');
        self::assertFileExists($this->getUrlInMount('/some/folder/' . $expectedFolderName));
    }

    /**
     * @test
     */
    public function basePathIsNormalizedWithTrailingSlash(): void
    {
        $subject = $this->createDriver();
        self::assertEquals('/', substr($subject->_call('getAbsoluteBasePath'), -1));
    }

    /**
     * @test
     */
    public function noSecondSlashIsAddedIfBasePathAlreadyHasTrailingSlash(): void
    {
        $subject = $this->createDriver();
        self::assertNotEquals('/', substr($subject->_call('getAbsoluteBasePath'), -2, 1));
    }

    /**
     * @return array
     */
    public function getSpecificFileInformationDataProvider(): array
    {
        return [
            'size' => [
                'expectedValue' => filesize(__DIR__ . '/Fixtures/Dummy.html'),
                'propertyName' => 'size'
            ],
            'atime' => [
                'expectedValue' => 'WILL_BE_REPLACED_BY_VFS_TIME',
                'propertyName' => 'atime'
            ],
            'mtime' => [
                'expectedValue' => 'WILL_BE_REPLACED_BY_VFS_TIME',
                'propertyName' => 'mtime'
            ],
            'ctime' => [
                'expectedValue' => 'WILL_BE_REPLACED_BY_VFS_TIME',
                'propertyName' => 'ctime'
            ],
            'name' => [
                'expectedValue' => 'Dummy.html',
                'propertyName' => 'name'
            ],
            'mimetype' => [
                'expectedValue' => 'text/html',
                'propertyName' => 'mimetype'
            ],
            'identifier' => [
                'expectedValue' => '/Dummy.html',
                'propertyName' => 'identifier'
            ],
            'storage' => [
                'expectedValue' => 5,
                'propertyName' => 'storage'
            ],
            'identifier_hash' => [
                'expectedValue' => 'b11efa5d7c0556a65c6aa261343b9807cac993bc',
                'propertyName' => 'identifier_hash'
            ],
            'folder_hash' => [
                'expectedValue' => '42099b4af021e53fd8fd4e056c2568d7c2e3ffa8',
                'propertyName' => 'folder_hash'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getSpecificFileInformationDataProvider
     * @param string|int $expectedValue
     * @param string $property
     */
    public function getSpecificFileInformationReturnsRequestedFileInformation($expectedValue, string $property): void
    {
        $root = vfsStream::setup('root');

        $subFolder = vfsStream::newDirectory('fileadmin');
        $root->addChild($subFolder);

        // Load fixture files and folders from disk
        $directory = vfsStream::copyFromFileSystem(__DIR__ . '/Fixtures/', $subFolder, 1024 * 1024);
        if (in_array($property, ['mtime', 'ctime', 'atime'])) {
            $expectedValue = $directory->getChild('Dummy.html')->filemtime();
        }

        $subject = $this->createDriver(['basePath' => 'vfs://root/fileadmin']);
        self::assertSame(
            $expectedValue,
            $subject->getSpecificFileInformation('vfs://root/fileadmin/Dummy.html', '/', $property)
        );
    }

    /**
     * @test
     */
    public function getAbsolutePathReturnsCorrectPath(): void
    {
        $this->addToMount([
            'someFolder' => [
                'file1.ext' => 'asdfg'
            ]
        ]);
        $subject = $this->createDriver();
        $path = $subject->_call('getAbsolutePath', '/someFolder/file1.ext');
        self::assertTrue(file_exists($path));
        self::assertEquals($this->getUrlInMount('/someFolder/file1.ext'), $path);
    }

    /**
     * @test
     */
    public function addFileMovesFileToCorrectLocation(): void
    {
        $this->addToMount(['targetFolder' => []]);
        $this->addToVfs([
            'sourceFolder' => [
                'file' => 'asdf'
            ]
        ]);
        $subject = $this->createDriver(
            [],
            ['getMimeTypeOfFile']
        );
        self::assertTrue(file_exists($this->getUrl('sourceFolder/file')));
        $subject->addFile($this->getUrl('sourceFolder/file'), '/targetFolder/', 'file');
        self::assertTrue(file_exists($this->getUrlInMount('/targetFolder/file')));
    }

    /**
     * @test
     */
    public function addFileUsesFilenameIfGiven(): void
    {
        $this->addToMount(['targetFolder' => []]);
        $this->addToVfs([
            'sourceFolder' => [
                'file' => 'asdf'
            ]
        ]);
        $subject = $this->createDriver(
            [],
            ['getMimeTypeOfFile']
        );
        self::assertTrue(file_exists($this->getUrl('sourceFolder/file')));
        $subject->addFile($this->getUrl('sourceFolder/file'), '/targetFolder/', 'targetFile');
        self::assertTrue(file_exists($this->getUrlInMount('/targetFolder/targetFile')));
    }

    /**
     * @test
     */
    public function addFileFailsIfFileIsInDriverStorage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314778269);
        $this->addToMount([
            'targetFolder' => [
                'file' => 'asdf'
            ]
        ]);
        $subject = $this->createDriver();
        $subject->addFile($this->getUrlInMount('/targetFolder/file'), '/targetFolder/', 'file');
    }

    /**
     * @test
     */
    public function addFileReturnsFileIdentifier(): void
    {
        $this->addToMount(['targetFolder' => []]);
        $this->addToVfs([
            'sourceFolder' => [
                'file' => 'asdf'
            ]
        ]);
        $subject = $this->createDriver(
            [],
            ['getMimeTypeOfFile']
        );
        self::assertTrue(file_exists($this->getUrl('sourceFolder/file')));
        $fileIdentifier = $subject->addFile($this->getUrl('sourceFolder/file'), '/targetFolder/', 'file');
        self::assertEquals('file', basename($fileIdentifier));
        self::assertEquals('/targetFolder/file', $fileIdentifier);
    }

    /**
     * @test
     */
    public function existenceChecksWorkForFilesAndFolders(): void
    {
        $this->addToMount([
            'file' => 'asdf',
            'folder' => []
        ]);
        $subject = $this->createDriver();
        // Using slashes at the beginning of paths because they will be stored in the DB this way.
        self::assertTrue($subject->fileExists('/file'));
        self::assertTrue($subject->folderExists('/folder/'));
        self::assertFalse($subject->fileExists('/nonexistingFile'));
        self::assertFalse($subject->folderExists('/nonexistingFolder/'));
    }

    /**
     * @test
     */
    public function existenceChecksInFolderWorkForFilesAndFolders(): void
    {
        $this->addToMount([
            'subfolder' => [
                'file' => 'asdf',
                'folder' => []
            ]
        ]);
        $subject = $this->createDriver();
        self::assertTrue($subject->fileExistsInFolder('file', '/subfolder/'));
        self::assertTrue($subject->folderExistsInFolder('folder', '/subfolder/'));
        self::assertFalse($subject->fileExistsInFolder('nonexistingFile', '/subfolder/'));
        self::assertFalse($subject->folderExistsInFolder('nonexistingFolder', '/subfolder/'));
    }

    /**
     * @test
     */
    public function getPublicUrlReturnsCorrectUriForConfiguredBaseUri(): void
    {
        $baseUri = 'http://example.org/foobar/' . StringUtility::getUniqueId('uri_');
        $this->addToMount([
            'file.ext' => 'asdf',
            'subfolder' => [
                'file2.ext' => 'asdf'
            ]
        ]);
        $subject = $this->createDriver([
            'baseUri' => $baseUri
        ]);
        self::assertEquals($baseUri . '/file.ext', $subject->getPublicUrl('/file.ext'));
        self::assertEquals($baseUri . '/subfolder/file2.ext', $subject->getPublicUrl('/subfolder/file2.ext'));
    }

    /**
     * Data provider for getPublicUrlReturnsValidUrlContainingSpecialCharacters().
     *
     * @return array
     */
    public function getPublicUrlReturnsValidUrlContainingSpecialCharacters_dataProvider(): array
    {
        return [
            ['/single file with some special chars äüö!.txt'],
            ['/on subfolder/with special chars äüö!.ext'],
            ['/who names a file like !"§$%&()=?*+~"#\'´`<>-.ext'],
            ['no leading slash !"§$%&()=?*+~#\'"´`"<>-.txt']
        ];
    }

    /**
     * @test
     * @dataProvider getPublicUrlReturnsValidUrlContainingSpecialCharacters_dataProvider
     * @param string $fileIdentifier
     */
    public function getPublicUrlReturnsValidUrlContainingSpecialCharacters(string $fileIdentifier): void
    {
        $baseUri = 'http://example.org/foobar/' . StringUtility::getUniqueId('uri_');
        $subject = $this->createDriver([
            'baseUri' => $baseUri
        ]);
        $publicUrl = $subject->getPublicUrl($fileIdentifier);
        self::assertTrue(
            GeneralUtility::isValidUrl($publicUrl),
            'getPublicUrl did not return a valid URL:' . $publicUrl
        );
    }

    /**
     * @test
     */
    public function fileContentsCanBeWrittenAndRead(): void
    {
        $fileContents = 'asdf';
        $this->addToMount([
            'file.ext' => $fileContents
        ]);
        $subject = $this->createDriver();
        self::assertEquals($fileContents, $subject->getFileContents('/file.ext'), 'File contents could not be read');
        $newFileContents = 'asdfgh';
        $subject->setFileContents('/file.ext', $newFileContents);
        self::assertEquals(
            $newFileContents,
            $subject->getFileContents('/file.ext'),
            'New file contents could not be read.'
        );
    }

    /**
     * @test
     */
    public function setFileContentsReturnsNumberOfBytesWrittenToFile(): void
    {
        $fileContents = 'asdf';
        $this->addToMount([
            'file.ext' => $fileContents
        ]);
        $subject = $this->createDriver();
        $newFileContents = 'asdfgh';
        $bytesWritten = $subject->setFileContents('/file.ext', $newFileContents);
        self::assertEquals(strlen($newFileContents), $bytesWritten);
    }

    /**
     * @test
     * @see http://phpmagazin.de/vfsStream-1.1.0-nutzt-PHP-5.4-M%C3%B6glichkeiten-064406.html
     */
    public function newFilesCanBeCreated(): void
    {
        $subject = $this->createDriver();
        $subject->createFile('testfile.txt', '/');
        self::assertTrue($subject->fileExists('/testfile.txt'));
    }

    /**
     * @test
     * @see http://phpmagazin.de/vfsStream-1.1.0-nutzt-PHP-5.4-M%C3%B6glichkeiten-064406.html
     */
    public function createdFilesAreEmpty(): void
    {
        $subject = $this->createDriver();
        $subject->createFile('testfile.txt', '/');
        self::assertTrue($subject->fileExists('/testfile.txt'));
        $fileData = $subject->getFileContents('/testfile.txt');
        self::assertEquals(0, strlen($fileData));
    }

    /**
     * @test
     */
    public function createFileFixesPermissionsOnCreatedFile(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped('createdFilesHaveCorrectRights() tests not available on Windows');
        }

        // No one will use this as his default file create mask so we hopefully don't get any false positives
        $testpattern = '0646';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = $testpattern;

        $this->addToMount(
            [
                'someDir' => []
            ]
        );
        /** @var $subject LocalDriver */
        [$basedir, $subject] = $this->prepareRealTestEnvironment();
        mkdir($basedir . '/someDir');
        $subject->createFile('testfile.txt', '/someDir');
        self::assertEquals((int)$testpattern, (int)(decoct(fileperms($basedir . '/someDir/testfile.txt') & 0777)));
    }

    /**********************************
     * File and directory listing
     **********************************/
    /**
     * @test
     */
    public function getFileReturnsCorrectIdentifier(): void
    {
        $root = vfsStream::setup('root');
        $subFolder = vfsStream::newDirectory('fileadmin');
        $root->addChild($subFolder);
        // Load fixture files and folders from disk
        vfsStream::copyFromFileSystem(__DIR__ . '/Fixtures/', $subFolder, 1024 * 1024);

        $subject = $this->createDriver(['basePath' => 'vfs://root/fileadmin']);

        $subdirFileInfo = $subject->getFileInfoByIdentifier('Dummy.html');
        self::assertEquals('/Dummy.html', $subdirFileInfo['identifier']);
        $rootFileInfo = $subject->getFileInfoByIdentifier('LocalDriverFilenameFilter.php');
        self::assertEquals('/LocalDriverFilenameFilter.php', $rootFileInfo['identifier']);
    }

    /**
     * @test
     */
    public function getFileThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314516809);
        $subject = $this->createDriver();
        $subject->getFileInfoByIdentifier('/some/file/at/a/random/path');
    }

    /**
     * @test
     */
    public function getFilesInFolderReturnsEmptyArrayForEmptyDirectory(): void
    {
        $subject = $this->createDriver();
        $fileList = $subject->getFilesInFolder('/');
        self::assertEmpty($fileList);
    }

    /**
     * @test
     */
    public function getFileListReturnsAllFilesInDirectory(): void
    {
        $dirStructure = [
            'aDir' => [],
            'file1' => 'asdfg',
            'file2' => 'fdsa'
        ];
        $this->addToMount($dirStructure);
        $subject = $this->createDriver(
            [],
            // Mocked because finfo() can not deal with vfs streams and throws warnings
            ['getMimeTypeOfFile']
        );
        $fileList = $subject->getFilesInFolder('/');
        self::assertEquals(['/file1', '/file2'], array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFileListReturnsAllFilesInSubdirectoryIfRecursiveParameterIsSet(): void
    {
        $dirStructure = [
            'aDir' => [
                'file3' => 'asdfgh',
                'subdir' => [
                    'file4' => 'asklfjklasjkl'
                ]
            ],
            'file1' => 'asdfg',
            'file2' => 'fdsa'
        ];
        $this->addToMount($dirStructure);
        $subject = $this->createDriver(
            [],
            // Mocked because finfo() can not deal with vfs streams and throws warnings
            ['getMimeTypeOfFile']
        );
        $fileList = $subject->getFilesInFolder('/', 0, 0, true);
        self::assertEquals(['/file1', '/file2', '/aDir/file3', '/aDir/subdir/file4'], array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFileListFailsIfDirectoryDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314349666);
        $this->addToMount(['somefile' => '']);
        $subject = $this->createDriver();
        $subject->getFilesInFolder('somedir/');
    }

    /**
     * @test
     */
    public function getFileInFolderCallsConfiguredCallbackFunctionWithGivenItemName(): void
    {
        $dirStructure = [
            'file2' => 'fdsa'
        ];
        // register static callback to self
        $callback = [
            [
                static::class,
                'callbackStaticTestFunction'
            ]
        ];
        $this->addToMount($dirStructure);
        $subject = $this->createDriver();
        // the callback function will throw an exception used to check if it was called with correct $itemName
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1336159604);
        $subject->getFilesInFolder('/', 0, 0, false, $callback);
    }

    /**
     * Static callback function used to test if the filter callbacks work
     * As it is static we are using an exception to test if it is really called and works
     *
     * @static
     * @param string $itemName
     * @throws \InvalidArgumentException
     * @see getFileListCallsConfiguredCallbackFunction
     */
    public static function callbackStaticTestFunction(string $itemName): void
    {
        if ($itemName === 'file2') {
            throw new \InvalidArgumentException('$itemName', 1336159604);
        }
    }

    /**
     * @test
     */
    public function getFileListFiltersItemsWithGivenFilterMethods(): void
    {
        $dirStructure = [
            'fileA' => 'asdfg',
            'fileB' => 'fdsa'
        ];
        $this->addToMount($dirStructure);
        $subject = $this->createDriver(
            [],
            // Mocked because finfo() can not deal with vfs streams and throws warnings
            ['getMimeTypeOfFile']
        );
        $filterCallbacks = [
            [
                LocalDriverFilenameFilter::class,
                'filterFilename',
            ],
        ];
        $fileList = $subject->getFilesInFolder('/', 0, 0, false, $filterCallbacks);
        self::assertNotContains('/fileA', array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFolderListReturnsAllDirectoriesInDirectory(): void
    {
        $dirStructure = [
            'dir1' => [],
            'dir2' => [],
            'file' => 'asdfg'
        ];
        $this->addToMount($dirStructure);
        $subject = $this->createDriver();
        $fileList = $subject->getFoldersInFolder('/');
        self::assertEquals(['/dir1/', '/dir2/'], array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFolderListReturnsHiddenFoldersByDefault(): void
    {
        $dirStructure = [
            '.someHiddenDir' => [],
            'aDir' => [],
            'file1' => ''
        ];
        $this->addToMount($dirStructure);
        $subject = $this->createDriver();

        $fileList = $subject->getFoldersInFolder('/');

        self::assertEquals(['/.someHiddenDir/', '/aDir/'], array_keys($fileList));
    }

    /**
     * Checks if the folder names . and .. are ignored when listing subdirectories
     *
     * @test
     */
    public function getFolderListLeavesOutNavigationalEntries(): void
    {
        // we have to add .. and . manually, as these are not included in vfsStream directory listings (as opposed
        // to normal filelistings)
        $this->addToMount([
            '..' => [],
            '.' => []
        ]);
        $subject = $this->createDriver();
        $fileList = $subject->getFoldersInFolder('/');
        self::assertEmpty($fileList);
    }

    /**
     * @test
     */
    public function getFolderListFiltersItemsWithGivenFilterMethods(): void
    {
        $dirStructure = [
            'folderA' => [],
            'folderB' => []
        ];
        $this->addToMount($dirStructure);
        $subject = $this->createDriver();
        $filterCallbacks = [
            [
                LocalDriverFilenameFilter::class,
                'filterFilename',
            ],
        ];
        $folderList = $subject->getFoldersInFolder('/', 0, 0, $filterCallbacks);
        self::assertNotContains('folderA', array_keys($folderList));
    }

    /**
     * @test
     */
    public function getFolderListFailsIfDirectoryDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314349666);
        $subject = $this->createDriver();
        vfsStream::create([$this->basedir => ['somefile' => '']]);
        $subject->getFoldersInFolder('somedir/');
    }

    /**
     * @test
     */
    public function hashReturnsCorrectHashes(): void
    {
        $contents = '68b329da9893e34099c7d8ad5cb9c940';
        $expectedMd5Hash = '8c67dbaf0ba22f2e7fbc26413b86051b';
        $expectedSha1Hash = 'a60cd808ba7a0bcfa37fa7f3fb5998e1b8dbcd9d';
        $this->addToMount(['hashFile' => $contents]);
        $subject = $this->createDriver();
        self::assertEquals($expectedSha1Hash, $subject->hash('/hashFile', 'sha1'));
        self::assertEquals($expectedMd5Hash, $subject->hash('/hashFile', 'md5'));
    }

    /**
     * @test
     */
    public function hashingWithUnsupportedAlgorithmFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1304964032);
        $subject = $this->createDriver();
        $subject->hash('/hashFile', StringUtility::getUniqueId('uri_'));
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Resource\Driver\LocalDriver::getFileForLocalProcessing
     */
    public function getFileForLocalProcessingCreatesCopyOfFileByDefault(): void
    {
        $fileContents = 'asdfgh';
        $this->addToMount([
            'someDir' => [
                'someFile' => $fileContents
            ]
        ]);
        $subject = $this->createDriver([], ['copyFileToTemporaryPath']);
        $subject->expects(self::once())->method('copyFileToTemporaryPath');
        $subject->getFileForLocalProcessing('/someDir/someFile');
    }

    /**
     * @test
     */
    public function getFileForLocalProcessingReturnsOriginalFilepathForReadonlyAccess(): void
    {
        $fileContents = 'asdfgh';
        $this->addToMount([
            'someDir' => [
                'someFile' => $fileContents
            ]
        ]);
        $subject = $this->createDriver();
        $filePath = $subject->getFileForLocalProcessing('/someDir/someFile', false);
        self::assertEquals($filePath, $this->getUrlInMount('someDir/someFile'));
    }

    /**
     * @test
     */
    public function filesCanBeCopiedToATemporaryPath(): void
    {
        $fileContents = 'asdfgh';
        $this->addToMount([
            'someDir' => [
                'someFile.ext' => $fileContents
            ]
        ]);
        $subject = $this->createDriver();
        $filePath = GeneralUtility::fixWindowsFilePath($subject->_call('copyFileToTemporaryPath', '/someDir/someFile.ext'));
        $this->testFilesToDelete[] = $filePath;
        self::assertStringContainsString(Environment::getVarPath() . '/transient/', $filePath);
        self::assertEquals($fileContents, file_get_contents($filePath));
    }

    /**
     * @test
     */
    public function permissionsAreCorrectlyRetrievedForAllowedFile(): void
    {
        /** @var $subject LocalDriver */
        [$basedir, $subject] = $this->prepareRealTestEnvironment();
        touch($basedir . '/someFile');
        chmod($basedir . '/someFile', 448);
        clearstatcache();
        self::assertEquals(['r' => true, 'w' => true], $subject->getPermissions('/someFile'));
    }

    /**
     * @test
     */
    public function permissionsAreCorrectlyRetrievedForForbiddenFile(): void
    {
        if (\function_exists('posix_getegid') && posix_getegid() === 0) {
            self::markTestSkipped('Test skipped if run on linux as root');
        } elseif (Environment::isWindows()) {
            self::markTestSkipped('Test skipped if run on Windows system');
        }
        /** @var $subject LocalDriver */
        [$basedir, $subject] = $this->prepareRealTestEnvironment();
        touch($basedir . '/someForbiddenFile');
        chmod($basedir . '/someForbiddenFile', 0);
        clearstatcache();
        self::assertEquals(['r' => false, 'w' => false], $subject->getPermissions('/someForbiddenFile'));
    }

    /**
     * @test
     */
    public function permissionsAreCorrectlyRetrievedForAllowedFolder(): void
    {
        /** @var $subject LocalDriver */
        [$basedir, $subject] = $this->prepareRealTestEnvironment();
        mkdir($basedir . '/someFolder');
        chmod($basedir . '/someFolder', 448);
        clearstatcache();
        self::assertEquals(['r' => true, 'w' => true], $subject->getPermissions('/someFolder'));
    }

    /**
     * @test
     */
    public function permissionsAreCorrectlyRetrievedForForbiddenFolder(): void
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            self::markTestSkipped('Test skipped if run on linux as root');
        } elseif (Environment::isWindows()) {
            self::markTestSkipped('Test skipped if run on Windows system');
        }
        /** @var $subject LocalDriver */
        [$basedir, $subject] = $this->prepareRealTestEnvironment();
        mkdir($basedir . '/someForbiddenFolder');
        chmod($basedir . '/someForbiddenFolder', 0);
        clearstatcache();
        $result = $subject->getPermissions('/someForbiddenFolder');
        // Change permissions back to writable, so the sub-folder can be removed in tearDown
        chmod($basedir . '/someForbiddenFolder', 0777);
        self::assertEquals(['r' => false, 'w' => false], $result);
    }

    /**
     * Dataprovider for getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser test
     *
     * @return array group, filemode and expected result
     */
    public function getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser_dataProvider(): array
    {
        $data = [];
        // On some OS, the posix_* functions do not exist
        if (function_exists('posix_getgid')) {
            $data = [
                'current group, readable/writable' => [
                    posix_getgid(),
                    48,
                    ['r' => true, 'w' => true]
                ],
                'current group, readable/not writable' => [
                    posix_getgid(),
                    32,
                    ['r' => true, 'w' => false]
                ],
                'current group, not readable/not writable' => [
                    posix_getgid(),
                    0,
                    ['r' => false, 'w' => false]
                ]
            ];
        }
        $data = array_merge_recursive($data, [
            'arbitrary group, readable/writable' => [
                vfsStream::GROUP_USER_1,
                6,
                ['r' => true, 'w' => true]
            ],
            'arbitrary group, readable/not writable' => [
                vfsStream::GROUP_USER_1,
                436,
                ['r' => true, 'w' => false]
            ],
            'arbitrary group, not readable/not writable' => [
                vfsStream::GROUP_USER_1,
                432,
                ['r' => false, 'w' => false]
            ]
        ]);
        return $data;
    }

    /**
     * @test
     * @dataProvider getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser_dataProvider
     * @param int $group
     * @param int $permissions
     * @param array $expectedResult
     */
    public function getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser(int $group, int $permissions, array $expectedResult): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped('Test skipped if run on Windows system');
        }
        $this->addToMount([
            'testfile' => 'asdfg'
        ]);
        $subject = $this->createDriver();
        /** @var $fileObject vfsStreamContent */
        $fileObject = vfsStreamWrapper::getRoot()->getChild($this->mountDir)->getChild('testfile');
        // just use an "arbitrary" user here - it is only important that
        $fileObject->chown(vfsStream::OWNER_USER_1);
        $fileObject->chgrp($group);
        $fileObject->chmod($permissions);
        self::assertEquals($expectedResult, $subject->getPermissions('/testfile'));
    }

    /**
     * @test
     */
    public function isWithinRecognizesFilesWithinFolderAndInOtherFolders(): void
    {
        $subject = $this->createDriver();
        self::assertTrue($subject->isWithin('/someFolder/', '/someFolder/test.jpg'));
        self::assertTrue($subject->isWithin('/someFolder/', '/someFolder/subFolder/test.jpg'));
        self::assertFalse($subject->isWithin('/someFolder/', '/someFolderWithALongName/test.jpg'));
    }

    /**
     * @test
     */
    public function isWithinAcceptsFileAndFolderObjectsAsContent(): void
    {
        $subject = $this->createDriver();
        self::assertTrue($subject->isWithin('/someFolder/', '/someFolder/test.jpg'));
        self::assertTrue($subject->isWithin('/someFolder/', '/someFolder/subfolder/'));
    }

    /**********************************
     * Copy/move file
     **********************************/

    /**
     * @test
     */
    public function filesCanBeCopiedWithinStorage(): void
    {
        $fileContents = StringUtility::getUniqueId('content_');
        $this->addToMount([
            'someFile' => $fileContents,
            'targetFolder' => []
        ]);
        $subject = $this->createDriver(
            [],
            ['getMimeTypeOfFile']
        );
        $subject->copyFileWithinStorage('/someFile', '/targetFolder/', 'someFile');
        self::assertFileEquals($this->getUrlInMount('/someFile'), $this->getUrlInMount('/targetFolder/someFile'));
    }

    /**
     * @test
     */
    public function filesCanBeMovedWithinStorage(): void
    {
        $fileContents = StringUtility::getUniqueId('content_');
        $this->addToMount([
            'targetFolder' => [],
            'someFile' => $fileContents
        ]);
        $subject = $this->createDriver();
        $newIdentifier = $subject->moveFileWithinStorage('/someFile', '/targetFolder/', 'file');
        self::assertEquals($fileContents, file_get_contents($this->getUrlInMount('/targetFolder/file')));
        self::assertFileNotExists($this->getUrlInMount('/someFile'));
        self::assertEquals('/targetFolder/file', $newIdentifier);
    }

    /**
     * @test
     */
    public function fileMetadataIsChangedAfterMovingFile(): void
    {
        $fileContents = StringUtility::getUniqueId('content_');
        $this->addToMount([
            'targetFolder' => [],
            'someFile' => $fileContents
        ]);
        $subject = $this->createDriver(
            [],
            // Mocked because finfo() can not deal with vfs streams and throws warnings
            ['getMimeTypeOfFile']
        );
        $newIdentifier = $subject->moveFileWithinStorage('/someFile', '/targetFolder/', 'file');
        $fileMetadata = $subject->getFileInfoByIdentifier($newIdentifier);
        self::assertEquals($newIdentifier, $fileMetadata['identifier']);
    }

    public function renamingFiles_dataProvider(): array
    {
        return [
            'file in subfolder' => [
                [
                    'targetFolder' => ['file' => '']
                ],
                '/targetFolder/file',
                'newFile',
                '/targetFolder/newFile'
            ],
            'file in rootfolder' => [
                [
                    'fileInRoot' => ''
                ],
                '/fileInRoot',
                'newFile',
                '/newFile'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider renamingFiles_dataProvider
     * @param array $filesystemStructure
     * @param string $oldFileIdentifier
     * @param string $newFileName
     * @param string $expectedNewIdentifier
     */
    public function renamingFilesChangesFilenameOnDisk(array $filesystemStructure, string $oldFileIdentifier, string $newFileName, string $expectedNewIdentifier)
    {
        $this->addToMount($filesystemStructure);
        $subject = $this->createDriver();
        $newIdentifier = $subject->renameFile($oldFileIdentifier, $newFileName);
        self::assertFalse($subject->fileExists($oldFileIdentifier));
        self::assertTrue($subject->fileExists($newIdentifier));
        self::assertEquals($expectedNewIdentifier, $newIdentifier);
    }

    /**
     * @test
     */
    public function renamingFilesFailsIfTargetFileExists(): void
    {
        $this->expectException(ExistingTargetFileNameException::class);
        $this->expectExceptionCode(1320291063);
        $this->addToMount([
            'targetFolder' => ['file' => '', 'newFile' => '']
        ]);
        $subject = $this->createDriver();
        $subject->renameFile('/targetFolder/file', 'newFile');
    }

    /**
     * We use this data provider for testing move methods because there are some issues with the
     *
     * @return array
     */
    public function renamingFolders_dataProvider(): array
    {
        return [
            'folder in root folder' => [
                [
                    'someFolder' => []
                ],
                '/someFolder/',
                'newFolder',
                '/newFolder/'
            ],
            'file in subfolder' => [
                [
                    'subfolder' => [
                        'someFolder' => []
                    ]
                ],
                '/subfolder/someFolder/',
                'newFolder',
                '/subfolder/newFolder/'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider renamingFolders_dataProvider
     * @param array $filesystemStructure
     * @param string $oldFolderIdentifier
     * @param string $newFolderName
     * @param string $expectedNewIdentifier
     */
    public function renamingFoldersChangesFolderNameOnDisk(
        array $filesystemStructure,
        string $oldFolderIdentifier,
        string $newFolderName,
        string $expectedNewIdentifier
    ): void {
        $this->addToMount($filesystemStructure);
        $subject = $this->createDriver();
        $mapping = $subject->renameFolder($oldFolderIdentifier, $newFolderName);
        self::assertFalse($subject->folderExists($oldFolderIdentifier));
        self::assertTrue($subject->folderExists($expectedNewIdentifier));
        self::assertEquals($expectedNewIdentifier, $mapping[$oldFolderIdentifier]);
    }

    /**
     * @test
     */
    public function renameFolderReturnsCorrectMappingInformationForAllFiles(): void
    {
        $fileContents = 'asdfg';
        $this->addToMount([
            'sourceFolder' => [
                'subFolder' => ['file' => $fileContents],
                'file2' => 'asdfg'
            ]
        ]);
        $subject = $this->createDriver();
        $mappingInformation = $subject->renameFolder('/sourceFolder/', 'newFolder');
        self::assertTrue(is_array($mappingInformation));
        self::assertEquals('/newFolder/', $mappingInformation['/sourceFolder/']);
        self::assertEquals('/newFolder/file2', $mappingInformation['/sourceFolder/file2']);
        self::assertEquals('/newFolder/subFolder/file', $mappingInformation['/sourceFolder/subFolder/file']);
        self::assertEquals('/newFolder/subFolder/', $mappingInformation['/sourceFolder/subFolder/']);
    }

    /**
     * @test
     */
    public function renameFolderRevertsRenamingIfFilenameMapCannotBeCreated(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1334160746);
        $this->addToMount([
            'sourceFolder' => [
                'file' => 'asdfg'
            ]
        ]);
        $subject = $this->createDriver([], ['createIdentifierMap']);
        $subject->expects(self::atLeastOnce())->method('createIdentifierMap')->will(
            self::throwException(
                new FileOperationErrorException('testing', 1476045666)
            )
        );
        $subject->renameFolder('/sourceFolder/', 'newFolder');
        self::assertFileExists($this->getUrlInMount('/sourceFolder/file'));
    }

    /**
     * @test
     */
    public function isFolderEmptyReturnsTrueForEmptyFolder()
    {
        // This also prepares the next few tests, so add more info than required for this test
        $this->addToMount([
            'emptyFolder' => []
        ]);
        $subject = $this->createDriver();
        self::assertTrue($subject->isFolderEmpty('/emptyFolder/'));
        return $subject;
    }

    /**
     * @test
     */
    public function isFolderEmptyReturnsFalseIfFolderHasFile(): void
    {
        $this->addToMount([
            'folderWithFile' => [
                'someFile' => ''
            ]
        ]);
        $subject = $this->createDriver();
        self::assertFalse($subject->isFolderEmpty('/folderWithFile/'));
    }

    /**
     * @test
     */
    public function isFolderEmptyReturnsFalseIfFolderHasSubfolder(): void
    {
        $this->addToMount([
            'folderWithSubfolder' => [
                'someFolder' => []
            ]
        ]);
        $subject = $this->createDriver();
        self::assertFalse($subject->isFolderEmpty('/folderWithSubfolder/'));
    }

    /**********************************
     * Copy/move folder
     **********************************/
    /**
     * @test
     */
    public function foldersCanBeMovedWithinStorage(): void
    {
        $fileContents = StringUtility::getUniqueId('content_');
        $this->addToMount([
            'sourceFolder' => [
                'file' => $fileContents,
            ],
            'targetFolder' => [],
        ]);
        $subject = $this->createDriver();
        /** @var LocalDriver $subject */
        $subject->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'someFolder');
        self::assertTrue(file_exists($this->getUrlInMount('/targetFolder/someFolder/')));
        self::assertEquals($fileContents, file_get_contents($this->getUrlInMount('/targetFolder/someFolder/file')));
        self::assertFileNotExists($this->getUrlInMount('/sourceFolder'));
    }

    /**
     * @test
     */
    public function moveFolderWithinStorageReturnsCorrectMappingInformationForAllFiles(): void
    {
        $fileContents = 'asdfg';
        $this->addToMount([
            'targetFolder' => [],
            'sourceFolder' => [
                'subFolder' => ['file' => $fileContents],
                'file' => 'asdfg'
            ]
        ]);
        $subject = $this->createDriver();
        $mappingInformation = $subject->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'sourceFolder');
        self::assertEquals('/targetFolder/sourceFolder/file', $mappingInformation['/sourceFolder/file']);
        self::assertEquals(
            '/targetFolder/sourceFolder/subFolder/file',
            $mappingInformation['/sourceFolder/subFolder/file']
        );
        self::assertEquals('/targetFolder/sourceFolder/subFolder/', $mappingInformation['/sourceFolder/subFolder/']);
    }

    /**
     * @test
     */
    public function folderCanBeRenamedWhenMoving(): void
    {
        $this->addToMount([
            'sourceFolder' => [
                'file' => StringUtility::getUniqueId('content_'),
            ],
            'targetFolder' => [],
        ]);
        $subject = $this->createDriver();
        $subject->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolder');
        self::assertTrue(file_exists($this->getUrlInMount('/targetFolder/newFolder/')));
    }

    /**
     * @test
     */
    public function copyFolderWithinStorageCopiesSingleFileToNewFolderName(): void
    {
        $this->addToMount([
            'sourceFolder' => [
                'file' => StringUtility::getUniqueId('name_'),
            ],
            'targetFolder' => [],
        ]);
        $subject = $this->createDriver();
        $subject->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
        self::assertTrue(is_file($this->getUrlInMount('/targetFolder/newFolderName/file')));
    }

    /**
     * @test
     */
    public function copyFolderWithinStorageCopiesSingleSubFolderToNewFolderName(): void
    {
        [$basePath, $subject] = $this->prepareRealTestEnvironment();
        GeneralUtility::mkdir_deep($basePath . '/sourceFolder/subFolder');
        GeneralUtility::mkdir_deep($basePath . '/targetFolder');

        $subject->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
        self::assertTrue(is_dir($basePath . '/targetFolder/newFolderName/subFolder'));
    }

    /**
     * @test
     */
    public function copyFolderWithinStorageCopiesFileInSingleSubFolderToNewFolderName(): void
    {
        [$basePath, $subject] = $this->prepareRealTestEnvironment();
        GeneralUtility::mkdir_deep($basePath . '/sourceFolder/subFolder');
        GeneralUtility::mkdir_deep($basePath . '/targetFolder');
        file_put_contents($basePath . '/sourceFolder/subFolder/file', StringUtility::getUniqueId('content_'));
        GeneralUtility::fixPermissions($basePath . '/sourceFolder/subFolder/file');

        $subject->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
        self::assertTrue(is_file($basePath . '/targetFolder/newFolderName/subFolder/file'));
    }

    ///////////////////////
    // Tests concerning sanitizeFileName
    ///////////////////////

    /**
     * Set up data for sanitizeFileName tests
     */
    public function setUpCharacterStrings(): void
    {
        // Generate string containing all characters for the iso8859-1 charset, charcode greater than 127
        $this->iso88591GreaterThan127 = '';
        for ($i = 0xA0; $i <= 0xFF; $i++) {
            $this->iso88591GreaterThan127 .= chr($i);
        }

        // Generate string containing all characters for the utf-8 Latin-1 Supplement (U+0080 to U+00FF)
        // without U+0080 to U+009F: control characters
        // Based on http://www.utf8-chartable.de/unicode-utf8-table.pl
        $this->utf8Latin1Supplement = '';
        for ($i = 0xA0; $i <= 0xBF; $i++) {
            $this->utf8Latin1Supplement .= chr(0xC2) . chr($i);
        }
        for ($i = 0x80; $i <= 0xBF; $i++) {
            $this->utf8Latin1Supplement .= chr(0xC3) . chr($i);
        }

        // Generate string containing all characters for the utf-8 Latin-1 Extended-A (U+0100 to U+017F)
        $this->utf8Latin1ExtendedA = '';
        for ($i = 0x80; $i <= 0xBF; $i++) {
            $this->utf8Latin1ExtendedA .= chr(0xC4) . chr($i);
        }
        for ($i = 0x80; $i <= 0xBF; $i++) {
            $this->utf8Latin1ExtendedA .= chr(0xC5) . chr($i);
        }
    }

    /**
     * Data provider for sanitizeFileNameUTF8FilesystemDataProvider
     *
     * Every array splits into:
     * - String value fileName
     * - String value charset (none = '', utf-8, latin1, etc.)
     * - Expected result (cleaned fileName)
     *
     * @return array
     */
    public function sanitizeFileNameUTF8FilesystemDataProvider(): array
    {
        $this->setUpCharacterStrings();
        return [
            // Characters ordered by ASCII table
            'allowed characters utf-8 (ASCII part)' => [
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
            ],
            // Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
            'replace special characters with _ (not allowed characters) utf-8 (ASCII part)' => [
                '! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
                '_____________________________'
            ],
            'utf-8 (Latin-1 Supplement)' => [
                $this->utf8Latin1Supplement,
                '________________________________ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ'
            ],
            'trim leading and tailing spaces utf-8' => [
                ' test.txt  ',
                'test.txt'
            ],
            'remove tailing dot' => [
                'test.txt.',
                'test.txt'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sanitizeFileNameUTF8FilesystemDataProvider
     * @param string $fileName
     * @param string $expectedResult
     */
    public function sanitizeFileNameUTF8Filesystem(string $fileName, string $expectedResult): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 1;
        self::assertEquals(
            $expectedResult,
            $this->createDriver()->sanitizeFileName($fileName)
        );
    }

    /**
     * Data provider for sanitizeFileNameNonUTF8Filesystem
     *
     * Every array splits into:
     * - String value fileName
     * - String value charset (none = '', utf-8, latin1, etc.)
     * - Expected result (cleaned fileName)
     *
     * @return array
     */
    public function sanitizeFileNameNonUTF8FilesystemDataProvider(): array
    {
        $this->setUpCharacterStrings();
        return [
            // Characters ordered by ASCII table
            'allowed characters iso-8859-1' => [
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
                'iso-8859-1',
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
            ],
            // Characters ordered by ASCII table
            'allowed characters utf-8' => [
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
                'utf-8',
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
            ],
            // Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
            'replace special characters with _ (not allowed characters) iso-8859-1' => [
                '! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
                'iso-8859-1',
                '_____________________________'
            ],
            // Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
            'replace special characters with _ (not allowed characters) utf-8' => [
                '! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
                'utf-8',
                '_____________________________'
            ],
            'iso-8859-1 (code > 127)' => [
                // http://de.wikipedia.org/wiki/ISO_8859-1
                // chr(0xA0) = NBSP (no-break space) => gets trimmed
                $this->iso88591GreaterThan127,
                'iso-8859-1',
                '_centpound_yen____c_a_____R_____-23_u___1o__1_41_23_4_AAAAAEAAAECEEEEIIIIDNOOOOOExOEUUUUEYTHssaaaaaeaaaeceeeeiiiidnoooooe_oeuuuueythy'
            ],
            'utf-8 (Latin-1 Supplement)' => [
                // chr(0xC2) . chr(0x0A) = NBSP (no-break space) => gets trimmed
                $this->utf8Latin1Supplement,
                'utf-8',
                '_centpound__yen______c_a_______R_______-23__u_____1o__1_41_23_4_AAAAAEAAAECEEEEIIIIDNOOOOOExOEUUUUEYTHssaaaaaeaaaeceeeeiiiidnoooooe_oeuuuueythy'
            ],
            'utf-8 (Latin-1 Extended A)' => [
                $this->utf8Latin1ExtendedA,
                'utf-8',
                'AaAaAaCcCcCcCcDdDdEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiIJijJjKk__LlLlLlL_l_LlNnNnNn_n____OOooOoOoOEoeRrRrRrSsSsSsSsTtTtTtUuUuUuUuUuUuWwYyYZzZzZzs'
            ],
            'trim leading and tailing spaces iso-8859-1' => [
                ' test.txt  ',
                'iso-8859-1',
                'test.txt'
            ],
            'trim leading and tailing spaces utf-8' => [
                ' test.txt  ',
                'utf-8',
                'test.txt'
            ],
            'remove tailing dot iso-8859-1' => [
                'test.txt.',
                'iso-8859-1',
                'test.txt'
            ],
            'remove tailing dot utf-8' => [
                'test.txt.',
                'utf-8',
                'test.txt'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sanitizeFileNameNonUTF8FilesystemDataProvider
     * @param string $fileName
     * @param string $charset
     * @param string $expectedResult
     */
    public function sanitizeFileNameNonUTF8Filesystem(string $fileName, string $charset, string $expectedResult): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 0;
        self::assertEquals(
            $expectedResult,
            $this->createDriver()->sanitizeFileName($fileName, $charset)
        );
    }

    /**
     * @test
     */
    public function sanitizeFileNameThrowsExceptionOnInvalidFileName(): void
    {
        $this->expectException(InvalidFileNameException::class);
        $this->expectExceptionCode(1320288991);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 1;
        $this->createDriver()->sanitizeFileName('');
    }

    /**
     * @test
     */
    public function applyFilterMethodsToDirectoryItemCallsFilterMethodIfClosure(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1463073434);
        $closure = function () {
            throw new \Exception('I was called!', 1463073434);
        };

        $filterMethods = [
            $closure,
        ];

        $this->createDriver()->_call('applyFilterMethodsToDirectoryItem', $filterMethods, '', '', '');
    }

    /**
     * @test
     */
    public function applyFilterMethodsToDirectoryItemCallsFilterMethodIfName(): void
    {
        $dummyObject = $this
            ->getMockBuilder(LocalDriver::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $method = [
            $dummyObject,
            'dummy',
        ];
        $dummyObject->expects(self::once())->method('dummy');
        $filterMethods = [
            $method,
        ];
        $this->createDriver()->_call('applyFilterMethodsToDirectoryItem', $filterMethods, '', '', '');
    }
}
