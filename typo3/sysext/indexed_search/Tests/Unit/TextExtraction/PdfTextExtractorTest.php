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

namespace TYPO3\CMS\IndexedSearch\Tests\Unit\TextExtraction;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
use TYPO3\CMS\IndexedSearch\TextExtraction\PartitionedDocument;
use TYPO3\CMS\IndexedSearch\TextExtraction\PdfTextExtractor;

final class PdfTextExtractorTest extends AbstractTextExtractorTestCase
{
    private const PDFINFO_OUTPUT = [
        'Title:          A title',
        'Pages:          20',
    ];

    #[Test]
    public function pdfSupportRequiresBothTools(): void
    {
        $subject = new PdfTextExtractor($this->createToolConfiguration([], ['pdfinfo']), $this->createExecutor());
        self::assertSame([], $subject->getSupportedMimeTypes());
        self::assertFalse($subject->canExtractText($this->createFile('a.pdf')));
    }

    #[Test]
    public function pdfIsSupportedWithBothTools(): void
    {
        $subject = new PdfTextExtractor($this->createToolConfiguration([], ['pdfinfo', 'pdftotext']), $this->createExecutor());
        self::assertSame(['application/pdf'], $subject->getSupportedMimeTypes());
        self::assertTrue($subject->canExtractText($this->createFile('a.pdf')));
        self::assertFalse($subject->canExtractText($this->createFile('a.doc')));
    }

    #[Test]
    public function splitPdfInfoDoesNotOverrideValues(): void
    {
        $subject = new PdfTextExtractor($this->createToolConfiguration(), $this->createExecutor());
        $input = '
Title:          BAA010718_Broschüre_Chancen_bieten_V2.indd
Creator:        Adobe InDesign CC 13.0 (Macintosh)
Pages:          20
Encrypted:      no
PDF subtype:    PDF/X-3:2002
    Title:         ISO 15930 - Electronic document file format for prepress digital data exchange (PDF/X)
    Abbreviation:  PDF/X-3:2002';
        $result = $subject->splitPdfInfo(explode(LF, $input));
        self::assertSame('BAA010718_Broschüre_Chancen_bieten_V2.indd', $result['title']);
        self::assertSame('20', $result['pages']);
        self::assertSame('PDF/X-3:2002', $result['pdf subtype']);
    }

    public static function partsDataProvider(): array
    {
        return [
            'zero pages' => [0, -20, ['0']],
            'one part per default group count' => [3, -20, ['1-1', '2-2', '3-3']],
            'negative mode is number of groups' => [10, -3, ['1-3', '4-6', '7-10']],
            'zero mode is one group' => [7, 0, ['1-7']],
            'positive mode is pages per group' => [12, 5, ['1-4', '5-8', '9-12']],
            'positive mode larger than document' => [4, 50, ['1-4']],
            'positive mode of one' => [3, 1, ['1-1', '2-2', '3-3']],
        ];
    }

    #[DataProvider('partsDataProvider')]
    #[Test]
    public function partsAreCalculatedFromPagesAndMode(int $pages, int $mode, array $expected): void
    {
        self::assertSame($expected, PdfTextExtractor::calculateParts($pages, $mode));
    }

    #[Test]
    public function partitionedDocumentUsesPageCountAndConfiguredMode(): void
    {
        $executor = $this->createExecutor(fn(): array => self::PDFINFO_OUTPUT);
        $configuration = $this->createToolConfiguration(['pdf_mode' => 10], ['pdfinfo', 'pdftotext']);
        $subject = new PdfTextExtractor($configuration, $executor);
        $parts = $subject->withPartitionedDocument($this->createFile('a.pdf'), fn(PartitionedDocument $document): array => $document->parts);
        self::assertSame(['1-10', '11-20'], $parts);
        self::assertStringContainsString("pdfinfo -enc UTF-8 '", $executor->commands[0]);
    }

    #[Test]
    public function partitionedDocumentIsWholeDocumentWithoutPages(): void
    {
        $configuration = $this->createToolConfiguration([], ['pdfinfo', 'pdftotext']);
        $subject = new PdfTextExtractor($configuration, $this->createExecutor());
        $parts = $subject->withPartitionedDocument($this->createFile('a.pdf'), fn(PartitionedDocument $document): array => $document->parts);
        self::assertSame(['0'], $parts);
    }

    #[Test]
    public function partitionedDocumentIsWholeDocumentWithoutTools(): void
    {
        $executor = $this->createExecutor();
        $subject = new PdfTextExtractor($this->createToolConfiguration(), $executor);
        $result = $subject->withPartitionedDocument(
            $this->createFile('a.pdf'),
            fn(PartitionedDocument $document): array => [$document->parts, $document->extractPart('0')->text]
        );
        self::assertSame([['0'], ''], $result);
        self::assertSame([], $executor->commands);
    }

    #[Test]
    public function partitionedDocumentReadsPageRangeAndTitle(): void
    {
        $executor = $this->createExecutor(function (string $command): array {
            if (str_contains($command, 'pdftotext') && preg_match("/'([^']+)'\$/", $command, $matches)) {
                file_put_contents($matches[1], "Some text\n\x0c");
                return [];
            }
            return self::PDFINFO_OUTPUT;
        });
        $subject = new PdfTextExtractor($this->createToolConfiguration([], ['pdfinfo', 'pdftotext']), $executor);
        $result = $subject->withPartitionedDocument($this->createFile('a.pdf'), fn(PartitionedDocument $document): ExtractedText => $document->extractPart('3-5'));
        self::assertEquals(new ExtractedText('Some text', 'A title'), $result);
        self::assertStringContainsString("-enc UTF-8 '", $executor->commands[0]);
        self::assertStringContainsString("-f '3' -l '5' -enc UTF-8 -q '", $executor->commands[1]);
    }

    #[Test]
    public function partitionedDocumentReadsFileAndRunsPdfinfoOnceForAllParts(): void
    {
        $executor = $this->createExecutor(function (string $command): array {
            if (str_contains($command, 'pdftotext') && preg_match("/'([^']+)'\$/", $command, $matches)) {
                file_put_contents($matches[1], 'Part');
                return [];
            }
            return self::PDFINFO_OUTPUT;
        });
        $localFilePath = $this->workingDirectory . '/a.pdf';
        touch($localFilePath);
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->expects($this->once())->method('getForLocalProcessing')->willReturn($localFilePath);
        $subject = new PdfTextExtractor($this->createToolConfiguration(['pdf_mode' => 5], ['pdfinfo', 'pdftotext']), $executor);

        $texts = $subject->withPartitionedDocument($file, fn(PartitionedDocument $document): array => array_map(
            fn(string $part): string => $document->extractPart($part)->text,
            $document->parts
        ));

        self::assertSame(['Part', 'Part', 'Part', 'Part'], $texts);
        self::assertCount(1, array_filter($executor->commands, fn(string $command): bool => str_contains($command, 'pdfinfo')));
    }

    #[Test]
    public function extractStructuredTextReadsWholeDocument(): void
    {
        $executor = $this->createExecutor(function (string $command): array {
            if (str_contains($command, 'pdftotext') && preg_match("/'([^']+)'\$/", $command, $matches)) {
                file_put_contents($matches[1], 'Everything');
                return [];
            }
            return self::PDFINFO_OUTPUT;
        });
        $subject = new PdfTextExtractor($this->createToolConfiguration([], ['pdfinfo', 'pdftotext']), $executor);
        self::assertSame('Everything', $subject->extractText($this->createFile('a.pdf')));
        self::assertStringContainsString("-f '1' -l '20'", $executor->commands[1]);
    }

    #[Test]
    public function missingToolYieldsEmptyText(): void
    {
        $executor = $this->createExecutor();
        $subject = new PdfTextExtractor($this->createToolConfiguration(), $executor);
        self::assertSame('', $subject->extractStructuredText($this->createFile('a.pdf'))->text);
        self::assertSame([], $executor->commands);
    }
}
