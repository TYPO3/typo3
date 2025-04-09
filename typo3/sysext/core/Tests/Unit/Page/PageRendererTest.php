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

namespace TYPO3\CMS\Core\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Page\ImportMap;
use TYPO3\CMS\Core\Page\ImportMapFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PageRendererTest extends UnitTestCase
{
    use PageRendererFactoryTrait;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $importMapMock = $this->createMock(ImportMap::class);
        $importMapMock->method('render')
            ->with(self::isString(), self::isInstanceOf(ConsumableNonce::class))
            ->willReturn('');
        $importMapFactoryMock = $this->createMock(ImportMapFactory::class);
        $importMapFactoryMock->method('create')->willReturn($importMapMock);
        GeneralUtility::setSingletonInstance(ImportMapFactory::class, $importMapFactoryMock);
    }

    #[Test]
    public function renderMethodCallsResetInAnyCase(): void
    {
        $pageRenderer = $this->getMockBuilder(PageRenderer::class)
            ->setConstructorArgs($this->getPageRendererConstructorArgs())
            ->onlyMethods(['reset', 'prepareRendering', 'renderJavaScriptAndCss', 'getPreparedMarkerArray', 'getTemplate'])
            ->getMock();

        $pageRenderer->expects(self::once())->method('reset');
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer);
        $pageRenderer->render();
    }

    #[Test]
    public function addBodyContentAddsContent(): void
    {
        $subject = $this->getAccessibleMock(PageRenderer::class, null, [], '', false);
        $expectedReturnValue = 'ABCDE';
        $subject->addBodyContent('A');
        $subject->addBodyContent('B');
        $subject->addBodyContent('C');
        $subject->addBodyContent('D');
        $subject->addBodyContent('E');
        $out = $subject->getBodyContent();
        self::assertEquals($expectedReturnValue, $out);
    }

    #[Test]
    public function addInlineLanguageLabelFileSetsInlineLanguageLabelFiles(): void
    {
        $subject = $this->getAccessibleMock(PageRenderer::class, null, [], '', false);
        $fileReference = StringUtility::getUniqueId('file_');
        $selectionPrefix = StringUtility::getUniqueId('prefix_');
        $stripFromSelectionName = StringUtility::getUniqueId('strip_');

        $expectedInlineLanguageLabelFile = [
            'fileRef' => $fileReference,
            'selectionPrefix' => $selectionPrefix,
            'stripFromSelectionName' => $stripFromSelectionName,
        ];

        $subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName);
        $actualResult = $subject->getInlineLanguageLabelFiles();

        self::assertSame($expectedInlineLanguageLabelFile, array_pop($actualResult));
    }

    #[Test]
    public function addInlineLanguageLabelFileSetsTwoDifferentInlineLanguageLabelFiles(): void
    {
        $subject = $this->getAccessibleMock(PageRenderer::class, null, [], '', false);
        $fileReference1 = StringUtility::getUniqueId('file1_');
        $selectionPrefix1 = StringUtility::getUniqueId('prefix1_');
        $stripFromSelectionName1 = StringUtility::getUniqueId('strip1_');
        $expectedInlineLanguageLabelFile1 = [
            'fileRef' => $fileReference1,
            'selectionPrefix' => $selectionPrefix1,
            'stripFromSelectionName' => $stripFromSelectionName1,
        ];
        $fileReference2 = StringUtility::getUniqueId('file2_');
        $selectionPrefix2 = StringUtility::getUniqueId('prefix2_');
        $stripFromSelectionName2 = StringUtility::getUniqueId('strip2_');
        $expectedInlineLanguageLabelFile2 = [
            'fileRef' => $fileReference2,
            'selectionPrefix' => $selectionPrefix2,
            'stripFromSelectionName' => $stripFromSelectionName2,
        ];

        $subject->addInlineLanguageLabelFile($fileReference1, $selectionPrefix1, $stripFromSelectionName1);
        $subject->addInlineLanguageLabelFile($fileReference2, $selectionPrefix2, $stripFromSelectionName2);
        $actualResult = $subject->getInlineLanguageLabelFiles();

        self::assertSame($expectedInlineLanguageLabelFile2, array_pop($actualResult));
        self::assertSame($expectedInlineLanguageLabelFile1, array_pop($actualResult));
    }

    #[Test]
    public function addInlineLanguageLabelFileDoesNotSetSameLanguageFileTwice(): void
    {
        $subject = $this->getAccessibleMock(PageRenderer::class, null, [], '', false);
        $fileReference = StringUtility::getUniqueId('file2_');
        $selectionPrefix = StringUtility::getUniqueId('prefix2_');
        $stripFromSelectionName = StringUtility::getUniqueId('strip2_');

        $subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName);
        $subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName);
        self::assertCount(1, $subject->getInlineLanguageLabelFiles());
    }

    #[Test]
    public function includeLanguageFileForInlineDoesNotAddToInlineLanguageLabelsIfFileCouldNotBeRead(): void
    {
        $subject = $this->getAccessibleMock(PageRenderer::class, ['readLLfile'], [], '', false);
        $subject->setLanguage(new Locale());
        $subject->method('readLLfile')->willReturn([]);
        $subject->_call('includeLanguageFileForInline', 'someLLFile.xml');
        self::assertEquals([], $subject->_get('inlineLanguageLabels'));
    }

    public static function includeLanguageFileForInlineAddsProcessesLabelsToInlineLanguageLabelsProvider(): array
    {
        $llFileContent = [
            'inline_label_first_Key' => 'first',
            'inline_label_second_Key' => 'second',
            'thirdKey' => 'third',
        ];
        return [
            'No processing' => [
                $llFileContent,
                '',
                '',
                $llFileContent,
            ],
            'Respect $selectionPrefix' => [
                $llFileContent,
                'inline_',
                '',
                [
                    'inline_label_first_Key' => 'first',
                    'inline_label_second_Key' => 'second',
                ],
            ],
            'Respect $stripFromSelectionName' => [
                $llFileContent,
                '',
                'inline_',
                [
                    'label_first_Key' => 'first',
                    'label_second_Key' => 'second',
                    'thirdKey' => 'third',
                ],
            ],
            'Respect $selectionPrefix and $stripFromSelectionName' => [
                $llFileContent,
                'inline_',
                'inline_label_',
                [
                    'first_Key' => 'first',
                    'second_Key' => 'second',
                ],
            ],
        ];
    }

    #[DataProvider('includeLanguageFileForInlineAddsProcessesLabelsToInlineLanguageLabelsProvider')]
    #[Test]
    public function includeLanguageFileForInlineAddsProcessesLabelsToInlineLanguageLabels(array $llFileContent, string $selectionPrefix, string $stripFromSelectionName, array $expectation): void
    {
        $subject = $this->getAccessibleMock(PageRenderer::class, ['readLLfile'], [], '', false);
        $subject->setLanguage(new Locale());
        $subject->method('readLLfile')->willReturn($llFileContent);
        $subject->_call('includeLanguageFileForInline', 'someLLFile.xml', $selectionPrefix, $stripFromSelectionName);
        self::assertEquals($expectation, $subject->_get('inlineLanguageLabels'));
    }

    #[Test]
    public function getAddedMetaTag(): void
    {
        $subject = $this->getMockBuilder(PageRenderer::class)
            ->setConstructorArgs($this->getPageRendererConstructorArgs())
            ->onlyMethods([])
            ->getMock();
        $subject->setMetaTag('nAme', 'Author', 'foobar');
        $actualResult = $subject->getMetaTag('naMe', 'AUTHOR');
        $expectedResult = [
            'type' => 'name',
            'name' => 'author',
            'content' => 'foobar',
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function overrideMetaTag(): void
    {
        $subject = $this->getMockBuilder(PageRenderer::class)
            ->setConstructorArgs($this->getPageRendererConstructorArgs())
            ->onlyMethods([])
            ->getMock();
        $subject->setMetaTag('nAme', 'Author', 'Axel Foley');
        $subject->setMetaTag('nAme', 'Author', 'foobar');
        $actualResult = $subject->getMetaTag('naMe', 'AUTHOR');
        $expectedResult = [
            'type' => 'name',
            'name' => 'author',
            'content' => 'foobar',
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function unsetAddedMetaTag(): void
    {
        $subject = $this->getMockBuilder(PageRenderer::class)
            ->setConstructorArgs($this->getPageRendererConstructorArgs())
            ->onlyMethods([])
            ->getMock();
        $subject->setMetaTag('nAme', 'Author', 'foobar');
        $subject->removeMetaTag('naMe', 'AUTHOR');
        $actualResult = $subject->getMetaTag('naMe', 'AUTHOR');
        $expectedResult = [];
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function parseLanguageLabelsForJavaScriptReturnsEmptyStringIfEmpty(): void
    {
        $subject = $this->getAccessibleMock(PageRenderer::class, null, [], '', false);
        $inlineLanguageLabels = [];
        $subject->_set('inlineLanguageLabels', $inlineLanguageLabels);
        $actual = $subject->_call('parseLanguageLabelsForJavaScript');
        self::assertEmpty($actual);
    }

    #[Test]
    public function parseLanguageLabelsForJavaScriptReturnsFlatArray(): void
    {
        $subject = $this->getAccessibleMock(PageRenderer::class, null, [], '', false);
        $inlineLanguageLabels = [
            'key' => 'label',
            'foo' => 'bar',
            'husel' => [
                [
                    'source' => 'pusel',
                ],
            ],
            'hello' => [
                [
                    'source' => 'world',
                    'target' => 'welt',
                ],
            ],
        ];
        $subject->_set('inlineLanguageLabels', $inlineLanguageLabels);
        $expected = [
            'key' => 'label',
            'foo' => 'bar',
            'husel' => 'pusel',
            'hello' => 'welt',
        ];
        $actual = $subject->_call('parseLanguageLabelsForJavaScript');
        self::assertSame($expected, $actual);
    }
}
