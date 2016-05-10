<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Formhandler;

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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Admin;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\Formhandler;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Page\PageTree;

/**
 * Tests for basic element fields
 */
class ElementsBasicCest
{
    /**
     * Selector of the record container in the listview
     * @var string
     */
    protected static $listViewRecordSelector = '#recordlist-tx_styleguide_elements_basic';

    public function _before(Admin $I, PageTree $pageTree)
    {
        $I->useExistingSession();

        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        $I->switchToIFrame('content');
    }

    /**
     * @param Admin $I
     * @param Formhandler $formhandler
     */
    public function checkThatBrowserSideValidationsWorkAndSaveRecord(Admin $I, Formhandler $formhandler)
    {
        $editRecordLinkCssPath = self::$listViewRecordSelector . ' a[data-original-title="Edit record"]';
        $I->waitForElement($editRecordLinkCssPath, 30);
        $I->click($editRecordLinkCssPath);
        $I->waitForText('Edit Form', 3, 'h1');

        $fieldTests = [
            'input_1' => [
                'tests' => [
                    [
                        'This is a demo text with 2 numbers #!',
                        'This is a demo text with 2 numbers #!',
                    ],
                ],
                'cleared' => [
                    ''
                ]
            ],
            'input_2, size=10' => [
                'tests' => [
                    [
                        'This is a demo text with 2 numbers #!',
                        'This is a demo text with 2 numbers #!',
                    ],
                ],
                'cleared' => [
                    ''
                ]
            ],
            'input_3 max=4' => [
                'tests' => [
                    [
                        'Kasper',
                        'Kasp',
                    ],
                ],
                'cleared' => [
                    ''
                ]
            ],
            'input_4 eval=alpha' => [
                'tests' => [
                    [
                        'Kasper = TYPO3',
                        'KasperTYPO',
                    ],
                    [
                        'Non-latin characters: ŠĐŽĆČ',
                        'Nonlatincharacters',
                    ],
                ],
                'cleared' => [
                    ''
                ]
            ],
            'input_5 eval=alphanum' => [
                'tests' => [
                    [
                        'Kasper = TYPO3',
                        'KasperTYPO3',
                    ],
                ],
                'cleared' => [
                    ''
                ]
            ],
            'input_6 eval=date' => [
                'tests' => [
                    [
                        '29-01-2016',
                        '29-01-2016',
                        '1454025600',
                    ],
                    [
                        '13-13-2016',
                        '13-01-2017',
                        '1484265600',
                    ],
                    [
                        '29-02-2016',
                        '29-02-2016',
                        '1456704000',
                        false,
                        'Check valid leap year input'
                    ],
                    [
                        '29-02-2015',
                        '01-03-2015',
                        '1425168000',
                        false,
                        'Check invalid leap year transformation'
                    ],
                ],
                'cleared' => [
                    '0'
                ]
            ],
            'input_36 dbType=date eval=date' => [
                'tests' => [
                    [
                        '29-01-2016',
                        '29-01-2016',
                        '1454025600',
                    ],
                    [
                        '13-13-2016',
                        '13-01-2017',
                        '1484265600',
                    ],
                    [
                        '29-02-2016',
                        '29-02-2016',
                        '1456704000',
                        false,
                        'Check valid leap year input'
                    ],
                    [
                        '29-02-2015',
                        '01-03-2015',
                        '1425168000',
                        false,
                        'Check invalid leap year transformation'
                    ],
                ],
                'cleared' => [
                    '0'
                ]
            ],
            'input_7 eval=datetime' => [
                'tests' => [
                    [
                        '05:23 29-01-2016',
                        '05:23 29-01-2016',
                        '1454044980',
                    ],
                    [
                        '05:23 13-13-2016',
                        '05:23 13-01-2017',
                        '1484284980',
                    ],
                    [
                        '05:23 29-02-2016',
                        '05:23 29-02-2016',
                        '1456723380',
                        false,
                        'Check valid leap year input'
                    ],
                    [
                        '05:23 29-02-2015',
                        '05:23 01-03-2015',
                        '1425187380',
                        false,
                        'Check invalid leap year transformation'
                    ],
                ],
                'cleared' => [
                    '0'
                ]
            ],
            'input_37 dbType=datetime eval=datetime' => [
                'tests' => [
                    [
                        '05:23 29-01-2016',
                        '05:23 29-01-2016',
                        '1454044980',
                    ],
                    [
                        '05:23 13-13-2016',
                        '05:23 13-01-2017',
                        '1484284980',
                    ],
                    [
                        '05:23 29-02-2016',
                        '05:23 29-02-2016',
                        '1456723380',
                        false,
                        'Check valid leap year input'
                    ],
                    [
                        '05:23 29-02-2015',
                        '05:23 01-03-2015',
                        '1425187380',
                        false,
                        'Check invalid leap year transformation'
                    ],
                ],
                'cleared' => [
                    '0'
                ]
            ],
            'input_8 eval=double2' => [
                'tests' => [
                    [
                        '12.335',
                        '12.34',
                        '12.34',
                    ],
                    [
                        '12,335',
                        '12.34',
                        '12.34',
                    ],
                    [
                        '1.1',
                        '1.10',
                        '1.10',
                    ],
                    [
                        'TYPO3',
                        '3.00',
                        '3.00',
                    ],
                    [
                        '3TYPO',
                        '3.00',
                        '3.00',
                    ],
                ],
                // @todo: add support for null values to the core
                'cleared' => [
                    '0.00',
                    '0.00',
                ]
            ],
            'input_9 eval=int' => [
                'tests' => [
                    [
                        '12.335',
                        '12',
                        '12',
                    ],
                    [
                        '12,9',
                        '12',
                        '12',
                    ],
                    [
                        'TYPO3',
                        '0',
                        '0',
                    ],
                    [
                        '3TYPO',
                        '3',
                        '3',
                    ],
                ],
                // @todo: add support for null values to the core
                'cleared' => [
                    '0',
                    '0',
                ]
            ],
            'input_10 eval=is_in, is_in=abc123' => [
                'tests' => [
                    [
                        'abcd1234',
                        'abc123',
                    ],
                    [
                        'Kasper TYPO3',
                        'a3',
                    ],
                ],
                'cleared' => [
                    '',
                ]
            ],
            'input_11 eval=lower' => [
                'tests' => [
                    [
                        'Kasper TYPO3!',
                        'kasper typo3!',
                    ],
                ],
                'cleared' => [
                    '',
                ]
            ],
            'input_12 eval=md5' => [
                'tests' => [
                    [
                        'Kasper TYPO3!',
                        '748469dd64911af8df8f9a3dcb2c9378',
                        '748469dd64911af8df8f9a3dcb2c9378',
                    ],
                    [
                        ' Kasper TYPO3! ',
                        '792a085606250c47d6ebb8c98804d5b0',
                        '792a085606250c47d6ebb8c98804d5b0',
                        false,
                        'Check that whitespaces are not trimmed.'
                    ],
                ],
                'cleared' => [
                    // @todo: add support for null values to the core
                    // cleared value currently keeps the previous value on save
                    '792a085606250c47d6ebb8c98804d5b0',
                    '792a085606250c47d6ebb8c98804d5b0'
                ]

            ],
            'input_13 eval=nospace' => [
                'tests' => [
                    [
                        ' Kasper TYPO3! ',
                        'KasperTYPO3!',
                    ],
                ],
                'cleared' => [
                    '',
                ]
            ],
            // @todo define test
            //'input_14 eval=null' => [
            //],
            'input_15 eval=num' => [
                'tests' => [
                    [
                        '12.335',
                        '12335',
                    ],
                    [
                        '12,9',
                        '129',
                    ],
                    [
                        'TYPO3',
                        '3',
                    ],
                    [
                        '3TYPO',
                        '3',
                    ],
                ],
                'cleared' => [
                    '',
                ]
            ],
            'input_16 eval=password' => [
                'tests' => [
                    [
                        'Kasper',
                        '********',
                        'Kasper',
                    ],
                ],
                'cleared' => [
                    '',
                ]
            ],
            'input_17 eval=time' => [
                'tests' => [
                    [
                        '13:30',
                        '13:30',
                        '48600',
                    ],
                    [
                        '123',
                        '12:03',
                        '43380',
                    ],
                    [
                        '12345',
                        '12:34',
                        '45240',
                    ],
                    [
                        '12:04+5',
                        '12:09',
                        '43740',
                    ],
                    [
                        '12:09-3',
                        '12:06',
                        '43560',
                    ]
                ],
                'cleared' => [
                    '0',
                    '00:00',
                ]
            ],
            'input_18 eval=timesec' => [
                'tests' => [
                    [
                        '13:30:00',
                        '13:30:00',
                        '48600',
                    ],
                    [
                        '12345',
                        '12:34:05',
                        '45245',
                    ],
                    [
                        // @todo is that the expected behavior?
                        '12:04:04+5',
                        '12:09:04',
                        '43744',
                    ],
                ],
                'cleared' => [
                    '0',
                    '00:00:00',
                ]
            ],
            'input_19 eval=trim' => [
                'tests' => [
                    [
                        ' Kasper ',
                        'Kasper',
                    ],
                    [
                        ' Kasper TYPO3 ',
                        'Kasper TYPO3',
                    ],
                ],
                'cleared' => [
                    '',
                ]
            ],
            // @todo Check why this test is currently broken
            //'input_20 eval with user function' => [
            //    [
            //        'Kasper',
            //        'KasperJSfoo',
            //    ]
            //],
            'input_23 eval=upper' => [
                'tests' => [
                    [
                        'Kasper TYPO3!',
                        'KASPER TYPO3!',
                    ],
                ],
                'cleared' => [
                    '',
                ]
            ],
            'input_24 eval=year' => [
                'tests' => [

                    [
                        '2016',
                        '2016',
                        '2016',
                    ],
                    [
                        '12',
                        '2012',
                        '2012',
                    ],
                    [
                        'Kasper',
                        date('Y'),
                        date('Y'),
                        false,
                        'Invalid character is converted to current year'
                    ],
                ],
                'cleared' => [
                    '0',
                    '0',
                ]
            ],
            'input_25 eval=int, default=0, range lower=-2, range upper=2' => [
                'tests' => [
                    [
                        'Kasper TYPO3',
                        '0',
                        '0',
                    ],
                    [
                        '2',
                        '2',
                        '2'
                    ],
                    [
                        '-1',
                        '-1',
                        '-1',
                    ],
                    [
                        '-3',
                        '-3',
                        '-3',
                        true,
                        'Expecting a modal with error on trying to save.'
                    ],
                    [
                        '3',
                        '-3',
                        '-3',
                        true,
                        'Expecting a modal with error on trying to save.'
                    ],
                ],
                'cleared' => [
                    '0',
                    '0'
                ]
            ],
        ];

        foreach ($fieldTests as $fieldLabel => $testData) {
            $formhandler->fillSeeSaveAndClearInputField(
                $fieldLabel,
                $testData
            );
        }
    }
}
