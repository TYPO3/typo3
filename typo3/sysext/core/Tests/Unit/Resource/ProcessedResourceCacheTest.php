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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ProcessedResourceCache;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProcessedResourceCacheTest extends UnitTestCase
{
    private function getResourceStub(string $identifier, string $hash): SystemResourceInterface
    {
        $stub = self::createStub(SystemResourceInterface::class);
        $stub->method('getResourceIdentifier')->willReturn($identifier);
        $stub->method('getHash')->willReturn($hash);
        return $stub;
    }

    private function getCacheDirectoryFor(SystemResourceInterface $resource): string
    {
        return Environment::getPublicPath() . '/typo3temp/assets/images/system/' . md5($resource->getResourceIdentifier()) . '/';
    }

    #[Test]
    public function findOneByOriginalAndTaskTypeAndConfigurationReturnsANewResourceWhenNothingIsCached(): void
    {
        $subject = new ProcessedResourceCache();
        $originalResource = $this->getResourceStub('PKG:my_ext:Resources/Public/uncached.png', 'abc123');

        $processedResource = $subject->findOneByOriginalAndTaskTypeAndConfiguration($originalResource, 'Image.CropScaleMask', ['width' => 100]);

        self::assertTrue($processedResource->isNew());
        self::assertTrue($processedResource->usesOriginalFile());
    }

    #[Test]
    public function persistRenderMovesTheTemporaryFileIntoTheCacheDirectoryAndCanBeFoundAgain(): void
    {
        $subject = new ProcessedResourceCache();
        $originalResource = $this->getResourceStub('PKG:my_ext:Resources/Public/found-again.png', 'abc123');
        $configuration = ['width' => 100];
        $this->testFilesToDelete[] = $this->getCacheDirectoryFor($originalResource);

        $temporaryRender = GeneralUtility::tempnam('processed-resource-cache-test-') . '.png';
        file_put_contents($temporaryRender, 'rendered-bytes');

        $processedResource = $subject->findOneByOriginalAndTaskTypeAndConfiguration($originalResource, 'Image.CropScaleMask', $configuration);
        $processedResource->updateWithLocalFile($temporaryRender);

        self::assertFalse($processedResource->isNew());
        self::assertFalse($processedResource->usesOriginalFile());
        self::assertTrue($processedResource->exists());
        self::assertFileDoesNotExist($temporaryRender, 'the temporary file must have been moved, not copied.');
        self::assertSame('rendered-bytes', $processedResource->getContents());

        $rediscovered = $subject->findOneByOriginalAndTaskTypeAndConfiguration($originalResource, 'Image.CropScaleMask', $configuration);
        self::assertFalse($rediscovered->isNew(), 'a render persisted once must be found by a later lookup with the same original, task and configuration.');
        self::assertSame($processedResource->getName(), $rediscovered->getName());
    }

    #[Test]
    public function persistRenderRemovesStaleSiblingVariantsForTheSameConfiguration(): void
    {
        $subject = new ProcessedResourceCache();
        $configuration = ['width' => 100];
        $identifier = 'PKG:my_ext:Resources/Public/changing-content.png';

        $originalResourceBeforeChange = $this->getResourceStub($identifier, 'oldhash');
        $cacheDirectory = $this->getCacheDirectoryFor($originalResourceBeforeChange);
        $this->testFilesToDelete[] = $cacheDirectory;

        $temporaryRenderOne = GeneralUtility::tempnam('processed-resource-cache-test-') . '.png';
        file_put_contents($temporaryRenderOne, 'old-bytes');
        $processedResourceOne = $subject->findOneByOriginalAndTaskTypeAndConfiguration($originalResourceBeforeChange, 'Image.CropScaleMask', $configuration);
        $processedResourceOne->updateWithLocalFile($temporaryRenderOne);

        self::assertCount(1, glob($cacheDirectory . '*') ?: []);

        $originalResourceAfterChange = $this->getResourceStub($identifier, 'newhash');
        $processedResourceTwo = $subject->findOneByOriginalAndTaskTypeAndConfiguration($originalResourceAfterChange, 'Image.CropScaleMask', $configuration);
        self::assertTrue($processedResourceTwo->isNew(), 'a changed content hash must not be found as an existing render.');

        $temporaryRenderTwo = GeneralUtility::tempnam('processed-resource-cache-test-') . '.png';
        file_put_contents($temporaryRenderTwo, 'new-bytes');
        $processedResourceTwo->updateWithLocalFile($temporaryRenderTwo);

        $remainingVariants = glob($cacheDirectory . '*') ?: [];
        self::assertCount(1, $remainingVariants, 'the stale variant for the old content hash must have been removed.');
        self::assertSame('new-bytes', file_get_contents($remainingVariants[0]));
    }

    #[Test]
    public function differentConfigurationsForTheSameOriginalGetIndependentVariants(): void
    {
        $subject = new ProcessedResourceCache();
        $originalResource = $this->getResourceStub('PKG:my_ext:Resources/Public/multi-config.png', 'abc123');
        $cacheDirectory = $this->getCacheDirectoryFor($originalResource);
        $this->testFilesToDelete[] = $cacheDirectory;

        $temporaryRenderSmall = GeneralUtility::tempnam('processed-resource-cache-test-') . '.png';
        file_put_contents($temporaryRenderSmall, 'small-bytes');
        $small = $subject->findOneByOriginalAndTaskTypeAndConfiguration($originalResource, 'Image.CropScaleMask', ['width' => 50]);
        $small->updateWithLocalFile($temporaryRenderSmall);

        $temporaryRenderLarge = GeneralUtility::tempnam('processed-resource-cache-test-') . '.png';
        file_put_contents($temporaryRenderLarge, 'large-bytes');
        $large = $subject->findOneByOriginalAndTaskTypeAndConfiguration($originalResource, 'Image.CropScaleMask', ['width' => 500]);
        $large->updateWithLocalFile($temporaryRenderLarge);

        self::assertCount(2, glob($cacheDirectory . '*') ?: []);
        self::assertSame('small-bytes', $small->getContents());
        self::assertSame('large-bytes', $large->getContents());

        $rediscoveredSmall = $subject->findOneByOriginalAndTaskTypeAndConfiguration($originalResource, 'Image.CropScaleMask', ['width' => 50]);
        self::assertFalse($rediscoveredSmall->isNew());
        self::assertSame('small-bytes', $rediscoveredSmall->getContents());
    }
}
