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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Capabilities;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidTargetFolderException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class StorageRepositoryTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::rmdir(Environment::getPublicPath() . '/fileadmin', true);
        mkdir(Environment::getPublicPath() . '/fileadmin');
        GeneralUtility::rmdir(Environment::getPublicPath() . '/typo3temp/assets/_processed_', true);
        parent::tearDown();
    }

    public static function bestStorageIsResolvedDataProvider(): iterable
    {
        // `{public}` will be replaced by public project path (not having trailing slash)
        // double slashes `//` are used on purpose for given file identifiers

        // legacy storage
        yield ['/favicon.ico', '0:/favicon.ico'];
        yield ['/favicon.ico', '0:/favicon.ico'];

        yield ['favicon.ico', '0:/favicon.ico'];
        yield ['{public}//favicon.ico', '0:/favicon.ico'];
        yield ['{public}/favicon.ico', '0:/favicon.ico'];

        // using storages with relative path
        yield ['/fileadmin/img.png', '1:/img.png'];
        yield ['fileadmin/img.png', '1:/img.png'];
        yield ['/fileadmin/images/img.png', '1:/images/img.png'];
        yield ['fileadmin/images/img.png', '1:/images/img.png'];
        yield ['/documents/doc.pdf', '2:/doc.pdf'];
        yield ['documents/doc.pdf', '2:/doc.pdf'];
        yield ['/fileadmin/nested/images/img.png', '3:/images/img.png'];
        yield ['fileadmin/nested/images/img.png', '3:/images/img.png'];

        yield ['{public}//fileadmin/img.png', '1:/img.png'];
        yield ['{public}/fileadmin/img.png', '1:/img.png'];
        yield ['{public}//fileadmin/images/img.png', '1:/images/img.png'];
        yield ['{public}/fileadmin/images/img.png', '1:/images/img.png'];
        yield ['{public}//documents/doc.pdf', '2:/doc.pdf'];
        yield ['{public}/documents/doc.pdf', '2:/doc.pdf'];
        yield ['{public}//fileadmin/nested/images/img.png', '3:/images/img.png'];
        yield ['{public}/fileadmin/nested/images/img.png', '3:/images/img.png'];

        // using storages with absolute path
        yield ['/files/img.png', '4:/img.png'];
        yield ['files/img.png', '4:/img.png'];
        yield ['/files/images/img.png', '4:/images/img.png'];
        yield ['files/images/img.png', '4:/images/img.png'];
        yield ['/docs/doc.pdf', '5:/doc.pdf'];
        yield ['docs/doc.pdf', '5:/doc.pdf'];
        yield ['/files/nested/images/img.png', '6:/images/img.png'];
        yield ['files/nested/images/img.png', '6:/images/img.png'];

        yield ['{public}//files/img.png', '4:/img.png'];
        yield ['{public}/files/img.png', '4:/img.png'];
        yield ['{public}//files/images/img.png', '4:/images/img.png'];
        yield ['{public}/files/images/img.png', '4:/images/img.png'];
        yield ['{public}//docs/doc.pdf', '5:/doc.pdf'];
        yield ['{public}/docs/doc.pdf', '5:/doc.pdf'];
        yield ['{public}//files/nested/images/img.png', '6:/images/img.png'];
        yield ['{public}/files/nested/images/img.png', '6:/images/img.png'];
    }

    #[DataProvider('bestStorageIsResolvedDataProvider')]
    #[Test]
    public function bestStorageIsResolved(string $sourceIdentifier, string $expectedCombinedIdentifier): void
    {
        $subject = $this->get(StorageRepository::class);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $publicPath = Environment::getPublicPath();
        $prefixDelegate = static function (string $value) use ($publicPath): string {
            return $publicPath . '/' . $value;
        };
        // array indexes are not relevant here, but are those expected to be used as storage UID (`1:/file.png`)
        // @todo it is possible to create ambiguous storages, e.g. `fileadmin/` AND `/fileadmin/`
        $relativeNames = [1 => 'fileadmin/', 2 => 'documents/', 3 => 'fileadmin/nested/'];
        // @todo: All these directories must exist. This is because createLocalStorage() calls testCaseSensitivity()
        //        which creates a file in each directory without checking if the directory does exist. Arguably, this
        //        should be handled in testCaseSensitivity(). For now, we create the directories in question and
        //        suppress errors so only the first test creates them and subsequent tests don't emit a warning here.
        @mkdir($this->instancePath . '/documents');
        @mkdir($this->instancePath . '/fileadmin/nested');
        $absoluteNames = array_map($prefixDelegate, [4 => 'files/', 5 => 'docs/', 6 => 'files/nested']);
        @mkdir($this->instancePath . '/files');
        @mkdir($this->instancePath . '/docs');
        @mkdir($this->instancePath . '/files/nested');
        foreach ($relativeNames as $relativeName) {
            $subject->createLocalStorage('rel:' . $relativeName, $relativeName, 'relative');
        }
        foreach ($absoluteNames as $absoluteName) {
            $subject->createLocalStorage('abs:' . $absoluteName, $absoluteName, 'absolute');
        }
        $sourceIdentifier = str_replace('{public}', Environment::getPublicPath(), $sourceIdentifier);
        $storage = $subject->getStorageObject(0, [], $sourceIdentifier);
        $combinedIdentifier = sprintf('%d:%s', $storage->getUid(), $sourceIdentifier);
        self::assertSame(
            $expectedCombinedIdentifier,
            $combinedIdentifier,
            sprintf('Given identifier "%s"', $sourceIdentifier)
        );
    }

    #[Test]
    public function getNestedProcessingFolderTest(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        $subject->setEvaluatePermissions(false);
        mkdir(Environment::getPublicPath() . '/fileadmin/_processed_');
        mkdir(Environment::getPublicPath() . '/fileadmin/aDirectory');
        mkdir(Environment::getPublicPath() . '/typo3temp/assets/_processed_');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/aDirectory/bar.txt', 'myData');
        $subject->addFileMount('/aDirectory/', ['read_only' => false]);
        $file = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/aDirectory/bar.txt');
        $rootProcessingFolder = $subject->getProcessingFolder();
        $processingFolder = $subject->getProcessingFolder($file);
        self::assertNotEquals($rootProcessingFolder, $processingFolder);
        for ($i = ResourceStorage::PROCESSING_FOLDER_LEVELS; $i > 0; $i--) {
            $processingFolder = $processingFolder->getParentFolder();
        }
        self::assertEquals($rootProcessingFolder, $processingFolder);
    }

    public static function isWithinFileMountBoundariesDataProvider(): array
    {
        return [
            'Access to file in ro file mount denied for write request' => [
                'targetDirectory' => 'fooBaz',
                'fileMountFolder' => 'fooBaz',
                'isFileMountReadOnly' => true,
                'checkWriteAccess' => true,
                'expectedResult' => false,
            ],
            'Access to file in ro file mount allowed for read request' => [
                'targetDirectory' => 'fooBaz',
                'fileMountFolder' => 'fooBaz',
                'isFileMountReadOnly' => true,
                'checkWriteAccess' => false,
                'expectedResult' => true,
            ],
            'Access to file in rw file mount allowed for write request' => [
                'targetDirectory' => 'fooBaz',
                'fileMountFolder' => 'fooBaz',
                'isFileMountReadOnly' => false,
                'checkWriteAccess' => true,
                'expectedResult' => true,
            ],
            'Access to file in rw file mount allowed for read request' => [
                'targetDirectory' => 'fooBaz',
                'fileMountFolder' => 'fooBaz',
                'isFileMountReadOnly' => false,
                'checkWriteAccess' => false,
                'expectedResult' => true,
            ],
            'Access to file not in file mount denied for write request' => [
                'targetDirectory' => 'fooBaz',
                'fileMountFolder' => 'barBaz',
                'isFileMountReadOnly' => false,
                'checkWriteAccess' => true,
                'expectedResult' => false,
            ],
            'Access to file not in file mount denied for read request' => [
                'targetDirectory' => 'fooBaz',
                'fileMountFolder' => 'barBaz',
                'isFileMountReadOnly' => false,
                'checkWriteAccess' => false,
                'expectedResult' => false,
            ],
        ];
    }

    #[DataProvider('isWithinFileMountBoundariesDataProvider')]
    #[Test]
    public function isWithinFileMountBoundariesRespectsReadOnlyFileMounts(string $targetDirectory, string $fileMountFolder, bool $isFileMountReadOnly, bool $checkWriteAccess, bool $expectedResult): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $fileName = 'bar.txt';
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        mkdir(Environment::getPublicPath() . '/fileadmin/_processed_');
        mkdir(Environment::getPublicPath() . '/fileadmin/' . $targetDirectory);
        if ($fileMountFolder !== $targetDirectory) {
            mkdir(Environment::getPublicPath() . '/fileadmin/' . $fileMountFolder);
        }
        file_put_contents(Environment::getPublicPath() . '/fileadmin/' . $targetDirectory . '/' . $fileName, 'myData');
        $file = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/' . $targetDirectory . '/' . $fileName);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        $subject->setEvaluatePermissions(true);
        // read_only = true -> no write access for user, so checking for second argument true should assert false
        $subject->addFileMount('/' . $fileMountFolder . '/', ['read_only' => $isFileMountReadOnly]);
        self::assertSame($expectedResult, $subject->isWithinFileMountBoundaries($file, $checkWriteAccess));
    }

    #[Test]
    public function getProcessingRootFolderTest(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        $processingFolder = $subject->getProcessingFolder();
        self::assertSame('/temp/assets/_processed_/', $processingFolder->getIdentifier());
    }

    #[Test]
    public function getRoleReturnsDefaultForRegularFolders(): void
    {
        $folderIdentifier = StringUtility::getUniqueId();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        $folder = new Folder($subject, '/foo/' . $folderIdentifier . '/', $folderIdentifier);
        $role = $subject->getRole($folder);
        self::assertSame(FolderInterface::ROLE_DEFAULT, $role);
    }

    #[Test]
    public function replaceFileFailsIfLocalFileDoesNotExist(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        mkdir(Environment::getPublicPath() . '/fileadmin/foo');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/foo/bar.txt', 'myData');
        $file = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/foo/bar.txt');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1325842622);
        $subject->replaceFile($file, Environment::getPublicPath() . '/' . StringUtility::getUniqueId());
    }

    #[Test]
    public function createFolderThrowsExceptionIfParentFolderDoesNotExist(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1325689164);
        $subject->createFolder('newFolder', new Folder($subject, '/foo/', 'foo'));
    }

    #[Test]
    public function deleteFileMovesFileToRecyclerFolderIfAvailable(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        mkdir(Environment::getPublicPath() . '/fileadmin/foo');
        mkdir(Environment::getPublicPath() . '/fileadmin/_recycler_');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/foo/bar.txt', 'myData');
        $file = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/foo/bar.txt');
        $subject->deleteFile($file);
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/_recycler_/bar.txt');
        self::assertFileDoesNotExist(Environment::getPublicPath() . '/fileadmin/foo/bar.txt');
    }

    #[Test]
    public function deleteFolderMovesFolderToRecyclerFolderIfAvailable(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        mkdir(Environment::getPublicPath() . '/fileadmin/foo');
        mkdir(Environment::getPublicPath() . '/fileadmin/_recycler_');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/foo/bar.txt', 'myData');
        $folder = $this->get(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:/foo');
        $subject->deleteFolder($folder, true);
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/_recycler_/foo/bar.txt');
        self::assertFileDoesNotExist(Environment::getPublicPath() . '/fileadmin/foo/bar.txt');
        self::assertDirectoryDoesNotExist(Environment::getPublicPath() . '/fileadmin/foo');
    }

    #[Test]
    public function deleteFileUnlinksFileIfNoRecyclerFolderAvailable(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        mkdir(Environment::getPublicPath() . '/fileadmin/foo');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/foo/bar.txt', 'myData');
        $file = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/foo/bar.txt');
        $subject->deleteFile($file);
        self::assertFileDoesNotExist(Environment::getPublicPath() . '/fileadmin/foo/bar.txt');
    }

    public static function searchFilesFindsFilesInFolderDataProvider(): array
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
                    static function ($itemName) {
                        return str_contains($itemName, 'blupp') ? true : -1;
                    },
                ],
                [
                    '/bar/blupp.txt',
                ],
            ],
        ];
    }

    /**
     * @param string[] $expectedIdentifiers
     */
    #[DataProvider('searchFilesFindsFilesInFolderDataProvider')]
    #[Test]
    public function searchFilesFindsFilesInFolder(string $searchTerm, ?string $searchFolder, bool $recursive, array $filters, array $expectedIdentifiers): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileSearch.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        $subject->setFileAndFolderNameFilters($filters);
        mkdir(Environment::getPublicPath() . '/fileadmin/bar');
        mkdir(Environment::getPublicPath() . '/fileadmin/bar/bla');
        mkdir(Environment::getPublicPath() . '/fileadmin/baz');
        mkdir(Environment::getPublicPath() . '/fileadmin/baz/bla');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/bar/bla/foo.txt', 'myData');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/baz/bla/baz.txt', 'myData');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/bar/blupp.txt', 'myData');
        $folder = $searchFolder ? $this->get(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:' . $searchFolder) : null;
        $search = FileSearchDemand::createForSearchTerm($searchTerm);
        if ($recursive) {
            $search = $search->withRecursive();
        }
        $result = $subject->searchFiles($search, $folder);
        $expectedFiles = array_map([$subject, 'getFile'], $expectedIdentifiers);
        self::assertSame($expectedFiles, iterator_to_array($result));
        // Check if search also works for non-hierarchical storages/drivers
        // This is a hack, as capabilities is not settable from the outside
        $objectReflection = new \ReflectionObject($subject);
        $property = $objectReflection->getProperty('capabilities');
        $property->setValue($subject, $subject->getCapabilities()->addCapabilities(Capabilities::CAPABILITY_BROWSABLE, Capabilities::CAPABILITY_PUBLIC, Capabilities::CAPABILITY_WRITABLE));
        $result = $subject->searchFiles($search, $folder);
        $expectedFiles = array_map([$subject, 'getFile'], $expectedIdentifiers);
        self::assertSame($expectedFiles, iterator_to_array($result));
    }

    #[Test]
    public function copyFolderThrowsErrorWhenFolderAlreadyExistsInTargetFolderAndConflictModeIsCancel(): void
    {
        $conflictMode = DuplicationBehavior::CANCEL;
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        mkdir(Environment::getPublicPath() . '/fileadmin/foo');
        $folderToCopy = $this->get(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:/foo');
        $targetParentFolder = $this->get(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:/');
        $this->expectException(InvalidTargetFolderException::class);
        $this->expectExceptionCode(1422723059);
        $subject->copyFolder($folderToCopy, $targetParentFolder, null, $conflictMode);
    }

    #[Test]
    public function copyFolderGeneratesNewFolderNameWhenFolderAlreadyExistsInTargetFolderAndConflictModeIsRename(): void
    {
        $conflictMode = DuplicationBehavior::RENAME;
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        mkdir(Environment::getPublicPath() . '/fileadmin/foo');
        $folderToCopy = $this->get(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:/foo');
        $targetParentFolder = $this->get(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:/');
        $subject->copyFolder($folderToCopy, $targetParentFolder, null, $conflictMode);
        $newFolder = $this->get(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:/foo_01');
        self::assertEquals('/foo_01/', $newFolder->getIdentifier());
    }

    #[Test]
    public function copyFileThrowsErrorWhenFileWithSameNameAlreadyExistsInTargetFolderAndConflictModeIsCancel(): void
    {
        $conflictMode = DuplicationBehavior::CANCEL;
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        mkdir(Environment::getPublicPath() . '/fileadmin/foo');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/foo/bar.txt', 'Temp file 1');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/bar.txt', 'Temp file 2');
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        $fileToCopy = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/foo/bar.txt');
        $targetParentFolder = $this->get(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:/');
        $this->expectException(ExistingTargetFileNameException::class);
        $this->expectExceptionCode(1320291064);
        $subject->copyFile($fileToCopy, $targetParentFolder, null, $conflictMode);
    }

    #[Test]
    public function copyFileGeneratesNewFileNameWhenFileAlreadyExistsInTargetFolderAndConflictModeIsRename(): void
    {
        $conflictMode = DuplicationBehavior::RENAME;
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        mkdir(Environment::getPublicPath() . '/fileadmin/foo');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/foo/bar.txt', 'Temp file 1');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/bar.txt', 'Temp file 2');
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        $fileToCopy = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/foo/bar.txt');
        $targetParentFolder = $this->get(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:/');
        $subject->copyFile($fileToCopy, $targetParentFolder, null, $conflictMode);
        $newFile = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/bar_01.txt');
        self::assertInstanceOf(File::class, $newFile);
    }

    #[Test]
    public function copyFileCopiesMetadata(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        mkdir(Environment::getPublicPath() . '/fileadmin/foo');
        file_put_contents(Environment::getPublicPath() . '/fileadmin/foo/bar.txt', 'Temp file');
        $subject = $this->get(StorageRepository::class)->findByUid(1);
        $fileToCopyMetaData = [
            'title' => 'Temp file title',
            'description' => 'Temp file description',
        ];
        /** @var File $fileToCopy */
        $fileToCopy = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier('1:/foo/bar.txt');
        $fileToCopy->getMetaData()->add($fileToCopyMetaData);
        $targetParentFolder = $this->get(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier('1:/');
        /** @var File $newFile */
        $newFile = $subject->copyFile($fileToCopy, $targetParentFolder);
        self::assertNotEquals($fileToCopy->getMetaData()->get()['file'], $newFile->getMetaData()->get()['file']);
        self::assertNotEquals($fileToCopy->getMetaData()->get()['uid'], $newFile->getMetaData()->get()['uid']);
        self::assertEquals($fileToCopyMetaData['title'], $newFile->getMetaData()->get()['title']);
        self::assertEquals($fileToCopyMetaData['description'], $newFile->getMetaData()->get()['description']);
    }
}
