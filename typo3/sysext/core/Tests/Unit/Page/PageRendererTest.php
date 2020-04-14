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

namespace TYPO3\CMS\Core\Tests\Unit\Page;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PageRendererTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderMethodCallsResetInAnyCase()
    {
        $this->resetSingletonInstances = true;
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);

        $pageRenderer = $this->getMockBuilder(PageRenderer::class)
            ->setMethods(['reset', 'prepareRendering', 'renderJavaScriptAndCss', 'getPreparedMarkerArray', 'getTemplateForPart', 'getTypoScriptFrontendController'])
            ->getMock();
        $pageRenderer->expects(self::any())->method('getTypoScriptFrontendController')->willReturn($tsfe->reveal());
        $pageRenderer->expects(self::exactly(3))->method('reset');

        $pageRenderer->render(PageRenderer::PART_COMPLETE);
        $pageRenderer->render(PageRenderer::PART_HEADER);
        $pageRenderer->render(PageRenderer::PART_FOOTER);
    }

    /**
     * @test
     */
    public function addBodyContentAddsContent()
    {
        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $expectedReturnValue = 'ABCDE';
        $subject->addBodyContent('A');
        $subject->addBodyContent('B');
        $subject->addBodyContent('C');
        $subject->addBodyContent('D');
        $subject->addBodyContent('E');
        $out = $subject->getBodyContent();
        self::assertEquals($expectedReturnValue, $out);
    }

    /**
     * @test
     */
    public function addInlineLanguageLabelFileSetsInlineLanguageLabelFiles()
    {
        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $fileReference = StringUtility::getUniqueId('file_');
        $selectionPrefix = StringUtility::getUniqueId('prefix_');
        $stripFromSelectionName = StringUtility::getUniqueId('strip_');

        $expectedInlineLanguageLabelFile = [
            'fileRef' => $fileReference,
            'selectionPrefix' => $selectionPrefix,
            'stripFromSelectionName' => $stripFromSelectionName
        ];

        $subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName);
        $actualResult = $subject->getInlineLanguageLabelFiles();

        self::assertSame($expectedInlineLanguageLabelFile, array_pop($actualResult));
    }

    /**
     * @test
     */
    public function addInlineLanguageLabelFileSetsTwoDifferentInlineLanguageLabelFiles()
    {
        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $fileReference1 = StringUtility::getUniqueId('file1_');
        $selectionPrefix1 = StringUtility::getUniqueId('prefix1_');
        $stripFromSelectionName1 = StringUtility::getUniqueId('strip1_');
        $expectedInlineLanguageLabelFile1 = [
            'fileRef' => $fileReference1,
            'selectionPrefix' => $selectionPrefix1,
            'stripFromSelectionName' => $stripFromSelectionName1
        ];
        $fileReference2 = StringUtility::getUniqueId('file2_');
        $selectionPrefix2 = StringUtility::getUniqueId('prefix2_');
        $stripFromSelectionName2 = StringUtility::getUniqueId('strip2_');
        $expectedInlineLanguageLabelFile2 = [
            'fileRef' => $fileReference2,
            'selectionPrefix' => $selectionPrefix2,
            'stripFromSelectionName' => $stripFromSelectionName2
        ];

        $subject->addInlineLanguageLabelFile($fileReference1, $selectionPrefix1, $stripFromSelectionName1);
        $subject->addInlineLanguageLabelFile($fileReference2, $selectionPrefix2, $stripFromSelectionName2);
        $actualResult = $subject->getInlineLanguageLabelFiles();

        self::assertSame($expectedInlineLanguageLabelFile2, array_pop($actualResult));
        self::assertSame($expectedInlineLanguageLabelFile1, array_pop($actualResult));
    }

    /**
     * @test
     */
    public function addInlineLanguageLabelFileDoesNotSetSameLanguageFileTwice()
    {
        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $fileReference = StringUtility::getUniqueId('file2_');
        $selectionPrefix = StringUtility::getUniqueId('prefix2_');
        $stripFromSelectionName = StringUtility::getUniqueId('strip2_');

        $subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName);
        $subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName);
        self::assertSame(1, count($subject->getInlineLanguageLabelFiles()));
    }

    /**
     * @test
     */
    public function includeLanguageFileForInlineThrowsExceptionIfLangIsNotSet()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1284906026);

        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $subject->_set('charSet', 'utf-8');
        $subject->_call('includeLanguageFileForInline', 'someLLFile.xml');
    }

    /**
     * @test
     */
    public function includeLanguageFileForInlineThrowsExceptionIfCharSetIsNotSet()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1284906026);

        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $subject->_set('lang', 'default');
        $subject->_call('includeLanguageFileForInline', 'someLLFile.xml');
    }

    /**
     * @test
     */
    public function includeLanguageFileForInlineDoesNotAddToInlineLanguageLabelsIfFileCouldNotBeRead()
    {
        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['readLLfile'], [], '', false);
        $subject->_set('lang', 'default');
        $subject->_set('charSet', 'utf-8');
        $subject->_set('inlineLanguageLabels', []);
        $subject->method('readLLfile')->willReturn(false);
        $subject->_call('includeLanguageFileForInline', 'someLLFile.xml');
        self::assertEquals([], $subject->_get('inlineLanguageLabels'));
    }

    /**
     * @return array
     */
    public function includeLanguageFileForInlineAddsProcessesLabelsToInlineLanguageLabelsProvider()
    {
        $llFileContent = [
            'default' => [
                'inline_label_first_Key' => 'first',
                'inline_label_second_Key' => 'second',
                'thirdKey' => 'third'
            ]
        ];
        return [
            'No processing' => [
                $llFileContent,
                '',
                '',
                $llFileContent['default']
            ],
            'Respect $selectionPrefix' => [
                $llFileContent,
                'inline_',
                '',
                [
                    'inline_label_first_Key' => 'first',
                    'inline_label_second_Key' => 'second'
                ]
            ],
            'Respect $stripFromSelectionName' => [
                $llFileContent,
                '',
                'inline_',
                [
                    'label_first_Key' => 'first',
                    'label_second_Key' => 'second',
                    'thirdKey' => 'third'
                ]
            ],
            'Respect $selectionPrefix and $stripFromSelectionName' => [
                $llFileContent,
                'inline_',
                'inline_label_',
                [
                    'first_Key' => 'first',
                    'second_Key' => 'second'
                ]
            ],
        ];
    }

    /**
     * @dataProvider includeLanguageFileForInlineAddsProcessesLabelsToInlineLanguageLabelsProvider
     * @test
     */
    public function includeLanguageFileForInlineAddsProcessesLabelsToInlineLanguageLabels($llFileContent, $selectionPrefix, $stripFromSelectionName, $expectation)
    {
        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['readLLfile'], [], '', false);
        $subject->_set('lang', 'default');
        $subject->_set('charSet', 'utf-8');
        $subject->_set('inlineLanguageLabels', []);
        $subject->method('readLLfile')->willReturn($llFileContent);
        $subject->_call('includeLanguageFileForInline', 'someLLFile.xml', $selectionPrefix, $stripFromSelectionName);
        self::assertEquals($expectation, $subject->_get('inlineLanguageLabels'));
    }

    /**
     * @test
     */
    public function getAddedMetaTag()
    {
        $this->resetSingletonInstances = true;
        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['whatDoesThisDo']);
        $subject->setMetaTag('nAme', 'Author', 'foobar');
        $actualResult = $subject->getMetaTag('naMe', 'AUTHOR');
        $expectedResult = [
            'type' => 'name',
            'name' => 'author',
            'content' => 'foobar'
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function overrideMetaTag()
    {
        $this->resetSingletonInstances = true;
        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['whatDoesThisDo']);
        $subject->setMetaTag('nAme', 'Author', 'Axel Foley');
        $subject->setMetaTag('nAme', 'Author', 'foobar');
        $actualResult = $subject->getMetaTag('naMe', 'AUTHOR');
        $expectedResult = [
            'type' => 'name',
            'name' => 'author',
            'content' => 'foobar'
        ];
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function unsetAddedMetaTag()
    {
        $this->resetSingletonInstances = true;
        /** @var PageRenderer|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(PageRenderer::class, ['whatDoesThisDo']);
        $subject->setMetaTag('nAme', 'Author', 'foobar');
        $subject->removeMetaTag('naMe', 'AUTHOR');
        $actualResult = $subject->getMetaTag('naMe', 'AUTHOR');
        $expectedResult = [];
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function parseLanguageLabelsForJavaScriptReturnsEmptyStringIfEmpty()
    {
        $subject = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $inlineLanguageLabels = [];
        $subject->_set('inlineLanguageLabels', $inlineLanguageLabels);
        $actual = $subject->_call('parseLanguageLabelsForJavaScript');
        self::assertEmpty($actual);
    }

    /**
     * @test
     */
    public function parseLanguageLabelsForJavaScriptReturnsFlatArray()
    {
        $subject = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $inlineLanguageLabels = [
            'key' => 'label',
            'foo' => 'bar',
            'husel' => [
                [
                    'source' => 'pusel',
                ]
            ],
            'hello' => [
                [
                    'source' => 'world',
                    'target' => 'welt',
                ]
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
