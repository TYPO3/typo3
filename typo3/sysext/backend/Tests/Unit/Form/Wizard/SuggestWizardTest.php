<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\Wizard;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\Wizard\SuggestWizard;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Test case
 */
class SuggestWizardTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderSuggestSelectorThrowsExceptionIfFlexFieldDoesNotContainDataStructureIdentifier()
    {
        $viewProphecy = $this->prophesize(StandaloneView::class);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478604742);
        (new SuggestWizard($viewProphecy->reveal()))->renderSuggestSelector(
            'aFieldName',
            'aTable',
            'aField',
            ['uid' => 42],
            [],
            [
                'config' => [
                        'type' => 'flex',
                        // there should be a 'dataStructureIdentifier' here
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function searchActionThrowsExceptionWithMissingArgument()
    {
        $viewProphecy = $this->prophesize(StandaloneView::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getParsedBody()->willReturn([
            'value' => 'theSearchValue',
            'table' => 'aTable',
            'field' => 'aField',
            'uid' => 'aUid',
            'dataStructureIdentifier' => 'anIdentifier',
            // hmac missing
        ]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478607036);
        (new SuggestWizard($viewProphecy->reveal()))
            ->searchAction($serverRequestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function searchActionThrowsExceptionWithWrongHmac()
    {
        $viewProphecy = $this->prophesize(StandaloneView::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getParsedBody()->willReturn([
            'value' => 'theSearchValue',
            'table' => 'aTable',
            'field' => 'aField',
            'uid' => 'aUid',
            'dataStructureIdentifier' => 'anIdentifier',
            'hmac' => 'wrongHmac'
        ]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478608245);
        (new SuggestWizard($viewProphecy->reveal()))
            ->searchAction($serverRequestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function getFieldConfigurationFetchesConfigurationDependentOnTheFullPathToField()
    {
        $config = [
            'el' => [
                'content' => [
                    'TCEforms' => [
                        'config' => [
                            'Sublevel field configuration',
                        ],
                    ],
                ],
            ],
        ];

        $dataStructure['sheets']['sSuggestCheckCombination']['ROOT']['el'] = [
            'settings.topname1' => [
                'el' => [
                    'item' => [
                        'el' => [
                            'content' => [
                                'TCEforms' => [
                                    'config' => [
                                        'different foo config for field with same name',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'settings.topname3' => [
                'el' => ['item' => $config]
            ],
            'settings.topname2' => [
                'el' => [
                    'item' => [
                        'el' => [
                            'content' => [
                                'TCEforms' => [
                                    'config' => [
                                        'different foo config for field with same name',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $parts = [
            0 => 'flex_1',
            1 => 'data',
            2 => 'sSuggestCheckCombination',
            3 => 'lDEF',
            4 => 'settings.topname3',
            5 => 'el',
            6 => 'ID-efa3ff7ed5-idx1460636854058-form',
            7 => 'item',
            8 => 'el',
            9 => 'content',
            10 => 'vDEF',
        ];

        /** @var SuggestWizard|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getAccessibleMock(SuggestWizard::class, ['getNestedDsFieldConfig'], [], '', false);
        $subject
            ->expects($this->once())
            ->method('getNestedDsFieldConfig')
            ->with($config, 'content');
        $subject->_call('getFieldConfiguration', $parts, $dataStructure);
    }

    /**
     * @test
     */
    public function getFieldConfigurationFetchesConfigurationForFieldsWithoutSheets()
    {
        $config = [
            'ROOT' => [
                'type' => 'array',
                'el' => [
                    'content' => [
                        'TCEforms' => [
                            'label' => 'group_db_1 wizard suggest',
                            'config' => [
                                'type' => 'group',
                                'internal_type' => 'db',
                                'allowed' => 'tx_styleguide_staticdata',
                                'wizards' => [
                                    'suggest' => [
                                        'type' => 'suggest',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ];
        $dataStructure = [
            'sheets' => [
                'sDEF' => $config
            ],
        ];
        $parts = [
            0 => 'flex_1',
            1 => 'data',
            2 => 'sDEF',
            3 => 'lDEF',
            4 => 'content',
            5 => 'vDEF',
        ];

        /** @var SuggestWizard|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getAccessibleMock(SuggestWizard::class, ['getNestedDsFieldConfig'], [], '', false);
        $subject
            ->expects($this->once())
            ->method('getNestedDsFieldConfig')
            ->with($config, 'content');

        $subject->_call('getFieldConfiguration', $parts, $dataStructure);
    }

    /**
     * @test
     * @dataProvider isTableHiddenIsProperlyRetrievedDataProvider
     */
    public function isTableHiddenIsProperlyRetrieved($expected, $array)
    {
        $subject = $this->getAccessibleMock(SuggestWizard::class, ['dummy'], [], '', false);
        $this->assertEquals($expected, $subject->_call('isTableHidden', $array));
    }

    public function isTableHiddenIsProperlyRetrievedDataProvider()
    {
        return [
          'notSetValue' => [false, ['ctrl' => ['hideTable' => null]]],
          'true' => [true, ['ctrl' => ['hideTable' => true]]],
          'false' => [false, ['ctrl' => ['hideTable' => false]]],
          'string with true' => [true, ['ctrl' => ['hideTable' => '1']]],
          'string with false' => [false, ['ctrl' => ['hideTable' => '0']]],
        ];
    }
}
