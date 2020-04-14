<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\TextExtraction;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\TextExtraction\PlainTextExtractor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class PlainTextExtractorTest
 */
class PlainTextExtractorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function canExtractTextReturnsTrueForPlainTextFiles()
    {
        $plainTextExtractor = new PlainTextExtractor();

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('text/plain');

        self::assertTrue($plainTextExtractor->canExtractText($fileResourceMock));
    }

    /**
     * @test
     */
    public function canExtractTextReturnsFalseForNonPlainTextFiles()
    {
        $plainTextExtractor = new PlainTextExtractor();

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('video/mp4');

        self::assertFalse($plainTextExtractor->canExtractText($fileResourceMock));
    }
}
