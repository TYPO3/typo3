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

namespace TYPO3\CMS\Core\Tests\Functional\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestTextExtractor\TextExtraction\HighPriorityPlainTextExtractor;
use TYPO3Tests\TestTextExtractor\TextExtraction\MarkdownTextExtractor;

final class AsTextExtractorAttributeTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_text_extractor',
    ];

    #[Test]
    public function textExtractorsAreRegisteredViaAttribute(): void
    {
        $textExtractorRegistry = $this->get(TextExtractorRegistry::class);

        self::assertInstanceOf(MarkdownTextExtractor::class, $textExtractorRegistry->getTextExtractor($this->createFileMock('text/markdown')));
    }

    #[Test]
    public function textExtractorWithHigherPriorityTakesPrecedenceOverCoreExtractors(): void
    {
        $textExtractorRegistry = $this->get(TextExtractorRegistry::class);

        self::assertInstanceOf(HighPriorityPlainTextExtractor::class, $textExtractorRegistry->getTextExtractor($this->createFileMock('text/plain')));
    }

    private function createFileMock(string $mimeType): File
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->method('getMimeType')->willReturn($mimeType);
        return $fileMock;
    }
}
