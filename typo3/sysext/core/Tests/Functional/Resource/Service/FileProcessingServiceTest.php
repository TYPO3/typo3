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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedResource;
use TYPO3\CMS\Core\Resource\ProcessedResourceCache;
use TYPO3\CMS\Core\Resource\Service\FileProcessingService;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\PackageResource;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Proves the FileProcessingService/ProcessedResourceCache/ImageCropScaleMaskTask chain
 * actually works end to end for a resource outside the File Abstraction Layer - not
 * just that it type-checks.
 */
final class FileProcessingServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_system_resources',
    ];

    #[Test]
    public function processingASystemResourceCropsItAndPersistsTheRenderInItsOwnCacheDirectory(): void
    {
        $resource = $this->get(SystemResourceFactory::class)
            ->createResource('PKG:typo3tests/test-system-resources:Resources/Public/Icons/Extension.svg');
        self::assertInstanceOf(PackageResource::class, $resource);

        // Offset must be non-zero: fromCropScaleValues() treats a crop area that exactly
        // covers "the whole incoming image" (which, given only "crop" and no separate
        // width/height, is defined as the crop area's own dimensions) as no crop at all -
        // this is existing, correct behaviour this test must respect, not trigger by accident.
        $configuration = ['crop' => new Area(10, 10, 32, 32)];
        $processedResource = $this->get(FileProcessingService::class)
            ->processFile($resource, ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, null, $configuration);

        self::assertInstanceOf(ProcessedResource::class, $processedResource);
        self::assertFalse($processedResource->usesOriginalFile(), 'a real crop must produce its own rendered variant, not fall back to the original.');
        self::assertTrue($processedResource->exists());
        self::assertSame(32, $processedResource->getImageDimension()->getWidth());
        self::assertSame(32, $processedResource->getImageDimension()->getHeight());
        self::assertNotNull($processedResource->getPublicUrl());
        self::assertStringStartsWith('/typo3temp/assets/images/system/', (string)$processedResource->getPublicUrl());

        // SvgImageProcessor wraps the untouched original in an outer crop container that
        // carries the viewBox crop and target dimensions - real proof of cropping is that
        // outer wrapper, not a rewrite of the (deliberately unchanged) original content.
        self::assertStringContainsString('viewBox="10 10 32 32"', $processedResource->getContents());
    }

    #[Test]
    public function publicUrlOfAProcessedSystemResourceIsPrefixedWithTheSitePath(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/sub/', 'GET')
            ->withAttribute('normalizedParams', new NormalizedParams(
                ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/sub/index.php'],
                [],
                Environment::getPublicPath() . '/index.php',
                Environment::getPublicPath(),
            ));
        $resource = $this->get(SystemResourceFactory::class)
            ->createResource('PKG:typo3tests/test-system-resources:Resources/Public/Icons/Extension.svg');
        assert($resource instanceof PackageResource);

        $processedResource = $this->get(FileProcessingService::class)
            ->processFile($resource, ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, null, ['crop' => new Area(10, 10, 32, 32)]);

        self::assertFalse($processedResource->usesOriginalFile());
        self::assertStringStartsWith('/sub/typo3temp/assets/images/system/', (string)$processedResource->getPublicUrl());
    }

    #[Test]
    public function processingTheSameSystemResourceAgainReusesTheCachedRenderInstead(): void
    {
        $resource = $this->get(SystemResourceFactory::class)
            ->createResource('PKG:typo3tests/test-system-resources:Resources/Public/Icons/Extension.svg');
        assert($resource instanceof PackageResource);
        $configuration = ['crop' => new Area(10, 10, 32, 32)];
        $fileProcessingService = $this->get(FileProcessingService::class);

        $first = $fileProcessingService->processFile($resource, ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, null, $configuration);
        $second = $fileProcessingService->processFile($resource, ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, null, $configuration);

        self::assertInstanceOf(ProcessedResource::class, $first);
        self::assertInstanceOf(ProcessedResource::class, $second);
        self::assertSame($first->getPublicUrl(), $second->getPublicUrl());
        self::assertSame($first->getImageDimension()->getWidth(), $second->getImageDimension()->getWidth());
    }

    #[Test]
    public function processingASystemResourceWithoutACropUsesTheOriginalUnchanged(): void
    {
        $resource = $this->get(SystemResourceFactory::class)
            ->createResource('PKG:typo3tests/test-system-resources:Resources/Public/Icons/Extension.svg');
        assert($resource instanceof PackageResource);

        $processedResource = $this->get(FileProcessingService::class)
            ->processFile($resource, ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, null, []);

        self::assertInstanceOf(ProcessedResource::class, $processedResource);
        self::assertTrue($processedResource->usesOriginalFile());
        self::assertSame($resource->getContents(), $processedResource->getContents());
    }

    #[Test]
    public function systemResourceUsedAsIsReportsTheDimensionsOfTheOriginal(): void
    {
        $resource = $this->get(SystemResourceFactory::class)
            ->createResource('PKG:typo3tests/test-system-resources:Resources/Public/Images/typo3-logo.png');
        assert($resource instanceof PackageResource);

        $processedResource = $this->get(FileProcessingService::class)
            ->processFile($resource, ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, null, []);

        self::assertTrue($processedResource->usesOriginalFile());
        self::assertSame(238, $processedResource->getProperty('width'));
        self::assertSame(100, $processedResource->getProperty('height'));
    }

    public static function processingResultIsCachedDataProvider(): iterable
    {
        yield 'original used as-is' => [
            'Resources/Public/Images/typo3-logo.png',
            [],
            true,
            238,
            100,
        ];
        yield 'SVG reused at another size' => [
            'Resources/Public/Icons/Extension.svg',
            ['width' => 32],
            true,
            32,
            32,
        ];
        yield 'rendered variant' => [
            'Resources/Public/Images/typo3-logo.png',
            ['width' => 119],
            false,
            119,
            50,
        ];
    }

    #[DataProvider('processingResultIsCachedDataProvider')]
    #[Test]
    public function processingResultIsCached(string $path, array $configuration, bool $usesOriginalFile, int $expectedWidth, int $expectedHeight): void
    {
        $resource = $this->get(SystemResourceFactory::class)
            ->createResource('PKG:typo3tests/test-system-resources:' . $path);
        assert($resource instanceof PackageResource);
        $this->get(FileProcessingService::class)
            ->processFile($resource, ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, null, $configuration);

        $cached = $this->get(ProcessedResourceCache::class)
            ->findOneByOriginalAndTaskTypeAndConfiguration($resource, ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $configuration);

        self::assertFalse($cached->isNew());
        self::assertTrue($cached->isProcessed());
        self::assertSame($usesOriginalFile, $cached->usesOriginalFile());
        self::assertSame($expectedWidth, (int)$cached->getProperty('width'));
        self::assertSame($expectedHeight, (int)$cached->getProperty('height'));
    }
}
