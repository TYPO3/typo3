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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ResourceFactoryTest extends FunctionalTestCase
{
    private ResourceFactory&MockObject&AccessibleObjectInterface $subject;

    private array $filesCreated = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(ResourceFactory::class, null, [], '', false);
    }

    protected function tearDown(): void
    {
        foreach ($this->filesCreated as $file) {
            unlink($file);
        }
        parent::tearDown();
    }

    /***********************************
     *  File Handling
     ***********************************/
    #[Test]
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithRelativePath(): void
    {
        $subject = $this->getAccessibleMock(
            ResourceFactory::class,
            ['getFolderObjectFromCombinedIdentifier'],
            [],
            '',
            false
        );
        $subject
            ->expects($this->once())
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('typo3temp');
        $subject->retrieveFileOrFolderObject('typo3temp');
    }

    #[Test]
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithAbsolutePath(): void
    {
        $subject = $this->getAccessibleMock(
            ResourceFactory::class,
            ['getFolderObjectFromCombinedIdentifier'],
            [],
            '',
            false
        );
        $subject
            ->expects($this->once())
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('typo3temp');
        $subject->retrieveFileOrFolderObject(Environment::getPublicPath() . '/typo3temp');
    }

    #[Test]
    public function retrieveFileOrFolderObjectReturnsFileIfPathIsGiven(): void
    {
        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObjectFromCombinedIdentifier'], [], '', false);
        $filename = 'typo3temp/var/tests/4711.txt';
        $this->subject->expects($this->once())
            ->method('getFileObjectFromCombinedIdentifier')
            ->with($filename);
        // Create and prepare test file
        GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $filename, '42');
        $this->filesCreated[] = Environment::getPublicPath() . '/' . $filename;
        $this->subject->retrieveFileOrFolderObject($filename);
    }

    #[Test]
    public function retrieveFileOrFolderObjectReturnsFileFromPublicFolderWhenProjectRootIsNotPublic(): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            true,
            Environment::getProjectPath(),
            Environment::getPublicPath() . '/typo3temp/public',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );

        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp');

        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObjectFromCombinedIdentifier'], [], '', false);
        $filename = 'typo3temp/var/tests/4711.txt';
        $this->subject->expects($this->once())
            ->method('getFileObjectFromCombinedIdentifier')
            ->with($filename);
        // Create and prepare test file
        GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $filename, '42');
        $this->filesCreated[] = Environment::getPublicPath() . '/' . $filename;
        $this->subject->retrieveFileOrFolderObject($filename);
    }

    #[Test]
    public function retrieveFileOrFolderObjectReturnsFileFromPublicExtensionResourceWhenExtensionIsNotPublic(): void
    {
        // Emulate public dir and private app dir
        Environment::initialize(
            Environment::getContext(),
            true,
            true,
            Environment::getProjectPath(),
            Environment::getPublicPath() . '/typo3temp/public',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );

        // Emulate publishing
        // The published asset directory is named after the package path relative to the
        // project root, which differs between installation modes. Mirrors DefaultPublicPrefix.
        $corePackagePath = $this->get(PackageManager::class)->getPackage('core')->getPackagePath();
        $publicFile = '/_assets/' . md5(substr($corePackagePath, strlen(Environment::getProjectPath()))) . '/Icons/Extension.svg';
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . dirname($publicFile));
        copy(GeneralUtility::getFileAbsFileName('EXT:core/Resources/Public/Icons/Extension.svg'), Environment::getPublicPath() . $publicFile);

        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObjectFromCombinedIdentifier'], [], '', false);
        $this->subject->expects($this->once())
            ->method('getFileObjectFromCombinedIdentifier')
            ->with($publicFile);
        // Create and prepare test file
        $this->subject->retrieveFileOrFolderObject('EXT:core/Resources/Public/Icons/Extension.svg');
    }

    #[Test]
    public function retrieveFileOrFolderObjectThrowsExceptionFromPrivateExtensionResourceWhenExtensionIsNotPublic(): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            true,
            Environment::getProjectPath(),
            Environment::getPublicPath() . '/typo3temp/public',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObjectFromCombinedIdentifier'], [], '', false);
        $this->expectException(ResourceDoesNotExistException::class);
        $this->subject->retrieveFileOrFolderObject('EXT:core/Resources/Private/Templates/PageRenderer.html');
    }

    #[Test]
    public function resolveFileObjectPassesAnAlreadyResolvedFileThrough(): void
    {
        $file = $this->createMock(File::class);
        $subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObject'], [], '', false);
        $subject->expects($this->never())->method('getFileObject');
        self::assertSame($file, $subject->resolveFileObject($file));
    }

    #[Test]
    public function resolveFileObjectUnwrapsADomainObjectViaGetOriginalResource(): void
    {
        $file = $this->createMock(File::class);
        $domainObject = new class ($file) {
            public function __construct(private readonly File $file) {}
            public function getOriginalResource(): File
            {
                return $this->file;
            }
        };
        $subject = $this->getAccessibleMock(ResourceFactory::class, null, [], '', false);
        self::assertSame($file, $subject->resolveFileObject($domainObject));
    }

    #[Test]
    public function resolveFileObjectThrowsForADomainObjectWithoutAValidResource(): void
    {
        $domainObject = new class {
            public function getOriginalResource(): \stdClass
            {
                return new \stdClass();
            }
        };
        $subject = $this->getAccessibleMock(ResourceFactory::class, null, [], '', false);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1625838481);
        $subject->resolveFileObject($domainObject);
    }

    #[Test]
    public function resolveFileObjectThrowsForAnUnsupportedObject(): void
    {
        $subject = $this->getAccessibleMock(ResourceFactory::class, null, [], '', false);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1625585157);
        $subject->resolveFileObject(new \stdClass());
    }

    #[Test]
    public function resolveFileObjectResolvesAnIntegerToAFile(): void
    {
        $file = $this->createMock(File::class);
        $subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObject', 'getFileReferenceObject'], [], '', false);
        $subject->expects($this->once())->method('getFileObject')->with('42')->willReturn($file);
        $subject->expects($this->never())->method('getFileReferenceObject');
        self::assertSame($file, $subject->resolveFileObject('42'));
    }

    #[Test]
    public function resolveFileObjectResolvesAnIntegerToAFileReferenceIfRequested(): void
    {
        $fileReference = $this->createMock(FileReference::class);
        $subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObject', 'getFileReferenceObject'], [], '', false);
        $subject->expects($this->once())->method('getFileReferenceObject')->with('42')->willReturn($fileReference);
        $subject->expects($this->never())->method('getFileObject');
        self::assertSame($fileReference, $subject->resolveFileObject('42', true));
    }

    #[Test]
    public function resolveFileObjectResolvesACombinedIdentifier(): void
    {
        $file = $this->createMock(File::class);
        $subject = $this->getAccessibleMock(ResourceFactory::class, ['getObjectFromCombinedIdentifier'], [], '', false);
        $subject->expects($this->once())
            ->method('getObjectFromCombinedIdentifier')
            ->with('1:/foo.jpg')
            ->willReturn($file);
        self::assertSame($file, $subject->resolveFileObject('1:/foo.jpg'));
    }

    #[Test]
    public function resolveFileObjectThrowsForAFolder(): void
    {
        $subject = $this->getAccessibleMock(ResourceFactory::class, ['getObjectFromCombinedIdentifier'], [], '', false);
        $subject->expects($this->once())
            ->method('getObjectFromCombinedIdentifier')
            ->with('1:/aFolder/')
            ->willReturn($this->createMock(Folder::class));
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1625585158);
        $subject->resolveFileObject('1:/aFolder/');
    }

    #[Test]
    public function resolveFileObjectDelegatesAT3FileUrnToTheLinkService(): void
    {
        $file = $this->createMock(File::class);
        $linkService = $this->createMock(LinkService::class);
        $linkService->expects($this->once())
            ->method('resolveByStringRepresentation')
            ->with('t3://file?uid=42')
            ->willReturn(['type' => 'file', 'file' => $file]);
        $storageRepository = $this->createMock(StorageRepository::class);
        $storageRepository->expects($this->never())->method('getStorageObject');
        $subject = new ResourceFactory(
            $storageRepository,
            self::createStub(FrontendInterface::class),
            self::createStub(FileIndexRepository::class),
            $linkService
        );
        self::assertSame($file, $subject->resolveFileObject('t3://file?uid=42'));
    }

    #[Test]
    public function resolveFileObjectThrowsForAResolvedProcessedFile(): void
    {
        $subject = $this->getAccessibleMock(ResourceFactory::class, ['getObjectFromCombinedIdentifier'], [], '', false);
        $subject->method('getObjectFromCombinedIdentifier')->willReturn($this->createMock(ProcessedFile::class));
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1382687163);
        $subject->resolveFileObject('1:/processed.jpg');
    }
}
