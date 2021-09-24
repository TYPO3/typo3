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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Form\Container\PaletteAndSingleContainer;
use TYPO3\CMS\Backend\Form\Container\SingleFieldContainer;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PaletteAndSingleContainerTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function renderUsesPaletteLabelFromFieldArray(): void
    {
        $nodeFactoryProphecy = $this->prophesize(NodeFactory::class);
        $singleFieldContainerProphecy = $this->prophesize(SingleFieldContainer::class);
        $singleFieldContainerReturn = [
            'additionalJavaScriptPost' => [],
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'requireJsModules' => [],
            'inlineData' => [],
            'html' => 'aFieldRenderedHtml',
        ];
        $singleFieldContainerProphecy->render(Argument::cetera())->shouldBeCalled()->willReturn($singleFieldContainerReturn);

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

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $backendUserAuthentication = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthentication->reveal();
        $languageService->loadSingleTableDescription(Argument::cetera())->willReturn('');

        // Expect translation call to the label reference and empty description
        $languageService->sL($labelReference)->willReturnArgument(0);
        $languageService->sL('')->willReturnArgument(0);

        $expectedChildDataArray = $input;
        $expectedChildDataArray['renderType'] = 'singleFieldContainer';
        $expectedChildDataArray['fieldName'] = 'aField';

        $nodeFactoryProphecy->create($expectedChildDataArray)->willReturn($singleFieldContainerProphecy->reveal());
        $containerResult = (new PaletteAndSingleContainer($nodeFactoryProphecy->reveal(), $input))->render();
        // Expect label is in answer HTML
        self::assertStringContainsString($labelReference, $containerResult['html']);
    }

    /**
     * @test
     */
    public function renderUsesPaletteValuesFromPaletteArray(): void
    {
        $nodeFactoryProphecy = $this->prophesize(NodeFactory::class);
        $singleFieldContainerProphecy = $this->prophesize(SingleFieldContainer::class);
        $singleFieldContainerReturn = [
            'additionalJavaScriptPost' => [],
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'requireJsModules' => [],
            'inlineData' => [],
            'html' => 'aFieldRenderedHtml',
        ];
        $singleFieldContainerProphecy->render(Argument::cetera())->shouldBeCalled()->willReturn($singleFieldContainerReturn);

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

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $backendUserAuthentication = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthentication->reveal();
        $languageService->loadSingleTableDescription(Argument::cetera())->willReturn('');

        // Expect translation call to the label and description references
        $languageService->sL($labelReference)->willReturnArgument(0);
        $languageService->sL($descriptionReference)->willReturnArgument(0);

        $expectedChildDataArray = $input;
        $expectedChildDataArray['renderType'] = 'singleFieldContainer';
        $expectedChildDataArray['fieldName'] = 'aField';

        $nodeFactoryProphecy->create($expectedChildDataArray)->willReturn($singleFieldContainerProphecy->reveal());
        $containerResult = (new PaletteAndSingleContainer($nodeFactoryProphecy->reveal(), $input))->render();
        // Expect label and description are in answer HTML
        self::assertStringContainsString($labelReference, $containerResult['html']);
        self::assertStringContainsString($descriptionReference, $containerResult['html']);
    }

    /**
     * @test
     */
    public function renderPrefersFieldArrayPaletteValuesOverPaletteValues(): void
    {
        $nodeFactoryProphecy = $this->prophesize(NodeFactory::class);
        $singleFieldContainerProphecy = $this->prophesize(SingleFieldContainer::class);
        $singleFieldContainerReturn = [
            'additionalJavaScriptPost' => [],
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'requireJsModules' => [],
            'inlineData' => [],
            'html' => 'aFieldRenderedHtml',
        ];
        $singleFieldContainerProphecy->render(Argument::cetera())->shouldBeCalled()->willReturn($singleFieldContainerReturn);

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

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $backendUserAuthentication = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthentication->reveal();
        $languageService->loadSingleTableDescription(Argument::cetera())->willReturn('');

        // Expect translation call to the label and description references
        $languageService->sL($labelReferenceFieldArray)->willReturnArgument(0);
        $languageService->sL($descriptionReferencePaletteArray)->willReturnArgument(0);

        $expectedChildDataArray = $input;
        $expectedChildDataArray['renderType'] = 'singleFieldContainer';
        $expectedChildDataArray['fieldName'] = 'aField';

        $nodeFactoryProphecy->create($expectedChildDataArray)->willReturn($singleFieldContainerProphecy->reveal());
        $containerResult = (new PaletteAndSingleContainer($nodeFactoryProphecy->reveal(), $input))->render();
        // Expect label and description are in answer HTML
        self::assertStringContainsString($labelReferenceFieldArray, $containerResult['html']);
        self::assertStringContainsString($descriptionReferencePaletteArray, $containerResult['html']);
    }
}
