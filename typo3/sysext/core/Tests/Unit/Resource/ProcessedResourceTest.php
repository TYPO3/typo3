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

namespace TYPO3\CMS\Core\Tests\Unit\Resource;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\ProcessedResource;
use TYPO3\CMS\Core\Resource\ProcessedResourceCache;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
final class ProcessedResourceTest extends UnitTestCase
{
    private function getProcessedResource(?string $existingRenderPath = null, ?SystemResourceInterface $originalResource = null): ProcessedResource
    {
        return new ProcessedResource(
            self::createStub(ProcessedResourceCache::class),
            $originalResource ?? self::createStub(SystemResourceInterface::class),
            'Image.CropScaleMask',
            ['width' => 100],
            $existingRenderPath,
        );
    }

    #[Test]
    public function constructedWithoutAnExistingRenderPathUsesTheOriginalFile(): void
    {
        $subject = $this->getProcessedResource();

        self::assertTrue($subject->isNew());
        self::assertTrue($subject->usesOriginalFile());
        self::assertTrue($subject->exists());
    }

    #[Test]
    public function constructedWithAnExistingRenderPathDoesNotUseTheOriginalFile(): void
    {
        $renderPath = GeneralUtility::tempnam('processed-resource-test-') . '.png';
        file_put_contents($renderPath, 'rendered-bytes');

        $subject = $this->getProcessedResource($renderPath);

        self::assertFalse($subject->isNew());
        self::assertFalse($subject->usesOriginalFile());
        self::assertTrue($subject->exists());
        self::assertTrue($subject->isProcessed());
        self::assertSame('rendered-bytes', $subject->getContents());

        unlink($renderPath);
    }

    #[Test]
    public function needsReprocessingIsTrueWhenTheRenderFileWasRemovedFromDisk(): void
    {
        $renderPath = GeneralUtility::tempnam('processed-resource-test-') . '.png';
        file_put_contents($renderPath, 'rendered-bytes');
        unlink($renderPath);

        $subject = $this->getProcessedResource($renderPath);

        self::assertTrue($subject->needsReprocessing());
        self::assertFalse($subject->exists());
    }

    #[Test]
    public function settingUsesOriginalFileClearsTheRenderPathWithoutDeletingTheFile(): void
    {
        $renderPath = GeneralUtility::tempnam('processed-resource-test-') . '.png';
        file_put_contents($renderPath, 'rendered-bytes');

        $subject = $this->getProcessedResource($renderPath);
        $subject->setUsesOriginalFile();

        self::assertTrue($subject->usesOriginalFile());
        self::assertFileExists($renderPath);

        unlink($renderPath);
    }

    #[Test]
    public function deleteRemovesTheRenderFileFromDiskAndFallsBackToTheOriginal(): void
    {
        $renderPath = GeneralUtility::tempnam('processed-resource-test-') . '.png';
        file_put_contents($renderPath, 'rendered-bytes');

        $subject = $this->getProcessedResource($renderPath);
        $subject->delete();

        self::assertFileDoesNotExist($renderPath);
        self::assertTrue($subject->usesOriginalFile());
    }

    #[Test]
    public function getPropertyAndUpdatePropertiesRoundTrip(): void
    {
        $originalResource = self::createStub(SystemResourceInterface::class);
        $originalResource->method('getImageDimension')->willReturn(new ImageDimension(238, 100));
        $subject = $this->getProcessedResource(null, $originalResource);

        self::assertSame(238, $subject->getProperty('width'), 'the original used as-is reports its own width.');

        $subject->updateProperties(['width' => 100, 'height' => 50]);

        self::assertSame(100, $subject->getProperty('width'));
        self::assertSame(50, $subject->getProperty('height'));
    }

    #[Test]
    public function delegatesDescriptiveMethodsToTheOriginalResourceWhenUsingIt(): void
    {
        $originalResource = self::createStub(SystemResourceInterface::class);
        $originalResource->method('getName')->willReturn('foo.png');
        $originalResource->method('getNameWithoutExtension')->willReturn('foo');
        $originalResource->method('getExtension')->willReturn('png');
        $originalResource->method('getMimeType')->willReturn('image/png');
        $originalResource->method('getContents')->willReturn('original-bytes');
        $originalResource->method('getHash')->willReturn('original-hash');
        $originalResource->method('isImage')->willReturn(true);

        $subject = $this->getProcessedResource(null, $originalResource);

        self::assertSame('foo.png', $subject->getName());
        self::assertSame('foo', $subject->getNameWithoutExtension());
        self::assertSame('png', $subject->getExtension());
        self::assertSame('image/png', $subject->getMimeType());
        self::assertSame('original-bytes', $subject->getContents());
        self::assertSame('original-hash', $subject->getHash());
        self::assertTrue($subject->isImage());
    }

    #[Test]
    public function getSizeReturnsTheSizeOfTheRenderedFile(): void
    {
        $renderPath = GeneralUtility::tempnam('processed-resource-test-') . '.png';
        file_put_contents($renderPath, 'rendered-bytes');

        $subject = $this->getProcessedResource($renderPath);

        self::assertSame(14, $subject->getSize());
        self::assertSame(14, $subject->getProperty('size'));

        unlink($renderPath);
    }

    #[Test]
    public function getSizeReturnsTheSizeOfTheOriginalResourceWhenUsingIt(): void
    {
        $originalResource = self::createStub(SystemResourceInterface::class);
        $originalResource->method('getContents')->willReturn('original-bytes');

        $subject = $this->getProcessedResource(null, $originalResource);

        self::assertSame(14, $subject->getSize());
    }

    #[Test]
    public function getImageDimensionPrefersTheDimensionsStoredByTheProcessor(): void
    {
        $originalResource = self::createStub(SystemResourceInterface::class);
        $originalResource->method('getImageDimension')->willReturn(new ImageDimension(64, 64));

        $subject = $this->getProcessedResource(null, $originalResource);
        $subject->updateProperties(['width' => '32', 'height' => '16']);

        self::assertEquals(new ImageDimension(32, 16), $subject->getImageDimension());
    }

    #[Test]
    public function getImageDimensionFallsBackToTheOriginalResourceWithoutStoredDimensions(): void
    {
        $originalResource = self::createStub(SystemResourceInterface::class);
        $originalResource->method('getImageDimension')->willReturn(new ImageDimension(64, 64));

        $subject = $this->getProcessedResource(null, $originalResource);

        self::assertEquals(new ImageDimension(64, 64), $subject->getImageDimension());
    }

    #[Test]
    public function getResourceIdentifierCombinesTheOriginalsIdentifierAndTheTaskType(): void
    {
        $originalResource = self::createStub(SystemResourceInterface::class);
        $originalResource->method('getResourceIdentifier')->willReturn('PKG:my_ext:Resources/Public/foo.png');

        $subject = $this->getProcessedResource(null, $originalResource);

        self::assertSame('PKG:my_ext:Resources/Public/foo.png:Image.CropScaleMask', $subject->getResourceIdentifier());
        self::assertSame((string)$subject, $subject->getResourceIdentifier());
    }

    #[Test]
    public function updateWithLocalFileDelegatesPersistenceToTheCache(): void
    {
        $cache = $this->createMock(ProcessedResourceCache::class);
        $cache->expects($this->once())->method('persistRender')->willReturn('/final/path.png');

        $subject = new ProcessedResource(
            $cache,
            self::createStub(SystemResourceInterface::class),
            'Image.CropScaleMask',
            ['width' => 100],
        );
        $subject->updateWithLocalFile('/tmp/some-temporary-file.png');

        self::assertFalse($subject->isNew());
        self::assertFalse($subject->usesOriginalFile());
    }
}
