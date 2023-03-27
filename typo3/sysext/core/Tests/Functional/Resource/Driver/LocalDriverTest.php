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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Driver;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Tests\Functional\Resource\Driver\Fixtures\LocalDriverFilenameFilter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LocalDriverTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    private string $baseDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDirectory = $this->instancePath . '/local-driver-tests';
        mkdir($this->baseDirectory);
        mkdir(Environment::getVarPath() . '/tests');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/local-driver-tests', true);
        GeneralUtility::rmdir(Environment::getVarPath() . '/tests');
        parent::tearDown();
    }

    private function getDefaultInitializedSubject(): LocalDriver
    {
        $driverConfiguration = [
            'basePath' => $this->baseDirectory,
        ];
        $subject = new LocalDriver($driverConfiguration);
        $subject->processConfiguration();
        return $subject;
    }

    /**
     * @test
     */
    public function calculatedBasePathRelativeIsSane(): void
    {
        // This would cause problems if you fill "/fileadmin/" into the base path field of a sys_file_storage record and select "relative" as path type
        $relativeDriverConfiguration = [
            'pathType' => 'relative',
            'basePath' => '/typo3temp/var/tests/',
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, null);
        $basePath = $subject->_call('calculateBasePath', $relativeDriverConfiguration);
        self::assertStringNotContainsString('//', $basePath);
    }

    /**
     * @test
     */
    public function calculatedBasePathAbsoluteIsSane(): void
    {
        // This test checks if "/../" are properly filtered out (i.e. from "Base path" field of sys_file_storage)
        $varPath = Environment::getVarPath();
        $projectPath = Environment::getProjectPath();
        $relativeVarPath = str_replace($projectPath, '', $varPath);
        $segments = str_repeat('/..', substr_count($relativeVarPath, '/') + 1);
        $relativeDriverConfiguration = [
            'basePath' => Environment::getVarPath() . '/tests' . $segments . $relativeVarPath . '/tests/',
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, null);
        $basePath = $subject->_call('calculateBasePath', $relativeDriverConfiguration);
        self::assertStringNotContainsString('/../', $basePath);
    }

    public static function publicUrlIsCalculatedCorrectlyWithDifferentBasePathsAndBasUrisDataProvider(): array
    {
        return [
            'no base uri, within public' => [
                '/files/',
                '',
                '/foo.txt',
                true,
                'files/foo.txt',
            ],
            'no base uri, within project' => [
                '/../files/',
                '',
                '/foo.txt',
                false,
                null,
            ],
            'base uri with host, within public' => [
                '/files/',
                'https://host.tld/',
                '/foo.txt',
                true,
                'https://host.tld/foo.txt',
            ],
            'base uri with host, within project' => [
                '/../files/',
                'https://host.tld/',
                '/foo.txt',
                true,
                'https://host.tld/foo.txt',
            ],
            'base uri with path only, within public' => [
                '/files/',
                'assets/',
                '/foo.txt',
                true,
                'assets/foo.txt',
            ],
            'base uri with path only, within project' => [
                '/../files/',
                'assets/',
                '/foo.txt',
                true,
                'assets/foo.txt',
            ],
            'base uri with path only, within other public dir' => [
                '/../public/assets/',
                'assets/',
                '/foo.txt',
                true,
                'assets/foo.txt',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider publicUrlIsCalculatedCorrectlyWithDifferentBasePathsAndBasUrisDataProvider
     */
    public function publicUrlIsCalculatedCorrectlyWithDifferentBasePathsAndBasUris(string $basePath, string $baseUri, string $fileName, bool $expectedIsPublic, ?string $expectedPublicUrl): void
    {
        $projectPath = $this->baseDirectory . '/app';
        $publicPath = $projectPath . '/public';
        $absoluteBaseDir = $publicPath . $basePath;
        mkdir($projectPath);
        mkdir($publicPath);
        mkdir($absoluteBaseDir, 0777, true);
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            $projectPath,
            $publicPath,
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isUnix() ? 'UNIX' : 'WINDOWS'
        );
        $driverConfiguration = [
            'pathType' => 'relative',
            'basePath' => $basePath,
            'baseUri' => $baseUri,
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, null, [$driverConfiguration]);
        $subject->processConfiguration();
        self::assertSame($expectedIsPublic, $subject->hasCapability(ResourceStorageInterface::CAPABILITY_PUBLIC));
        self::assertSame($fileName, $subject->createFile($fileName, '/'));
        self::assertSame($expectedPublicUrl, $subject->getPublicUrl($fileName));
    }

    /**
     * @test
     */
    public function createFolderRecursiveSanitizesFilename(): void
    {
        $driverConfiguration = [
            'basePath' => $this->baseDirectory,
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, ['sanitizeFilename'], [$driverConfiguration]);
        $subject->processConfiguration();
        $subject->expects(self::exactly(2))
            ->method('sanitizeFileName')
            ->willReturn(
                'sanitized'
            );
        $subject->createFolder('newFolder/andSubfolder', '/', true);
        self::assertFileExists($this->baseDirectory . '/sanitized/sanitized');
    }

    /**
     * @test
     */
    public function determineBaseUrlUrlEncodesUriParts(): void
    {
        $subject = $this->getAccessibleMock(LocalDriver::class, ['hasCapability'], [], '', false);
        $subject->expects(self::once())
            ->method('hasCapability')
            ->with(ResourceStorageInterface::CAPABILITY_PUBLIC)
            ->willReturn(
                true
            );
        $subject->_set('absoluteBasePath', Environment::getPublicPath() . '/un encö/ded %path/');
        $subject->_call('determineBaseUrl');
        $baseUri = $subject->_get('baseUri');
        self::assertEquals(rawurlencode('un encö') . '/' . rawurlencode('ded %path') . '/', $baseUri);
    }

    /**
     * @test
     */
    public function getDefaultFolderReturnsFolderForUserUploadPath(): void
    {
        $subject = $this->getDefaultInitializedSubject();
        self::assertEquals('/user_upload/', $subject->getDefaultFolder());
    }

    /**
     * @test
     */
    public function defaultLevelFolderFolderIsCreatedIfItDoesntExist(): void
    {
        $subject = $this->getDefaultInitializedSubject();
        self::assertFileExists($this->baseDirectory . '/' . $subject->getDefaultFolder());
    }

    /**
     * @test
     */
    public function getFolderInFolderReturnsCorrectFolderObject(): void
    {
        mkdir($this->baseDirectory . '/someDir');
        mkdir($this->baseDirectory . '/someDir/someSubdir');
        $subject = $this->getDefaultInitializedSubject();
        self::assertEquals('/someDir/someSubdir/', $subject->getFolderInFolder('someSubdir', '/someDir/'));
    }

    /**
     * @test
     */
    public function createFolderCreatesFolderOnDisk(): void
    {
        mkdir($this->baseDirectory . '/some');
        mkdir($this->baseDirectory . '/some/folder');
        $subject = $this->getDefaultInitializedSubject();
        $subject->createFolder('path', '/some/folder/');
        self::assertFileExists($this->baseDirectory . '/some/folder/path');
    }

    /**
     * @test
     */
    public function createFolderReturnsFolderObject(): void
    {
        mkdir($this->baseDirectory . '/some');
        mkdir($this->baseDirectory . '/some/folder');
        $subject = $this->getDefaultInitializedSubject();
        $createdFolder = $subject->createFolder('path', '/some/folder/');
        self::assertEquals('/some/folder/path/', $createdFolder);
    }

    public static function createFolderSanitizesFolderNameBeforeCreationDataProvider(): array
    {
        return [
            'folder name with NULL character' => [
                'some' . "\0" . 'Folder',
                'some_Folder',
            ],
            'folder name with directory part' => [
                '../someFolder',
                '.._someFolder',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider createFolderSanitizesFolderNameBeforeCreationDataProvider
     */
    public function createFolderSanitizesFolderNameBeforeCreation(string $newFolderName, string $expectedFolderName): void
    {
        mkdir($this->baseDirectory . '/some');
        mkdir($this->baseDirectory . '/some/folder');
        $subject = $this->getDefaultInitializedSubject();
        $subject->createFolder($newFolderName, '/some/folder/');
        self::assertFileExists($this->baseDirectory . '/some/folder/' . $expectedFolderName);
    }

    /**
     * @test
     */
    public function basePathIsNormalizedWithTrailingSlash(): void
    {
        $driverConfiguration = [
            'basePath' => $this->baseDirectory,
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, null, [$driverConfiguration]);
        $subject->processConfiguration();
        self::assertEquals('/', substr($subject->_call('getAbsoluteBasePath'), -1));
    }

    /**
     * @test
     */
    public function noSecondSlashIsAddedIfBasePathAlreadyHasTrailingSlash(): void
    {
        $driverConfiguration = [
            'basePath' => $this->baseDirectory,
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, null, [$driverConfiguration]);
        $subject->processConfiguration();
        self::assertNotEquals('/', substr($subject->_call('getAbsoluteBasePath'), -2, 1));
    }

    public static function getSpecificFileInformationDataProvider(): array
    {
        return [
            'size' => [
                'expectedValue' => filesize(__DIR__ . '/Fixtures/Dummy.html'),
                'propertyName' => 'size',
            ],
            'atime' => [
                'expectedValue' => 'WILL_BE_REPLACED_BY_VFS_TIME',
                'propertyName' => 'atime',
            ],
            'mtime' => [
                'expectedValue' => 'WILL_BE_REPLACED_BY_VFS_TIME',
                'propertyName' => 'mtime',
            ],
            'ctime' => [
                'expectedValue' => 'WILL_BE_REPLACED_BY_VFS_TIME',
                'propertyName' => 'ctime',
            ],
            'name' => [
                'expectedValue' => 'Dummy.html',
                'propertyName' => 'name',
            ],
            'mimetype' => [
                'expectedValue' => 'text/html',
                'propertyName' => 'mimetype',
            ],
            'identifier' => [
                'expectedValue' => '/Dummy.html',
                'propertyName' => 'identifier',
            ],
            'storage' => [
                'expectedValue' => 5,
                'propertyName' => 'storage',
            ],
            'identifier_hash' => [
                'expectedValue' => 'b11efa5d7c0556a65c6aa261343b9807cac993bc',
                'propertyName' => 'identifier_hash',
            ],
            'folder_hash' => [
                'expectedValue' => '42099b4af021e53fd8fd4e056c2568d7c2e3ffa8',
                'propertyName' => 'folder_hash',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getSpecificFileInformationDataProvider
     */
    public function getSpecificFileInformationReturnsRequestedFileInformation(string|int $expectedValue, string $property): void
    {
        copy(__DIR__ . '/Fixtures/Dummy.html', $this->baseDirectory . '/Dummy.html');
        if (in_array($property, ['mtime', 'ctime', 'atime'])) {
            $expectedValue = filemtime($this->baseDirectory . '/Dummy.html');
        }
        $subject = $this->getDefaultInitializedSubject();
        $subject->setStorageUid(5);
        self::assertSame($expectedValue, $subject->getSpecificFileInformation($this->baseDirectory . '/Dummy.html', '/', $property));
    }

    /**
     * @test
     */
    public function getAbsolutePathReturnsCorrectPath(): void
    {
        mkdir($this->baseDirectory . '/someFolder');
        file_put_contents($this->baseDirectory . '/someFolder/file1.ext', 'asdfg');
        $driverConfiguration = [
            'basePath' => $this->baseDirectory,
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, null, [$driverConfiguration]);
        $subject->processConfiguration();
        self::assertEquals($this->baseDirectory . '/someFolder/file1.ext', $subject->_call('getAbsolutePath', '/someFolder/file1.ext'));
    }

    /**
     * @test
     */
    public function addFileMovesFileToCorrectLocation(): void
    {
        mkdir($this->baseDirectory . '/targetFolder');
        mkdir($this->baseDirectory . '/sourceFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/file', 'asdf');
        $subject = $this->getDefaultInitializedSubject();
        $subject->addFile($this->baseDirectory . '/sourceFolder/file', '/targetFolder/', 'file');
        self::assertFileExists($this->baseDirectory . '/targetFolder/file');
    }

    /**
     * @test
     */
    public function addFileUsesFilenameIfGiven(): void
    {
        mkdir($this->baseDirectory . '/targetFolder');
        mkdir($this->baseDirectory . '/sourceFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/file', 'asdf');
        $subject = $this->getDefaultInitializedSubject();
        $subject->addFile($this->baseDirectory . '/sourceFolder/file', '/targetFolder/', 'targetFile');
        self::assertFileExists($this->baseDirectory . '/targetFolder/targetFile');
    }

    /**
     * @test
     */
    public function addFileFailsIfFileIsInDriverStorage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314778269);
        mkdir($this->baseDirectory . '/targetFolder');
        file_put_contents($this->baseDirectory . '/targetFolder/file', 'asdf');
        $subject = $this->getDefaultInitializedSubject();
        $subject->setStorageUid(5);
        $subject->addFile($this->baseDirectory . '/targetFolder/file', '/targetFolder/', 'file');
    }

    /**
     * @test
     */
    public function addFileReturnsFileIdentifier(): void
    {
        mkdir($this->baseDirectory . '/targetFolder');
        mkdir($this->baseDirectory . '/sourceFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/file', 'asdf');
        $subject = $this->getDefaultInitializedSubject();
        $fileIdentifier = $subject->addFile($this->baseDirectory . '/sourceFolder/file', '/targetFolder/', 'file');
        self::assertEquals('/targetFolder/file', $fileIdentifier);
    }

    /**
     * @test
     */
    public function existenceChecksWorkForFilesAndFolders(): void
    {
        mkdir($this->baseDirectory . '/folder');
        file_put_contents($this->baseDirectory . '/file', 'asdf');
        $subject = $this->getDefaultInitializedSubject();
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
        mkdir($this->baseDirectory . '/subfolder');
        file_put_contents($this->baseDirectory . '/subfolder/file', 'asdf');
        mkdir($this->baseDirectory . '/subfolder/folder');
        $subject = $this->getDefaultInitializedSubject();
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
        $baseUri = 'https://example.org/foobar/' . StringUtility::getUniqueId('uri_');
        $driverConfiguration = [
            'baseUri' => $baseUri,
            'basePath' => $this->baseDirectory,
        ];
        $subject = new LocalDriver($driverConfiguration);
        $subject->processConfiguration();
        self::assertEquals($baseUri . '/file.ext', $subject->getPublicUrl('/file.ext'));
        self::assertEquals($baseUri . '/subfolder/file2.ext', $subject->getPublicUrl('/subfolder/file2.ext'));
    }

    public static function getPublicUrlReturnsValidUrlContainingSpecialCharactersDataProvider(): array
    {
        return [
            ['/single file with some special chars äüö!.txt'],
            ['/on subfolder/with special chars äüö!.ext'],
            ['/who names a file like !"§$%&()=?*+~"#\'´`<>-.ext'],
            ['no leading slash !"§$%&()=?*+~#\'"´`"<>-.txt'],
        ];
    }

    /**
     * @test
     * @dataProvider getPublicUrlReturnsValidUrlContainingSpecialCharactersDataProvider
     */
    public function getPublicUrlReturnsValidUrlContainingSpecialCharacters(string $fileIdentifier): void
    {
        $baseUri = 'https://example.org/foobar/' . StringUtility::getUniqueId('uri_');
        $driverConfiguration = [
            'baseUri' => $baseUri,
            'basePath' => $this->baseDirectory,
        ];
        $subject = new LocalDriver($driverConfiguration);
        $subject->processConfiguration();
        $publicUrl = $subject->getPublicUrl($fileIdentifier);
        self::assertTrue(GeneralUtility::isValidUrl($publicUrl));
    }

    /**
     * @test
     */
    public function fileContentsCanBeWrittenAndRead(): void
    {
        $fileContents = 'asdf';
        file_put_contents($this->baseDirectory . '/file.ext', $fileContents);
        $subject = $this->getDefaultInitializedSubject();
        self::assertEquals($fileContents, $subject->getFileContents('/file.ext'));
        $newFileContents = 'asdfgh';
        $subject->setFileContents('/file.ext', $newFileContents);
        self::assertEquals($newFileContents, $subject->getFileContents('/file.ext'));
    }

    /**
     * @test
     */
    public function setFileContentsReturnsNumberOfBytesWrittenToFile(): void
    {
        $fileContents = 'asdf';
        file_put_contents($this->baseDirectory . '/file.ext', $fileContents);
        $subject = $this->getDefaultInitializedSubject();
        $newFileContents = 'asdfgh';
        $bytesWritten = $subject->setFileContents('/file.ext', $newFileContents);
        self::assertEquals(strlen($newFileContents), $bytesWritten);
    }

    /**
     * @test
     */
    public function newFilesCanBeCreated(): void
    {
        $subject = $this->getDefaultInitializedSubject();
        $subject->createFile('testfile.txt', '/');
        self::assertFileExists($this->baseDirectory . '/testfile.txt');
        self::assertTrue($subject->fileExists('/testfile.txt'));
    }

    /**
     * @test
     */
    public function createdFilesAreEmpty(): void
    {
        $subject = $this->getDefaultInitializedSubject();
        $subject->createFile('testfile.txt', '/');
        self::assertSame('', file_get_contents($this->baseDirectory . '/testfile.txt'));
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
        // No one will use this as his default file create mask, we hopefully don't get any false positives
        $testPattern = '0646';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = $testPattern;
        mkdir($this->baseDirectory . '/someDir');
        $subject = $this->getDefaultInitializedSubject();
        $subject->createFile('testfile.txt', '/someDir');
        self::assertEquals((int)$testPattern, (int)(decoct(fileperms($this->baseDirectory . '/someDir/testfile.txt') & 0777)));
    }

    /**
     * @test
     */
    public function getFileReturnsCorrectIdentifier(): void
    {
        copy(__DIR__ . '/Fixtures/Dummy.html', $this->baseDirectory . '/Dummy.html');
        copy(__DIR__ . '/Fixtures/LocalDriverFilenameFilter.php', $this->baseDirectory . '/LocalDriverFilenameFilter.php');
        $subject = $this->getDefaultInitializedSubject();
        $fileInfo = $subject->getFileInfoByIdentifier('Dummy.html');
        self::assertEquals('/Dummy.html', $fileInfo['identifier']);
        $fileInfo = $subject->getFileInfoByIdentifier('LocalDriverFilenameFilter.php');
        self::assertEquals('/LocalDriverFilenameFilter.php', $fileInfo['identifier']);
    }

    /**
     * @test
     */
    public function getFileThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314516809);
        $subject = $this->getDefaultInitializedSubject();
        $subject->getFileInfoByIdentifier('/some/file/at/a/random/path');
    }

    /**
     * @test
     */
    public function getFilesInFolderReturnsEmptyArrayForEmptyDirectory(): void
    {
        $subject = $this->getDefaultInitializedSubject();
        $fileList = $subject->getFilesInFolder('/');
        self::assertEmpty($fileList);
    }

    /**
     * @test
     */
    public function getFileListReturnsAllFilesInDirectory(): void
    {
        mkdir($this->baseDirectory . '/aDir');
        file_put_contents($this->baseDirectory . '/file1', 'asdfg');
        file_put_contents($this->baseDirectory . '/file2', 'fdsa');
        $subject = $this->getDefaultInitializedSubject();
        $fileList = $subject->getFilesInFolder('/');
        self::assertEquals(['/file1', '/file2'], array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFileListReturnsAllFilesInSubdirectoryIfRecursiveParameterIsSet(): void
    {
        mkdir($this->baseDirectory . '/aDir');
        file_put_contents($this->baseDirectory . '/aDir/file3', 'asdfgh');
        mkdir($this->baseDirectory . '/aDir/subDir');
        file_put_contents($this->baseDirectory . '/aDir/subDir/file4', 'asklfjklasjkl');
        file_put_contents($this->baseDirectory . '/file1', 'asdfg');
        file_put_contents($this->baseDirectory . '/file2', 'fdsa');
        $subject = $this->getDefaultInitializedSubject();
        $fileList = $subject->getFilesInFolder('/', 0, 0, true);
        self::assertEquals(['/file1', '/file2', '/aDir/file3', '/aDir/subDir/file4'], array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFileListFailsIfDirectoryDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314349666);
        file_put_contents($this->baseDirectory . '/somefile', '');
        $subject = $this->getDefaultInitializedSubject();
        $subject->getFilesInFolder('somedir/');
    }

    /**
     * @test
     */
    public function getFileInFolderCallsConfiguredCallbackFunctionWithGivenItemName(): void
    {
        file_put_contents($this->baseDirectory . '/file2', 'fdsa');
        $callback = [
            [
                static::class,
                'callbackStaticTestFunction',
            ],
        ];
        // the callback function will throw an exception used to check if it was called with correct $itemName
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1336159604);
        $subject = $this->getDefaultInitializedSubject();
        $subject->getFilesInFolder('/', 0, 0, false, $callback);
    }

    /**
     * Static callback function used to test if the filter callbacks work
     * As it is static we are using an exception to test if it is really called and works
     *
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
        file_put_contents($this->baseDirectory . '/fileA', 'asdfg');
        file_put_contents($this->baseDirectory . '/fileB', 'fdsa');
        $subject = $this->getDefaultInitializedSubject();
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
        mkdir($this->baseDirectory . '/dir1');
        mkdir($this->baseDirectory . '/dir2');
        file_put_contents($this->baseDirectory . '/file', 'asdfg');
        $subject = $this->getDefaultInitializedSubject();
        $fileList = $subject->getFoldersInFolder('/');
        self::assertEquals(['/dir1/', '/dir2/'], array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFolderListReturnsHiddenFoldersByDefault(): void
    {
        mkdir($this->baseDirectory . '/.someHiddenDir');
        mkdir($this->baseDirectory . '/aDir');
        file_put_contents($this->baseDirectory . '/file1', '');
        $subject = $this->getDefaultInitializedSubject();
        $fileList = $subject->getFoldersInFolder('/');
        self::assertEquals(['/.someHiddenDir/', '/aDir/'], array_keys($fileList));
    }

    /**
     * Checks if the folder names '.' and '..' are ignored when listing subdirectories
     *
     * @test
     */
    public function getFolderListLeavesOutNavigationalEntries(): void
    {
        $subject = $this->getDefaultInitializedSubject();
        $fileList = $subject->getFoldersInFolder('/');
        self::assertEmpty($fileList);
    }

    /**
     * @test
     */
    public function getFolderListFiltersItemsWithGivenFilterMethods(): void
    {
        mkdir($this->baseDirectory . '/folderA');
        mkdir($this->baseDirectory . '/folderB');
        $subject = $this->getDefaultInitializedSubject();
        $filterCallbacks = [
            [
                LocalDriverFilenameFilter::class,
                'filterFilename',
            ],
        ];
        $folderList = $subject->getFoldersInFolder('/', 0, 0, false, $filterCallbacks);
        self::assertNotContains('/folderA/', array_values($folderList));
    }

    /**
     * @test
     */
    public function getFolderListFailsIfDirectoryDoesNotExist(): void
    {
        file_put_contents($this->baseDirectory . 'somefile', '');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314349666);
        $subject = $this->getDefaultInitializedSubject();
        $subject->getFoldersInFolder('somedir/');
    }

    /**
     * @test
     */
    public function hashReturnsCorrectHashes(): void
    {
        $expectedMd5Hash = '8c67dbaf0ba22f2e7fbc26413b86051b';
        $expectedSha1Hash = 'a60cd808ba7a0bcfa37fa7f3fb5998e1b8dbcd9d';
        file_put_contents($this->baseDirectory . '/hashFile', '68b329da9893e34099c7d8ad5cb9c940');
        $subject = $this->getDefaultInitializedSubject();
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
        file_put_contents($this->baseDirectory . '/hashFile', '68b329da9893e34099c7d8ad5cb9c940');
        $subject = $this->getDefaultInitializedSubject();
        $subject->hash('/hashFile', StringUtility::getUniqueId('uri_'));
    }

    /**
     * @test
     */
    public function getFileForLocalProcessingCreatesCopyOfFileByDefault(): void
    {
        mkdir($this->baseDirectory . '/someDir');
        file_put_contents($this->baseDirectory . '/someDir/someFile', 'asdfgh');
        $driverConfiguration = [
            'basePath' => $this->baseDirectory,
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, ['copyFileToTemporaryPath'], [$driverConfiguration]);
        $subject->processConfiguration();
        $subject->expects(self::once())->method('copyFileToTemporaryPath');
        $subject->getFileForLocalProcessing('/someDir/someFile');
    }

    /**
     * @test
     */
    public function getFileForLocalProcessingReturnsOriginalFilepathForReadonlyAccess(): void
    {
        mkdir($this->baseDirectory . '/someDir');
        file_put_contents($this->baseDirectory . '/someDir/someFile', 'asdfgh');
        $subject = $this->getDefaultInitializedSubject();
        $filePath = $subject->getFileForLocalProcessing('/someDir/someFile', false);
        self::assertEquals($filePath, $this->baseDirectory . '/someDir/someFile');
    }

    /**
     * @test
     */
    public function filesCanBeCopiedToATemporaryPath(): void
    {
        mkdir($this->baseDirectory . '/someDir');
        file_put_contents($this->baseDirectory . '/someDir/someFile.ext', 'asdfgh');
        $driverConfiguration = [
            'basePath' => $this->baseDirectory,
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, null, [$driverConfiguration]);
        $subject->processConfiguration();
        $filePath = $subject->_call('copyFileToTemporaryPath', '/someDir/someFile.ext');
        self::assertStringContainsString(Environment::getVarPath() . '/transient/', $filePath);
        self::assertEquals('asdfgh', file_get_contents($filePath));
    }

    /**
     * @test
     */
    public function permissionsAreCorrectlyRetrievedForAllowedFile(): void
    {
        file_put_contents($this->baseDirectory . '/someFile', '');
        chmod($this->baseDirectory . '/someFile', 448);
        $subject = $this->getDefaultInitializedSubject();
        self::assertEquals(['r' => true, 'w' => true], $subject->getPermissions('/someFile'));
    }

    /**
     * @test
     */
    public function permissionsAreCorrectlyRetrievedForAllowedFolder(): void
    {
        mkdir($this->baseDirectory . '/someFolder');
        chmod($this->baseDirectory . '/someFolder', 448);
        $subject = $this->getDefaultInitializedSubject();
        self::assertEquals(['r' => true, 'w' => true], $subject->getPermissions('/someFolder'));
    }

    /**
     * @test
     */
    public function isWithinRecognizesFilesWithinFolderAndInOtherFolders(): void
    {
        $subject = $this->getDefaultInitializedSubject();
        self::assertTrue($subject->isWithin('/someFolder/', '/someFolder/test.jpg'));
        self::assertTrue($subject->isWithin('/someFolder/', '/someFolder/subFolder/test.jpg'));
        self::assertFalse($subject->isWithin('/someFolder/', '/someFolderWithALongName/test.jpg'));
    }

    /**
     * @test
     */
    public function isWithinAcceptsFileAndFolderObjectsAsContent(): void
    {
        $subject = $this->getDefaultInitializedSubject();
        self::assertTrue($subject->isWithin('/someFolder/', '/someFolder/test.jpg'));
        self::assertTrue($subject->isWithin('/someFolder/', '/someFolder/subfolder/'));
    }

    /**
     * @test
     */
    public function filesCanBeCopiedWithinStorage(): void
    {
        $fileContents = StringUtility::getUniqueId('content_');
        file_put_contents($this->baseDirectory . '/someFile', $fileContents);
        mkdir($this->baseDirectory . '/targetFolder');
        $subject = $this->getDefaultInitializedSubject();
        $subject->copyFileWithinStorage('/someFile', '/targetFolder/', 'someFile');
        self::assertFileEquals($this->baseDirectory . '/someFile', $this->baseDirectory . '/targetFolder/someFile');
    }

    /**
     * @test
     */
    public function filesCanBeMovedWithinStorage(): void
    {
        $fileContents = StringUtility::getUniqueId('content_');
        file_put_contents($this->baseDirectory . '/someFile', $fileContents);
        mkdir($this->baseDirectory . '/targetFolder');
        $subject = $this->getDefaultInitializedSubject();
        $newIdentifier = $subject->moveFileWithinStorage('/someFile', '/targetFolder/', 'file');
        self::assertEquals($fileContents, file_get_contents($this->baseDirectory . '/targetFolder/file'));
        self::assertFileDoesNotExist($this->baseDirectory . '/someFile');
        self::assertEquals('/targetFolder/file', $newIdentifier);
    }

    /**
     * @test
     */
    public function fileMetadataIsChangedAfterMovingFile(): void
    {
        $fileContents = StringUtility::getUniqueId('content_');
        file_put_contents($this->baseDirectory . '/someFile', $fileContents);
        mkdir($this->baseDirectory . '/targetFolder');
        $subject = $this->getDefaultInitializedSubject();
        $newIdentifier = $subject->moveFileWithinStorage('/someFile', '/targetFolder/', 'file');
        $fileMetadata = $subject->getFileInfoByIdentifier($newIdentifier);
        self::assertEquals($newIdentifier, $fileMetadata['identifier']);
    }

    /**
     * @test
     */
    public function renamingFilesChangesFilenameOnDiskInRootFolder(): void
    {
        file_put_contents($this->baseDirectory . '/file', '');
        $subject = $this->getDefaultInitializedSubject();
        $newIdentifier = $subject->renameFile('/file', 'newFile');
        self::assertFileDoesNotExist($this->baseDirectory . '/file');
        self::assertFalse($subject->fileExists('/file'));
        self::assertFileExists($this->baseDirectory . '/newFile');
        self::assertTrue($subject->fileExists('/newFile'));
        self::assertEquals('/newFile', $newIdentifier);
    }

    /**
     * @test
     */
    public function renamingFilesChangesFilenameOnDiskInSubFolder(): void
    {
        mkdir($this->baseDirectory . '/targetFolder');
        file_put_contents($this->baseDirectory . '/targetFolder/file', '');
        $subject = $this->getDefaultInitializedSubject();
        $newIdentifier = $subject->renameFile('/targetFolder/file', 'newFile');
        self::assertFileDoesNotExist($this->baseDirectory . '/targetFolder/file');
        self::assertFalse($subject->fileExists('/targetFolder/file'));
        self::assertFileExists($this->baseDirectory . '/targetFolder/newFile');
        self::assertTrue($subject->fileExists('/targetFolder/newFile'));
        self::assertEquals('/targetFolder/newFile', $newIdentifier);
    }

    /**
     * @test
     */
    public function renamingFilesFailsIfTargetFileExists(): void
    {
        $this->expectException(ExistingTargetFileNameException::class);
        $this->expectExceptionCode(1320291063);
        mkdir($this->baseDirectory . '/targetFolder');
        file_put_contents($this->baseDirectory . '/targetFolder/file', '');
        file_put_contents($this->baseDirectory . '/targetFolder/newFile', '');
        $subject = $this->getDefaultInitializedSubject();
        $subject->renameFile('/targetFolder/file', 'newFile');
    }

    /**
     * @test
     */
    public function renamingFoldersChangesFolderNameOnDiskInRootFolder(): void
    {
        mkdir($this->baseDirectory . '/someFolder');
        $subject = $this->getDefaultInitializedSubject();
        $mapping = $subject->renameFolder('/someFolder/', 'newFolder');
        self::assertFileDoesNotExist($this->baseDirectory . '/someFolder');
        self::assertFalse($subject->folderExists('/someFolder/'));
        self::assertFileExists($this->baseDirectory . '/newFolder');
        self::assertTrue($subject->folderExists('/newFolder/'));
        self::assertEquals('/newFolder/', $mapping['/someFolder/']);
    }

    /**
     * @test
     */
    public function renamingFoldersChangesFolderNameOnDiskInSubFolder(): void
    {
        mkdir($this->baseDirectory . '/subFolder');
        mkdir($this->baseDirectory . '/subFolder/someFolder');
        $subject = $this->getDefaultInitializedSubject();
        $mapping = $subject->renameFolder('/subFolder/someFolder/', 'newFolder');
        self::assertFileDoesNotExist($this->baseDirectory . '/subFolder/someFolder');
        self::assertFalse($subject->folderExists('/subFolder/someFolder'));
        self::assertFileExists($this->baseDirectory . '/subFolder/newFolder');
        self::assertTrue($subject->folderExists('/subFolder/newFolder'));
        self::assertEquals('/subFolder/newFolder/', $mapping['/subFolder/someFolder/']);
    }

    /**
     * @test
     */
    public function renameFolderReturnsCorrectMappingInformationForAllFiles(): void
    {
        mkdir($this->baseDirectory . '/sourceFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/file2', 'asdfg');
        mkdir($this->baseDirectory . '/sourceFolder/subFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/subFolder/file', 'asdfg');
        $subject = $this->getDefaultInitializedSubject();
        $mappingInformation = $subject->renameFolder('/sourceFolder/', 'newFolder');
        self::assertIsArray($mappingInformation);
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
        mkdir($this->baseDirectory . '/sourceFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/file', 'asdfg');
        $driverConfiguration = [
            'basePath' => $this->baseDirectory,
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, ['createIdentifierMap'], [$driverConfiguration]);
        $subject->processConfiguration();
        $subject->expects(self::atLeastOnce())->method('createIdentifierMap')->will(
            self::throwException(
                new FileOperationErrorException('testing', 1476045666)
            )
        );
        $subject->renameFolder('/sourceFolder/', 'newFolder');
        self::assertFileExists($this->baseDirectory . '/sourceFolder/file');
    }

    /**
     * @test
     */
    public function isFolderEmptyReturnsTrueForEmptyFolder(): LocalDriver
    {
        mkdir($this->baseDirectory . '/emptyFolder');
        $subject = $this->getDefaultInitializedSubject();
        self::assertTrue($subject->isFolderEmpty('/emptyFolder/'));
        return $subject;
    }

    /**
     * @test
     */
    public function isFolderEmptyReturnsFalseIfFolderHasFile(): void
    {
        mkdir($this->baseDirectory . '/folderWithFile');
        file_put_contents($this->baseDirectory . '/folderWithFile/someFile', '');
        $subject = $this->getDefaultInitializedSubject();
        self::assertFalse($subject->isFolderEmpty('/folderWithFile/'));
    }

    /**
     * @test
     */
    public function isFolderEmptyReturnsFalseIfFolderHasSubfolder(): void
    {
        mkdir($this->baseDirectory . '/folderWithSubFolder');
        mkdir($this->baseDirectory . '/folderWithSubFolder/someFolder');
        $subject = $this->getDefaultInitializedSubject();
        self::assertFalse($subject->isFolderEmpty('/folderWithSubFolder/'));
    }

    /**
     * @test
     */
    public function foldersCanBeMovedWithinStorage(): void
    {
        $fileContents = StringUtility::getUniqueId('content_');
        mkdir($this->baseDirectory . '/sourceFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/file', $fileContents);
        mkdir($this->baseDirectory . '/targetFolder');
        $subject = $this->getDefaultInitializedSubject();
        $subject->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'someFolder');
        self::assertFileExists($this->baseDirectory . '/targetFolder/someFolder/');
        self::assertEquals($fileContents, file_get_contents($this->baseDirectory . '/targetFolder/someFolder/file'));
        self::assertFileDoesNotExist($this->baseDirectory . '/sourceFolder');
    }

    /**
     * @test
     */
    public function moveFolderWithinStorageReturnsCorrectMappingInformationForAllFiles(): void
    {
        mkdir($this->baseDirectory . '/targetFolder');
        mkdir($this->baseDirectory . '/sourceFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/file', 'asdfg');
        mkdir($this->baseDirectory . '/sourceFolder/subFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/subFolder/file', 'asdfg');
        $subject = $this->getDefaultInitializedSubject();
        $mappingInformation = $subject->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'sourceFolder');
        self::assertEquals('/targetFolder/sourceFolder/file', $mappingInformation['/sourceFolder/file']);
        self::assertEquals('/targetFolder/sourceFolder/subFolder/file', $mappingInformation['/sourceFolder/subFolder/file']);
        self::assertEquals('/targetFolder/sourceFolder/subFolder/', $mappingInformation['/sourceFolder/subFolder/']);
    }

    /**
     * @test
     */
    public function folderCanBeRenamedWhenMoving(): void
    {
        mkdir($this->baseDirectory . '/sourceFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/file', StringUtility::getUniqueId('content_'));
        mkdir($this->baseDirectory . '/targetFolder');
        $subject = $this->getDefaultInitializedSubject();
        $subject->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolder');
        self::assertFileExists($this->baseDirectory . '/targetFolder/newFolder/');
    }

    /**
     * @test
     */
    public function copyFolderWithinStorageCopiesSingleFileToNewFolderName(): void
    {
        mkdir($this->baseDirectory . '/sourceFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/file', StringUtility::getUniqueId('name_'));
        mkdir($this->baseDirectory . '/targetFolder');
        $subject = $this->getDefaultInitializedSubject();
        $subject->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
        self::assertTrue(is_file($this->baseDirectory . '/targetFolder/newFolderName/file'));
    }

    /**
     * @test
     */
    public function copyFolderWithinStorageCopiesSingleSubFolderToNewFolderName(): void
    {
        mkdir($this->baseDirectory . '/sourceFolder');
        mkdir($this->baseDirectory . '/sourceFolder/subFolder');
        mkdir($this->baseDirectory . '/targetFolder');
        $subject = $this->getDefaultInitializedSubject();
        $subject->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
        self::assertDirectoryExists($this->baseDirectory . '/targetFolder/newFolderName/subFolder');
    }

    /**
     * @test
     */
    public function copyFolderWithinStorageCopiesFileInSingleSubFolderToNewFolderName(): void
    {
        mkdir($this->baseDirectory . '/sourceFolder');
        mkdir($this->baseDirectory . '/sourceFolder/subFolder');
        file_put_contents($this->baseDirectory . '/sourceFolder/subFolder/file', StringUtility::getUniqueId('content_'));
        mkdir($this->baseDirectory . '/targetFolder');
        $subject = $this->getDefaultInitializedSubject();
        $subject->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
        self::assertTrue(is_file($this->baseDirectory . '/targetFolder/newFolderName/subFolder/file'));
    }

    /**
     * Every array splits into:
     * - String value fileName
     * - String value charset (none = '', utf-8, latin1, etc.)
     * - Expected result (cleaned fileName)
     */
    public static function sanitizeFileNameUTF8FilesystemDataProvider(): array
    {
        // Generate string containing all characters for the utf-8 Latin-1 Supplement (U+0080 to U+00FF)
        // without U+0080 to U+009F: control characters
        // Based on http://www.utf8-chartable.de/unicode-utf8-table.pl
        $utf8Latin1Supplement = '';
        for ($i = 0xA0; $i <= 0xBF; $i++) {
            $utf8Latin1Supplement .= chr(0xC2) . chr($i);
        }
        for ($i = 0x80; $i <= 0xBF; $i++) {
            $utf8Latin1Supplement .= chr(0xC3) . chr($i);
        }
        return [
            // Characters ordered by ASCII table
            'allowed characters utf-8 (ASCII part)' => [
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
            ],
            // Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
            'replace special characters with _ (not allowed characters) utf-8 (ASCII part)' => [
                '! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
                '_____________________________',
            ],
            'utf-8 (Latin-1 Supplement)' => [
                $utf8Latin1Supplement,
                '________________________________ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ',
            ],
            'utf-8 but not in NFC (Canonical Composition)' => [
                hex2bin('667275cc88686e65757a6569746c696368656e'),
                'frühneuzeitlichen',
            ],
            'trim leading and tailing spaces utf-8' => [
                ' test.txt  ',
                'test.txt',
            ],
            'remove tailing dot' => [
                'test.txt.',
                'test.txt',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sanitizeFileNameUTF8FilesystemDataProvider
     */
    public function sanitizeFileNameUTF8Filesystem(string $fileName, string $expectedResult): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 1;
        $subject = $this->getDefaultInitializedSubject();
        self::assertEquals($expectedResult, $subject->sanitizeFileName($fileName));
    }

    /**
     * Every array splits into:
     * - String value fileName
     * - String value charset (none = '', utf-8, latin1, etc.)
     * - Expected result (cleaned fileName)
     */
    public static function sanitizeFileNameNonUTF8FilesystemDataProvider(): array
    {
        // Generate string containing all characters for the iso8859-1 charset, charcode greater than 127
        $iso88591GreaterThan127 = '';
        for ($i = 0xA0; $i <= 0xFF; $i++) {
            $iso88591GreaterThan127 .= chr($i);
        }
        // Generate string containing all characters for the utf-8 Latin-1 Supplement (U+0080 to U+00FF)
        // without U+0080 to U+009F: control characters
        // Based on http://www.utf8-chartable.de/unicode-utf8-table.pl
        $utf8Latin1Supplement = '';
        for ($i = 0xA0; $i <= 0xBF; $i++) {
            $utf8Latin1Supplement .= chr(0xC2) . chr($i);
        }
        for ($i = 0x80; $i <= 0xBF; $i++) {
            $utf8Latin1Supplement .= chr(0xC3) . chr($i);
        }
        // Generate string containing all characters for the utf-8 Latin-1 Extended-A (U+0100 to U+017F)
        $utf8Latin1ExtendedA = '';
        for ($i = 0x80; $i <= 0xBF; $i++) {
            $utf8Latin1ExtendedA .= chr(0xC4) . chr($i);
        }
        for ($i = 0x80; $i <= 0xBF; $i++) {
            $utf8Latin1ExtendedA .= chr(0xC5) . chr($i);
        }

        return [
            // Characters ordered by ASCII table
            'allowed characters iso-8859-1' => [
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
                'iso-8859-1',
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
            ],
            // Characters ordered by ASCII table
            'allowed characters utf-8' => [
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
                'utf-8',
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
            ],
            // Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
            'replace special characters with _ (not allowed characters) iso-8859-1' => [
                '! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
                'iso-8859-1',
                '_____________________________',
            ],
            // Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
            'replace special characters with _ (not allowed characters) utf-8' => [
                '! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
                'utf-8',
                '_____________________________',
            ],
            'iso-8859-1 (code > 127)' => [
                // http://de.wikipedia.org/wiki/ISO_8859-1
                // chr(0xA0) = NBSP (no-break space) => gets trimmed
                $iso88591GreaterThan127,
                'iso-8859-1',
                '_centpound_yen____c_a_____R_____-23_u___1o__1_41_23_4_AAAAAEAAAECEEEEIIIIDNOOOOOExOEUUUUEYTHssaaaaaeaaaeceeeeiiiidnoooooe_oeuuuueythy',
            ],
            'utf-8 (Latin-1 Supplement)' => [
                // chr(0xC2) . chr(0x0A) = NBSP (no-break space) => gets trimmed
                $utf8Latin1Supplement,
                'utf-8',
                '_centpound__yen______c_a_______R_______-23__u_____1o__1_41_23_4_AAAAAEAAAECEEEEIIIIDNOOOOOExOEUUUUEYTHssaaaaaeaaaeceeeeiiiidnoooooe_oeuuuueythy',
            ],
            'utf-8 (Latin-1 Extended A)' => [
                $utf8Latin1ExtendedA,
                'utf-8',
                'AaAaAaCcCcCcCcDdDdEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiIJijJjKk__LlLlLlL_l_LlNnNnNn_n____OOooOoOoOEoeRrRrRrSsSsSsSsTtTtTtUuUuUuUuUuUuWwYyYZzZzZzs',
            ],
            'utf-8 but not in NFC (Canonical Composition)' => [
                hex2bin('667275cc88686e65757a6569746c696368656e'),
                'utf-8',
                'fruehneuzeitlichen',
            ],
            'trim leading and tailing spaces iso-8859-1' => [
                ' test.txt  ',
                'iso-8859-1',
                'test.txt',
            ],
            'trim leading and tailing spaces utf-8' => [
                ' test.txt  ',
                'utf-8',
                'test.txt',
            ],
            'remove tailing dot iso-8859-1' => [
                'test.txt.',
                'iso-8859-1',
                'test.txt',
            ],
            'remove tailing dot utf-8' => [
                'test.txt.',
                'utf-8',
                'test.txt',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sanitizeFileNameNonUTF8FilesystemDataProvider
     */
    public function sanitizeFileNameNonUTF8Filesystem(string $fileName, string $charset, string $expectedResult): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 0;
        $subject = $this->getDefaultInitializedSubject();
        self::assertEquals($expectedResult, $subject->sanitizeFileName($fileName, $charset));
    }

    /**
     * @test
     */
    public function sanitizeFileNameThrowsExceptionOnInvalidFileName(): void
    {
        $this->expectException(InvalidFileNameException::class);
        $this->expectExceptionCode(1320288991);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 1;
        $subject = $this->getDefaultInitializedSubject();
        $subject->sanitizeFileName('');
    }

    /**
     * @test
     */
    public function applyFilterMethodsToDirectoryItemCallsFilterMethodIfClosure(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1463073434);
        $closure = static function () {
            throw new \Exception('I was called!', 1463073434);
        };
        $filterMethods = [
            $closure,
        ];
        $driverConfiguration = [
            'basePath' => $this->baseDirectory,
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, null, [$driverConfiguration]);
        $subject->processConfiguration();
        $subject->_call('applyFilterMethodsToDirectoryItem', $filterMethods, '', '', '');
    }

    /**
     * @test
     */
    public function applyFilterMethodsToDirectoryItemCallsFilterMethodIfName(): void
    {
        $dummyObject = $this->getMockBuilder(\stdClass::class)->addMethods(['dummy'])->getMock();
        $method = [
            $dummyObject,
            'dummy',
        ];
        $dummyObject->expects(self::once())->method('dummy');
        $filterMethods = [
            $method,
        ];
        $driverConfiguration = [
            'basePath' => $this->baseDirectory,
        ];
        $subject = $this->getAccessibleMock(LocalDriver::class, null, [$driverConfiguration]);
        $subject->processConfiguration();
        $subject->_call('applyFilterMethodsToDirectoryItem', $filterMethods, '', '', '');
    }
}
