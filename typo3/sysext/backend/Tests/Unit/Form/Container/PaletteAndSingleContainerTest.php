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

use TYPO3\CMS\Backend\Form\Container\PaletteAndSingleContainer;
use TYPO3\CMS\Backend\Form\Container\SingleFieldContainer;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PaletteAndSingleContainerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderUsesPaletteLabelFromFieldArray(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $singleFieldContainerMock = $this->createMock(SingleFieldContainer::class);
        $singleFieldContainerReturn = [
            'additionalJavaScriptPost' => [],
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
            'html' => 'aFieldRenderedHtml',
        ];
        $singleFieldContainerMock->expects(self::atLeastOnce())->method('render')->withAnyParameters()->willReturn($singleFieldContainerReturn);

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
        $languageService->method('sL')->withConsecutive([$labelReference], [''])->willReturnArgument(0);

        $expectedChildDataArray = $input;
        $expectedChildDataArray['renderType'] = 'singleFieldContainer';
        $expectedChildDataArray['fieldName'] = 'aField';

        $nodeFactoryMock->method('create')->with($expectedChildDataArray)->willReturn($singleFieldContainerMock);
        $containerResult = (new PaletteAndSingleContainer($nodeFactoryMock, $input))->render();
        // Expect label is in answer HTML
        self::assertStringContainsString($labelReference, $containerResult['html']);
    }

    /**
     * @test
     */
    public function renderUsesPaletteValuesFromPaletteArray(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $singleFieldContainerMock = $this->createMock(SingleFieldContainer::class);
        $singleFieldContainerReturn = [
            'additionalJavaScriptPost' => [],
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
            'html' => 'aFieldRenderedHtml',
        ];
        $singleFieldContainerMock->expects(self::atLeastOnce())->method('render')->withAnyParameters()->willReturn($singleFieldContainerReturn);

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
        $languageService->method('sL')->withConsecutive([$labelReference], [$descriptionReference])->willReturnArgument(0);

        $expectedChildDataArray = $input;
        $expectedChildDataArray['renderType'] = 'singleFieldContainer';
        $expectedChildDataArray['fieldName'] = 'aField';

        $nodeFactoryMock->method('create')->with($expectedChildDataArray)->willReturn($singleFieldContainerMock);
        $containerResult = (new PaletteAndSingleContainer($nodeFactoryMock, $input))->render();
        // Expect label and description are in answer HTML
        self::assertStringContainsString($labelReference, $containerResult['html']);
        self::assertStringContainsString($descriptionReference, $containerResult['html']);
    }

    /**
     * @test
     */
    public function renderPrefersFieldArrayPaletteValuesOverPaletteValues(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $singleFieldContainerMock = $this->createMock(SingleFieldContainer::class);
        $singleFieldContainerReturn = [
            'additionalJavaScriptPost' => [],
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
            'html' => 'aFieldRenderedHtml',
        ];
        $singleFieldContainerMock->expects(self::atLeastOnce())->method('render')->withAnyParameters()->willReturn($singleFieldContainerReturn);

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
        $languageService->method('sL')->withConsecutive(
            [$labelReferenceFieldArray],
            [$descriptionReferencePaletteArray]
        )->willReturnArgument(0);

        $expectedChildDataArray = $input;
        $expectedChildDataArray['renderType'] = 'singleFieldContainer';
        $expectedChildDataArray['fieldName'] = 'aField';

        $nodeFactoryMock->method('create')->with($expectedChildDataArray)->willReturn($singleFieldContainerMock);
        $containerResult = (new PaletteAndSingleContainer($nodeFactoryMock, $input))->render();
        // Expect label and description are in answer HTML
        self::assertStringContainsString($labelReferenceFieldArray, $containerResult['html']);
        self::assertStringContainsString($descriptionReferencePaletteArray, $containerResult['html']);
    }
}
