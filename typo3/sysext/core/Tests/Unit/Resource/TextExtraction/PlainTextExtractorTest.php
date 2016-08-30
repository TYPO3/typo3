<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\TextExtraction;

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
use TYPO3\CMS\Core\Resource\TextExtraction\PlainTextExtractor;

/**
 * Class PlainTextExtractorTest
 */
class PlainTextExtractorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function canExtractTextReturnsTrueForPlainTextFiles()
    {
        $plainTextExtractor = new PlainTextExtractor();

        $fileResourceMock = $this->getMock(File::class, [], [], '', false);
        $fileResourceMock->expects($this->any())->method('getMimeType')->will($this->returnValue('text/plain'));

        $this->assertTrue($plainTextExtractor->canExtractText($fileResourceMock));
    }

    /**
     * @test
     */
    public function canExtractTextReturnsFalseForNonPlainTextFiles()
    {
        $plainTextExtractor = new PlainTextExtractor();

        $fileResourceMock = $this->getMock(File::class, [], [], '', false);
        $fileResourceMock->expects($this->any())->method('getMimeType')->will($this->returnValue('video/mp4'));

        $this->assertFalse($plainTextExtractor->canExtractText($fileResourceMock));
    }
}
