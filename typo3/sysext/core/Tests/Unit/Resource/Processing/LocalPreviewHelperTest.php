<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Processing;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper
 */
class LocalPreviewHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function processProvidesDefaultSizeIfNotConfigured()
    {
        $file = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Use size slightly larger than default size to ensure processing
        $file->expects($this->any())->method('getProperty')->will($this->returnValueMap([
            ['width', 65],
            ['height', 65],
        ]));

        $task = $this->getMock(TaskInterface::class);
        $task->expects($this->once())->method('getSourceFile')->willReturn($file);
        $task->expects($this->once())->method('getConfiguration')->willReturn([]);

        $localPreviewHelper = $this->getMockBuilder(LocalPreviewHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTemporaryFilePath', 'generatePreviewFromFile'])
            ->getMock();
        $localPreviewHelper->expects($this->once())->method('getTemporaryFilePath')->willReturn('test/file');
        // Assert that by default 64x64 is used as preview size
        $localPreviewHelper->expects($this->once())->method('generatePreviewFromFile')
            ->with($file, ['width' => 64, 'height' => 64], 'test/file');

        $localPreviewHelper->process($task);
    }

    /**
     * @test
     */
    public function processDoesNotScaleUpImages()
    {
        $file = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $file->expects($this->any())->method('getProperty')->will($this->returnValueMap([
            ['width', 20],
            ['height', 20],
        ]));

        $localPreviewHelper = $this->getMockBuilder(LocalPreviewHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['dummy'])
            ->getMock();

        $task = $this->getMock(TaskInterface::class);
        $task->expects($this->once())->method('getSourceFile')->willReturn($file);
        $task->expects($this->once())->method('getConfiguration')->willReturn(['width' => 30, 'height' => 30]);

        $this->assertNull($localPreviewHelper->process($task));
    }

    /**
     * @test
     */
    public function processGeneratesPreviewEvenIfSourceFileHasNoSize()
    {
        $file = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $file->expects($this->any())->method('getProperty')->will($this->returnValueMap([
            ['width', 0],
            ['height', 0],
        ]));

        $task = $this->getMock(TaskInterface::class);
        $task->expects($this->once())->method('getSourceFile')->willReturn($file);
        $task->expects($this->once())->method('getConfiguration')->willReturn([]);

        $localPreviewHelper = $this->getMockBuilder(LocalPreviewHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTemporaryFilePath', 'generatePreviewFromFile'])
            ->getMock();
        $expectedResult = ['width' => 20, 'height' => 20, 'filePath' => 'test/file'];
        $localPreviewHelper->expects($this->once())->method('generatePreviewFromFile')->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $localPreviewHelper->process($task));
    }
}
