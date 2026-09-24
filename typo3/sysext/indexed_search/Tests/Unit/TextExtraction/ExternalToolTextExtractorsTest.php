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
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
use TYPO3\CMS\IndexedSearch\TextExtraction\AbstractTextExtractor;
use TYPO3\CMS\IndexedSearch\TextExtraction\CatdocTextExtractor;
use TYPO3\CMS\IndexedSearch\TextExtraction\CsvTextExtractor;
use TYPO3\CMS\IndexedSearch\TextExtraction\HtmlContentConverter;
use TYPO3\CMS\IndexedSearch\TextExtraction\HtmlTextExtractor;
use TYPO3\CMS\IndexedSearch\TextExtraction\ImageCommentTextExtractor;
use TYPO3\CMS\IndexedSearch\TextExtraction\LegacyExcelTextExtractor;
use TYPO3\CMS\IndexedSearch\TextExtraction\LegacyPowerPointTextExtractor;
use TYPO3\CMS\IndexedSearch\TextExtraction\OfficeOpenXmlTextExtractor;
use TYPO3\CMS\IndexedSearch\TextExtraction\OpenDocumentTextExtractor;
use TYPO3\CMS\IndexedSearch\TextExtraction\RtfTextExtractor;
use TYPO3\CMS\IndexedSearch\TextExtraction\XmlTextExtractor;

final class ExternalToolTextExtractorsTest extends AbstractTextExtractorTestCase
{
    private function create(string $class, array $configuration = [], array $tools = [], ?\Closure $responder = null): AbstractTextExtractor
    {
        $configuration = $this->createToolConfiguration($configuration, $tools);
        $executor = $this->createExecutor($responder);
        return match ($class) {
            CatdocTextExtractor::class,
            OfficeOpenXmlTextExtractor::class,
            OpenDocumentTextExtractor::class,
            ImageCommentTextExtractor::class => new $class($configuration, $executor),
            default => new $class($configuration, $executor, new HtmlContentConverter()),
        };
    }

    public static function extractorsDataProvider(): array
    {
        return [
            'catdoc' => [CatdocTextExtractor::class, 'catdoc', ['application/msword'], ['doc']],
            'ppthtml' => [LegacyPowerPointTextExtractor::class, 'ppthtml', ['application/vnd.ms-powerpoint'], ['ppt', 'pps']],
            'xlhtml' => [LegacyExcelTextExtractor::class, 'xlhtml', ['application/vnd.ms-excel'], ['xls']],
            'ooxml' => [
                OfficeOpenXmlTextExtractor::class,
                'unzip',
                [
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
                    'application/vnd.openxmlformats-officedocument.presentationml.template',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
                ],
                ['docx', 'dotx', 'pptx', 'ppsx', 'potx', 'xlsx', 'xltx'],
            ],
            'odf' => [
                OpenDocumentTextExtractor::class,
                'unzip',
                [
                    'application/vnd.sun.xml.calc',
                    'application/vnd.sun.xml.impress',
                    'application/vnd.sun.xml.writer',
                    'application/vnd.oasis.opendocument.spreadsheet',
                    'application/vnd.oasis.opendocument.presentation',
                    'application/vnd.oasis.opendocument.text',
                ],
                ['sxc', 'sxi', 'sxw', 'ods', 'odp', 'odt'],
            ],
            'unrtf' => [RtfTextExtractor::class, 'unrtf', ['application/rtf', 'text/rtf'], ['rtf']],
        ];
    }

    #[DataProvider('extractorsDataProvider')]
    #[Test]
    public function mimeTypesAreSupportedWhenToolIsAvailable(string $class, string $tool, array $mimeTypes, array $extensions): void
    {
        $subject = $this->create($class, [], [$tool]);
        self::assertSame($mimeTypes, $subject->getSupportedMimeTypes());
        foreach ($extensions as $extension) {
            self::assertTrue($subject->canExtractText($this->createFile('file.' . $extension)));
        }
    }

    #[DataProvider('extractorsDataProvider')]
    #[Test]
    public function nothingIsSupportedWithoutTool(string $class, string $tool, array $mimeTypes, array $extensions): void
    {
        $subject = $this->create($class);
        self::assertSame([], $subject->getSupportedMimeTypes());
        self::assertFalse($subject->canExtractText($this->createFile('file.' . $extensions[0])));
    }

    #[Test]
    public function missingToolsAreLoggedOnce(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('notice')->with(
            self::stringContains('not available'),
            self::equalTo([
                'extractor' => RtfTextExtractor::class,
                'mimeTypes' => 'application/rtf, text/rtf',
                'tools' => 'unrtf',
            ])
        );
        $subject = $this->create(RtfTextExtractor::class);
        $subject->setLogger($logger);
        $subject->getSupportedMimeTypes();
        $subject->getSupportedMimeTypes();
    }

    #[DataProvider('extractorsDataProvider')]
    #[Test]
    public function nothingIsSupportedWithoutConfiguredDirectory(string $class, string $tool, array $mimeTypes, array $extensions): void
    {
        $subject = $this->create($class, ['catdoc' => '', 'ppthtml' => '', 'xlhtml' => '', 'unzip' => '', 'unrtf' => ''], [$tool]);
        self::assertSame([], $subject->getSupportedMimeTypes());
    }

    #[Test]
    public function fileIsSelectedByMimeTypeNotByExtension(): void
    {
        $subject = $this->create(CatdocTextExtractor::class, [], ['catdoc']);
        $pdfNamedDoc = self::createStub(FileInterface::class);
        $pdfNamedDoc->method('getExtension')->willReturn('doc');
        $pdfNamedDoc->method('getMimeType')->willReturn('application/pdf');
        self::assertFalse($subject->canExtractText($pdfNamedDoc));
    }

    public static function alwaysAvailableDataProvider(): array
    {
        return [
            'html' => [HtmlTextExtractor::class, ['text/html'], ['html', 'htm']],
            'xml' => [XmlTextExtractor::class, ['text/xml', 'application/xml'], ['xml']],
            'csv' => [CsvTextExtractor::class, ['text/csv'], ['csv']],
            'images' => [ImageCommentTextExtractor::class, ['image/jpeg', 'image/tiff'], ['jpg', 'jpeg', 'tif']],
        ];
    }

    #[DataProvider('alwaysAvailableDataProvider')]
    #[Test]
    public function toolFreeExtractorsAreSupportedWithoutTools(string $class, array $mimeTypes, array $extensions): void
    {
        $subject = $this->create($class);
        self::assertSame($mimeTypes, $subject->getSupportedMimeTypes());
        foreach ($extensions as $extension) {
            self::assertTrue($subject->canExtractText($this->createFile('file.' . $extension)));
        }
    }

    #[Test]
    public function catdocRunsWithUtf8AndCleansOutput(): void
    {
        $subject = $this->create(CatdocTextExtractor::class, [], ['catdoc'], fn(): array => ['Line one', 'Line two', '', "\x0c"]);
        $file = $this->createFile("we ird's.doc");
        $result = $subject->extractStructuredText($file);
        self::assertSame("Line one\nLine two", $result->text);
        self::assertSame('', $result->title);
    }

    #[Test]
    public function catdocCommandEscapesFilePath(): void
    {
        $commands = [];
        $subject = $this->create(CatdocTextExtractor::class, [], ['catdoc'], function (string $command) use (&$commands): array {
            $commands[] = $command;
            return [];
        });
        $file = $this->createFile('x y.doc');
        $subject->extractText($file);
        self::assertSame($this->workingDirectory . '/bin/catdoc -d utf-8 \'' . $this->workingDirectory . '/x y.doc\'', $commands[0]);
    }

    #[Test]
    public function powerPointUsesFileNameAsTitleAndReadsMetaTags(): void
    {
        $html = '<html><head><title>Ignored</title><meta name="keywords" content="a,b"></head><body><p>Slide one</p><script>x()</script></body></html>';
        $subject = $this->create(LegacyPowerPointTextExtractor::class, [], ['ppthtml'], fn(): array => [$html, '']);
        $result = $subject->extractStructuredText($this->createFile('deck.ppt'));
        self::assertEquals(new ExtractedText('Slide one', 'deck.ppt', '', 'a, b'), $result);
    }

    #[Test]
    public function excelUsesXlhtmlFlags(): void
    {
        $commands = [];
        $subject = $this->create(LegacyExcelTextExtractor::class, [], ['xlhtml'], function (string $command) use (&$commands): array {
            $commands[] = $command;
            return ['<body><table><tr><td>Cell</td></tr></table></body>'];
        });
        $result = $subject->extractStructuredText($this->createFile('sheet.xls'));
        self::assertSame('Cell', $result->text);
        self::assertSame('sheet.xls', $result->title);
        self::assertStringContainsString(' -nc -te ', $commands[0]);
    }

    #[Test]
    public function rtfTitleComesFromDocument(): void
    {
        $subject = $this->create(RtfTextExtractor::class, [], ['unrtf'], fn(): array => ['<html><head><title>Letter</title></head><body>Dear &amp; all</body></html>']);
        $result = $subject->extractStructuredText($this->createFile('letter.rtf'));
        self::assertSame('Letter', $result->title);
        self::assertSame('Dear & all', $result->text);
    }

    #[Test]
    public function wordDocumentReadsBodyAndCoreProperties(): void
    {
        $core = '<cp:coreProperties xmlns:cp="c" xmlns:dc="d"><dc:title>My Title</dc:title><dc:subject>Sub</dc:subject><dc:description>Desc</dc:description><cp:keywords>k1, k2</cp:keywords></cp:coreProperties>';
        $commands = [];
        $subject = $this->create(OfficeOpenXmlTextExtractor::class, [], ['unzip'], function (string $command) use (&$commands, $core): array {
            $commands[] = $command;
            return match (true) {
                str_contains($command, ' -l ') => ['    100  x  word/document.xml', '    100  x  docProps/core.xml'],
                str_contains($command, 'docProps/core.xml') => [$core],
                default => ['<w:p><w:t>Hello</w:t></w:p><w:p><w:t>World</w:t></w:p>'],
            };
        });
        $result = $subject->extractStructuredText($this->createFile('doc.docx'));
        self::assertEquals(new ExtractedText('Hello    World', 'doc.docx My Title', 'Sub Desc', 'k1, k2'), $result);
        self::assertStringContainsString("-p '", $commands[1]);
        self::assertStringEndsWith("'word/document.xml'", $commands[1]);
    }

    #[Test]
    public function wordDocumentWithGenericMimeTypeIsDetectedByArchiveContent(): void
    {
        $subject = $this->create(OfficeOpenXmlTextExtractor::class, [], ['unzip'], fn(string $command): array => match (true) {
            str_contains($command, ' -l ') => ['    100  x  word/document.xml'],
            str_contains($command, 'word/document.xml') => ['<w:p><w:t>Hello</w:t></w:p>'],
            default => [],
        });
        $file = $this->createFile('doc.zip');
        self::assertStringNotContainsString('openxmlformats', $file->getMimeType());
        self::assertSame('Hello', $subject->extractStructuredText($file)->text);
    }

    #[Test]
    public function spreadsheetReadsSharedStrings(): void
    {
        $commands = [];
        $subject = $this->create(OfficeOpenXmlTextExtractor::class, [], ['unzip'], function (string $command) use (&$commands): array {
            $commands[] = $command;
            return [];
        });
        $subject->extractStructuredText($this->createFile('sheet.xlsx'));
        self::assertStringEndsWith("'xl/sharedStrings.xml'", $commands[1]);
    }

    #[Test]
    public function presentationReadsEverySlide(): void
    {
        $subject = $this->create(OfficeOpenXmlTextExtractor::class, [], ['unzip'], function (string $command): array {
            return match (true) {
                str_contains($command, ' -l ') => ['   100  x  ppt/slides/slide1.xml', '   100  x  ppt/slides/slide2.xml', '   1  x  ppt/slides/_rels/slide1.xml.rels', '   1  x  ppt/theme/theme1.xml'],
                str_contains($command, 'slide1.xml') => ['<a:t>First</a:t>'],
                str_contains($command, 'slide2.xml') => ['<a:t>Second</a:t>'],
                default => [],
            };
        });
        $result = $subject->extractStructuredText($this->createFile('deck.pptx'));
        self::assertSame("First\nSecond", $result->text);
        self::assertSame('deck.pptx', $result->title);
    }

    #[Test]
    public function openDocumentReadsContentAndMetaData(): void
    {
        $meta = '<office:document-meta><office:meta><dc:title>ODF Title</dc:title><dc:subject>Sub</dc:subject><dc:description>Desc</dc:description>'
            . '<meta:keywords><meta:keyword>one</meta:keyword><meta:keyword>two</meta:keyword></meta:keywords></office:meta></office:document-meta>';
        $subject = $this->create(OpenDocumentTextExtractor::class, [], ['unzip'], fn(string $command): array => str_contains($command, 'meta.xml') ? [$meta] : ['<text:p>Paragraph</text:p>']);
        $result = $subject->extractStructuredText($this->createFile('doc.odt'));
        self::assertEquals(new ExtractedText('Paragraph', 'ODF Title', 'Sub Desc', 'one two'), $result);
    }

    #[Test]
    public function openDocumentFallsBackToFileNameWithoutMetaData(): void
    {
        $subject = $this->create(OpenDocumentTextExtractor::class, [], ['unzip'], fn(string $command): array => str_contains($command, 'meta.xml') ? [] : ['<text:p>Paragraph</text:p>']);
        $result = $subject->extractStructuredText($this->createFile('doc.odt'));
        self::assertSame('doc.odt', $result->title);
        self::assertSame('Paragraph', $result->text);
    }

    #[Test]
    public function htmlFileIsSplitAndConvertedToUtf8(): void
    {
        $html = mb_convert_encoding('<html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><title>Größe</title></head><body>Grüße<script>a()</script><style>b{}</style></body></html>', 'ISO-8859-1', 'UTF-8');
        $result = $this->create(HtmlTextExtractor::class)->extractStructuredText($this->createFile('page.htm', $html));
        self::assertSame('Größe', $result->title);
        self::assertSame('Grüße', $result->text);
    }

    #[Test]
    public function xmlCharsetIsDetectedFromDeclaration(): void
    {
        $subject = new XmlTextExtractor($this->createToolConfiguration(), $this->createExecutor(), new HtmlContentConverter());
        self::assertSame('iso-8859-1', $subject->detectCharset('<?xml version="1.0" encoding="ISO-8859-1"?><a/>'));
        self::assertSame('utf-8', $subject->detectCharset('<?xml version="1.0"?><a/>'));
        self::assertSame('utf-16', $subject->detectCharset("  <?xml version='1.0' encoding = ' UTF-16 '?>"));
    }

    #[Test]
    public function xmlTextIsConvertedToUtf8(): void
    {
        $xml = mb_convert_encoding('<?xml version="1.0" encoding="ISO-8859-1"?><root><a>Grüße</a><b>Welt</b></root>', 'ISO-8859-1', 'UTF-8');
        $result = $this->create(XmlTextExtractor::class)->extractStructuredText($this->createFile('data.xml', $xml));
        self::assertSame('data.xml', $result->title);
        self::assertStringContainsString('Grüße', $result->text);
        self::assertStringContainsString('Welt', $result->text);
        self::assertStringNotContainsString('<', $result->text);
    }

    #[Test]
    public function csvContentIsReturnedWithFileNameAsTitle(): void
    {
        $result = $this->create(CsvTextExtractor::class)->extractStructuredText($this->createFile('list.csv', "a;b\nÄpfel &amp; Birnen;2"));
        self::assertEquals(new ExtractedText("a;b\nÄpfel & Birnen;2", 'list.csv'), $result);
    }

    #[Test]
    public function imageWithoutReadableExifYieldsEmptyComment(): void
    {
        $result = $this->create(ImageCommentTextExtractor::class)->extractStructuredText($this->createFile('photo.jpg', 'not an image'));
        self::assertEquals(new ExtractedText('', 'photo.jpg'), $result);
    }

    #[Test]
    public function removeEndJunkTrimsFormFeedsAndLineBreaks(): void
    {
        self::assertSame("Text\nmore", AbstractTextExtractor::removeEndJunk("Text\nmore\n\x0c\n"));
    }
}
