<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Index;

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
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class IndexerTest
 */
class IndexerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function isFileTypeSupportedByExtractorReturnsFalesForFileTypeTextAndExtractorLimitedToFileTypeImage()
    {
        $mockStorage = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, [], [], '', false);
        $mockFile = $this->getMock(File::class, [], [], '', false);
        $mockFile->expects($this->any())->method('getType')->will($this->returnValue(
            File::FILETYPE_TEXT
        ));

        $mockExtractor = $this->getMock(ExtractorInterface::class, [], [], '', false);
        $mockExtractor->expects($this->any())->method('getFileTypeRestrictions')->will($this->returnValue(
            [File::FILETYPE_IMAGE]
        ));

        $method = new \ReflectionMethod(Indexer::class, 'isFileTypeSupportedByExtractor');
        $method->setAccessible(true);
        $arguments = [
            $mockFile,
            $mockExtractor
        ];

        $result = $method->invokeArgs(new Indexer($mockStorage), $arguments);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function isFileTypeSupportedByExtractorReturnsTrueForFileTypeImageAndExtractorLimitedToFileTypeImage()
    {
        $mockStorage = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, [], [], '', false);
        $mockFile = $this->getMock(File::class, [], [], '', false);
        $mockFile->expects($this->any())->method('getType')->will($this->returnValue(
            File::FILETYPE_IMAGE
        ));

        $mockExtractor = $this->getMock(ExtractorInterface::class, [], [], '', false);
        $mockExtractor->expects($this->any())->method('getFileTypeRestrictions')->will($this->returnValue(
            [File::FILETYPE_IMAGE]
        ));

        $method = new \ReflectionMethod(Indexer::class, 'isFileTypeSupportedByExtractor');
        $method->setAccessible(true);
        $arguments = [
            $mockFile,
            $mockExtractor
        ];

        $result = $method->invokeArgs(new Indexer($mockStorage), $arguments);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function isFileTypeSupportedByExtractorReturnsTrueForFileTypeTextAndExtractorHasNoFileTypeLimitation()
    {
        $mockStorage = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, [], [], '', false);
        $mockFile = $this->getMock(File::class, [], [], '', false);
        $mockFile->expects($this->any())->method('getType')->will($this->returnValue(
            File::FILETYPE_TEXT
        ));

        $mockExtractor = $this->getMock(ExtractorInterface::class, [], [], '', false);
        $mockExtractor->expects($this->any())->method('getFileTypeRestrictions')->will($this->returnValue(
            []
        ));

        $method = new \ReflectionMethod(Indexer::class, 'isFileTypeSupportedByExtractor');
        $method->setAccessible(true);
        $arguments = [
            $mockFile,
            $mockExtractor
        ];

        $result = $method->invokeArgs(new Indexer($mockStorage), $arguments);
        $this->assertTrue($result);
    }
}
