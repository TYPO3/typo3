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

namespace TYPO3\CMS\Core\Tests\Functional\SystemResource\Publishing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotGenerateUriException;
use TYPO3\CMS\Core\SystemResource\Publishing\DefaultSystemResourcePublisher;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\PackageResource;
use TYPO3\CMS\Core\SystemResource\Type\PublicPackageFile;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\DummyFileCreationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DefaultResourcePublisherTest extends FunctionalTestCase
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

    public static function generatesUriForAllKindsOfResourcesDataProvider(): \Generator
    {
        $iconMtime = filemtime(__DIR__ . '/../../Fixtures/Extensions/test_system_resources/Resources/Private/Icons/Extension.svg');
        yield 'public resource string' => [
            'resourceString' => 'PKG:typo3tests/test-system-resources:Resources/Public/Icons/Extension.svg',
            'url' => '/typo3conf/ext/test_system_resources/Resources/Public/Icons/Extension.svg?' . $iconMtime,
        ];
        yield 'public resource string with query' => [
            'resourceString' => 'PKG:typo3tests/test-system-resources:Resources/Public/Icons/Extension.svg?v=42',
            'url' => '/typo3conf/ext/test_system_resources/Resources/Public/Icons/Extension.svg?v=42&' . $iconMtime,
            'endsWith' => '/typo3conf/ext/test_system_resources/Resources/Public/Icons/Extension.svg?v=42&' . $iconMtime,
        ];
        yield 'public resource string with section' => [
            'resourceString' => 'PKG:typo3tests/test-system-resources:Resources/Public/Icons/Extension.svg#foo-bar',
            'url' => '/typo3conf/ext/test_system_resources/Resources/Public/Icons/Extension.svg?' . $iconMtime . '#foo-bar',
            'endsWith' => '/typo3conf/ext/test_system_resources/Resources/Public/Icons/Extension.svg?' . $iconMtime . '#foo-bar',
        ];
        yield 'public ext path' => [
            'resourceString' => 'EXT:test_system_resources/Resources/Public/Icons/Extension.svg',
            'url' => '/typo3conf/ext/test_system_resources/Resources/Public/Icons/Extension.svg?' . $iconMtime,
        ];
        yield 'public ext path with query' => [
            'resourceString' => 'EXT:test_system_resources/Resources/Public/Icons/Extension.svg?v=42',
            'url' => '/typo3conf/ext/test_system_resources/Resources/Public/Icons/Extension.svg?v=42&' . $iconMtime,
            'endsWith' => '/typo3conf/ext/test_system_resources/Resources/Public/Icons/Extension.svg?v=42&' . $iconMtime,
        ];
        yield 'public ext path of not existing file' => [
            'resourceString' => 'EXT:test_system_resources/Resources/Public/Icons/NotHere.svg',
            'url' => '/typo3conf/ext/test_system_resources/Resources/Public/Icons/NotHere.svg',
            'endsWith' => '/typo3conf/ext/test_system_resources/Resources/Public/Icons/NotHere.svg',
        ];
        yield 'absolute http url' => [
            'resourceString' => 'http://host.tld/Resources/Private/Icons/Extension.svg',
            'url' => 'http://host.tld/Resources/Private/Icons/Extension.svg',
            'endsWith' => 'http://host.tld/Resources/Private/Icons/Extension.svg',
        ];
        yield 'absolute https url' => [
            'resourceString' => 'https://host.tld/Resources/Private/Icons/Extension.svg',
            'url' => 'https://host.tld/Resources/Private/Icons/Extension.svg',
            'endsWith' => 'https://host.tld/Resources/Private/Icons/Extension.svg',
        ];
        yield 'project path with uploads' => [
            'resourceString' => 'PKG:typo3/app:uploads/relative/path/to/some/icon.svg',
            'url' => '/uploads/relative/path/to/some/icon.svg?',
        ];
        yield 'project path with uploads but non existing file' => [
            'resourceString' => 'PKG:typo3/app:uploads/not/here/icon.svg',
            'url' => '/uploads/not/here/icon.svg',
            'endsWith' => '/uploads/not/here/icon.svg',
        ];
        yield 'public temporary asset path via typo3/app package reference' => [
            'resourceString' => 'PKG:typo3/app:typo3temp/assets/Extension.svg',
            'url' => '/typo3temp/assets/Extension.svg?',
        ];
        yield 'combined FAL identifier' => [
            'resourceString' => 'FAL:1:/Extension.svg',
            'url' => '/fileadmin/Extension.svg?da39a3ee5e6b4b0d3255bfef95601890afd80709',
        ];
        yield 'legacy: FAL resolving' => [
            'resourceString' => 'fileadmin/Extension.svg',
            'url' => '/fileadmin/Extension.svg?da39a3ee5e6b4b0d3255bfef95601890afd80709',
        ];
        yield 'legacy: FAL resolving leading slash' => [
            'resourceString' => '/fileadmin/Extension.svg',
            'url' => '/fileadmin/Extension.svg?da39a3ee5e6b4b0d3255bfef95601890afd80709',
        ];
        yield 'legacy: public asset folder' => [
            'resourceString' => '_assets/vite/asset.svg',
            'url' => '/_assets/vite/asset.svg?',
        ];
        yield 'legacy: public asset folder leading slash' => [
            'resourceString' => '/_assets/vite/asset.svg',
            'url' => '/_assets/vite/asset.svg?',
        ];
        yield 'legacy: uploads folder' => [
            'resourceString' => 'uploads/relative/path/to/some/icon.svg',
            'url' => '/uploads/relative/path/to/some/icon.svg?',
        ];
        yield 'legacy: uploads folder leading slash' => [
            'resourceString' => '/uploads/relative/path/to/some/icon.svg',
            'url' => '/uploads/relative/path/to/some/icon.svg?',
        ];
    }

    #[Test]
    #[DataProvider('generatesUriForAllKindsOfResourcesDataProvider')]
    public function generatesUriForAllKindsOfResources(string $resourceString, string $url, ?string $endsWith = null): void
    {
        $this->file->ensureFilesExistInPublicFolder('/_assets/vite/asset.svg');
        $this->file->ensureFilesExistInPublicFolder('/typo3temp/assets/Extension.svg');
        $this->file->ensureFilesExistInPublicFolder('/uploads/relative/path/to/some/icon.svg');
        $this->file->ensureFilesExistInStorage('/Extension.svg');
        $resourceFactory = $this->get(SystemResourceFactory::class);
        $resourcePublisher = $this->get(DefaultSystemResourcePublisher::class);
        $resource = $resourceFactory->createPublicResource($resourceString);
        self::assertStringStartsWith($url, (string)$resourcePublisher->generateUri($resource, null));
        if ($endsWith !== null) {
            self::assertStringEndsWith($endsWith, (string)$resourcePublisher->generateUri($resource, null));
        }
    }

    #[Test]
    public function unpublishedPublicFileThrowsExceptionWhenBuildingUri(): void
    {
        $this->expectException(CanNotGenerateUriException::class);
        $resourceFactory = $this->get(SystemResourceFactory::class);
        $resourcePublisher = $this->get(DefaultSystemResourcePublisher::class);
        $privateResource = $resourceFactory->createResource('PKG:typo3/cms-core:Resources/Private/Font/nimbus.ttf');
        self::assertInstanceOf(PackageResource::class, $privateResource);
        $resourcePublisher->generateUri(PublicPackageFile::fromPackageResource($privateResource), null);
    }

    #[Test]
    public function falFileFromPrivateStorageThrowsExceptionWhenBuildingUri(): void
    {
        $this->expectException(CanNotGenerateUriException::class);
        $resourcePublisher = $this->get(DefaultSystemResourcePublisher::class);
        $resourcePublisher->generateUri($this->getPrivateFileMock(), null);
    }

    /**
     * @todo it would be nicer for a functional test to create a real private storage
     *       and retrieve a real file from it, instead of mocking everything
     */
    private function getPrivateFileMock(): File
    {
        $class = new \ReflectionClass(File::class);
        $allMethods = array_map(static fn($method) => $method->name, $class->getMethods());
        $methodsToMock = array_diff($allMethods, ['isPublished']);
        $storage = $this->createMock(ResourceStorage::class);
        $storage->method('isPublic')
            ->willReturn(false);
        return $this->getMockBuilder(File::class)
            ->setConstructorArgs([
                [],
                $storage,
            ])
            ->onlyMethods($methodsToMock)
            ->getMock();
    }
}
