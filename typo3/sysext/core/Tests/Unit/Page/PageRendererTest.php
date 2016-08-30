<?php
namespace TYPO3\CMS\Core\Tests\Unit\Page;

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

use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Unit test case
 *
 * @see According functional test case
 */
class PageRendererTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function renderMethodCallsResetInAnyCase()
    {
        $pageRenderer = $this->getMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['reset', 'prepareRendering', 'renderJavaScriptAndCss', 'getPreparedMarkerArray', 'getTemplateForPart']);
        $pageRenderer->expects($this->exactly(3))->method('reset');

        $pageRenderer->render(PageRenderer::PART_COMPLETE);
        $pageRenderer->render(PageRenderer::PART_HEADER);
        $pageRenderer->render(PageRenderer::PART_FOOTER);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @test
     */
    public function includingNotAvailableLocalJqueryVersionThrowsException()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['dummy'], [], '', false);
        $subject->_set('availableLocalJqueryVersions', ['1.1.1']);
        $subject->loadJquery('2.2.2');
    }

    /**
     * @expectedException \UnexpectedValueException
     * @test
     */
    public function includingJqueryWithNonAlphnumericNamespaceThrowsException()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['dummy'], [], '', false);
        $subject->loadJquery(null, null, '12sd.12fsd');
        $subject->render();
    }

    /**
     * @test
     */
    public function addBodyContentAddsContent()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['dummy'], [], '', false);
        $expectedReturnValue = 'ABCDE';
        $subject->addBodyContent('A');
        $subject->addBodyContent('B');
        $subject->addBodyContent('C');
        $subject->addBodyContent('D');
        $subject->addBodyContent('E');
        $out = $subject->getBodyContent();
        $this->assertEquals($expectedReturnValue, $out);
    }

    /**
     * @test
     */
    public function addInlineLanguageLabelFileSetsInlineLanguageLabelFiles()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['dummy'], [], '', false);
        $fileReference = $this->getUniqueId('file_');
        $selectionPrefix = $this->getUniqueId('prefix_');
        $stripFromSelectionName = $this->getUniqueId('strip_');
        $errorMode = 0;

        $expectedInlineLanguageLabelFile = [
            'fileRef' => $fileReference,
            'selectionPrefix' => $selectionPrefix,
            'stripFromSelectionName' => $stripFromSelectionName,
            'errorMode' => $errorMode
        ];

        $subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName, $errorMode);
        $actualResult = $subject->getInlineLanguageLabelFiles();

        $this->assertSame($expectedInlineLanguageLabelFile, array_pop($actualResult));
    }

    /**
     * @test
     */
    public function addInlineLanguageLabelFileSetsTwoDifferentInlineLanguageLabelFiles()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['dummy'], [], '', false);
        $fileReference1 = $this->getUniqueId('file1_');
        $selectionPrefix1 = $this->getUniqueId('prefix1_');
        $stripFromSelectionName1 = $this->getUniqueId('strip1_');
        $errorMode1 = 0;
        $expectedInlineLanguageLabelFile1 = [
            'fileRef' => $fileReference1,
            'selectionPrefix' => $selectionPrefix1,
            'stripFromSelectionName' => $stripFromSelectionName1,
            'errorMode' => $errorMode1
        ];
        $fileReference2 = $this->getUniqueId('file2_');
        $selectionPrefix2 = $this->getUniqueId('prefix2_');
        $stripFromSelectionName2 = $this->getUniqueId('strip2_');
        $errorMode2 = 0;
        $expectedInlineLanguageLabelFile2 = [
            'fileRef' => $fileReference2,
            'selectionPrefix' => $selectionPrefix2,
            'stripFromSelectionName' => $stripFromSelectionName2,
            'errorMode' => $errorMode2
        ];

        $subject->addInlineLanguageLabelFile($fileReference1, $selectionPrefix1, $stripFromSelectionName1, $errorMode1);
        $subject->addInlineLanguageLabelFile($fileReference2, $selectionPrefix2, $stripFromSelectionName2, $errorMode2);
        $actualResult = $subject->getInlineLanguageLabelFiles();

        $this->assertSame($expectedInlineLanguageLabelFile2, array_pop($actualResult));
        $this->assertSame($expectedInlineLanguageLabelFile1, array_pop($actualResult));
    }

    /**
     * @test
     */
    public function addInlineLanguageLabelFileDoesNotSetSameLanguageFileTwice()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['dummy'], [], '', false);
        $fileReference = $this->getUniqueId('file2_');
        $selectionPrefix = $this->getUniqueId('prefix2_');
        $stripFromSelectionName = $this->getUniqueId('strip2_');
        $errorMode = 0;

        $subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName, $errorMode);
        $subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName, $errorMode);
        $this->assertSame(1, count($subject->getInlineLanguageLabelFiles()));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function includeLanguageFileForInlineThrowsExceptionIfLangIsNotSet()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['dummy'], [], '', false);
        $subject->_set('charSet', 'utf-8');
        $subject->_call('includeLanguageFileForInline', 'someLLFile.xml');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function includeLanguageFileForInlineThrowsExceptionIfCharSetIsNotSet()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['dummy'], [], '', false);
        $subject->_set('lang', 'default');
        $subject->_call('includeLanguageFileForInline', 'someLLFile.xml');
    }

    /**
     * @test
     */
    public function includeLanguageFileForInlineDoesNotAddToInlineLanguageLabelsIfFileCouldNotBeRead()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['readLLfile'], [], '', false);
        $subject->_set('lang', 'default');
        $subject->_set('charSet', 'utf-8');
        $subject->_set('inlineLanguageLabels', []);
        $subject->method('readLLfile')->willReturn(false);
        $subject->_call('includeLanguageFileForInline', 'someLLFile.xml');
        $this->assertEquals([], $subject->_get('inlineLanguageLabels'));
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
        /** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Page\PageRenderer::class, ['readLLfile'], [], '', false);
        $subject->_set('lang', 'default');
        $subject->_set('charSet', 'utf-8');
        $subject->_set('inlineLanguageLabels', []);
        $subject->method('readLLfile')->willReturn($llFileContent);
        $subject->_call('includeLanguageFileForInline', 'someLLFile.xml', $selectionPrefix, $stripFromSelectionName);
        $this->assertEquals($expectation, $subject->_get('inlineLanguageLabels'));
    }
}
