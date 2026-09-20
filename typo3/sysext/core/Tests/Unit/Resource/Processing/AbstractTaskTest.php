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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Processing;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\ProcessedResourceInterface;
use TYPO3\CMS\Core\Resource\Processing\ImageCropScaleMaskTask;
use TYPO3\CMS\Core\Resource\Processing\ImagePreviewTask;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractTaskTest extends UnitTestCase
{
    #[Test]
    public function getSourceFileReturnsTheTargetFilesOriginalResource(): void
    {
        $sourceResource = self::createStub(SystemResourceInterface::class);
        $targetFile = self::createStub(ProcessedResourceInterface::class);
        $targetFile->method('getOriginalResource')->willReturn($sourceResource);

        $task = new ImageCropScaleMaskTask($targetFile, []);

        self::assertSame($sourceResource, $task->getSourceFile());
    }

    /**
     * AbstractTask::getChecksumData() computes a checksum for any task, FAL or not,
     * without a subclass having to override it - even one that has never heard of a
     * non-FAL source, such as ImagePreviewTask.
     */
    #[Test]
    public function getConfigurationChecksumComputesAChecksumForATaskWithANonFalSource(): void
    {
        $sourceResource = self::createStub(SystemResourceInterface::class);
        $sourceResource->method('getResourceIdentifier')->willReturn('PKG:my_ext:Resources/Public/foo.png');
        $sourceResource->method('getHash')->willReturn('abc123');
        $targetFile = self::createStub(ProcessedResourceInterface::class);
        $targetFile->method('getOriginalResource')->willReturn($sourceResource);

        $task = new ImagePreviewTask($targetFile, []);

        self::assertNotSame('', $task->getConfigurationChecksum());
    }
}
