<?php

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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ResourceStorageTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        // cleanup manually created folders
        foreach (glob(Environment::getPublicPath() . '/fileadmin/*') as $folderToRemove) {
            GeneralUtility::rmdir($folderToRemove, true);
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getNestedProcessingFolderTest()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);
        $subject = (new StorageRepository())->findByUid(1);
        $subject->setEvaluatePermissions(false);

        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/_processed_');
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/adirectory');
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp/assets/_processed_/');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/adirectory/bar.txt', 'myData');
        clearstatcache();
        $subject->addFileMount('/adirectory/', ['read_only' => false]);
        $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/adirectory/bar.txt');

        $rootProcessingFolder = $subject->getProcessingFolder();
        $processingFolder = $subject->getProcessingFolder($file);

        self::assertInstanceOf(Folder::class, $processingFolder);
        self::assertNotEquals($rootProcessingFolder, $processingFolder);

        for ($i = ResourceStorage::PROCESSING_FOLDER_LEVELS; $i>0; $i--) {
            $processingFolder = $processingFolder->getParentFolder();
        }
        self::assertEquals($rootProcessingFolder, $processingFolder);
    }

    /**
     * @param string $targetDirectory
     * @param string $fileMountFolder
     * @param bool $isFileMountReadOnly
     * @param bool $checkWriteAccess
     * @param bool $expectedResult
     * @test
     * @dataProvider isWithinFileMountBoundariesDataProvider
     */
    public function isWithinFileMountBoundariesRespectsReadOnlyFileMounts($targetDirectory, $fileMountFolder, $isFileMountReadOnly, $checkWriteAccess, $expectedResult)
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $fileName = 'bar.txt';
        $this->setUpBackendUserFromFixture(1);
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/_processed_');
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/' . $targetDirectory);
        if ($fileMountFolder !== $targetDirectory) {
            GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/' . $fileMountFolder);
        }
        file_put_contents(Environment::getPublicPath() . '/fileadmin/' . $targetDirectory . '/' . $fileName, 'myData');
        clearstatcache();
        $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/' . $targetDirectory . '/' . $fileName);

        $subject = (new StorageRepository())->findByUid(1);
        $subject->setEvaluatePermissions(true);

        // read_only = true -> no write access for user, so checking for second argument true should assert false
        $subject->addFileMount('/' . $fileMountFolder . '/', ['read_only' => $isFileMountReadOnly]);
        self::assertSame($expectedResult, $subject->isWithinFileMountBoundaries($file, $checkWriteAccess));
    }

    /**
     * @return array
     */
    public function isWithinFileMountBoundariesDataProvider()
    {
        return [
            'Access to file in ro file mount denied for write request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'fooBaz',
                '$isFileMountReadOnly' => true,
                '$checkWriteAccess' => true,
                '$expectedResult' => false,
            ],
            'Access to file in ro file mount allowed for read request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'fooBaz',
                '$isFileMountReadOnly' => true,
                '$checkWriteAccess' => false,
                '$expectedResult' => true,
            ],
            'Access to file in rw file mount allowed for write request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'fooBaz',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => true,
                '$expectedResult' => true,
            ],
            'Access to file in rw file mount allowed for read request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'fooBaz',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => false,
                '$expectedResult' => true,
            ],
            'Access to file not in file mount denied for write request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'barBaz',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => true,
                '$expectedResult' => false,
            ],
            'Access to file not in file mount denied for read request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'barBaz',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => false,
                '$expectedResult' => false,
            ],
        ];
    }

    /**
     * @test
     */
    public function getProcessingRootFolderTest()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);

        $subject = (new StorageRepository())->findByUid(1);
        $processingFolder = $subject->getProcessingFolder();

        self::assertInstanceOf(Folder::class, $processingFolder);
    }

    /**
     * @test
     */
    public function getRoleReturnsDefaultForRegularFolders()
    {
        $folderIdentifier = StringUtility::getUniqueId();
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);

        $subject = (new StorageRepository())->findByUid(1);
        $folder = new Folder($subject, '/foo/' . $folderIdentifier . '/', $folderIdentifier);

        $role = $subject->getRole($folder);

        self::assertSame(FolderInterface::ROLE_DEFAULT, $role);
    }

    /**
     * @test
     */
    public function replaceFileFailsIfLocalFileDoesNotExist()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);
        $subject = (new StorageRepository())->findByUid(1);

        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/foo');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/foo/bar.txt', 'myData');
        clearstatcache();
        $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/foo/bar.txt');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1325842622);
        $subject->replaceFile($file, Environment::getPublicPath() . '/' . StringUtility::getUniqueId());
    }

    /**
     * @test
     */
    public function createFolderThrowsExceptionIfParentFolderDoesNotExist()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);
        $subject = (new StorageRepository())->findByUid(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1325689164);
        $subject->createFolder('newFolder', new Folder($subject, '/foo/', 'foo'));
    }

    /**
     * @test
     */
    public function deleteFileMovesFileToRecyclerFolderIfAvailable()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);
        $subject = (new StorageRepository())->findByUid(1);

        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/foo');
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/_recycler_');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/foo/bar.txt', 'myData');
        clearstatcache();

        $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/foo/bar.txt');
        $subject->deleteFile($file);

        self::assertTrue(file_exists(Environment::getPublicPath() . '/fileadmin/_recycler_/bar.txt'));
        self::assertFalse(file_exists(Environment::getPublicPath() . '/fileadmin/foo/bar.txt'));
    }

    /**
     * @test
     */
    public function deleteFileUnlinksFileIfNoRecyclerFolderAvailable()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);
        $subject = (new StorageRepository())->findByUid(1);

        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/foo');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/foo/bar.txt', 'myData');
        clearstatcache();

        $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/foo/bar.txt');
        $subject->deleteFile($file);

        self::assertFalse(file_exists(Environment::getPublicPath() . '/fileadmin/foo/bar.txt'));
    }

    public function searchFilesFindsFilesInFolderDataProvider(): array
    {
        return [
            'Finds foo recursive by name' => [
                'foo',
                '/bar/',
                true,
                [],
                [
                    '/bar/bla/foo.txt',
                ],
            ],
            'Finds foo not recursive by name' => [
                'foo',
                '/bar/bla/',
                false,
                [],
                [
                    '/bar/bla/foo.txt',
                ],
            ],
            'Finds nothing when not recursive for top level folder' => [
                'foo',
                '/bar/',
                false,
                [],
                [],
            ],
            'Finds foo by description' => [
                'fodescrip',
                '/bar/',
                true,
                [],
                [
                    '/bar/bla/foo.txt',
                ],
            ],
            'Finds foo by translated description' => [
                'fotranslated',
                '/bar/',
                true,
                [],
                [
                    '/bar/bla/foo.txt',
                ],
            ],
            'Finds blupp by name' => [
                'blupp',
                '/bar/',
                false,
                [],
                [
                    '/bar/blupp.txt',
                ],
            ],
            'Finds only blupp by title for non recursive' => [
                'title',
                '/bar/',
                false,
                [],
                [
                    '/bar/blupp.txt',
                ],
            ],
            'Finds foo and blupp by title for recursive' => [
                'title',
                '/bar/',
                true,
                [],
                [
                    '/bar/blupp.txt',
                    '/bar/bla/foo.txt',
                ],
            ],
            'Finds foo, baz and blupp with no folder' => [
                'title',
                null,
                true,
                [],
                [
                    '/baz/bla/baz.txt',
                    '/bar/blupp.txt',
                    '/bar/bla/foo.txt',
                ],
            ],
            'Finds nothing for not existing' => [
                'baz',
                '/bar/',
                true,
                [],
                [],
            ],
            'Finds nothing in root, when not recursive' => [
                'title',
                '/',
                false,
                [],
                [],
            ],
            'Finds nothing, when not recursive and no folder given' => [
                'title',
                null,
                false,
                [],
                [],
            ],
            'Filter is applied to result' => [
                'title',
                null,
                true,
                [
                    function ($itemName) {
                        return strpos($itemName, 'blupp') !== false ? true : -1;
                    }
                ],
                [
                    '/bar/blupp.txt',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider searchFilesFindsFilesInFolderDataProvider
     * @param string $searchTerm
     * @param string $searchFolder
     * @param bool $recursive
     * @param array $filters
     * @param string[] $expectedIdentifiers
     * @throws \TYPO3\TestingFramework\Core\Exception
     */
    public function searchFilesFindsFilesInFolder(string $searchTerm, ?string $searchFolder, bool $recursive, array $filters, array $expectedIdentifiers)
    {
        try {
            $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
            $this->importDataSet(__DIR__ . '/Fixtures/FileSearch.xml');
            $this->setUpBackendUserFromFixture(1);
            $subject = (new StorageRepository())->findByUid(1);
            $subject->setFileAndFolderNameFilters($filters);

            GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/bar/bla');
            GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/baz/bla');
            file_put_contents(Environment::getPublicPath() . '/fileadmin/bar/bla/foo.txt', 'myData');
            file_put_contents(Environment::getPublicPath() . '/fileadmin/baz/bla/baz.txt', 'myData');
            file_put_contents(Environment::getPublicPath() . '/fileadmin/bar/blupp.txt', 'myData');
            clearstatcache();

            $folder = $searchFolder ? GeneralUtility::makeInstance(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:' . $searchFolder) : null;
            $search = FileSearchDemand::createForSearchTerm($searchTerm);
            if ($recursive) {
                $search = $search->withRecursive();
            }

            $result = $subject->searchFiles($search, $folder);
            $expectedFiles = array_map([$subject, 'getFile'], $expectedIdentifiers);
            self::assertSame($expectedFiles, iterator_to_array($result));

            // Check if search also works for non hierarchical storages/drivers
            // This is a hack, as capabilities is not settable from the outside
            $objectReflection = new \ReflectionObject($subject);
            $property = $objectReflection->getProperty('capabilities');
            $property->setAccessible(true);
            $property->setValue('capabilities', $subject->getCapabilities() & 7);
            $result = $subject->searchFiles($search, $folder);
            $expectedFiles = array_map([$subject, 'getFile'], $expectedIdentifiers);
            self::assertSame($expectedFiles, iterator_to_array($result));
        } finally {
            GeneralUtility::rmdir(Environment::getPublicPath() . '/fileadmin/bar', true);
            GeneralUtility::rmdir(Environment::getPublicPath() . '/fileadmin/baz', true);
        }
    }
}
