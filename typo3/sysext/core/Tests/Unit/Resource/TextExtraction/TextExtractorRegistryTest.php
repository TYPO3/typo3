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
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
use TYPO3\CMS\Core\Resource\TextExtraction\StructuredTextExtractorInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
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

        $fileResourceMock = self::createStub(File::class);

        $textExtractorRegistry = new TextExtractorRegistry([$textExtractor1, $textExtractor2, $textExtractor3]);

        self::assertSame($textExtractor2, $textExtractorRegistry->getTextExtractor($fileResourceMock));
    }

    #[Test]
    public function getTextExtractorReturnsNullIfNoTextExtractorMatches(): void
    {
        $textExtractor = self::createStub(TextExtractorInterface::class);
        $textExtractor->method('canExtractText')->willReturn(false);

        $fileResourceMock = self::createStub(File::class);

        $textExtractorRegistry = new TextExtractorRegistry([$textExtractor]);

        self::assertNull($textExtractorRegistry->getTextExtractor($fileResourceMock));
    }

    #[Test]
    public function extractWrapsTextOfPlainTextExtractor(): void
    {
        $file = self::createStub(File::class);
        $textExtractor = self::createStub(TextExtractorInterface::class);
        $textExtractor->method('canExtractText')->willReturn(true);
        $textExtractor->method('extractText')->willReturn('some text');

        $extractedText = new TextExtractorRegistry([$textExtractor])->extract($file);

        self::assertEquals(new ExtractedText('some text'), $extractedText);
    }

    #[Test]
    public function extractReturnsStructuredResultOfStructuredTextExtractor(): void
    {
        $file = self::createStub(File::class);
        $file->method('getMimeType')->willReturn('application/pdf');
        $expected = new ExtractedText('some text', 'A title', 'A description', 'a, b');
        $textExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $textExtractor->method('getSupportedMimeTypes')->willReturn(['application/pdf']);
        $textExtractor->method('extractStructuredText')->willReturn($expected);

        self::assertSame($expected, new TextExtractorRegistry([$textExtractor])->extract($file));
    }

    #[Test]
    public function extractHandsSystemResourceToStructuredTextExtractor(): void
    {
        $resource = self::createStub(SystemResourceInterface::class);
        $resource->method('getMimeType')->willReturn('application/pdf');
        $expected = new ExtractedText('package text');
        $textExtractor = $this->createMock(StructuredTextExtractorInterface::class);
        $textExtractor->method('getSupportedMimeTypes')->willReturn(['application/pdf']);
        $textExtractor->expects($this->once())->method('extractStructuredText')->with($resource)->willReturn($expected);

        self::assertSame($expected, new TextExtractorRegistry([$textExtractor])->extract($resource));
    }

    #[Test]
    public function extractResolvesFileReferenceToItsOriginalFile(): void
    {
        $originalFile = self::createStub(File::class);
        $originalFile->method('getMimeType')->willReturn('application/pdf');
        $fileReference = self::createStub(FileReference::class);
        $fileReference->method('getOriginalFile')->willReturn($originalFile);
        $textExtractor = $this->createMock(StructuredTextExtractorInterface::class);
        $textExtractor->method('getSupportedMimeTypes')->willReturn(['application/pdf']);
        $textExtractor->expects($this->once())->method('extractStructuredText')->with($originalFile)->willReturn(new ExtractedText('text'));

        new TextExtractorRegistry([$textExtractor])->extract($fileReference);
    }

    #[Test]
    public function extractDoesNotHandSystemResourceToFileBasedTextExtractor(): void
    {
        $resource = self::createStub(SystemResourceInterface::class);
        $resource->method('getMimeType')->willReturn('text/plain');
        $textExtractor = $this->createMock(TextExtractorInterface::class);
        $textExtractor->expects($this->never())->method('canExtractText');

        self::assertNull(new TextExtractorRegistry([$textExtractor])->extract($resource));
    }

    #[Test]
    public function findTextExtractorSelectsStructuredTextExtractorByMimeType(): void
    {
        $file = self::createStub(File::class);
        $file->method('getMimeType')->willReturn('text/csv');
        $pdfExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $pdfExtractor->method('getSupportedMimeTypes')->willReturn(['application/pdf']);
        $csvExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $csvExtractor->method('getSupportedMimeTypes')->willReturn(['text/csv']);

        self::assertSame($csvExtractor, new TextExtractorRegistry([$pdfExtractor, $csvExtractor])->findTextExtractor($file));
    }

    #[Test]
    public function findTextExtractorResolvesContainerMimeTypeByFileExtension(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['application/zip']['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $file = self::createStub(File::class);
        $file->method('getMimeType')->willReturn('application/zip');
        $file->method('getExtension')->willReturn('DOCX');
        $zipExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $zipExtractor->method('getSupportedMimeTypes')->willReturn(['application/pdf']);
        $wordExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $wordExtractor->method('getSupportedMimeTypes')->willReturn(['application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

        self::assertSame($wordExtractor, new TextExtractorRegistry([$zipExtractor, $wordExtractor])->findTextExtractor($file));
    }

    #[Test]
    public function extractHandsFileWithContainerMimeTypeToExtractorOfMappedMimeType(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['application/zip']['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $file = self::createStub(File::class);
        $file->method('getMimeType')->willReturn('application/zip');
        $file->method('getExtension')->willReturn('docx');
        $expected = new ExtractedText('word text');
        $wordExtractor = $this->createMock(StructuredTextExtractorInterface::class);
        $wordExtractor->method('getSupportedMimeTypes')->willReturn(['application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
        $wordExtractor->expects($this->once())->method('extractStructuredText')->with($file)->willReturn($expected);

        self::assertSame($expected, new TextExtractorRegistry([$wordExtractor])->extract($file));
    }

    #[Test]
    public function findTextExtractorPrefersExtractorOfStoredMimeType(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['application/zip']['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $file = self::createStub(File::class);
        $file->method('getMimeType')->willReturn('application/zip');
        $file->method('getExtension')->willReturn('docx');
        $wordExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $wordExtractor->method('getSupportedMimeTypes')->willReturn(['application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
        $zipExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $zipExtractor->method('getSupportedMimeTypes')->willReturn(['application/zip']);

        self::assertSame($zipExtractor, new TextExtractorRegistry([$wordExtractor, $zipExtractor])->findTextExtractor($file));
    }

    #[Test]
    public function findTextExtractorDoesNotResolveContainerMimeTypeOfUnknownFileExtension(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['application/zip']['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $file = self::createStub(File::class);
        $file->method('getMimeType')->willReturn('application/zip');
        $file->method('getExtension')->willReturn('zip');
        $wordExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $wordExtractor->method('getSupportedMimeTypes')->willReturn(['application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

        self::assertNull(new TextExtractorRegistry([$wordExtractor])->findTextExtractor($file));
    }

    #[Test]
    public function findTextExtractorReturnsFileBasedTextExtractorWithHigherPriority(): void
    {
        $file = self::createStub(File::class);
        $file->method('getMimeType')->willReturn('application/pdf');
        $fileBasedExtractor = self::createStub(TextExtractorInterface::class);
        $fileBasedExtractor->method('canExtractText')->willReturn(true);
        $structuredExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $structuredExtractor->method('getSupportedMimeTypes')->willReturn(['application/pdf']);

        self::assertSame($fileBasedExtractor, new TextExtractorRegistry([$fileBasedExtractor, $structuredExtractor])->findTextExtractor($file));
    }

    #[Test]
    public function findTextExtractorReturnsStructuredTextExtractorWithHigherPriority(): void
    {
        $file = self::createStub(File::class);
        $file->method('getMimeType')->willReturn('application/pdf');
        $structuredExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $structuredExtractor->method('getSupportedMimeTypes')->willReturn(['application/pdf']);
        $fileBasedExtractor = $this->createMock(TextExtractorInterface::class);
        $fileBasedExtractor->expects($this->never())->method('canExtractText');

        self::assertSame($structuredExtractor, new TextExtractorRegistry([$structuredExtractor, $fileBasedExtractor])->findTextExtractor($file));
    }

    #[Test]
    public function findTextExtractorPrefersFileBasedTextExtractorOverResolvedContainerMimeType(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility']['application/zip']['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $file = self::createStub(File::class);
        $file->method('getMimeType')->willReturn('application/zip');
        $file->method('getExtension')->willReturn('docx');
        $wordExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $wordExtractor->method('getSupportedMimeTypes')->willReturn(['application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
        $fileBasedExtractor = self::createStub(TextExtractorInterface::class);
        $fileBasedExtractor->method('canExtractText')->willReturn(true);

        self::assertSame($fileBasedExtractor, new TextExtractorRegistry([$wordExtractor, $fileBasedExtractor])->findTextExtractor($file));
    }

    #[Test]
    public function extractUsesFileBasedTextExtractorWithHigherPriority(): void
    {
        $file = self::createStub(File::class);
        $file->method('getMimeType')->willReturn('application/pdf');
        $fileBasedExtractor = self::createStub(TextExtractorInterface::class);
        $fileBasedExtractor->method('canExtractText')->willReturn(true);
        $fileBasedExtractor->method('extractText')->willReturn('legacy text');
        $structuredExtractor = $this->createMock(StructuredTextExtractorInterface::class);
        $structuredExtractor->method('getSupportedMimeTypes')->willReturn(['application/pdf']);
        $structuredExtractor->expects($this->never())->method('extractStructuredText');

        self::assertEquals(
            new ExtractedText('legacy text'),
            new TextExtractorRegistry([$fileBasedExtractor, $structuredExtractor])->extract($file)
        );
    }

    #[Test]
    public function extractReturnsNullIfNoTextExtractorMatches(): void
    {
        $textExtractor = self::createStub(TextExtractorInterface::class);
        $textExtractor->method('canExtractText')->willReturn(false);

        self::assertNull(new TextExtractorRegistry([$textExtractor])->extract(self::createStub(File::class)));
    }

    #[Test]
    public function getSupportedFileExtensionsReturnsExtensionsOfSupportedMimeTypes(): void
    {
        $registry = new TextExtractorRegistry($this->createExtractorsForSupportedTypes());

        self::assertSame(['7z', 'odt', 'pdf'], $registry->getSupportedFileExtensions());
    }

    /**
     * @return list<TextExtractorInterface|StructuredTextExtractorInterface>
     */
    private function createExtractorsForSupportedTypes(): array
    {
        $pdfExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $pdfExtractor->method('getSupportedMimeTypes')->willReturn(['application/pdf']);
        $officeExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $officeExtractor->method('getSupportedMimeTypes')->willReturn(['application/vnd.oasis.opendocument.text', 'application/pdf', 'application/x-7z-compressed']);
        $unavailableExtractor = self::createStub(StructuredTextExtractorInterface::class);
        $unavailableExtractor->method('getSupportedMimeTypes')->willReturn([]);
        $plainExtractor = self::createStub(TextExtractorInterface::class);
        return [$pdfExtractor, $officeExtractor, $unavailableExtractor, $plainExtractor];
    }
}
