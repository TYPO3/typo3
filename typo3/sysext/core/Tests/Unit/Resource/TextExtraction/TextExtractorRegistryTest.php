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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\TextExtraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TextExtractorRegistryTest extends UnitTestCase
{
    #[Test]
    public function getTextExtractorReturnsFirstMatchingTextExtractor(): void
    {
        $textExtractor1 = $this->createMock(TextExtractorInterface::class);
        $textExtractor1->expects($this->once())->method('canExtractText')->willReturn(false);
        $textExtractor2 = $this->createMock(TextExtractorInterface::class);
        $textExtractor2->expects($this->once())->method('canExtractText')->willReturn(true);
        $textExtractor3 = $this->createMock(TextExtractorInterface::class);
        $textExtractor3->expects($this->never())->method('canExtractText');

        $fileResourceMock = $this->createMock(File::class);

        $textExtractorRegistry = new TextExtractorRegistry([$textExtractor1, $textExtractor2, $textExtractor3]);

        self::assertSame($textExtractor2, $textExtractorRegistry->getTextExtractor($fileResourceMock));
    }

    #[Test]
    public function getTextExtractorReturnsNullIfNoTextExtractorMatches(): void
    {
        $textExtractor = $this->createMock(TextExtractorInterface::class);
        $textExtractor->method('canExtractText')->willReturn(false);

        $fileResourceMock = $this->createMock(File::class);

        $textExtractorRegistry = new TextExtractorRegistry([$textExtractor]);

        self::assertNull($textExtractorRegistry->getTextExtractor($fileResourceMock));
    }
}
