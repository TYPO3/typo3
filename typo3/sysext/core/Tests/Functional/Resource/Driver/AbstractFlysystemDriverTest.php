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

use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Capabilities;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Resource\Exception\ResourcePermissionsUnavailableException;
use TYPO3\CMS\Core\Tests\Functional\Resource\Driver\Fixtures\LocalFlysystemDriver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AbstractFlysystemDriverTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    private string $baseDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDirectory = $this->instancePath . '/flysystem-driver-tests';
        mkdir($this->baseDirectory);
        GeneralUtility::mkdir_deep(Environment::getVarPath() . '/transient');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->baseDirectory, true);
        parent::tearDown();
    }

    private function getSubject(array $configuration = []): LocalFlysystemDriver
    {
        $subject = new LocalFlysystemDriver(array_merge(['basePath' => $this->baseDirectory, 'caseSensitive' => true], $configuration));
        $subject->setStorageUid(3);
        $subject->processConfiguration();
        return $subject;
    }

    #[Test]
    public function processConfigurationThrowsForInvalidConfiguration(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new LocalFlysystemDriver()->processConfiguration();
    }

    #[Test]
    public function unconfiguredDriverThrowsInsteadOfAccessingAnyPath(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1792093589);
        new LocalFlysystemDriver(['basePath' => $this->baseDirectory])->fileExists('/file.txt');
    }

    #[Test]
    public function storageIsOnlyPublicWithBaseUri(): void
    {
        file_put_contents($this->baseDirectory . '/some file.txt', 'content');
        $subject = $this->getSubject();
        self::assertFalse($subject->hasCapability(Capabilities::CAPABILITY_PUBLIC));
        self::assertNull($subject->getPublicUrl('/some file.txt'));

        $subject = $this->getSubject(['baseUri' => 'https://cdn.example.com/files/']);
        self::assertTrue($subject->hasCapability(Capabilities::CAPABILITY_PUBLIC));
        self::assertSame('https://cdn.example.com/files/some%20file.txt', $subject->getPublicUrl('/some file.txt'));
    }

    #[Test]
    public function publicUrlsOfTheAdapterAreNotUsed(): void
    {
        file_put_contents($this->baseDirectory . '/file.txt', 'content');
        $subject = $this->getSubject(['generatesPublicUrls' => true]);
        self::assertFalse($subject->hasCapability(Capabilities::CAPABILITY_PUBLIC));
        self::assertNull($subject->getPublicUrl('/file.txt'));
    }

    #[Test]
    public function pathsCannotLeaveTheStorage(): void
    {
        file_put_contents($this->instancePath . '/outside.txt', '');
        $subject = $this->getSubject();
        self::assertFalse($subject->fileExists('/../outside.txt'));
        self::assertFalse($subject->fileExists('/folder/../../outside.txt'));
        unlink($this->instancePath . '/outside.txt');
    }

    #[Test]
    public function identifiersAreLowercasedOnCaseInsensitiveStorages(): void
    {
        $subject = $this->getSubject(['caseSensitive' => false]);
        self::assertSame('/folder/file.txt', $subject->getFileInFolder('File.TXT', '/Folder/'));
        self::assertSame('/folder/sub/', $subject->getFolderInFolder('Sub', '/Folder/'));
    }

    #[Test]
    public function filesAndFoldersCanBeCreatedAndRead(): void
    {
        $subject = $this->getSubject();
        $folderIdentifier = $subject->createFolder('new folder');
        self::assertSame('/new_folder/', $folderIdentifier);
        self::assertSame('/new_folder/a/b/', $subject->createFolder('a/b', $folderIdentifier, true));
        $fileIdentifier = $subject->createFile('file.txt', $folderIdentifier);
        self::assertSame('/new_folder/file.txt', $fileIdentifier);
        self::assertSame(7, $subject->setFileContents($fileIdentifier, 'content'));
        self::assertSame('content', $subject->getFileContents($fileIdentifier));
        self::assertTrue($subject->fileExists($fileIdentifier));
        self::assertTrue($subject->fileExistsInFolder('file.txt', $folderIdentifier));
        self::assertTrue($subject->folderExistsInFolder('a', $folderIdentifier));
        self::assertTrue($subject->folderExists('/'));
        self::assertSame('', $subject->getFileContents('/missing.txt'));
        self::assertSame(sha1('content'), $subject->hash($fileIdentifier, 'sha1'));
    }

    #[Test]
    public function getFileInfoByIdentifierReturnsFileInformation(): void
    {
        mkdir($this->baseDirectory . '/folder');
        file_put_contents($this->baseDirectory . '/folder/file.txt', 'content');
        touch($this->baseDirectory . '/folder/file.txt', 1700000000);
        $subject = $this->getSubject();
        $fileInfo = $subject->getFileInfoByIdentifier('/folder/file.txt');
        self::assertSame(7, $fileInfo['size']);
        self::assertSame(1700000000, $fileInfo['mtime']);
        self::assertSame('text/plain', $fileInfo['mimetype']);
        self::assertSame('file.txt', $fileInfo['name']);
        self::assertSame('txt', $fileInfo['extension']);
        self::assertSame('/folder/file.txt', $fileInfo['identifier']);
        self::assertSame(3, $fileInfo['storage']);
        self::assertSame(sha1('/folder/file.txt'), $fileInfo['identifier_hash']);
        self::assertSame(sha1('/folder'), $fileInfo['folder_hash']);
        self::assertSame(['identifier' => '/folder/', 'name' => 'folder'], array_intersect_key($subject->getFolderInfoByIdentifier('/folder'), ['identifier' => 1, 'name' => 1]));
    }

    public static function mimeTypeIsDetectedLikeInLocalDriverDataProvider(): array
    {
        $vCard = "BEGIN:VCARD\nVERSION:3.0\nFN:Jane Doe\nEND:VCARD\n";
        return [
            'HTML' => ['file.html', '<!DOCTYPE html><html><head><title>Title</title></head><body></body></html>', 'text/html'],
            'plain text' => ['file.txt', 'Some text', 'text/plain'],
            'file without extension' => ['README', 'Some text', 'text/plain'],
            'empty file' => ['empty.txt', '', 'application/x-empty'],
            'vCard' => ['contact.vcf', $vCard, 'text/vcard'],
            'vCard with upper-case extension' => ['CONTACT.VCF', $vCard, 'text/vcard'],
            'YAML' => ['config.yaml', "key: value\nlist:\n  - item\n", 'application/yaml'],
            'JSON' => ['data.json', '{"key": "value"}', 'application/json'],
            'SVG' => ['image.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>', 'image/svg+xml'],
            'XLIFF' => ['labels.xlf', '<?xml version="1.0" encoding="UTF-8"?><xliff version="1.2"><file source-language="en"></file></xliff>', 'application/xliff+xml'],
            'GIF' => ['image.gif', base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='), 'image/gif'],
        ];
    }

    #[DataProvider('mimeTypeIsDetectedLikeInLocalDriverDataProvider')]
    #[Test]
    public function mimeTypeIsDetectedLikeInLocalDriver(string $fileName, string $contents, string $expectedMimeType): void
    {
        file_put_contents($this->baseDirectory . '/' . $fileName, $contents);
        $localDriver = new LocalDriver(['basePath' => $this->baseDirectory, 'caseSensitive' => true]);
        $localDriver->processConfiguration();
        self::assertSame($expectedMimeType, $localDriver->getFileInfoByIdentifier('/' . $fileName, ['mimetype'])['mimetype'], 'LocalDriver');

        self::assertSame($expectedMimeType, $this->getSubject()->getFileInfoByIdentifier('/' . $fileName, ['mimetype'])['mimetype'], 'Flysystem driver');
        $subject = $this->getSubject();
        $subject->getFilesInFolder('/');
        self::assertSame($expectedMimeType, $subject->getFileInfoByIdentifier('/' . $fileName, ['mimetype'])['mimetype'], 'Flysystem driver after listing');
    }

    #[Test]
    public function configuredMimeTypeOfFileExtensionTakesPrecedence(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['abc'] = 'application/x-abc';
        file_put_contents($this->baseDirectory . '/file.ABC', 'Some text');
        $localDriver = new LocalDriver(['basePath' => $this->baseDirectory, 'caseSensitive' => true]);
        $localDriver->processConfiguration();
        self::assertSame('application/x-abc', $localDriver->getFileInfoByIdentifier('/file.ABC', ['mimetype'])['mimetype']);

        $subject = $this->getSubject();
        $subject->countingAdapter->reportedMimeType = 'text/plain';
        self::assertSame('application/x-abc', $subject->getFileInfoByIdentifier('/file.ABC', ['mimetype'])['mimetype']);
        self::assertSame(0, $subject->countingAdapter->count('mimeType'));
    }

    #[Test]
    public function mimeTypeReportedByTheAdapterIsRefinedLikeADetectedOne(): void
    {
        file_put_contents($this->baseDirectory . '/labels.xlf', 'content');
        $subject = $this->getSubject();
        $subject->countingAdapter->reportedMimeType = 'text/xml';
        self::assertSame('application/xliff+xml', $subject->getFileInfoByIdentifier('/labels.xlf', ['mimetype'])['mimetype']);
    }

    #[Test]
    public function getFileInfoByIdentifierThrowsForMissingFile(): void
    {
        $this->expectException(FileDoesNotExistException::class);
        $this->getSubject()->getFileInfoByIdentifier('/missing.txt');
    }

    #[Test]
    public function listingsAreSortedFilteredAndLimited(): void
    {
        mkdir($this->baseDirectory . '/sub');
        file_put_contents($this->baseDirectory . '/b.txt', '22');
        file_put_contents($this->baseDirectory . '/a.txt', '333');
        file_put_contents($this->baseDirectory . '/c.jpg', '1');
        file_put_contents($this->baseDirectory . '/sub/d.txt', '');
        $subject = $this->getSubject();
        $skipJpg = static fn(string $itemName): bool|int => str_ends_with($itemName, '.jpg') ? -1 : true;

        self::assertSame(['/a.txt', '/b.txt', '/c.jpg'], array_values($subject->getFilesInFolder('/')));
        self::assertSame(['/c.jpg', '/b.txt', '/a.txt'], array_values($subject->getFilesInFolder('/', sort: 'size')));
        self::assertSame(['/b.txt', '/a.txt'], array_values($subject->getFilesInFolder('/', sortRev: true, filenameFilterCallbacks: [$skipJpg])));
        self::assertSame(['/b.txt'], array_values($subject->getFilesInFolder('/', 1, 1)));
        self::assertSame(['/a.txt', '/b.txt', '/c.jpg', '/sub/d.txt'], array_values($subject->getFilesInFolder('/', recursive: true)));
        self::assertSame(['/sub/' => '/sub/'], $subject->getFoldersInFolder('/'));
        self::assertSame(3, $subject->countFilesInFolder('/', true, [$skipJpg]));
        self::assertSame(1, $subject->countFoldersInFolder('/'));
        self::assertFalse($subject->isFolderEmpty('/sub/'));
        self::assertTrue($subject->isFolderEmpty($subject->createFolder('empty')));
    }

    #[Test]
    public function renameFileThrowsIfTargetExists(): void
    {
        file_put_contents($this->baseDirectory . '/file.txt', '');
        file_put_contents($this->baseDirectory . '/other.txt', '');
        $this->expectException(ExistingTargetFileNameException::class);
        $this->getSubject()->renameFile('/file.txt', 'other.txt');
    }

    #[Test]
    public function filesCanBeRenamedCopiedAndMoved(): void
    {
        mkdir($this->baseDirectory . '/target');
        file_put_contents($this->baseDirectory . '/file.txt', 'content');
        $subject = $this->getSubject();
        self::assertSame('/renamed.txt', $subject->renameFile('/file.txt', 'renamed.txt'));
        self::assertSame('/target/copy.txt', $subject->copyFileWithinStorage('/renamed.txt', '/target/', 'copy.txt'));
        self::assertSame('/target/moved.txt', $subject->moveFileWithinStorage('/renamed.txt', '/target/', 'moved.txt'));
        self::assertFileDoesNotExist($this->baseDirectory . '/renamed.txt');
        self::assertStringEqualsFile($this->baseDirectory . '/target/copy.txt', 'content');
        self::assertStringEqualsFile($this->baseDirectory . '/target/moved.txt', 'content');
    }

    #[Test]
    public function foldersCanBeRenamedCopiedAndMovedWithTheirContent(): void
    {
        mkdir($this->baseDirectory . '/source/sub', 0777, true);
        mkdir($this->baseDirectory . '/target');
        file_put_contents($this->baseDirectory . '/source/sub/file.txt', 'content');
        $subject = $this->getSubject();

        self::assertTrue($subject->copyFolderWithinStorage('/source/', '/target/', 'copy'));
        self::assertStringEqualsFile($this->baseDirectory . '/target/copy/sub/file.txt', 'content');

        self::assertSame(
            ['/source/' => '/renamed/', '/source/sub/' => '/renamed/sub/', '/source/sub/file.txt' => '/renamed/sub/file.txt'],
            $subject->renameFolder('/source/', 'renamed')
        );
        self::assertDirectoryDoesNotExist($this->baseDirectory . '/source');

        self::assertSame(
            ['/renamed/' => '/target/moved/', '/renamed/sub/' => '/target/moved/sub/', '/renamed/sub/file.txt' => '/target/moved/sub/file.txt'],
            $subject->moveFolderWithinStorage('/renamed/', '/target/', 'moved')
        );
        self::assertDirectoryDoesNotExist($this->baseDirectory . '/renamed');
        self::assertStringEqualsFile($this->baseDirectory . '/target/moved/sub/file.txt', 'content');
    }

    #[Test]
    public function nonEmptyFolderIsOnlyDeletedRecursively(): void
    {
        mkdir($this->baseDirectory . '/folder');
        file_put_contents($this->baseDirectory . '/folder/file.txt', '');
        $subject = $this->getSubject();
        try {
            $subject->deleteFolder('/folder/');
            self::fail('Expected exception was not thrown');
        } catch (FileOperationErrorException $e) {
            self::assertSame(1792093590, $e->getCode());
        }
        self::assertTrue($subject->deleteFolder('/folder/', true));
        self::assertDirectoryDoesNotExist($this->baseDirectory . '/folder');
    }

    #[Test]
    public function localFilesCanBeAddedAndReplaced(): void
    {
        $localFile = Environment::getVarPath() . '/transient/upload.txt';
        file_put_contents($localFile, 'uploaded');
        $subject = $this->getSubject();
        self::assertSame('/new_name.txt', $subject->addFile($localFile, '/', 'new name.txt'));
        self::assertFileDoesNotExist($localFile);
        self::assertStringEqualsFile($this->baseDirectory . '/new_name.txt', 'uploaded');

        file_put_contents($localFile, 'replaced');
        self::assertTrue($subject->replaceFile('/new_name.txt', $localFile));
        self::assertFileDoesNotExist($localFile);
        self::assertStringEqualsFile($this->baseDirectory . '/new_name.txt', 'replaced');
    }

    #[Test]
    public function getFileForLocalProcessingReturnsLocalCopy(): void
    {
        file_put_contents($this->baseDirectory . '/file.txt', 'content');
        $subject = $this->getSubject();
        $writableCopy = $subject->getFileForLocalProcessing('/file.txt');
        self::assertStringEqualsFile($writableCopy, 'content');
        self::assertStringNotContainsString($this->baseDirectory, $writableCopy);

        $readOnlyCopy = $subject->getFileForLocalProcessing('/file.txt', false);
        self::assertSame($readOnlyCopy, $subject->getFileForLocalProcessing('/file.txt', false));
        unset($subject);
        self::assertFileDoesNotExist($readOnlyCopy);
        self::assertFileExists($writableCopy);
        unlink($writableCopy);
    }

    #[Test]
    public function streamFileReturnsResponseWithFileContents(): void
    {
        file_put_contents($this->baseDirectory . '/file.txt', 'content');
        $response = $this->getSubject()->streamFile('/file.txt', ['as_download' => true]);
        self::assertSame('content', (string)$response->getBody());
        self::assertSame('attachment; filename="file.txt"', $response->getHeaderLine('Content-Disposition'));
        self::assertSame('text/plain', $response->getHeaderLine('Content-Type'));
        self::assertSame('7', $response->getHeaderLine('Content-Length'));
    }

    #[Test]
    public function getPermissionsReflectsWritableCapability(): void
    {
        file_put_contents($this->baseDirectory . '/file.txt', '');
        $subject = $this->getSubject();
        self::assertSame(['r' => true, 'w' => true], $subject->getPermissions('/file.txt'));
        $subject->mergeConfigurationCapabilities(new Capabilities(Capabilities::CAPABILITY_BROWSABLE));
        self::assertSame(['r' => true, 'w' => false], $subject->getPermissions('/file.txt'));
    }

    #[Test]
    public function createFileThrowsAndKeepsContentIfFileExists(): void
    {
        file_put_contents($this->baseDirectory . '/file.txt', 'content');
        try {
            $this->getSubject()->createFile('file.txt', '/');
            self::fail('Expected exception was not thrown');
        } catch (ExistingTargetFileNameException $e) {
            self::assertSame(1792154301, $e->getCode());
        }
        self::assertStringEqualsFile($this->baseDirectory . '/file.txt', 'content');
    }

    #[Test]
    public function readOnlyLocalCopyIsRenewedWhenTheFileChanges(): void
    {
        file_put_contents($this->baseDirectory . '/file.txt', 'content');
        $localFile = Environment::getVarPath() . '/transient/upload.txt';
        $subject = $this->getSubject();

        $copy = $subject->getFileForLocalProcessing('/file.txt', false);
        $subject->setFileContents('/file.txt', 'changed');
        self::assertFileDoesNotExist($copy);
        self::assertStringEqualsFile($subject->getFileForLocalProcessing('/file.txt', false), 'changed');

        file_put_contents($localFile, 'replaced');
        $subject->replaceFile('/file.txt', $localFile);
        self::assertStringEqualsFile($subject->getFileForLocalProcessing('/file.txt', false), 'replaced');

        file_put_contents($this->baseDirectory . '/other.txt', 'moved');
        $subject->moveFileWithinStorage('/other.txt', '/', 'file.txt');
        self::assertStringEqualsFile($subject->getFileForLocalProcessing('/file.txt', false), 'moved');

        mkdir($this->baseDirectory . '/a');
        mkdir($this->baseDirectory . '/c');
        file_put_contents($this->baseDirectory . '/a/file.txt', 'a');
        file_put_contents($this->baseDirectory . '/c/file.txt', 'c');
        self::assertStringEqualsFile($subject->getFileForLocalProcessing('/a/file.txt', false), 'a');
        $subject->renameFolder('/a/', 'b');
        $subject->moveFolderWithinStorage('/c/', '/', 'a');
        self::assertStringEqualsFile($subject->getFileForLocalProcessing('/a/file.txt', false), 'c');
    }

    #[Test]
    public function fileInformationOfListedFilesIsTakenFromTheListing(): void
    {
        file_put_contents($this->baseDirectory . '/a.txt', 'a');
        file_put_contents($this->baseDirectory . '/b.txt', 'bb');
        $subject = $this->getSubject();
        $subject->getFilesInFolder('/');
        $subject->countingAdapter->calls = [];

        self::assertSame(2, $subject->getFileInfoByIdentifier('/b.txt')['size']);
        self::assertSame(['mimeType' => 1], $subject->countingAdapter->calls);
        $subject->countingAdapter->calls = [];
        $subject->getFileInfoByIdentifier('/a.txt', ['size', 'atime', 'mtime', 'ctime', 'name', 'identifier_hash']);
        self::assertSame([], $subject->countingAdapter->calls);
    }

    #[Test]
    public function fileInformationIsRequestedOncePerProperty(): void
    {
        file_put_contents($this->baseDirectory . '/a.txt', 'a');
        $subject = $this->getSubject();

        $subject->getFileInfoByIdentifier('/a.txt');
        self::assertSame(['fileExists' => 1, 'fileSize' => 1, 'lastModified' => 1, 'mimeType' => 1], $subject->countingAdapter->calls);
        $subject->countingAdapter->calls = [];
        $subject->getFileInfoByIdentifier('/a.txt');
        self::assertSame([], $subject->countingAdapter->calls);

        $subject->setFileContents('/a.txt', 'changed');
        self::assertSame(7, $subject->getFileInfoByIdentifier('/a.txt', ['size'])['size']);
    }

    #[Test]
    public function pathsWithInvisibleCharactersAreAccessible(): void
    {
        $fileName = "family-\u{1F468}\u{200D}\u{1F469}.txt";
        file_put_contents($this->baseDirectory . '/' . $fileName, 'content');
        $subject = $this->getSubject();
        self::assertTrue($subject->fileExists('/' . $fileName));
        self::assertSame('content', $subject->getFileContents('/' . $fileName));
        self::assertTrue($subject->deleteFile('/' . $fileName));
        self::assertFileDoesNotExist($this->baseDirectory . '/' . $fileName);
    }

    #[Test]
    public function permissionsOfListedEntriesAreNotRequestedFromTheAdapter(): void
    {
        mkdir($this->baseDirectory . '/folder');
        file_put_contents($this->baseDirectory . '/a.txt', 'a');
        $subject = $this->getSubject();
        $subject->getFilesInFolder('/');
        $subject->getFoldersInFolder('/');
        $subject->countingAdapter->calls = [];

        self::assertSame(['r' => true, 'w' => true], $subject->getPermissions('/a.txt'));
        self::assertSame(['r' => true, 'w' => true], $subject->getPermissions('/folder/'));
        self::assertSame([], $subject->countingAdapter->calls);
    }

    #[Test]
    public function permissionsOfUnknownEntriesAreCheckedOnce(): void
    {
        mkdir($this->baseDirectory . '/folder');
        file_put_contents($this->baseDirectory . '/a.txt', 'a');
        $subject = $this->getSubject();

        $subject->getPermissions('/a.txt');
        self::assertSame(['fileExists' => 1], $subject->countingAdapter->calls);
        $subject->countingAdapter->calls = [];
        $subject->getPermissions('/folder/');
        self::assertSame(['directoryExists' => 1], $subject->countingAdapter->calls);
        $subject->countingAdapter->calls = [];
        $subject->getPermissions('/a.txt');
        $subject->getPermissions('/folder/');
        self::assertSame([], $subject->countingAdapter->calls);
    }

    #[Test]
    public function getPermissionsThrowsForMissingEntry(): void
    {
        $this->expectException(ResourcePermissionsUnavailableException::class);
        $this->expectExceptionCode(1792093593);
        $this->getSubject()->getPermissions('/missing.txt');
    }

    #[Test]
    public function folderInformationOfListedFoldersIsTakenFromTheListing(): void
    {
        mkdir($this->baseDirectory . '/folder');
        touch($this->baseDirectory . '/folder', 1700000000);
        $subject = $this->getSubject();
        $subject->getFoldersInFolder('/');
        $subject->countingAdapter->calls = [];

        $folderInfo = $subject->getFolderInfoByIdentifier('/folder/');
        self::assertSame('/folder/', $folderInfo['identifier']);
        self::assertSame(1700000000, $folderInfo['mtime']);
        self::assertSame([], $subject->countingAdapter->calls);
    }

    #[Test]
    public function folderInformationOfUnknownFolderOnlyChecksItsExistence(): void
    {
        mkdir($this->baseDirectory . '/folder');
        $subject = $this->getSubject();

        self::assertSame('folder', $subject->getFolderInfoByIdentifier('/folder/')['name']);
        self::assertSame(['directoryExists' => 1], $subject->countingAdapter->calls);
    }

    #[Test]
    public function folderInformationIsDiscardedWhenTheFolderIsDeleted(): void
    {
        mkdir($this->baseDirectory . '/folder');
        $subject = $this->getSubject();
        $subject->getFoldersInFolder('/');
        $subject->deleteFolder('/folder/');

        $this->expectException(FolderDoesNotExistException::class);
        $this->expectExceptionCode(1792093596);
        $subject->getFolderInfoByIdentifier('/folder/');
    }

    #[Test]
    public function getFileContentsReturnsEmptyStringForMissingFile(): void
    {
        self::assertSame('', $this->getSubject()->getFileContents('/missing.txt'));
    }

    #[Test]
    public function getFileContentsReportsFailureOfExistingFile(): void
    {
        file_put_contents($this->baseDirectory . '/file.txt', 'content');
        $subject = $this->getSubject();
        $subject->countingAdapter->failReads = true;

        $this->expectException(UnableToReadFile::class);
        $subject->getFileContents('/file.txt');
    }

    #[Test]
    public function getFileForLocalProcessingThrowsForIncompleteCopy(): void
    {
        file_put_contents($this->baseDirectory . '/file.txt', 'content');
        $subject = $this->getSubject();
        $subject->countingAdapter->truncateReadStreamsTo = 3;
        $temporaryFilesBefore = glob(Environment::getVarPath() . '/transient/fal-tempfile-*');

        try {
            $subject->getFileForLocalProcessing('/file.txt', false);
            self::fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            self::assertSame(1794516508, $e->getCode());
        }
        self::assertSame($temporaryFilesBefore, glob(Environment::getVarPath() . '/transient/fal-tempfile-*'));

        $subject->countingAdapter->truncateReadStreamsTo = null;
        self::assertStringEqualsFile($subject->getFileForLocalProcessing('/file.txt', false), 'content');
    }

    #[Test]
    public function getFileForLocalProcessingAcceptsFileChangedSinceItWasListed(): void
    {
        file_put_contents($this->baseDirectory . '/file.txt', 'content');
        $subject = $this->getSubject();
        $subject->getFilesInFolder('/');
        file_put_contents($this->baseDirectory . '/file.txt', 'changed by another process');

        self::assertStringEqualsFile($subject->getFileForLocalProcessing('/file.txt', false), 'changed by another process');
        self::assertSame(26, $subject->getFileInfoByIdentifier('/file.txt', ['size'])['size']);
    }
}
