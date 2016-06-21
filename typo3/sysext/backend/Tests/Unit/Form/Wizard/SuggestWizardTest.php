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

use TYPO3\CMS\Backend\Form\Wizard\SuggestWizard;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class SuggestWizardTest extends UnitTestCase
{
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
        $subject = $this->getAccessibleMock(SuggestWizard::class, array('getNestedDsFieldConfig'), array(), '', false);
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
        $subject = $this->getAccessibleMock(SuggestWizard::class, array('getNestedDsFieldConfig'), array(), '', false);
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
    public function isTableHiddenIsProperlyRetrieved($expected, $array) {
        $subject = $this->getAccessibleMock(SuggestWizard::class, array('dummy'), array(), '', false);
        $this->assertEquals($expected, $subject->_call('isTableHidden', $array));
    }

    public function isTableHiddenIsProperlyRetrievedDataProvider() {
        return [
          'notSetValue' => [false, array('ctrl' => array('hideTable' => null))],
          'true' => [true, array('ctrl' => array('hideTable' => true))],
          'false' => [false, array('ctrl' => array('hideTable' => false))],
          'string with true' => [true, array('ctrl' => array('hideTable' => '1'))],
          'string with false' => [false, array('ctrl' => array('hideTable' => '0'))],
        ];
    }
}
