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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Container;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Container\PaletteAndSingleContainer;
use TYPO3\CMS\Backend\Form\Container\SingleFieldContainer;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PaletteAndSingleContainerTest extends UnitTestCase
{
    #[Test]
    public function renderUsesPaletteLabelFromFieldArray(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $singleFieldContainerMock = $this->createMock(SingleFieldContainer::class);
        $singleFieldContainerReturn = [
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
            'html' => 'aFieldRenderedHtml',
        ];
        $singleFieldContainerMock->expects($this->atLeastOnce())->method('render')->withAnyParameters()->willReturn($singleFieldContainerReturn);

        $labelReference = 'LLL:EXT:Resources/Private/Language/locallang.xlf:aLabel';
        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'columns' => [
                    'aField' => [],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'aField',
                    ],
                ],
            ],
            'fieldsArray' => [
                '--palette--;' . $labelReference . ';aPalette',
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $backendUserAuthentication = $this->createMock(BackendUserAuthentication::class);
        $backendUserAuthentication->method('shallDisplayDebugInformation')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUserAuthentication;

        // Expect translation call to the label reference and empty description
        $series = [
            $labelReference,
            '',
        ];
        $languageService->method('sL')->willReturnCallback(function (string $input) use (&$series): string {
            self::assertSame(array_shift($series), $input);
            return $input;
        });

        $expectedChildDataArray = $input;
        $expectedChildDataArray['renderType'] = 'singleFieldContainer';
        $expectedChildDataArray['fieldName'] = 'aField';

        $nodeFactoryMock->method('create')->with($expectedChildDataArray)->willReturn($singleFieldContainerMock);

        $subject = new PaletteAndSingleContainer();
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($input);
        $containerResult = $subject->render();

        // Expect label is in answer HTML
        self::assertStringContainsString($labelReference, $containerResult['html']);
    }

    #[Test]
    public function renderUsesPaletteValuesFromPaletteArray(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $singleFieldContainerMock = $this->createMock(SingleFieldContainer::class);
        $singleFieldContainerReturn = [
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
            'html' => 'aFieldRenderedHtml',
        ];
        $singleFieldContainerMock->expects($this->atLeastOnce())->method('render')->withAnyParameters()->willReturn($singleFieldContainerReturn);

        $labelReference = 'LLL:EXT:Resources/Private/Language/locallang.xlf:aLabel';
        $descriptionReference = 'LLL:EXT:Resources/Private/Language/locallang.xlf:aDescription';
        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'columns' => [
                    'aField' => [],
                ],
                'palettes' => [
                    'aPalette' => [
                        'label' => $labelReference,
                        'description' => $descriptionReference,
                        'showitem' => 'aField',
                    ],
                ],
            ],
            'fieldsArray' => [
                '--palette--;;aPalette',
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $backendUserAuthentication = $this->createMock(BackendUserAuthentication::class);
        $backendUserAuthentication->method('shallDisplayDebugInformation')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUserAuthentication;

        // Expect translation call to the label and description references
        $series = [
            $labelReference,
            $descriptionReference,
        ];
        $languageService->method('sL')->willReturnCallback(function (string $input) use (&$series): string {
            self::assertSame(array_shift($series), $input);
            return $input;
        });

        $expectedChildDataArray = $input;
        $expectedChildDataArray['renderType'] = 'singleFieldContainer';
        $expectedChildDataArray['fieldName'] = 'aField';

        $nodeFactoryMock->method('create')->with($expectedChildDataArray)->willReturn($singleFieldContainerMock);

        $subject = new PaletteAndSingleContainer();
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($input);
        $containerResult = $subject->render();

        // Expect label and description are in answer HTML
        self::assertStringContainsString($labelReference, $containerResult['html']);
        self::assertStringContainsString($descriptionReference, $containerResult['html']);
    }

    #[Test]
    public function renderPrefersFieldArrayPaletteValuesOverPaletteValues(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $singleFieldContainerMock = $this->createMock(SingleFieldContainer::class);
        $singleFieldContainerReturn = [
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
            'html' => 'aFieldRenderedHtml',
        ];
        $singleFieldContainerMock->expects($this->atLeastOnce())->method('render')->withAnyParameters()->willReturn($singleFieldContainerReturn);

        $labelReferenceFieldArray = 'LLL:EXT:Resources/Private/Language/locallang.xlf:aLabel';
        $labelReferencePaletteArray = 'LLL:EXT:Resources/Private/Language/locallang.xlf:aLabelPalette';
        $descriptionReferencePaletteArray = 'LLL:EXT:Resources/Private/Language/locallang.xlf:aDescriptionPalette';
        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'columns' => [
                    'aField' => [],
                ],
                'palettes' => [
                    'aPalette' => [
                        'label' => $labelReferencePaletteArray,
                        'description' => $descriptionReferencePaletteArray,
                        'showitem' => 'aField',
                    ],
                ],
            ],
            'fieldsArray' => [
                '--palette--;' . $labelReferenceFieldArray . ';aPalette',
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $backendUserAuthentication = $this->createMock(BackendUserAuthentication::class);
        $backendUserAuthentication->method('shallDisplayDebugInformation')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUserAuthentication;

        // Expect translation call to the label and description references
        $series = [
            $labelReferenceFieldArray,
            $descriptionReferencePaletteArray,
        ];
        $languageService->method('sL')->willReturnCallback(function (string $input) use (&$series): string {
            self::assertSame(array_shift($series), $input);
            return $input;
        });

        $expectedChildDataArray = $input;
        $expectedChildDataArray['renderType'] = 'singleFieldContainer';
        $expectedChildDataArray['fieldName'] = 'aField';

        $nodeFactoryMock->method('create')->with($expectedChildDataArray)->willReturn($singleFieldContainerMock);

        $subject = new PaletteAndSingleContainer();
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($input);
        $containerResult = $subject->render();

        // Expect label and description are in answer HTML
        self::assertStringContainsString($labelReferenceFieldArray, $containerResult['html']);
        self::assertStringContainsString($descriptionReferencePaletteArray, $containerResult['html']);
    }
}
