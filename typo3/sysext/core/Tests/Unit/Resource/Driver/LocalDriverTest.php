<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver;

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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Tests\FileStreamWrapper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for the local storage driver class of the TYPO3 VFS
 */
class LocalDriverTest extends \TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver
     */
    protected $localDriver = null;

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = array();

    /**
     * @var array
     */
    protected $testDirs = array();

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
    protected function tearDown()
    {
        foreach ($this->testDirs as $dir) {
            chmod($dir, 0777);
            \TYPO3\CMS\Core\Utility\GeneralUtility::rmdir($dir, true);
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
    protected function createRealTestdir()
    {
        $basedir = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('fal-test-');
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
    protected function prepareRealTestEnvironment()
    {
        $basedir = $this->createRealTestdir();
        $subject = $this->createDriver(array(
            'basePath' => $basedir
        ));
        return array($basedir, $subject);
    }

    /**
     * Creates a mocked driver object as test subject, optionally using a given mount object.
     *
     * IMPORTANT: Call this only after setting up the virtual file system (with the addTo* methods)!
     *
     * @param array $driverConfiguration
     * @param array $mockedDriverMethods
     * @return \TYPO3\CMS\Core\Resource\Driver\LocalDriver
     */
    protected function createDriver($driverConfiguration = array(), $mockedDriverMethods = array())
    {
        // it's important to do that here, so vfsContents could have been set before
        if (!isset($driverConfiguration['basePath'])) {
            $this->initializeVfs();
            $driverConfiguration['basePath'] = $this->getMountRootUrl();
        }
        /** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver $driver */
        $mockedDriverMethods[] = 'isPathValid';
        $driver = $this->getAccessibleMock(\TYPO3\CMS\Core\Resource\Driver\LocalDriver::class, $mockedDriverMethods, array($driverConfiguration));
        $driver->expects($this->any())
            ->method('isPathValid')
            ->will(
                $this->returnValue(true)
            );

        $driver->setStorageUid(5);
        $driver->processConfiguration();
        $driver->initialize();
        return $driver;
    }

    /**
     * @test
     */
    public function calculatedBasePathRelativeIsSane()
    {
        $subject = $this->createDriver();

        // This would cause problems if you fill "/fileadmin/" into the base path field of a sys_file_storage record and select "relative" as path type
        $relativeDriverConfiguration = array(
            'pathType' => 'relative',
            'basePath' => '/typo3temp/var/tests/',
        );
        $basePath = $subject->_call('calculateBasePath', $relativeDriverConfiguration);

        $this->assertNotContains('//', $basePath);
    }

    /**
     * @test
     */
    public function calculatedBasePathAbsoluteIsSane()
    {
        $subject = $this->createDriver();

        // This test checks if "/../" are properly filtered out (i.e. from "Base path" field of sys_file_storage)
        $relativeDriverConfiguration = array(
            'basePath' => PATH_site . 'typo3temp/var/tests/../../../typo3temp/var/tests/',
        );
        $basePath = $subject->_call('calculateBasePath', $relativeDriverConfiguration);

        $this->assertNotContains('/../', $basePath);
    }

    /**
     * @test
     */
    public function createFolderRecursiveSanitizesFilename()
    {
        /** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $driver */
        $driver = $this->createDriver(array(), array('sanitizeFilename'));
        $driver->expects($this->exactly(2))
            ->method('sanitizeFileName')
            ->will(
                $this->returnValue('sanitized')
            );
        $driver->createFolder('newFolder/andSubfolder', '/', true);
        $this->assertFileExists($this->getUrlInMount('/sanitized/sanitized/'));
    }

    /**
     * @test
     */
    public function determineBaseUrlUrlEncodesUriParts()
    {
        /** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $driver */
        $driver = $this->getAccessibleMock(\TYPO3\CMS\Core\Resource\Driver\LocalDriver::class, array('hasCapability'), array(), '', false);
        $driver->expects($this->once())
            ->method('hasCapability')
            ->with(\TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_PUBLIC)
            ->will(
                $this->returnValue(true)
            );
        $driver->_set('absoluteBasePath', PATH_site . 'un encö/ded %path/');
        $driver->_call('determineBaseUrl');
        $baseUri = $driver->_get('baseUri');
        $this->assertEquals(rawurlencode('un encö') . '/' . rawurlencode('ded %path') . '/', $baseUri);
    }

    /**
     * @test
     */
    public function getDefaultFolderReturnsFolderForUserUploadPath()
    {
        $subject = $this->createDriver();
        $folderIdentifier = $subject->getDefaultFolder();
        $this->assertEquals('/user_upload/', $folderIdentifier);
    }

    /**
     * @test
     */
    public function defaultLevelFolderFolderIsCreatedIfItDoesntExist()
    {
        $subject = $this->createDriver();
        $this->assertFileExists($this->getUrlInMount($subject->getDefaultFolder()));
    }

    /**
     * @test
     */
    public function getFolderInFolderReturnsCorrectFolderObject()
    {
        $this->addToMount(array(
            'someDir' => array(
                'someSubdir' => array()
            )
        ));
        $subject = $this->createDriver();
        $folder = $subject->getFolderInFolder('someSubdir', '/someDir/');
        $this->assertEquals('/someDir/someSubdir/', $folder);
    }

    /**
     * @test
     */
    public function createFolderCreatesFolderOnDisk()
    {
        $this->addToMount(array('some' => array('folder' => array())));
        $subject = $this->createDriver();
        $subject->createFolder('path', '/some/folder/');
        $this->assertFileExists($this->getUrlInMount('/some/folder/'));
        $this->assertFileExists($this->getUrlInMount('/some/folder/path'));
    }

    /**
     * @test
     */
    public function createFolderReturnsFolderObject()
    {
        $this->addToMount(array('some' => array('folder' => array())));
        $subject = $this->createDriver();
        $createdFolder = $subject->createFolder('path', '/some/folder/');
        $this->assertEquals('/some/folder/path/', $createdFolder);
    }

    public static function createFolderSanitizesFolderNameBeforeCreationDataProvider()
    {
        return array(
            'folder name with NULL character' => array(
                'some' . chr(0) . 'Folder',
                'some_Folder'
            ),
            'folder name with directory part' => array(
                '../someFolder',
                '.._someFolder'
            )
        );
    }

    /**
     * @test
     * @dataProvider createFolderSanitizesFolderNameBeforeCreationDataProvider
     */
    public function createFolderSanitizesFolderNameBeforeCreation($newFolderName, $expectedFolderName)
    {
        $this->addToMount(array('some' => array('folder' => array())));
        $subject = $this->createDriver();
        $subject->createFolder($newFolderName, '/some/folder/');
        $this->assertFileExists($this->getUrlInMount('/some/folder/' . $expectedFolderName));
    }

    /**
     * @test
     */
    public function basePathIsNormalizedWithTrailingSlash()
    {
        $subject = $this->createDriver();
        $this->assertEquals('/', substr($subject->_call('getAbsoluteBasePath'), -1));
    }

    /**
     * @test
     */
    public function noSecondSlashIsAddedIfBasePathAlreadyHasTrailingSlash()
    {
        $subject = $this->createDriver();
        $this->assertNotEquals('/', substr($subject->_call('getAbsoluteBasePath'), -2, 1));
    }

    public function getSpecificFileInformationDataProvider()
    {
        return array(
            'size' => array(
                'expectedValue' => filesize(__DIR__ . '/Fixtures/Dummy.html'),
                'propertyName' => 'size'
            ),
            'atime' => array(
                'expectedValue' => 'WILL_BE_REPLACED_BY_VFS_TIME',
                'propertyName' => 'atime'
            ),
            'mtime' => array(
                'expectedValue' => 'WILL_BE_REPLACED_BY_VFS_TIME',
                'propertyName' => 'mtime'
            ),
            'ctime' => array(
                'expectedValue' => 'WILL_BE_REPLACED_BY_VFS_TIME',
                'propertyName' => 'ctime'
            ),
            'name' => array(
                'expectedValue' => 'Dummy.html',
                'propertyName' => 'name'
            ),
            'mimetype' => array(
                'expectedValue' => 'text/html',
                'propertyName' => 'mimetype'
            ),
            'identifier' => array(
                'expectedValue' => '/Dummy.html',
                'propertyName' => 'identifier'
            ),
            'storage' => array(
                'expectedValue' => 5,
                'propertyName' => 'storage'
            ),
            'identifier_hash' => array(
                'expectedValue' => 'b11efa5d7c0556a65c6aa261343b9807cac993bc',
                'propertyName' => 'identifier_hash'
            ),
            'folder_hash' => array(
                'expectedValue' => '42099b4af021e53fd8fd4e056c2568d7c2e3ffa8',
                'propertyName' => 'folder_hash'
            )
        );
    }

    /**
     * @test
     * @dataProvider getSpecificFileInformationDataProvider
     */
    public function getSpecificFileInformationReturnsRequestedFileInformation($expectedValue, $property)
    {
        $root = vfsStream::setup();
        $subFolder = vfsStream::newDirectory('fileadmin');
        $root->addChild($subFolder);
        // Load fixture files and folders from disk
        $directory = vfsStream::copyFromFileSystem(__DIR__ . '/Fixtures/', $subFolder, 1024*1024);
        if (in_array($property, array('mtime', 'ctime', 'atime'))) {
            $expectedValue = $directory->getChild('Dummy.html')->filemtime();
        }
        FileStreamWrapper::init(PATH_site);
        FileStreamWrapper::registerOverlayPath('fileadmin', 'vfs://root/fileadmin', false);

        $subject = $this->createDriver(array('basePath' => PATH_site . 'fileadmin'));
        $this->assertSame(
            $expectedValue,
            $subject->getSpecificFileInformation(PATH_site . 'fileadmin/Dummy.html', '/', $property)
        );

        FileStreamWrapper::destroy();
    }

    /**
     * @test
     */
    public function getAbsolutePathReturnsCorrectPath()
    {
        $this->addToMount(array(
            'someFolder' => array(
                'file1.ext' => 'asdfg'
            )
        ));
        $subject = $this->createDriver();
        $path = $subject->_call('getAbsolutePath', '/someFolder/file1.ext');
        $this->assertTrue(file_exists($path));
        $this->assertEquals($this->getUrlInMount('/someFolder/file1.ext'), $path);
    }

    /**
     * @test
     */
    public function addFileMovesFileToCorrectLocation()
    {
        $this->addToMount(array('targetFolder' => array()));
        $this->addToVfs(array(
            'sourceFolder' => array(
                'file' => 'asdf'
            )
        ));
        $subject = $this->createDriver(
            array(),
            array('getMimeTypeOfFile')
        );
        $this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
        $subject->addFile($this->getUrl('sourceFolder/file'), '/targetFolder/', 'file');
        $this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/file')));
    }

    /**
     * @test
     */
    public function addFileUsesFilenameIfGiven()
    {
        $this->addToMount(array('targetFolder' => array()));
        $this->addToVfs(array(
            'sourceFolder' => array(
                'file' => 'asdf'
            )
        ));
        $subject = $this->createDriver(
            array(),
            array('getMimeTypeOfFile')
        );
        $this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
        $subject->addFile($this->getUrl('sourceFolder/file'), '/targetFolder/', 'targetFile');
        $this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/targetFile')));
    }

    /**
     * @test
     */
    public function addFileFailsIfFileIsInDriverStorage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314778269);
        $this->addToMount(array(
            'targetFolder' => array(
                'file' => 'asdf'
            )
        ));
        $subject = $this->createDriver();
        $subject->addFile($this->getUrlInMount('/targetFolder/file'), '/targetFolder/', 'file');
    }

    /**
     * @test
     */
    public function addFileReturnsFileIdentifier()
    {
        $this->addToMount(array('targetFolder' => array()));
        $this->addToVfs(array(
            'sourceFolder' => array(
                'file' => 'asdf'
            )
        ));
        $subject = $this->createDriver(
            array(),
            array('getMimeTypeOfFile')
        );
        $this->assertTrue(file_exists($this->getUrl('sourceFolder/file')));
        $fileIdentifier = $subject->addFile($this->getUrl('sourceFolder/file'), '/targetFolder/', 'file');
        $this->assertEquals('file', basename($fileIdentifier));
        $this->assertEquals('/targetFolder/file', $fileIdentifier);
    }

    /**
     * @test
     */
    public function existenceChecksWorkForFilesAndFolders()
    {
        $this->addToMount(array(
            'file' => 'asdf',
            'folder' => array()
        ));
        $subject = $this->createDriver();
        // Using slashes at the beginning of paths because they will be stored in the DB this way.
        $this->assertTrue($subject->fileExists('/file'));
        $this->assertTrue($subject->folderExists('/folder/'));
        $this->assertFalse($subject->fileExists('/nonexistingFile'));
        $this->assertFalse($subject->folderExists('/nonexistingFolder/'));
    }

    /**
     * @test
     */
    public function existenceChecksInFolderWorkForFilesAndFolders()
    {
        $this->addToMount(array(
            'subfolder' => array(
                'file' => 'asdf',
                'folder' => array()
            )
        ));
        $subject = $this->createDriver();
        $this->assertTrue($subject->fileExistsInFolder('file', '/subfolder/'));
        $this->assertTrue($subject->folderExistsInFolder('folder', '/subfolder/'));
        $this->assertFalse($subject->fileExistsInFolder('nonexistingFile', '/subfolder/'));
        $this->assertFalse($subject->folderExistsInFolder('nonexistingFolder', '/subfolder/'));
    }

    /**
     * @test
     */
    public function getPublicUrlReturnsCorrectUriForConfiguredBaseUri()
    {
        $baseUri = 'http://example.org/foobar/' . $this->getUniqueId();
        $this->addToMount(array(
            'file.ext' => 'asdf',
            'subfolder' => array(
                'file2.ext' => 'asdf'
            )
        ));
        $subject = $this->createDriver(array(
            'baseUri' => $baseUri
        ));
        $this->assertEquals($baseUri . '/file.ext', $subject->getPublicUrl('/file.ext'));
        $this->assertEquals($baseUri . '/subfolder/file2.ext', $subject->getPublicUrl('/subfolder/file2.ext'));
    }

    /**
     * Data provider for getPublicUrlReturnsValidUrlContainingSpecialCharacters().
     *
     * @return array
     */
    public function getPublicUrlReturnsValidUrlContainingSpecialCharacters_dataProvider()
    {
        return array(
            array('/single file with some special chars äüö!.txt'),
            array('/on subfolder/with special chars äüö!.ext'),
            array('/who names a file like !"§$%&()=?*+~"#\'´`<>-.ext'),
            array('no leading slash !"§$%&()=?*+~#\'"´`"<>-.txt')
        );
    }

    /**
     * @test
     * @dataProvider getPublicUrlReturnsValidUrlContainingSpecialCharacters_dataProvider
     */
    public function getPublicUrlReturnsValidUrlContainingSpecialCharacters($fileIdentifier)
    {
        $baseUri = 'http://example.org/foobar/' . $this->getUniqueId();
        $subject = $this->createDriver(array(
            'baseUri' => $baseUri
        ));
        $publicUrl = $subject->getPublicUrl($fileIdentifier);
        $this->assertTrue(GeneralUtility::isValidUrl($publicUrl), 'getPublicUrl did not return a valid URL:' . $publicUrl);
    }

    /**
     * @test
     */
    public function fileContentsCanBeWrittenAndRead()
    {
        $fileContents = 'asdf';
        $this->addToMount(array(
            'file.ext' => $fileContents
        ));
        $subject = $this->createDriver();
        $this->assertEquals($fileContents, $subject->getFileContents('/file.ext'), 'File contents could not be read');
        $newFileContents = 'asdfgh';
        $subject->setFileContents('/file.ext', $newFileContents);
        $this->assertEquals($newFileContents, $subject->getFileContents('/file.ext'), 'New file contents could not be read.');
    }

    /**
     * @test
     */
    public function setFileContentsReturnsNumberOfBytesWrittenToFile()
    {
        $fileContents = 'asdf';
        $this->addToMount(array(
            'file.ext' => $fileContents
        ));
        $subject = $this->createDriver();
        $newFileContents = 'asdfgh';
        $bytesWritten = $subject->setFileContents('/file.ext', $newFileContents);
        $this->assertEquals(strlen($newFileContents), $bytesWritten);
    }

    /**
     * @test
     * @see http://phpmagazin.de/vfsStream-1.1.0-nutzt-PHP-5.4-M%C3%B6glichkeiten-064406.html
     */
    public function newFilesCanBeCreated()
    {
        $subject = $this->createDriver();
        $subject->createFile('testfile.txt', '/');
        $this->assertTrue($subject->fileExists('/testfile.txt'));
    }

    /**
     * @test
     * @see http://phpmagazin.de/vfsStream-1.1.0-nutzt-PHP-5.4-M%C3%B6glichkeiten-064406.html
     */
    public function createdFilesAreEmpty()
    {
        $subject = $this->createDriver();
        $subject->createFile('testfile.txt', '/');
        $this->assertTrue($subject->fileExists('/testfile.txt'));
        $fileData = $subject->getFileContents('/testfile.txt');
        $this->assertEquals(0, strlen($fileData));
    }

    /**
     * @test
     */
    public function createFileFixesPermissionsOnCreatedFile()
    {
        if (TYPO3_OS == 'WIN') {
            $this->markTestSkipped('createdFilesHaveCorrectRights() tests not available on Windows');
        }

        // No one will use this as his default file create mask so we hopefully don't get any false positives
        $testpattern = '0646';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = $testpattern;

        $this->addToMount(
            array(
                'someDir' => array()
            )
        );
        /** @var $subject \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
        list($basedir, $subject) = $this->prepareRealTestEnvironment();
        mkdir($basedir . '/someDir');
        $subject->createFile('testfile.txt', '/someDir');
        $this->assertEquals($testpattern, decoct(fileperms($basedir . '/someDir/testfile.txt') & 0777));
    }

    /**********************************
     * File and directory listing
     **********************************/
    /**
     * @test
     */
    public function getFileReturnsCorrectIdentifier()
    {
        $root = vfsStream::setup();
        $subFolder = vfsStream::newDirectory('fileadmin');
        $root->addChild($subFolder);
        // Load fixture files and folders from disk
        $directory = vfsStream::copyFromFileSystem(__DIR__ . '/Fixtures/', $subFolder, 1024*1024);
        FileStreamWrapper::init(PATH_site);
        FileStreamWrapper::registerOverlayPath('fileadmin/', 'vfs://root/fileadmin/', false);

        $subject = $this->createDriver(array('basePath' => PATH_site . 'fileadmin'));

        $subdirFileInfo = $subject->getFileInfoByIdentifier('Dummy.html');
        $this->assertEquals('/Dummy.html', $subdirFileInfo['identifier']);
        $rootFileInfo = $subject->getFileInfoByIdentifier('LocalDriverFilenameFilter.php');
        $this->assertEquals('/LocalDriverFilenameFilter.php', $rootFileInfo['identifier']);

        FileStreamWrapper::destroy();
    }

    /**
     * @test
     */
    public function getFileThrowsExceptionIfFileDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314516809);
        $subject = $this->createDriver();
        $subject->getFileInfoByIdentifier('/some/file/at/a/random/path');
    }

    /**
     * @test
     */
    public function getFilesInFolderReturnsEmptyArrayForEmptyDirectory()
    {
        $subject = $this->createDriver();
        $fileList = $subject->getFilesInFolder('/');
        $this->assertEmpty($fileList);
    }

    /**
     * @test
     */
    public function getFileListReturnsAllFilesInDirectory()
    {
        $dirStructure = array(
            'aDir' => array(),
            'file1' => 'asdfg',
            'file2' => 'fdsa'
        );
        $this->addToMount($dirStructure);
        $subject = $this->createDriver(
            array(),
                // Mocked because finfo() can not deal with vfs streams and throws warnings
            array('getMimeTypeOfFile')
        );
        $fileList = $subject->getFilesInFolder('/');
        $this->assertEquals(array('/file1', '/file2'), array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFileListReturnsAllFilesInSubdirectoryIfRecursiveParameterIsSet()
    {
        $dirStructure = array(
            'aDir' => array(
                'file3' => 'asdfgh',
                'subdir' => array(
                    'file4' => 'asklfjklasjkl'
                )
            ),
            'file1' => 'asdfg',
            'file2' => 'fdsa'
        );
        $this->addToMount($dirStructure);
        $subject = $this->createDriver(
            array(),
                // Mocked because finfo() can not deal with vfs streams and throws warnings
            array('getMimeTypeOfFile')
        );
        $fileList = $subject->getFilesInFolder('/', 0, 0, true);
        $this->assertEquals(array('/file1', '/file2', '/aDir/file3', '/aDir/subdir/file4'), array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFileListFailsIfDirectoryDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314349666);
        $this->addToMount(array('somefile' => ''));
        $subject = $this->createDriver();
        $subject->getFilesInFolder('somedir/');
    }

    /**
     * @test
     */
    public function getFileInFolderCallsConfiguredCallbackFunctionWithGivenItemName()
    {
        $dirStructure = array(
            'file2' => 'fdsa'
        );
        // register static callback to self
        $callback = array(
            array(
                get_class($this),
                'callbackStaticTestFunction'
            )
        );
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
    public static function callbackStaticTestFunction($itemName)
    {
        if ($itemName === 'file2') {
            throw new \InvalidArgumentException('$itemName', 1336159604);
        }
    }

    /**
     * @test
     */
    public function getFileListFiltersItemsWithGivenFilterMethods()
    {
        $dirStructure = array(
            'fileA' => 'asdfg',
            'fileB' => 'fdsa'
        );
        $this->addToMount($dirStructure);
        $subject = $this->createDriver(
            array(),
                // Mocked because finfo() can not deal with vfs streams and throws warnings
            array('getMimeTypeOfFile')
        );
        $filterCallbacks = array(
            array(
                \TYPO3\CMS\Core\Tests\Unit\Resource\Driver\Fixtures\LocalDriverFilenameFilter::class,
                'filterFilename',
            ),
        );
        $fileList = $subject->getFilesInFolder('/', 0, 0, false, $filterCallbacks);
        $this->assertNotContains('/fileA', array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFolderListReturnsAllDirectoriesInDirectory()
    {
        $dirStructure = array(
            'dir1' => array(),
            'dir2' => array(),
            'file' => 'asdfg'
        );
        $this->addToMount($dirStructure);
        $subject = $this->createDriver();
        $fileList = $subject->getFoldersInFolder('/');
        $this->assertEquals(array('/dir1/', '/dir2/'), array_keys($fileList));
    }

    /**
     * @test
     */
    public function getFolderListReturnsHiddenFoldersByDefault()
    {
        $dirStructure = array(
            '.someHiddenDir' => array(),
            'aDir' => array(),
            'file1' => ''
        );
        $this->addToMount($dirStructure);
        $subject = $this->createDriver();

        $fileList = $subject->getFoldersInFolder('/');

        $this->assertEquals(array('/.someHiddenDir/', '/aDir/'), array_keys($fileList));
    }

    /**
     * Checks if the folder names . and .. are ignored when listing subdirectories
     *
     * @test
     */
    public function getFolderListLeavesOutNavigationalEntries()
    {
        // we have to add .. and . manually, as these are not included in vfsStream directory listings (as opposed
        // to normal filelistings)
        $this->addToMount(array(
            '..' => array(),
            '.' => array()
        ));
        $subject = $this->createDriver();
        $fileList = $subject->getFoldersInFolder('/');
        $this->assertEmpty($fileList);
    }

    /**
     * @test
     */
    public function getFolderListFiltersItemsWithGivenFilterMethods()
    {
        $dirStructure = array(
            'folderA' => array(),
            'folderB' => array()
        );
        $this->addToMount($dirStructure);
        $subject = $this->createDriver();
        $filterCallbacks = array(
            array(
                \TYPO3\CMS\Core\Tests\Unit\Resource\Driver\Fixtures\LocalDriverFilenameFilter::class,
                'filterFilename',
            ),
        );
        $folderList = $subject->getFoldersInFolder('/', 0, 0, $filterCallbacks);
        $this->assertNotContains('folderA', array_keys($folderList));
    }

    /**
     * @test
     */
    public function getFolderListFailsIfDirectoryDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314349666);
        $subject = $this->createDriver();
        vfsStream::create(array($this->basedir => array('somefile' => '')));
        $subject->getFoldersInFolder('somedir/');
    }

    /**
     * @test
     */
    public function hashReturnsCorrectHashes()
    {
        $contents = '68b329da9893e34099c7d8ad5cb9c940';
        $expectedMd5Hash = '8c67dbaf0ba22f2e7fbc26413b86051b';
        $expectedSha1Hash = 'a60cd808ba7a0bcfa37fa7f3fb5998e1b8dbcd9d';
        $this->addToMount(array('hashFile' => $contents));
        $subject = $this->createDriver();
        $this->assertEquals($expectedSha1Hash, $subject->hash('/hashFile', 'sha1'));
        $this->assertEquals($expectedMd5Hash, $subject->hash('/hashFile', 'md5'));
    }

    /**
     * @test
     */
    public function hashingWithUnsupportedAlgorithmFails()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1304964032);
        $subject = $this->createDriver();
        $subject->hash('/hashFile', $this->getUniqueId());
    }

    /**
     * @test
     * @covers TYPO3\CMS\Core\Resource\Driver\LocalDriver::getFileForLocalProcessing
     */
    public function getFileForLocalProcessingCreatesCopyOfFileByDefault()
    {
        $fileContents = 'asdfgh';
        $this->addToMount(array(
            'someDir' => array(
                'someFile' => $fileContents
            )
        ));
        $subject = $this->createDriver(array(), array('copyFileToTemporaryPath'));
        $subject->expects($this->once())->method('copyFileToTemporaryPath');
        $subject->getFileForLocalProcessing('/someDir/someFile');
    }

    /**
     * @test
     */
    public function getFileForLocalProcessingReturnsOriginalFilepathForReadonlyAccess()
    {
        $fileContents = 'asdfgh';
        $this->addToMount(array(
            'someDir' => array(
                'someFile' => $fileContents
            )
        ));
        $subject = $this->createDriver();
        $filePath = $subject->getFileForLocalProcessing('/someDir/someFile', false);
        $this->assertEquals($filePath, $this->getUrlInMount('someDir/someFile'));
    }

    /**
     * @test
     */
    public function filesCanBeCopiedToATemporaryPath()
    {
        $fileContents = 'asdfgh';
        $this->addToMount(array(
            'someDir' => array(
                'someFile' => $fileContents
            )
        ));
        $subject = $this->createDriver();
        $filePath = GeneralUtility::fixWindowsFilePath($subject->_call('copyFileToTemporaryPath', '/someDir/someFile'));
        $this->testFilesToDelete[] = $filePath;
        $this->assertContains('/typo3temp/var/transient/', $filePath);
        $this->assertEquals($fileContents, file_get_contents($filePath));
    }

    /**
     * @test
     */
    public function permissionsAreCorrectlyRetrievedForAllowedFile()
    {
        /** @var $subject \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
        list($basedir, $subject) = $this->prepareRealTestEnvironment();
        touch($basedir . '/someFile');
        chmod($basedir . '/someFile', 448);
        clearstatcache();
        $this->assertEquals(array('r' => true, 'w' => true), $subject->getPermissions('/someFile'));
    }

    /**
     * @test
     */
    public function permissionsAreCorrectlyRetrievedForForbiddenFile()
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            $this->markTestSkipped('Test skipped if run on linux as root');
        } elseif (TYPO3_OS === 'WIN') {
            $this->markTestSkipped('Test skipped if run on Windows system');
        }
        /** @var $subject \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
        list($basedir, $subject) = $this->prepareRealTestEnvironment();
        touch($basedir . '/someForbiddenFile');
        chmod($basedir . '/someForbiddenFile', 0);
        clearstatcache();
        $this->assertEquals(array('r' => false, 'w' => false), $subject->getPermissions('/someForbiddenFile'));
    }

    /**
     * @test
     */
    public function permissionsAreCorrectlyRetrievedForAllowedFolder()
    {
        /** @var $subject \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
        list($basedir, $subject) = $this->prepareRealTestEnvironment();
        mkdir($basedir . '/someFolder');
        chmod($basedir . '/someFolder', 448);
        clearstatcache();
        $this->assertEquals(array('r' => true, 'w' => true), $subject->getPermissions('/someFolder'));
    }

    /**
     * @test
     */
    public function permissionsAreCorrectlyRetrievedForForbiddenFolder()
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            $this->markTestSkipped('Test skipped if run on linux as root');
        } elseif (TYPO3_OS === 'WIN') {
            $this->markTestSkipped('Test skipped if run on Windows system');
        }
        /** @var $subject \TYPO3\CMS\Core\Resource\Driver\LocalDriver */
        list($basedir, $subject) = $this->prepareRealTestEnvironment();
        mkdir($basedir . '/someForbiddenFolder');
        chmod($basedir . '/someForbiddenFolder', 0);
        clearstatcache();
        $result = $subject->getPermissions('/someForbiddenFolder');
        // Change permissions back to writable, so the sub-folder can be removed in tearDown
        chmod($basedir . '/someForbiddenFolder', 0777);
        $this->assertEquals(array('r' => false, 'w' => false), $result);
    }

    /**
     * Dataprovider for getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser test
     *
     * @return array group, filemode and expected result
     */
    public function getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser_dataProvider()
    {
        $data = array();
        // On some OS, the posix_* functions do not exits
        if (function_exists('posix_getgid')) {
            $data = array(
                'current group, readable/writable' => array(
                    posix_getgid(),
                    48,
                    array('r' => true, 'w' => true)
                ),
                'current group, readable/not writable' => array(
                    posix_getgid(),
                    32,
                    array('r' => true, 'w' => false)
                ),
                'current group, not readable/not writable' => array(
                    posix_getgid(),
                    0,
                    array('r' => false, 'w' => false)
                )
            );
        }
        $data = array_merge_recursive($data, array(
            'arbitrary group, readable/writable' => array(
                vfsStream::GROUP_USER_1,
                6,
                array('r' => true, 'w' => true)
            ),
            'arbitrary group, readable/not writable' => array(
                vfsStream::GROUP_USER_1,
                436,
                array('r' => true, 'w' => false)
            ),
            'arbitrary group, not readable/not writable' => array(
                vfsStream::GROUP_USER_1,
                432,
                array('r' => false, 'w' => false)
            )
        ));
        return $data;
    }

    /**
     * @test
     * @dataProvider getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser_dataProvider
     */
    public function getFilePermissionsReturnsCorrectPermissionsForFilesNotOwnedByCurrentUser($group, $permissions, $expectedResult)
    {
        if (TYPO3_OS === 'WIN') {
            $this->markTestSkipped('Test skipped if run on Windows system');
        }
        $this->addToMount(array(
            'testfile' => 'asdfg'
        ));
        $subject = $this->createDriver();
        /** @var $fileObject vfsStreamContent */
        $fileObject = vfsStreamWrapper::getRoot()->getChild($this->mountDir)->getChild('testfile');
        // just use an "arbitrary" user here - it is only important that
        $fileObject->chown(vfsStream::OWNER_USER_1);
        $fileObject->chgrp($group);
        $fileObject->chmod($permissions);
        $this->assertEquals($expectedResult, $subject->getPermissions('/testfile'));
    }

    /**
     * @test
     */
    public function isWithinRecognizesFilesWithinFolderAndInOtherFolders()
    {
        $subject = $this->createDriver();
        $this->assertTrue($subject->isWithin('/someFolder/', '/someFolder/test.jpg'));
        $this->assertTrue($subject->isWithin('/someFolder/', '/someFolder/subFolder/test.jpg'));
        $this->assertFalse($subject->isWithin('/someFolder/', '/someFolderWithALongName/test.jpg'));
    }

    /**
     * @test
     */
    public function isWithinAcceptsFileAndFolderObjectsAsContent()
    {
        $subject = $this->createDriver();
        $this->assertTrue($subject->isWithin('/someFolder/', '/someFolder/test.jpg'));
        $this->assertTrue($subject->isWithin('/someFolder/', '/someFolder/subfolder/'));
    }

    /**********************************
     * Copy/move file
     **********************************/

    /**
     * @test
     */
    public function filesCanBeCopiedWithinStorage()
    {
        $fileContents = $this->getUniqueId();
        $this->addToMount(array(
            'someFile' => $fileContents,
            'targetFolder' => array()
        ));
        $subject = $this->createDriver(
            array(),
            array('getMimeTypeOfFile')
        );
        $subject->copyFileWithinStorage('/someFile', '/targetFolder/', 'someFile');
        $this->assertFileEquals($this->getUrlInMount('/someFile'), $this->getUrlInMount('/targetFolder/someFile'));
    }

    /**
     * @test
     */
    public function filesCanBeMovedWithinStorage()
    {
        $fileContents = $this->getUniqueId();
        $this->addToMount(array(
            'targetFolder' => array(),
            'someFile' => $fileContents
        ));
        $subject = $this->createDriver();
        $newIdentifier = $subject->moveFileWithinStorage('/someFile', '/targetFolder/', 'file');
        $this->assertEquals($fileContents, file_get_contents($this->getUrlInMount('/targetFolder/file')));
        $this->assertFileNotExists($this->getUrlInMount('/someFile'));
        $this->assertEquals('/targetFolder/file', $newIdentifier);
    }

    /**
     * @test
     */
    public function fileMetadataIsChangedAfterMovingFile()
    {
        $fileContents = $this->getUniqueId();
        $this->addToMount(array(
            'targetFolder' => array(),
            'someFile' => $fileContents
        ));
        $subject = $this->createDriver(
            array(),
                // Mocked because finfo() can not deal with vfs streams and throws warnings
            array('getMimeTypeOfFile')
        );
        $newIdentifier = $subject->moveFileWithinStorage('/someFile', '/targetFolder/', 'file');
        $fileMetadata = $subject->getFileInfoByIdentifier($newIdentifier);
        $this->assertEquals($newIdentifier, $fileMetadata['identifier']);
    }

    public function renamingFiles_dataProvider()
    {
        return array(
            'file in subfolder' => array(
                array(
                    'targetFolder' => array('file' => '')
                ),
                '/targetFolder/file',
                'newFile',
                '/targetFolder/newFile'
            ),
            'file in rootfolder' => array(
                array(
                    'fileInRoot' => ''
                ),
                '/fileInRoot',
                'newFile',
                '/newFile'
            )
        );
    }

    /**
     * @test
     * @dataProvider renamingFiles_dataProvider
     */
    public function renamingFilesChangesFilenameOnDisk(array $filesystemStructure, $oldFileIdentifier, $newFileName, $expectedNewIdentifier)
    {
        $this->addToMount($filesystemStructure);
        $subject = $this->createDriver();
        $newIdentifier = $subject->renameFile($oldFileIdentifier, $newFileName);
        $this->assertFalse($subject->fileExists($oldFileIdentifier));
        $this->assertTrue($subject->fileExists($newIdentifier));
        $this->assertEquals($expectedNewIdentifier, $newIdentifier);
    }

    /**
     * @test
     */
    public function renamingFilesFailsIfTargetFileExists()
    {
        $this->expectException(\TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException::class);
        $this->expectExceptionCode(1320291063);
        $this->addToMount(array(
            'targetFolder' => array('file' => '', 'newFile' => '')
        ));
        $subject = $this->createDriver();
        $subject->renameFile('/targetFolder/file', 'newFile');
    }

    /**
     * We use this data provider for testing move methods because there are some issues with the
     *
     * @return array
     */
    public function renamingFolders_dataProvider()
    {
        return array(
            'folder in root folder' => array(
                array(
                    'someFolder' => array()
                ),
                '/someFolder/',
                'newFolder',
                '/newFolder/'
            ),
            'file in subfolder' => array(
                array(
                    'subfolder' => array(
                        'someFolder' => array()
                    )
                ),
                '/subfolder/someFolder/',
                'newFolder',
                '/subfolder/newFolder/'
            )
        );
    }

    /**
     * @test
     * @dataProvider renamingFolders_dataProvider
     */
    public function renamingFoldersChangesFolderNameOnDisk(array $filesystemStructure, $oldFolderIdentifier, $newFolderName, $expectedNewIdentifier)
    {
        $this->addToMount($filesystemStructure);
        $subject = $this->createDriver();
        $mapping = $subject->renameFolder($oldFolderIdentifier, $newFolderName);
        $this->assertFalse($subject->folderExists($oldFolderIdentifier));
        $this->assertTrue($subject->folderExists($expectedNewIdentifier));
        $this->assertEquals($expectedNewIdentifier, $mapping[$oldFolderIdentifier]);
    }

    /**
     * @test
     */
    public function renameFolderReturnsCorrectMappingInformationForAllFiles()
    {
        $fileContents = 'asdfg';
        $this->addToMount(array(
            'sourceFolder' => array(
                'subFolder' => array('file' => $fileContents),
                'file2' => 'asdfg'
            )
        ));
        $subject = $this->createDriver();
        $mappingInformation = $subject->renameFolder('/sourceFolder/', 'newFolder');
        $this->isTrue(is_array($mappingInformation));
        $this->assertEquals('/newFolder/', $mappingInformation['/sourceFolder/']);
        $this->assertEquals('/newFolder/file2', $mappingInformation['/sourceFolder/file2']);
        $this->assertEquals('/newFolder/subFolder/file', $mappingInformation['/sourceFolder/subFolder/file']);
        $this->assertEquals('/newFolder/subFolder/', $mappingInformation['/sourceFolder/subFolder/']);
    }

    /**
     * @test
     */
    public function renameFolderRevertsRenamingIfFilenameMapCannotBeCreated()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1334160746);
        $this->addToMount(array(
            'sourceFolder' => array(
                'file' => 'asdfg'
            )
        ));
        $subject = $this->createDriver(array(), array('createIdentifierMap'));
        $subject->expects($this->atLeastOnce())->method('createIdentifierMap')->will($this->throwException(new \TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException()));
        $subject->renameFolder('/sourceFolder/', 'newFolder');
        $this->assertFileExists($this->getUrlInMount('/sourceFolder/file'));
    }

    /**
     * @test
     */
    public function isFolderEmptyReturnsTrueForEmptyFolder()
    {
        // This also prepares the next few tests, so add more info than required for this test
        $this->addToMount(array(
            'emptyFolder' => array()
        ));
        $subject = $this->createDriver();
        $this->assertTrue($subject->isFolderEmpty('/emptyFolder/'));
        return $subject;
    }

    /**
     * @test
     */
    public function isFolderEmptyReturnsFalseIfFolderHasFile()
    {
        $this->addToMount(array(
            'folderWithFile' => array(
                'someFile' => ''
            )
        ));
        $subject = $this->createDriver();
        $this->assertFalse($subject->isFolderEmpty('/folderWithFile/'));
    }

    /**
     * @test
     */
    public function isFolderEmptyReturnsFalseIfFolderHasSubfolder()
    {
        $this->addToMount(array(
            'folderWithSubfolder' => array(
                'someFolder' => array()
            )
        ));
        $subject = $this->createDriver();
        $this->assertFalse($subject->isFolderEmpty('/folderWithSubfolder/'));
    }

    /**********************************
     * Copy/move folder
     **********************************/
    /**
     * @test
     */
    public function foldersCanBeMovedWithinStorage()
    {
        $fileContents = $this->getUniqueId();
        $this->addToMount(array(
            'sourceFolder' => array(
                'file' => $fileContents,
            ),
            'targetFolder' => array(),
        ));
        $subject = $this->createDriver();
        /** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver $subject */
        $subject->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'someFolder');
        $this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/someFolder/')));
        $this->assertEquals($fileContents, file_get_contents($this->getUrlInMount('/targetFolder/someFolder/file')));
        $this->assertFileNotExists($this->getUrlInMount('/sourceFolder'));
    }

    /**
     * @test
     */
    public function moveFolderWithinStorageReturnsCorrectMappingInformationForAllFiles()
    {
        $fileContents = 'asdfg';
        $this->addToMount(array(
            'targetFolder' => array(),
            'sourceFolder' => array(
                'subFolder' => array('file' => $fileContents),
                'file' => 'asdfg'
            )
        ));
        $subject = $this->createDriver();
        $mappingInformation = $subject->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'sourceFolder');
        $this->assertEquals('/targetFolder/sourceFolder/file', $mappingInformation['/sourceFolder/file']);
        $this->assertEquals('/targetFolder/sourceFolder/subFolder/file', $mappingInformation['/sourceFolder/subFolder/file']);
        $this->assertEquals('/targetFolder/sourceFolder/subFolder/', $mappingInformation['/sourceFolder/subFolder/']);
    }

    /**
     * @test
     */
    public function folderCanBeRenamedWhenMoving()
    {
        $this->addToMount(array(
            'sourceFolder' => array(
                'file' => $this->getUniqueId(),
            ),
            'targetFolder' => array(),
        ));
        $subject = $this->createDriver();
        $subject->moveFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolder');
        $this->assertTrue(file_exists($this->getUrlInMount('/targetFolder/newFolder/')));
    }

    /**
     * @test
     */
    public function copyFolderWithinStorageCopiesSingleFileToNewFolderName()
    {
        $this->addToMount(array(
            'sourceFolder' => array(
                'file' => $this->getUniqueId(),
            ),
            'targetFolder' => array(),
        ));
        $subject = $this->createDriver();
        $subject->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
        $this->assertTrue(is_file($this->getUrlInMount('/targetFolder/newFolderName/file')));
    }

    /**
     * @test
     */
    public function copyFolderWithinStorageCopiesSingleSubFolderToNewFolderName()
    {
        list($basePath, $subject) = $this->prepareRealTestEnvironment();
        GeneralUtility::mkdir_deep($basePath, '/sourceFolder/subFolder');
        GeneralUtility::mkdir_deep($basePath, '/targetFolder');

        $subject->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
        $this->isTrue(is_dir($basePath . '/targetFolder/newFolderName/subFolder'));
    }

    /**
     * @test
     */
    public function copyFolderWithinStorageCopiesFileInSingleSubFolderToNewFolderName()
    {
        list($basePath, $subject) = $this->prepareRealTestEnvironment();
        GeneralUtility::mkdir_deep($basePath, '/sourceFolder/subFolder');
        GeneralUtility::mkdir_deep($basePath, '/targetFolder');
        file_put_contents($basePath . '/sourceFolder/subFolder/file', $this->getUniqueId());
        GeneralUtility::fixPermissions($basePath . '/sourceFolder/subFolder/file');

        $subject->copyFolderWithinStorage('/sourceFolder/', '/targetFolder/', 'newFolderName');
        $this->assertTrue(is_file($basePath . '/targetFolder/newFolderName/subFolder/file'));
    }

    ///////////////////////
    // Tests concerning sanitizeFileName
    ///////////////////////

    /**
     * Set up data for sanitizeFileName tests
     */
    public function setUpCharacterStrings()
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
    public function sanitizeFileNameUTF8FilesystemDataProvider()
    {
        $this->setUpCharacterStrings();
        return array(
            // Characters ordered by ASCII table
            'allowed characters utf-8 (ASCII part)' => array(
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
            ),
            // Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
            'replace special characters with _ (not allowed characters) utf-8 (ASCII part)' => array(
                '! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
                '_____________________________'
            ),
            'utf-8 (Latin-1 Supplement)' => array(
                $this->utf8Latin1Supplement,
                '________________________________ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ'
            ),
            'trim leading and tailing spaces utf-8' => array(
                ' test.txt  ',
                'test.txt'
            ),
            'remove tailing dot' => array(
                'test.txt.',
                'test.txt'
            ),
        );
    }

    /**
     * @test
     * @dataProvider sanitizeFileNameUTF8FilesystemDataProvider
     */
    public function sanitizeFileNameUTF8Filesystem($fileName, $expectedResult)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 1;
        $this->assertEquals(
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
    public function sanitizeFileNameNonUTF8FilesystemDataProvider()
    {
        $this->setUpCharacterStrings();
        return array(
            // Characters ordered by ASCII table
            'allowed characters iso-8859-1' => array(
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
                'iso-8859-1',
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
            ),
            // Characters ordered by ASCII table
            'allowed characters utf-8' => array(
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz',
                'utf-8',
                '-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'
            ),
            // Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
            'replace special characters with _ (not allowed characters) iso-8859-1' => array(
                '! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
                'iso-8859-1',
                '_____________________________'
            ),
            // Characters ordered by ASCII table (except for space-character, because space-character ist trimmed)
            'replace special characters with _ (not allowed characters) utf-8' => array(
                '! "#$%&\'()*+,/:;<=>?[\\]^`{|}~',
                'utf-8',
                '_____________________________'
            ),
            'iso-8859-1 (code > 127)' => array(
                // http://de.wikipedia.org/wiki/ISO_8859-1
                // chr(0xA0) = NBSP (no-break space) => gets trimmed
                $this->iso88591GreaterThan127,
                'iso-8859-1',
                '_centpound_yen____c_a_____R_____-23_u___1o__1_41_23_4_AAAAAEAAAECEEEEIIIIDNOOOOOExOEUUUUEYTHssaaaaaeaaaeceeeeiiiidnoooooe_oeuuuueythy'
            ),
            'utf-8 (Latin-1 Supplement)' => array(
                // chr(0xC2) . chr(0x0A) = NBSP (no-break space) => gets trimmed
                $this->utf8Latin1Supplement,
                'utf-8',
                '_centpound__yen______c_a_______R_______-23__u_____1o__1_41_23_4_AAAAAEAAAECEEEEIIIIDNOOOOOExOEUUUUEYTHssaaaaaeaaaeceeeeiiiidnoooooe_oeuuuueythy'
            ),
            'utf-8 (Latin-1 Extended A)' => array(
                $this->utf8Latin1ExtendedA,
                'utf-8',
                'AaAaAaCcCcCcCcDdDdEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiIJijJjKk__LlLlLlL_l_LlNnNnNn_n____OOooOoOoOEoeRrRrRrSsSsSsSsTtTtTtUuUuUuUuUuUuWwYyYZzZzZzs'
            ),
            'trim leading and tailing spaces iso-8859-1' => array(
                ' test.txt  ',
                'iso-8859-1',
                'test.txt'
            ),
            'trim leading and tailing spaces utf-8' => array(
                ' test.txt  ',
                'utf-8',
                'test.txt'
            ),
            'remove tailing dot iso-8859-1' => array(
                'test.txt.',
                'iso-8859-1',
                'test.txt'
            ),
            'remove tailing dot utf-8' => array(
                'test.txt.',
                'utf-8',
                'test.txt'
            ),
        );
    }

    /**
     * @test
     * @dataProvider sanitizeFileNameNonUTF8FilesystemDataProvider
     */
    public function sanitizeFileNameNonUTF8Filesystem($fileName, $charset, $expectedResult)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 0;
        $this->assertEquals(
            $expectedResult,
            $this->createDriver()->sanitizeFileName($fileName, $charset)
        );
    }

    /**
     * @test
     */
    public function sanitizeFileNameThrowsExceptionOnInvalidFileName()
    {
        $this->expectException(InvalidFileNameException::class);
        $this->expectExceptionCode(1320288991);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 1;
        $this->createDriver()->sanitizeFileName('');
    }

    /**
     * @test
     */
    public function applyFilterMethodsToDirectoryItemCallsFilterMethodIfClosure()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1463073434);
        $closure = function () {
            throw new \Exception('I was called!', 1463073434);
        };

        $filterMethods = array(
            $closure,
        );

        $this->createDriver()->_call('applyFilterMethodsToDirectoryItem', $filterMethods, '', '', '');
    }

    /**
     * @test
     */
    public function applyFilterMethodsToDirectoryItemCallsFilterMethodIfName()
    {
        $dummyObject = $this
            ->getMockBuilder('\TYPO3\CMS\Core\Resource\Driver\LocalDriver')
            ->setMethods(array('dummy'))
            ->disableOriginalConstructor()
            ->getMock();
        $method = array(
            $dummyObject,
            'dummy',
        );
        $dummyObject->expects($this->once())->method('dummy');
        $filterMethods = array(
            $method,
        );
        $this->createDriver()->_call('applyFilterMethodsToDirectoryItem', $filterMethods, '', '', '');
    }
}
