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

use PHPUnit\Framework\Attributes\Test;
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
        $importMapFactoryStub = self::createStub(ImportMapFactory::class);
        $importMapFactoryStub->method('create')->willReturn($importMapMock);
        GeneralUtility::setSingletonInstance(ImportMapFactory::class, $importMapFactoryStub);
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
        $subjectPropertyReflection = (new \ReflectionProperty($subject, 'bodyContent'));
        self::assertEquals($expectedReturnValue, $subjectPropertyReflection->getValue($subject));
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
        $subjectPropertyReflection = (new \ReflectionProperty($subject, 'inlineLanguageLabelFiles'));
        $actualResult = $subjectPropertyReflection->getValue($subject);

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
        $subjectPropertyReflection = (new \ReflectionProperty($subject, 'inlineLanguageLabelFiles'));
        $actualResult = $subjectPropertyReflection->getValue($subject);

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
        $subjectPropertyReflection = (new \ReflectionProperty($subject, 'inlineLanguageLabelFiles'));
        self::assertCount(1, $subjectPropertyReflection->getValue($subject));
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
