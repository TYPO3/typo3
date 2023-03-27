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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\ImageCropScaleMaskTask;
use TYPO3\CMS\Core\Resource\Processing\ImagePreviewTask;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Representing an image dimension (width and height)
 * and calculating the dimension from a source with a given processing instruction
 */
class ImageDimensionTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public static function givenProcessingInstructionsCalculatesCorrectDimensionDataProvider(): array
    {
        return [
            'max width is applied' => [
                [
                    'maxWidth' => 100,
                ],
                new ImageDimension(1000, 500),
                'jpg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(100, 50),
            ],
            'max width is applied when provided in width' => [
                [
                    'width' => '100m',
                ],
                new ImageDimension(1000, 500),
                'jpg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(100, 50),
            ],
            'max height is applied' => [
                [
                    'maxHeight' => 100,
                ],
                new ImageDimension(1000, 500),
                'jpg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(200, 100),
            ],
            'max height is applied when provided in height' => [
                [
                    'height' => '100m',
                ],
                new ImageDimension(1000, 500),
                'jpg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(200, 100),
            ],
            'crop scale is applied' => [
                [
                    'width' => 100,
                    'height' => '100c',
                ],
                new ImageDimension(1000, 500),
                'jpg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(100, 100),
            ],
            'maxWidth higher than crop scale' => [
                [
                    'width' => 100,
                    'height' => '100c',
                    'maxWidth' => 200,
                ],
                new ImageDimension(1000, 500),
                'jpg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(100, 100),
            ],
            'maxWidth lower than crop scale (crop scale is ignored)' => [
                [
                    'width' => 100,
                    'height' => '100c',
                    'maxWidth' => 50,
                ],
                new ImageDimension(1000, 500),
                'jpg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(50, 25),
            ],
            'width and height are applied as given' => [
                [
                    'width' => 100,
                    'height' => 125,
                ],
                new ImageDimension(1000, 500),
                'jpg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(100, 125),
            ],
            'cropping is applied before scaling' => [
                [
                    'maxWidth' => 100,
                    'crop' => new Area(0, 0, 121.8, 45.3),
                ],
                new ImageDimension(1000, 500),
                'jpg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(100, 37),
            ],
            'width and height act as maxWidth and maxHeight for previews' => [
                [
                    'width' => 100,
                    'height' => 125,
                ],
                new ImageDimension(1000, 500),
                'jpg',
                ImagePreviewTask::class,
                new ImageDimension(100, 50),
            ],
            'width and height act as maxWidth and maxHeight for previews, max height' => [
                [
                    'width' => 100,
                    'height' => 125,
                ],
                new ImageDimension(500, 1000),
                'jpg',
                ImagePreviewTask::class,
                new ImageDimension(63, 125),
            ],
            'SVGs are scaled when crop scale is applied' => [
                [
                    'width' => 100,
                    'height' => '100c',
                ],
                new ImageDimension(1000, 500),
                'svg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(100, 50),
            ],
            'cropping is applied on SVGs' => [
                [
                    'crop' => new Area(0, 0, 121.8, 45.3),
                ],
                new ImageDimension(1000, 500),
                'svg',
                ImageCropScaleMaskTask::class,
                new ImageDimension(122, 45),
            ],
        ];
    }

    /**
     * @dataProvider givenProcessingInstructionsCalculatesCorrectDimensionDataProvider
     * @test
     */
    public function givenProcessingInstructionsCalculatesCorrectDimension(
        array $processingConfiguration,
        ImageDimension $originalImageDimension,
        string $fileExtension,
        string $taskClass,
        ImageDimension $expectedImageDimension
    ): void {
        $task = $this->createTask($processingConfiguration, $originalImageDimension, $fileExtension, $taskClass);
        $calculatedDimension = ImageDimension::fromProcessingTask($task);
        self::assertEquals($expectedImageDimension, $calculatedDimension);
    }

    private function createTask(array $processingConfiguration, ImageDimension $originalImageDimension, string $fileExtension, string $taskClass = ImageCropScaleMaskTask::class): TaskInterface
    {
        $originalFileMock = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();
        $originalFileMock->method('getExtension')->willReturn($fileExtension);
        $originalFileMock->method('getProperty')->willReturnOnConsecutiveCalls($originalImageDimension->getWidth(), $originalImageDimension->getHeight());
        $processedFileMock = $this->getMockBuilder(ProcessedFile::class)->disableOriginalConstructor()->getMock();
        $processedFileMock->method('getOriginalFile')->willReturn($originalFileMock);

        /** @var TaskInterface $task */
        $task = new $taskClass(
            $processedFileMock,
            $processingConfiguration
        );

        $processedFileMock->method('getTaskIdentifier')->willReturn($task->getType() . '.' . $task->getName());

        return $task;
    }
}
