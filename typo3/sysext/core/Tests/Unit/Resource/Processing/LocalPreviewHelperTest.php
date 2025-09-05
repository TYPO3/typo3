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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\ImagePreviewTask;
use TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper
 */
final class LocalPreviewHelperTest extends UnitTestCase
{
    #[Test]
    public function processProvidesDefaultSizeIfNotConfigured(): void
    {
        $file = $this->createMock(File::class);
        // Use size slightly larger than default size to ensure processing
        $file->method('getProperty')->willReturnMap([
            ['width', 65],
            ['height', 65],
        ]);

        $processedFile = $this->createMock(ProcessedFile::class);
        $processedFile->method('getOriginalFile')->willReturn($file);
        $task = new ImagePreviewTask($processedFile, []);

        $localPreviewHelper = $this->getMockBuilder(LocalPreviewHelper::class)
            ->onlyMethods(['getTemporaryFilePath', 'generatePreviewFromFile'])
            ->getMock();
        $localPreviewHelper->expects($this->once())->method('getTemporaryFilePath')->willReturn('test/file');
        // Assert that by default 64x64 is used as preview size
        $localPreviewHelper->expects($this->once())->method('generatePreviewFromFile')
            ->with($file, ['width' => 64, 'height' => 64], 'test/file');

        $localPreviewHelper->process($task);
    }

    public static function processDoesNotScaleUpImagesDataProvider(): array
    {
        return [
            '20x20 to 30x30 not scaled up (both dimensions below limit)' => [
                'width' => 20,
                'height' => 20,
                'requestedWidth' => 30,
                'requestedHeight' => 30,
            ],
            '20x200 to 30x30 not scaled up (width below limit)' => [
                'width' => 20,
                'height' => 200,
                'requestedWidth' => 30,
                'requestedHeight' => 30,
            ],
            '200x20 to 30x30 not scaled up (height below limit)' => [
                'width' => 200,
                'height' => 20,
                'requestedWidth' => 30,
                'requestedHeight' => 30,
            ],
        ];
    }

    #[DataProvider('processDoesNotScaleUpImagesDataProvider')]
    #[Test]
    public function processDoesNotScaleUpImages(int $width, int $height, int $requestedWidth, int $requestedHeight): void
    {
        $file = $this->createMock(File::class);
        $file->method('getProperty')->willReturnMap([
            ['width', $width],
            ['height', $height],
        ]);

        $localPreviewHelper = $this->getMockBuilder(LocalPreviewHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $task = $this->createMock(TaskInterface::class);
        $task->expects($this->once())->method('getSourceFile')->willReturn($file);
        $task->expects($this->once())->method('getConfiguration')->willReturn(['width' => $requestedWidth, 'height' => $requestedHeight]);

        self::assertNull($localPreviewHelper->process($task));
        // Note: Functional test in @see TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\ImageViewHelperTest->noUpScaling()
    }
    #[Test]
    public function processGeneratesPreviewEvenIfSourceFileHasNoSize(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getProperty')->willReturnMap([
            ['width', 0],
            ['height', 0],
        ]);

        $task = $this->createMock(TaskInterface::class);
        $task->expects($this->once())->method('getSourceFile')->willReturn($file);
        $task->expects($this->once())->method('getConfiguration')->willReturn([]);

        $localPreviewHelper = $this->getMockBuilder(LocalPreviewHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTemporaryFilePath', 'generatePreviewFromFile'])
            ->getMock();
        $expectedResult = ['width' => 20, 'height' => 20, 'filePath' => 'test/file'];
        $localPreviewHelper->expects($this->once())->method('generatePreviewFromFile')->willReturn($expectedResult);
        $localPreviewHelper->expects($this->once())->method('getTemporaryFilePath')->willReturn('foo_file');

        self::assertEquals($expectedResult, $localPreviewHelper->process($task));
    }
}
