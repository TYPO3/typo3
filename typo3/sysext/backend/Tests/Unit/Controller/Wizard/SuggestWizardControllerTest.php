<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Controller\Wizard;

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
use TYPO3\CMS\Backend\Controller\Wizard\SuggestWizardController;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Test case
 */
class SuggestWizardControllerTest extends UnitTestCase
{
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
        (new SuggestWizardController($viewProphecy->reveal()))
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
        (new SuggestWizardController($viewProphecy->reveal()))
            ->searchAction($serverRequestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function getFlexFieldConfigurationThrowsExceptionIfSimpleFlexFieldIsNotFound()
    {
        $dataStructure = [
            'sheets' => [
                'sDb' => [
                    'ROOT' => [
                        'el' => [
                            'differentField' => [
                                'TCEforms' => [
                                    'config' => [
                                        'Sublevel field configuration',
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
            2 => 'sDb',
            3 => 'lDEF',
            4 => 'group_db_1',
            5 => 'vDEF',
        ];

        /** @var SuggestWizardController|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getAccessibleMock(SuggestWizardController::class, ['dummy'], [], '', false);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1480609491);
        $subject->_call('getFlexFieldConfiguration', $parts, $dataStructure);
    }

    /**
     * @test
     */
    public function getFlexFieldConfigurationThrowsExceptionIfSectionContainerFlexFieldIsNotFound()
    {
        $dataStructure = [
            'sheets' => [
                'sDb' => [
                    'ROOT' => [
                        'el' => [
                            'notTheFieldYouAreLookingFor' => [
                                'TCEforms' => [
                                    'config' => [
                                        'Sublevel field configuration',
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
            4 => 'settings.subelements',
            5 => 'el',
            6 => '1',
            7 => 'item',
            8 => 'el',
            9 => 'content',
            10 => 'vDEF',
        ];

        /** @var SuggestWizardController|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getAccessibleMock(SuggestWizardController::class, ['dummy'], [], '', false);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1480611208);
        $subject->_call('getFlexFieldConfiguration', $parts, $dataStructure);
    }

    /**
     * @test
     */
    public function getFlexFieldConfigurationThrowsExceptionPartsIsOfUnexpectedLength()
    {
        /** @var SuggestWizardController|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getAccessibleMock(SuggestWizardController::class, ['dummy'], [], '', false);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1480611252);
        $subject->_call('getFlexFieldConfiguration', [], []);
    }

    /**
     * @test
     */
    public function getFlexFieldConfigurationFindsConfigurationOfSimpleFlexField()
    {
        $dataStructure = [
            'sheets' => [
                'sDb' => [
                    'ROOT' => [
                        'el' => [
                            'group_db_1' => [
                                'TCEforms' => [
                                    'config' => [
                                        'Sublevel field configuration',
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
            2 => 'sDb',
            3 => 'lDEF',
            4 => 'group_db_1',
            5 => 'vDEF',
        ];

        $expected = $dataStructure['sheets']['sDb']['ROOT']['el']['group_db_1']['TCEforms']['config'];

        /** @var SuggestWizardController|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getAccessibleMock(SuggestWizardController::class, ['dummy'], [], '', false);
        $result = $subject->_call('getFlexFieldConfiguration', $parts, $dataStructure);
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function getFlexFieldConfigurationFindsConfigurationOfSectionContainerField()
    {
        $dataStructure = [
            'sheets' => [
                'sSuggestCheckCombination' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'settings.subelements' => [
                                'title' => 'Subelements',
                                'section' => 1,
                                'type' => 'array',
                                'el' => [
                                    'item' => [
                                        'type' => 'array',
                                        'title' => 'Subelement',
                                        'el' => [
                                            'content' => [
                                                'TCEforms' => [
                                                    'label' => 'Content',
                                                    'config' => [
                                                        'type' => 'group',
                                                        'internal_type' => 'db',
                                                        'allowed' => 'pages',
                                                        'size' => 5,
                                                        'maxitems' => 10,
                                                        'minitems' => 1,
                                                        'show_thumbs' => 1,
                                                        'wizards' => [
                                                            'suggest' => [
                                                                'type' => 'suggest',
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
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
            4 => 'settings.subelements',
            5 => 'el',
            6 => '1',
            7 => 'item',
            8 => 'el',
            9 => 'content',
            10 => 'vDEF',
        ];

        $expected = $dataStructure['sheets']['sSuggestCheckCombination']['ROOT']['el']['settings.subelements']
            ['el']['item']['el']['content']['TCEforms']['config'];

        /** @var SuggestWizardController|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getAccessibleMock(SuggestWizardController::class, ['dummy'], [], '', false);
        $result = $subject->_call('getFlexFieldConfiguration', $parts, $dataStructure);
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider isTableHiddenIsProperlyRetrievedDataProvider
     */
    public function isTableHiddenIsProperlyRetrieved($expected, $array)
    {
        $subject = $this->getAccessibleMock(SuggestWizardController::class, ['dummy'], [], '', false);
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
