<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

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

/**
 * Testcase
 *
 */
class FileHandlingUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var array List of created fake extensions to be deleted in tearDown() again
     */
    protected $fakedExtensions = [];

    /**
     * Creates a fake extension inside typo3temp/. No configuration is created,
     * just the folder
     *
     * @param bool $extkeyOnly
     * @return string The extension key
     */
    protected function createFakeExtension($extkeyOnly = false)
    {
        $extKey = strtolower($this->getUniqueId('testing'));
        $absExtPath = PATH_site . 'typo3temp/ext-' . $extKey . '/';
        $relPath = 'typo3temp/ext-' . $extKey . '/';
        $this->fakedExtensions[$extKey] = [
            'siteRelPath' => $relPath,
            'siteAbsPath' => $absExtPath
        ];
        if ($extkeyOnly === true) {
            return $extKey;
        }
        \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($absExtPath);
        $this->testFilesToDelete[] = PATH_site . 'typo3temp/ext-' . $extKey;
        return $extKey;
    }

    /**
     * @test
     * @return void
     */
    public function makeAndClearExtensionDirRemovesExtensionDirIfAlreadyExists()
    {
        $extKey = $this->createFakeExtension();
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['removeDirectory', 'addDirectory', 'getExtensionDir'], [], '', false);
        $fileHandlerMock->expects($this->once())
            ->method('removeDirectory')
            ->with(PATH_site . 'typo3temp/ext-' . $extKey . '/');
        $fileHandlerMock->expects($this->any())
            ->method('getExtensionDir')
            ->willReturn(PATH_site . 'typo3temp/ext-' . $extKey . '/');
        $fileHandlerMock->_call('makeAndClearExtensionDir', $extKey);
    }

    /**
     * @return array
     */
    public function invalidRelativePathDataProvider()
    {
        return [
            ['../../'],
            ['/foo/bar'],
            ['foo//bar'],
            ['foo/bar' . chr(0)],
        ];
    }

    /**
     * @param string $invalidRelativePath
     * @test
     * @dataProvider invalidRelativePathDataProvider
     * @expectedException \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    public function getAbsolutePathThrowsExceptionForInvalidRelativePaths($invalidRelativePath)
    {
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['dummy'], []);
        $fileHandlerMock->_call('getAbsolutePath', $invalidRelativePath);
    }

    /**
     * @return array
     */
    public function validRelativePathDataProvider()
    {
        return [
            ['foo/../bar', PATH_site . 'bar'],
            ['bas', PATH_site . 'bas'],
        ];
    }

    /**
     * @param string $validRelativePath
     * @param string $expectedAbsolutePath
     * @test
     * @dataProvider validRelativePathDataProvider
     */
    public function getAbsolutePathReturnsAbsolutePathForValidRelativePaths($validRelativePath, $expectedAbsolutePath)
    {
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['dummy']);
        $this->assertSame($expectedAbsolutePath, $fileHandlerMock->_call('getAbsolutePath', $validRelativePath));
    }

    /**
     * @test
     * @return void
     */
    public function makeAndClearExtensionDirAddsDir()
    {
        $extKey = $this->createFakeExtension();
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['removeDirectory', 'addDirectory', 'getExtensionDir']);
        $fileHandlerMock->expects($this->once())
            ->method('addDirectory')
            ->with(PATH_site . 'typo3temp/ext-' . $extKey . '/');
        $fileHandlerMock->expects($this->any())
            ->method('getExtensionDir')
            ->willReturn(PATH_site . 'typo3temp/ext-' . $extKey . '/');
        $fileHandlerMock->_call('makeAndClearExtensionDir', $extKey);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     * @return void
     */
    public function makeAndClearExtensionDirThrowsExceptionOnInvalidPath()
    {
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['removeDirectory', 'addDirectory']);
        $languageServiceMock = $this->getMock(\TYPO3\CMS\Lang\LanguageService::class);
        $fileHandlerMock->_set('languageService', $languageServiceMock);
        $fileHandlerMock->_call('makeAndClearExtensionDir', 'testing123', 'fakepath');
    }

    /**
     * @test
     * @return void
     */
    public function addDirectoryAddsDirectory()
    {
        $extDirPath = PATH_site . '/typo3temp/' . $this->getUniqueId('test-extensions-');
        $this->testFilesToDelete[] = $extDirPath;
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['dummy']);
        $fileHandlerMock->_call('addDirectory', $extDirPath);
        $this->assertTrue(is_dir($extDirPath));
    }

    /**
     * @test
     * @return void
     */
    public function removeDirectoryRemovesDirectory()
    {
        $extDirPath = PATH_site . '/typo3temp/' . $this->getUniqueId('test-extensions-');
        @mkdir($extDirPath);
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['dummy']);
        $fileHandlerMock->_call('removeDirectory', $extDirPath);
        $this->assertFalse(is_dir($extDirPath));
    }

    /**
     * @test
     * @return void
     */
    public function removeDirectoryRemovesSymlink()
    {
        $absoluteSymlinkPath = PATH_site . 'typo3temp/' . $this->getUniqueId('test_symlink_');
        $absoluteFilePath = PATH_site . 'typo3temp/' . $this->getUniqueId('test_file_');
        touch($absoluteFilePath);
        $this->testFilesToDelete[] = $absoluteFilePath;
        symlink($absoluteFilePath, $absoluteSymlinkPath);
        $fileHandler = new \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility();
        $fileHandler->removeDirectory($absoluteSymlinkPath);
        $this->assertFalse(is_link($absoluteSymlinkPath));
    }

    /**
     * @test
     * @return void
     */
    public function removeDirectoryDoesNotRemoveContentOfSymlinkedTargetDirectory()
    {
        $absoluteSymlinkPath = PATH_site . 'typo3temp/' . $this->getUniqueId('test_symlink_');
        $absoluteDirectoryPath = PATH_site . 'typo3temp/' . $this->getUniqueId('test_dir_') . '/';
        $relativeFilePath = $this->getUniqueId('test_file_');

        mkdir($absoluteDirectoryPath);
        touch($absoluteDirectoryPath . $relativeFilePath);

        $this->testFilesToDelete[] = $absoluteDirectoryPath . $relativeFilePath;
        $this->testFilesToDelete[] = $absoluteDirectoryPath;

        symlink($absoluteDirectoryPath, $absoluteSymlinkPath);

        $fileHandler = new \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility();
        $fileHandler->removeDirectory($absoluteSymlinkPath);
        $this->assertTrue(is_file($absoluteDirectoryPath . $relativeFilePath));
    }

    /**
     * @test
     * @return void
     */
    public function unpackExtensionFromExtensionDataArrayCreatesTheExtensionDirectory()
    {
        $extensionData = [
            'extKey' => 'test'
        ];
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, [
            'makeAndClearExtensionDir',
            'writeEmConfToFile',
            'extractFilesArrayFromExtensionData',
            'extractDirectoriesFromExtensionData',
            'createDirectoriesForExtensionFiles',
            'writeExtensionFiles',
            'reloadPackageInformation',
        ]);
        $fileHandlerMock->expects($this->once())->method('extractFilesArrayFromExtensionData')->will($this->returnValue([]));
        $fileHandlerMock->expects($this->once())->method('extractDirectoriesFromExtensionData')->will($this->returnValue([]));
        $fileHandlerMock->expects($this->once())->method('makeAndClearExtensionDir')->with($extensionData['extKey']);
        $fileHandlerMock->_call('unpackExtensionFromExtensionDataArray', $extensionData);
    }

    /**
     * @test
     * @return void
     */
    public function unpackExtensionFromExtensionDataArrayStripsDirectoriesFromFilesArray()
    {
        $extensionData = [
            'extKey' => 'test'
        ];
        $files = [
            'ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write'
            ],
            'doc/' => [
                'name' => 'doc/',
                'size' => 0,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => ''
            ],
            'doc/ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write'
            ],
        ];
        $cleanedFiles = [
            'ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write'
            ],
            'doc/ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write'
            ],
        ];
        $directories = [
            'doc/',
            'mod/doc/'
        ];

        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, [
            'makeAndClearExtensionDir',
            'writeEmConfToFile',
            'extractFilesArrayFromExtensionData',
            'extractDirectoriesFromExtensionData',
            'createDirectoriesForExtensionFiles',
            'writeExtensionFiles',
            'reloadPackageInformation',
        ]);
        $fileHandlerMock->expects($this->once())->method('extractFilesArrayFromExtensionData')->will($this->returnValue($files));
        $fileHandlerMock->expects($this->once())->method('extractDirectoriesFromExtensionData')->will($this->returnValue($directories));
        $fileHandlerMock->expects($this->once())->method('createDirectoriesForExtensionFiles')->with($directories);
        $fileHandlerMock->expects($this->once())->method('writeExtensionFiles')->with($cleanedFiles);
        $fileHandlerMock->expects($this->once())->method('reloadPackageInformation')->with('test');
        $fileHandlerMock->_call('unpackExtensionFromExtensionDataArray', $extensionData);
    }

    /**
     * @test
     * @return void
     */
    public function extractFilesArrayFromExtensionDataReturnsFileArray()
    {
        $extensionData = [
            'key' => 'test',
            'FILES' => [
                'filename1' => 'dummycontent',
                'filename2' => 'dummycontent2'
            ]
        ];
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['makeAndClearExtensionDir']);
        $extractedFiles = $fileHandlerMock->_call('extractFilesArrayFromExtensionData', $extensionData);
        $this->assertArrayHasKey('filename1', $extractedFiles);
        $this->assertArrayHasKey('filename2', $extractedFiles);
    }

    /**
     * @test
     * @return void
     */
    public function writeExtensionFilesWritesFiles()
    {
        $files = [
            'ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write'
            ],
            'README' => [
                'name' => 'README',
                'size' => 4566,
                'mtime' => 1219448533,
                'is_executable' => false,
                'content' => 'FEEL FREE TO ADD SOME DOCUMENTATION HERE'
            ]
        ];
        $rootPath = ($extDirPath = $this->fakedExtensions[$this->createFakeExtension()]['siteAbsPath']);
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['makeAndClearExtensionDir']);
        $fileHandlerMock->_call('writeExtensionFiles', $files, $rootPath);
        $this->assertTrue(file_exists($rootPath . 'ChangeLog'));
    }

    /**
     * @test
     * @return void
     */
    public function extractDirectoriesFromExtensionDataExtractsDirectories()
    {
        $files = [
            'ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write'
            ],
            'doc/' => [
                'name' => 'doc/',
                'size' => 0,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => ''
            ],
            'doc/ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write'
            ],
            'doc/README' => [
                'name' => 'README',
                'size' => 4566,
                'mtime' => 1219448533,
                'is_executable' => false,
                'content' => 'FEEL FREE TO ADD SOME DOCUMENTATION HERE'
            ],
            'mod/doc/README' => [
                'name' => 'README',
                'size' => 4566,
                'mtime' => 1219448533,
                'is_executable' => false,
                'content' => 'FEEL FREE TO ADD SOME DOCUMENTATION HERE'
            ]
        ];
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['makeAndClearExtensionDir']);
        $extractedDirectories = $fileHandlerMock->_call('extractDirectoriesFromExtensionData', $files);
        $expected = [
            'doc/',
            'mod/doc/'
        ];
        $this->assertSame($expected, array_values($extractedDirectories));
    }

    /**
     * @test
     * @return void
     */
    public function createDirectoriesForExtensionFilesCreatesDirectories()
    {
        $rootPath = $this->fakedExtensions[$this->createFakeExtension()]['siteAbsPath'];
        $directories = [
            'doc/',
            'mod/doc/'
        ];
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['makeAndClearExtensionDir']);
        $this->assertFalse(is_dir($rootPath . 'doc/'));
        $this->assertFalse(is_dir($rootPath . 'mod/doc/'));
        $fileHandlerMock->_call('createDirectoriesForExtensionFiles', $directories, $rootPath);
        $this->assertTrue(is_dir($rootPath . 'doc/'));
        $this->assertTrue(is_dir($rootPath . 'mod/doc/'));
    }

    /**
     * @test
     * @return void
     */
    public function writeEmConfWritesEmConfFile()
    {
        $extKey = $this->createFakeExtension();
        $extensionData = [
            'extKey' => $extKey,
            'EM_CONF' => [
                'title' => 'Plugin cache engine',
                'description' => 'Provides an interface to cache plugin content elements based on 4.3 caching framework',
                'category' => 'Frontend',
            ]
        ];
        $rootPath = $this->fakedExtensions[$extKey]['siteAbsPath'];
        $emConfUtilityMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\EmConfUtility::class, ['constructEmConf']);
        $emConfUtilityMock->expects($this->once())->method('constructEmConf')->with($extensionData)->will($this->returnValue(var_export($extensionData['EM_CONF'], true)));
        $fileHandlerMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['makeAndClearExtensionDir']);
        $fileHandlerMock->_set('emConfUtility', $emConfUtilityMock);
        $fileHandlerMock->_call('writeEmConfToFile', $extensionData, $rootPath);
        $this->assertTrue(file_exists($rootPath . 'ext_emconf.php'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
     */
    protected function getPreparedFileHandlingMockForDirectoryCreationTests()
    {
        /** @var $fileHandlerMock \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility|\PHPUnit_Framework_MockObject_MockObject */
        $fileHandlerMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class, ['createNestedDirectory', 'getAbsolutePath', 'directoryExists']);
        $fileHandlerMock->expects($this->any())
            ->method('getAbsolutePath')
            ->will($this->returnArgument(0));
        return $fileHandlerMock;
    }

    /**
     * @test
     */
    public function uploadFolderIsNotCreatedIfNotRequested()
    {
        $fileHandlerMock = $this->getPreparedFileHandlingMockForDirectoryCreationTests();
        $fileHandlerMock->expects($this->never())
            ->method('createNestedDirectory');
        $fileHandlerMock->ensureConfiguredDirectoriesExist([
                'key' => 'foo_bar',
                'uploadfolder' => 0,
            ]
        );
    }

    /**
     * @test
     */
    public function additionalFoldersAreNotCreatedIfNotRequested()
    {
        $fileHandlerMock = $this->getPreparedFileHandlingMockForDirectoryCreationTests();
        $fileHandlerMock->expects($this->never())
            ->method('createNestedDirectory');
        $fileHandlerMock->ensureConfiguredDirectoriesExist([
                'key' => 'foo_bar',
                'createDirs' => '',
            ]
        );
    }

    /**
     * @test
     */
    public function configuredUploadFolderIsCreatedIfRequested()
    {
        $fileHandlerMock = $this->getPreparedFileHandlingMockForDirectoryCreationTests();
        $fileHandlerMock->expects($this->once())
            ->method('createNestedDirectory')
            ->with('uploads/tx_foobar/');
        $fileHandlerMock->ensureConfiguredDirectoriesExist([
                'key' => 'foo_bar',
                'uploadfolder' => 1,
            ]
        );
    }

    /**
     * @test
     */
    public function configuredAdditionalDirectoriesAreCreatedIfRequested()
    {
        $fileHandlerMock = $this->getPreparedFileHandlingMockForDirectoryCreationTests();
        $fileHandlerMock->expects($this->exactly(2))
            ->method('createNestedDirectory')
            ->will($this->returnCallback(function ($path) {
                if (!in_array($path, ['foo/bar', 'baz/foo'])) {
                    throw new \Exception('Path "' . $path . '" is not expected to be created');
                }
            })
            );
        $fileHandlerMock->ensureConfiguredDirectoriesExist([
                'key' => 'foo_bar',
                'createDirs' => 'foo/bar, baz/foo',
            ]
        );
    }

    /**
     * @test
     */
    public function configuredDirectoriesAreNotCreatedIfTheyAlreadyExist()
    {
        $fileHandlerMock = $this->getPreparedFileHandlingMockForDirectoryCreationTests();
        $fileHandlerMock->expects($this->exactly(3))
            ->method('directoryExists')
            ->will($this->returnValue(true));
        $fileHandlerMock->expects($this->never())
            ->method('createNestedDirectory');
        $fileHandlerMock->ensureConfiguredDirectoriesExist([
                'key' => 'foo_bar',
                'uploadfolder' => 1,
                'createDirs' => 'foo/bar, baz/foo',
            ]
        );
    }

    /**
     * Warning: This test asserts multiple things at once to keep the setup short.
     *
     * @test
     */
    public function createZipFileFromExtensionGeneratesCorrectArchive()
    {
        // 42 second of first day in 1970 - used to have achieve stable file names
        $GLOBALS['EXEC_TIME'] = 42;

        // Create extension for testing:
        $extKey = $this->createFakeExtension();
        $extensionRoot = $this->fakedExtensions[$extKey]['siteAbsPath'];

        // Build mocked fileHandlingUtility:
        $fileHandlerMock = $this->getAccessibleMock(
            \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class,
            ['getAbsoluteExtensionPath', 'getExtensionVersion']
        );
        $fileHandlerMock->expects($this->any())
            ->method('getAbsoluteExtensionPath')
            ->will($this->returnValue($extensionRoot));
        $fileHandlerMock->expects($this->any())
            ->method('getExtensionVersion')
            ->will($this->returnValue('0.0.0'));

        // Add files and directories to extension:
        touch($extensionRoot . 'emptyFile.txt');
        file_put_contents($extensionRoot . 'notEmptyFile.txt', 'content');
        touch($extensionRoot . '.hiddenFile');
        mkdir($extensionRoot . 'emptyDir');
        mkdir($extensionRoot . 'notEmptyDir');
        touch($extensionRoot . 'notEmptyDir/file.txt');

        // Create zip-file from extension
        $filename = $fileHandlerMock->_call('createZipFileFromExtension', $extKey);

        $expectedFilename = PATH_site . 'typo3temp/ExtensionManager/' . $extKey . '_0.0.0_' . date('YmdHi', 42) . '.zip';
        $this->testFilesToDelete[] = $filename;
        $this->assertEquals($expectedFilename, $filename, 'Archive file name differs from expectation');

        // File was created
        $this->assertTrue(file_exists($filename), 'Zip file not created');

        // Read archive and check its contents
        $archive = new \ZipArchive();
        $this->assertTrue($archive->open($filename), 'Unable to open archive');
        $this->assertEquals($archive->statName('emptyFile.txt')->size, 0, 'Empty file not in archive');
        $this->assertEquals($archive->getFromName('notEmptyFile.txt'), 'content', 'Expected content not found');
        $this->assertFalse($archive->statName('.hiddenFile'), 'Hidden file not in archive');
        $this->assertTrue(is_array($archive->statName('emptyDir/')), 'Empty directory not in archive');
        $this->assertTrue(is_array($archive->statName('notEmptyDir/')), 'Not empty directory not in archive');
        $this->assertTrue(is_array($archive->statName('notEmptyDir/file.txt')), 'File within directory not in archive');

        // Check that the archive has no additional content
        $this->assertEquals($archive->numFiles, 5, 'Too many or too less files in archive');
    }
}
