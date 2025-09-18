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

namespace TYPO3\CMS\Core\Tests\Functional\SystemResource;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotResolvePublicResourceException;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotResolveSystemResourceException;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\UriResource;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\DummyFileCreationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SystemResourceFactoryTest extends FunctionalTestCase
{
    private DummyFileCreationService $file;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_system_resources',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = new DummyFileCreationService($this->get(StorageRepository::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->file->cleanupCreatedFiles();
    }

    public static function createResourceResolvesAllKindOfFilesDataProvider(): \Generator
    {
        yield 'public resource string' => [
            'resourceString' => 'PKG:typo3tests/test-system-resources:Resources/Public/Icons/Extension.svg',
            'expectedClass' => PublicResourceInterface::class,
        ];
        yield 'public ext path' => [
            'resourceString' => 'EXT:test_system_resources/Resources/Public/Icons/Extension.svg',
            'expectedClass' => PublicResourceInterface::class,
        ];
        yield 'public ext path of not existing file' => [
            'resourceString' => 'EXT:test_system_resources/Resources/Public/Icons/NotHere.svg',
            'expectedClass' => PublicResourceInterface::class,
        ];
        yield 'private resource string' => [
            'resourceString' => 'PKG:typo3tests/test-system-resources:Resources/Private/Icons/Extension.svg',
            'expectedClass' => SystemResourceInterface::class,
            'notExpectedClass' => PublicResourceInterface::class,
        ];
        yield 'private ext path' => [
            'resourceString' => 'EXT:test_system_resources/Resources/Private/Icons/Extension.svg',
            'expectedClass' => SystemResourceInterface::class,
            'notExpectedClass' => PublicResourceInterface::class,
        ];
        yield 'private ext path of not existing file' => [
            'resourceString' => 'EXT:test_system_resources/Resources/Private/Icons/NotHere.svg',
            'expectedClass' => SystemResourceInterface::class,
            'notExpectedClass' => PublicResourceInterface::class,
        ];
        yield 'absolute http url' => [
            'resourceString' => 'http://host.tld/Resources/Private/Icons/Extension.svg',
            'expectedClass' => UriResource::class,
            'notExpectedClass' => SystemResourceInterface::class,
        ];
        yield 'absolute https url' => [
            'resourceString' => 'https://host.tld/Resources/Private/Icons/Extension.svg',
            'expectedClass' => UriResource::class,
            'notExpectedClass' => SystemResourceInterface::class,
        ];
        yield 'public asset path via typo3/app package reference' => [
            'resourceString' => 'PKG:typo3/app:_assets/vite/asset.svg',
            'expectedClass' => PublicResourceInterface::class,
        ];
        yield 'public temporary asset path via typo3/app package reference' => [
            'resourceString' => 'PKG:typo3/app:typo3temp/assets/Extension.svg',
            'expectedClass' => PublicResourceInterface::class,
        ];
        yield 'public uploads path via typo3/app package reference' => [
            'resourceString' => 'PKG:typo3/app:uploads/assets/Extension.svg',
            'expectedClass' => PublicResourceInterface::class,
        ];
        // For legacy relative path resolving to work, the files must exist
        yield 'legacy resolving: public asset path' => [
            'resourceString' => '_assets/vite/asset.svg',
            'expectedClass' => PublicResourceInterface::class,
            'createPublicFiles' => true,
        ];
        yield 'legacy resolving: public asset absolute path' => [
            'resourceString' => '/_assets/vite/asset.svg',
            'expectedClass' => PublicResourceInterface::class,
            'createPublicFiles' => true,
        ];
        yield 'legacy resolving: public temporary asset path' => [
            'resourceString' => 'typo3temp/assets/Extension.svg',
            'expectedClass' => PublicResourceInterface::class,
            'createPublicFiles' => true,
        ];
        yield 'legacy resolving: public temporary asset absolute path' => [
            'resourceString' => '/typo3temp/assets/Extension.svg',
            'expectedClass' => PublicResourceInterface::class,
            'createPublicFiles' => true,
        ];
        yield 'legacy resolving: public uploads path' => [
            'resourceString' => 'uploads/assets/Extension.svg',
            'expectedClass' => PublicResourceInterface::class,
            'createPublicFiles' => true,
        ];
        yield 'legacy resolving: public uploads absolute path' => [
            'resourceString' => '/uploads/assets/Extension.svg',
            'expectedClass' => PublicResourceInterface::class,
            'createPublicFiles' => true,
        ];
    }

    #[Test]
    #[DataProvider('createResourceResolvesAllKindOfFilesDataProvider')]
    public function createResourceResolvesAllKindOfFiles(string $resourceString, string $expectedClass, ?string $notExpectedClass = null, ?bool $createPublicFiles = null): void
    {
        if ($createPublicFiles === true) {
            $this->file->ensureFilesExistInPublicFolder('/typo3temp/assets/Extension.svg');
            $this->file->ensureFilesExistInPublicFolder('/uploads/assets/Extension.svg');
            $this->file->ensureFilesExistInPublicFolder('/_assets/vite/asset.svg');
        }
        $resourceFactory = $this->get(SystemResourceFactory::class);
        $resource = $resourceFactory->createResource($resourceString);
        self::assertInstanceOf($expectedClass, $resource);
        if ($notExpectedClass !== null) {
            self::assertNotInstanceOf($notExpectedClass, $resource);
        }
    }

    public static function createResourceThrowsForInvalidResourceStringsDataProvider(): \Generator
    {
        yield 'not in asset, _assets nor uploads folder' => [
            'PKG:typo3/app:typo3temp/foo/Extension.svg',
        ];
        yield 'legacy resolving not in asset, _assets nor uploads folder, but file exists' => [
            'typo3temp/foo/Extension.svg',
        ];
        yield 'not existing combined identifier' => [
            'FAL:1:/foo/bar/Extension.svg',
        ];
        yield 'not existing uid' => [
            'FAL:2343',
        ];
        yield 'folder' => [
            'FAL:1:/',
        ];
        yield 'malformed resource string (leading slash)' => [
            'PKG:typo3tests/test-system-resources:/Resources/Private/Icons/Extension.svg',
        ];
        yield 'malformed resource string (too many colons)' => [
            'PKG:typo3tests/test-system-resources::Resources/Private/Icons/Extension.svg',
        ];
        yield 'not existing extension in ext path' => [
            'EXT:not_here/Resources/Private/Icons/Extension.svg',
        ];
        yield 'not existing extension in resource uri' => [
            'PKG:not_here:Resources/Private/Icons/Extension.svg',
        ];
        // For legacy relative path resolving to work, the files must exist
        yield 'legacy resolving: public asset path' => [
            '_assets/vite/asset.svg',
        ];
        yield 'legacy resolving: public temporary asset path' => [
            'typo3temp/assets/Extension.svg',
        ];
        yield 'legacy resolving: public uploads path' => [
            'uploads/assets/Extension.svg',
        ];
    }

    #[Test]
    #[DataProvider('createResourceThrowsForInvalidResourceStringsDataProvider')]
    public function createResourceThrowsForInvalidResourceStrings(string $resourceString): void
    {
        $this->file->ensureFilesExistInPublicFolder('/typo3temp/foo/Extension.svg');
        $this->expectException(CanNotResolveSystemResourceException::class);
        $resourceFactory = $this->get(SystemResourceFactory::class);
        $resourceFactory->createResource($resourceString);
    }

    #[Test]
    public function createResourceThrowsForAbsolutePaths(): void
    {
        $resourceString = $this->file->ensureFilesExistInPublicFolder('/typo3temp/assets/Extension.svg');
        $this->expectException(CanNotResolveSystemResourceException::class);
        $resourceFactory = $this->get(SystemResourceFactory::class);
        $resourceFactory->createResource($resourceString);
    }

    #[Test]
    public function createResourceCreatesResourceForAllowedFolder(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths'] = 'typo3temp/foo,typo3temp/bar/';
        $resourceFactory = $this->get(SystemResourceFactory::class);
        $resource = $resourceFactory->createResource('PKG:typo3/app:typo3temp/foo/Extension.svg');
        self::assertInstanceOf(PublicResourceInterface::class, $resource);
        $resource = $resourceFactory->createResource('PKG:typo3/app:typo3temp/foobar/Extension.svg');
        self::assertInstanceOf(PublicResourceInterface::class, $resource);
        $resource = $resourceFactory->createResource('PKG:typo3/app:typo3temp/bar/Extension.svg');
        self::assertInstanceOf(PublicResourceInterface::class, $resource);
    }

    public static function createPublicResourceThrowsWhenResolvingPrivateResourcesDataProvider(): \Generator
    {
        yield 'private resource string' => [
            'PKG:typo3tests/test-system-resources:Resources/Private/Icons/Extension.svg',
        ];
        yield 'private ext path' => [
            'EXT:test_system_resources/Resources/Private/Icons/Extension.svg',
        ];
        yield 'private ext path of not existing file' => [
            'EXT:test_system_resources/Resources/Private/Icons/NotHere.svg',
        ];
    }

    #[Test]
    #[DataProvider('createPublicResourceThrowsWhenResolvingPrivateResourcesDataProvider')]
    public function createPublicResourceThrowsWhenResolvingPrivateResources(string $resourceString): void
    {
        $this->expectException(CanNotResolvePublicResourceException::class);
        $resourceFactory = $this->get(SystemResourceFactory::class);
        $resourceFactory->createPublicResource($resourceString);
    }

    public static function createResourceResolvesFalFilesDataProvider(): \Generator
    {
        yield 'combined identifier' => [
            'FAL:1:/Extension.svg',
        ];
        yield 'legacy resolving' => [
            'fileadmin/Extension.svg',
        ];
    }

    #[Test]
    #[DataProvider('createResourceResolvesFalFilesDataProvider')]
    public function createResourceResolvesFalFiles(string $resourceString): void
    {
        $this->file->ensureFilesExistInStorage('/Extension.svg');
        $resourceFactory = $this->get(SystemResourceFactory::class);
        $resource = $resourceFactory->createResource($resourceString);
        self::assertInstanceOf(PublicResourceInterface::class, $resource);
        self::assertInstanceOf(SystemResourceInterface::class, $resource);
        self::assertInstanceOf(File::class, $resource);
    }
}
