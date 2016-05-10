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
        $I->click(self::$listViewRecordSelector . ' a[data-original-title="Edit record"]');
        $I->waitForText('Edit Form', 3, 'h1');

        $fieldTests = [
            'input_1' => [
                [
                    'This is a demo text with 2 numbers #!',
                    'This is a demo text with 2 numbers #!',
                ],
            ],
            'input_2, size=10' => [
                [
                    'This is a demo text with 2 numbers #!',
                    'This is a demo text with 2 numbers #!',
                ],
            ],
            'input_3 max=4' => [
                [
                    'Kasper',
                    'Kasp',
                ],
            ],
            'input_4 eval=alpha' => [
                [
                    'Kasper = TYPO3',
                    'KasperTYPO',
                ],
            ],
            'input_5 eval=alphanum' => [
                [
                    'Kasper = TYPO3',
                    'KasperTYPO3',
                ],
            ],
            'input_6 eval=date' => [
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

                'check valid leap year input' => [
                    '29-02-2016',
                    '29-02-2016',
                    '1456704000',
                ],
                'check invalid leap year transformation' => [
                    '29-02-2015',
                    '01-03-2015',
                    '1425168000',
                ],
            ],
            'input_8 eval=double2' => [
                [
                    '12.335',
                    '12.34',
                ],
                [
                    '12,335',
                    '12.34',
                ],
                [
                    '1.1',
                    '1.10',
                ],
                [
                    'TYPO3',
                    '3.00',
                ],
                [
                    '3TYPO',
                    '3.00',
                ],
            ],
            'input_9 eval=int' => [
                [
                    '12.335',
                    '12',
                ],
                [
                    '12,9',
                    '12',
                ],
                [
                    'TYPO3',
                    '0',
                ],
                [
                    '3TYPO',
                    '3',
                ],
            ],
            'input_10 eval=is_in, is_in=abc123' => [
                [
                    'abcd1234',
                    'abc123',
                ],
                [
                    'Kasper TYPO3',
                    'a3',
                ],
            ],
            'input_11 eval=lower' => [
                [
                    'Kasper TYPO3!',
                    'kasper typo3!',
                ],
            ],
            'input_12 eval=md5' => [
                [
                    'Kasper TYPO3!',
                    '748469dd64911af8df8f9a3dcb2c9378',
                ],
                'check that whitespace is not trimmed' => [
                    'Kasper TYPO3! ',
                    '265e09df9b9b08ab1f946510f510d3ef',
                ],
            ],
            'input_13 eval=nospace' => [
                [
                    ' Kasper TYPO3! ',
                    'KasperTYPO3!',
                ],
            ],
            // @todo define test
            //'input_14 eval=null' => [
            //],
            'input_15 eval=num' => [
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
            'input_16 eval=password' => [
                [
                    'Kasper',
                    '********',
                    'Kasper',
                ],
            ],
            'input_17 eval=time' => [
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
            'input_18 eval=timesec' => [
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
            'input_19 eval=trim' => [
                [
                    ' Kasper ',
                    'Kasper',
                ],
                [
                    ' Kasper TYPO3 ',
                    'Kasper TYPO3',
                ],
            ],
            // @todo Check why this test is currently broken
            //'input_20 eval with user function' => [
            //    [
            //        'Kasper',
            //        'KasperJSfoo',
            //    ]
            //],
            'input_23 eval=upper' => [
                [
                    'Kasper TYPO3!',
                    'KASPER TYPO3!',
                ],
            ],
            'input_24 eval=year' => [
                [
                    '2016',
                    '2016',
                ],
                [
                    '12',
                    '2012',
                ],
                'Invalid character is converted to current year' => [
                    'Kasper',
                    date('Y'),
                ],
            ],
            'input_25 eval=int, default=0, range lower=-2, range upper=2' => [
                [
                    'Kasper TYPO3',
                    '0',
                ],
                [
                    '2',
                    '2',
                ],
                [
                    '-1',
                    '-1',
                ],
                [
                    '-3',
                    '-3',
                    // @todo Check for validation error
                ],
                [
                    '3',
                    '3',
                    // @todo Check for validation error
                ],
            ],
        ];

        foreach ($fieldTests as $fieldKey => $testData) {
            $formhandler->fillSeeDeleteInputField(
                $formhandler->getContextForFormhandlerField($fieldKey),
                $testData
            );
        }
    }
}
