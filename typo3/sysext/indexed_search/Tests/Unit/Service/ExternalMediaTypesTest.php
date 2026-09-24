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

namespace TYPO3\CMS\IndexedSearch\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\TranslatorInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\StructuredTextExtractorInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\CMS\IndexedSearch\Service\ExternalMediaTypes;
use TYPO3\CMS\IndexedSearch\TextExtraction\ExternalToolConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExternalMediaTypesTest extends UnitTestCase
{
    #[Test]
    public function supportedExtensionsAreTheExtensionsOfTheRegisteredExtractorsWithoutIgnoredOnes(): void
    {
        $extractor = self::createStub(StructuredTextExtractorInterface::class);
        $extractor->method('getSupportedMimeTypes')->willReturn([
            'application/pdf',
            'text/csv',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
        $subject = $this->createSubject(['ignoreExtensions' => 'CSV, txt'], [$extractor]);

        self::assertSame(['docx', 'pdf'], $subject->getSupportedExtensions());
    }

    #[Test]
    public function supportedExtensionsAreEmptyWithoutExtractors(): void
    {
        self::assertSame([], $this->createSubject()->getSupportedExtensions());
    }

    public static function itemTypeDataProvider(): \Generator
    {
        yield 'htm is stored as html' => ['htm', 'html'];
        yield 'html stays' => ['html', 'html'];
        yield 'jpg is stored as jpeg' => ['jpg', 'jpeg'];
        yield 'jpeg stays' => ['jpeg', 'jpeg'];
        yield 'upper case is normalized' => ['PDF', 'pdf'];
        yield 'other extensions are kept' => ['docx', 'docx'];
    }

    #[DataProvider('itemTypeDataProvider')]
    #[Test]
    public function getItemTypeMapsExtensions(string $extension, string $expected): void
    {
        self::assertSame($expected, $this->createSubject()->getItemType($extension));
    }

    public static function iconDataProvider(): \Generator
    {
        yield 'pdf' => ['pdf', 'EXT:indexed_search/Resources/Public/Icons/FileTypes/pdf.gif'];
        yield 'htm' => ['htm', 'EXT:indexed_search/Resources/Public/Icons/FileTypes/html.gif'];
        yield 'jpeg' => ['jpeg', 'EXT:indexed_search/Resources/Public/Icons/FileTypes/jpg.gif'];
        yield 'dot has no icon' => ['dot', ''];
    }

    #[DataProvider('iconDataProvider')]
    #[Test]
    public function getIconReturnsIconPath(string $extension, string $expected): void
    {
        self::assertSame($expected, $this->createSubject()->getIcon($extension));
    }

    public static function labelDataProvider(): \Generator
    {
        yield 'pdf' => ['pdf', 'extension.PDF'];
        yield 'docx is a document' => ['docx', 'extension.DOC'];
        yield 'pptx is a presentation' => ['pptx', 'extension.PP'];
        yield 'xlsx is a spreadsheet' => ['xlsx', 'extension.XLS'];
        yield 'jpg is an image' => ['jpg', 'extension.images'];
        yield 'htm is html' => ['htm', 'extension.HTML'];
        yield 'txt' => ['txt', 'extension.TXT'];
        yield 'csv' => ['csv', 'extension.CSV'];
        yield 'xml' => ['xml', 'extension.XML'];
    }

    #[DataProvider('labelDataProvider')]
    #[Test]
    public function getLabelTranslatesTheLabelOfTheExtension(string $extension, string $expectedLabelKey): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('translate')
            ->with($expectedLabelKey, 'indexed_search.main', [$extension])
            ->willReturn('Translated');

        self::assertSame('Translated', $this->createSubject()->getLabel($extension, $translator));
    }

    #[Test]
    public function getLabelReturnsEmptyStringForUnknownExtension(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->never())->method('translate');

        self::assertSame('', $this->createSubject()->getLabel('unknown', $translator));
    }

    #[Test]
    public function getLabelReturnsEmptyStringIfNothingIsTranslated(): void
    {
        $translator = self::createStub(TranslatorInterface::class);
        $translator->method('translate')->willReturn(null);

        self::assertSame('', $this->createSubject()->getLabel('pdf', $translator));
    }

    #[Test]
    public function onlyPdfIsMultiPageItemType(): void
    {
        $subject = $this->createSubject();

        self::assertTrue($subject->isMultiPageItemType('pdf'));
        self::assertFalse($subject->isMultiPageItemType('docx'));
        self::assertFalse($subject->isMultiPageItemType('0'));
    }

    /**
     * @param list<StructuredTextExtractorInterface> $extractors
     */
    private function createSubject(array $configuration = [], array $extractors = []): ExternalMediaTypes
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn($configuration);
        return new ExternalMediaTypes(
            new TextExtractorRegistry($extractors),
            new ExternalToolConfiguration($extensionConfiguration),
        );
    }
}
