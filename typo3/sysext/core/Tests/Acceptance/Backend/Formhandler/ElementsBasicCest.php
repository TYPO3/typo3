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

use TYPO3\Components\TestingFramework\Core\Acceptance\Step\Backend\Admin;
use TYPO3\Components\TestingFramework\Core\Acceptance\Support\Helper\Formhandler;
use TYPO3\Components\TestingFramework\Core\Acceptance\Support\Helper\FormHandlerElementTestDataObject;
use TYPO3\Components\TestingFramework\Core\Acceptance\Support\Page\PageTree;

/**
 * Tests for basic element fields
 */
class ElementsBasicCest
{
    /**
     * Selector of the record container in the listview
     *
     * @var string
     */
    protected static $listViewRecordSelector = '#recordlist-tx_styleguide_elements_basic';

    public function _before(Admin $I, PageTree $pageTree)
    {
        $I->useExistingSession();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->switchToIFrame('list_frame');
        $I->waitForText('Web Content Management System');
        $I->switchToIFrame();

        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        $I->switchToIFrame('list_frame');
    }

    /**
     * @param Admin $I
     * @param Formhandler $formhandler
     */
    public function checkThatBrowserSideValidationsWorkAndSaveRecord(Admin $I, Formhandler $formhandler)
    {
        $this->waitForFormReady($I);

        $fieldTests = [
            'input_1' => [
                new FormHandlerElementTestDataObject(
                    'This is a demo text with 2 numbers #!',
                    'This is a demo text with 2 numbers #!'
                )
            ],
            'input_2, size=10' => [
                new FormHandlerElementTestDataObject(
                    'This is a demo text with 2 numbers #!',
                    'This is a demo text with 2 numbers #!'
                )
            ],
            'input_3 max=4' => [
                new FormHandlerElementTestDataObject(
                    'Kasper',
                    'Kasp'
                )
            ],
            'input_4 eval=alpha' => [
                new FormHandlerElementTestDataObject(
                    'Kasper = TYPO3',
                    'KasperTYPO'
                ),
                new FormHandlerElementTestDataObject(
                    'Non-latin characters: ŠĐŽĆČ',
                    'Nonlatincharacters'
                ),
            ],
            'input_5 eval=alphanum' => [
                new FormHandlerElementTestDataObject(
                    'Kasper = TYPO3',
                    'KasperTYPO3'
                ),

            ],
            'input_8 eval=double2' => [
                new FormHandlerElementTestDataObject(
                    '12.335',
                    '12.34',
                    '12.34'
                ),
                new FormHandlerElementTestDataObject(
                    '12,335',
                    '12.34',
                    '12.34'
                ),
                new FormHandlerElementTestDataObject(
                    '1.1',
                    '1.10',
                    '1.10'
                ),
                new FormHandlerElementTestDataObject(
                    'TYPO3',
                    '3.00',
                    '3.00'
                ),
                new FormHandlerElementTestDataObject(
                    '3TYPO',
                    '3.00',
                    '3.00'
                )
            ],
            'input_9 eval=int' => [
                new FormHandlerElementTestDataObject(
                    '12.335',
                    '12',
                    '12'

                ),
                new FormHandlerElementTestDataObject(
                    '12,9',
                    '12',
                    '12'
                ),
                new FormHandlerElementTestDataObject(
                    'TYPO3',
                    '0',
                    '0'
                ),
                new FormHandlerElementTestDataObject(
                    '3TYPO',
                    '3',
                    '3'
                )
            ],
            'input_10 eval=is_in, is_in=abc123' => [
                new FormHandlerElementTestDataObject(
                    'abcd1234',
                    'abc123'
                ),
                new FormHandlerElementTestDataObject(
                    'Kasper TYPO3',
                    'a3'
                )
            ],
            'input_11 eval=lower' => [
                new FormHandlerElementTestDataObject(
                    'Kasper TYPO3!',
                    'kasper typo3!'
                )
            ],
            'input_12 eval=md5' => [
                new FormHandlerElementTestDataObject(
                    'Kasper TYPO3!',
                    '748469dd64911af8df8f9a3dcb2c9378',
                    '748469dd64911af8df8f9a3dcb2c9378'
                ),
                new FormHandlerElementTestDataObject(
                    ' Kasper TYPO3! ',
                    '792a085606250c47d6ebb8c98804d5b0',
                    '792a085606250c47d6ebb8c98804d5b0',
                    '792a085606250c47d6ebb8c98804d5b0',
                    false,
                    'Check that whitespaces are not trimmed.'
                )
            ],
            'input_13 eval=nospace' => [
                new FormHandlerElementTestDataObject(
                    ' Kasper TYPO3! ',
                    'KasperTYPO3!'
                )
            ],
            'input_15 eval=num' => [
                new FormHandlerElementTestDataObject(
                    '12.335',
                    '12335'
                ),
                new FormHandlerElementTestDataObject(
                    '12,9',
                    '129'
                ),
                new FormHandlerElementTestDataObject(
                    'TYPO3',
                    '3'
                ),
                new FormHandlerElementTestDataObject(
                    '3TYPO',
                    '3'
                ),
            ],
            'input_16 eval=password' => [
                new FormHandlerElementTestDataObject(
                    'Kasper',
                    '********',
                    'Kasper'
                ),
            ],
            'input_19 eval=trim' => [
                new FormHandlerElementTestDataObject(
                    ' Kasper ',
                    'Kasper'
                ),
                new FormHandlerElementTestDataObject(
                    ' Kasper TYPO3 ',
                    'Kasper TYPO3'
                ),
            ],
            'input_23 eval=upper' => [
                new FormHandlerElementTestDataObject(
                    'Kasper TYPO3!',
                    'KASPER TYPO3!'
                )
            ],
            'input_25 eval=int, default=0, range lower=-2, range upper=2' => [
                new FormHandlerElementTestDataObject(
                    'Kasper TYPO3',
                    '0',
                    '0'
                ),
                new FormHandlerElementTestDataObject(
                    '2',
                    '2',
                    '2'
                ),
                new FormHandlerElementTestDataObject(
                    '-1',
                    '-1',
                    '-1'
                ),
                new FormHandlerElementTestDataObject(
                    '-3',
                    '-3',
                    '-3',
                    '-3',
                    true,
                    'Expecting a modal with error on trying to save.'

                ),
                new FormHandlerElementTestDataObject(
                    '3',
                    '-3',
                    '-3',
                    '-3',
                    true,
                    'Expecting a modal with error on trying to save.'
                )
            ],
        ];

        foreach ($fieldTests as $fieldLabel => $testData) {
            $formhandler->fillSeeSaveAndClearInputField(
                $fieldLabel,
                $testData
            );
        }
    }

    /**
     * @param Admin $I
     * @param Formhandler $formhandler
     */
    public function checkThatValidationWorks_evalYear(Admin $I, Formhandler $formhandler)
    {
        $this->waitForFormReady($I);

        $testData = [
            'input_24 eval=year' => [
                new FormHandlerElementTestDataObject(
                    '2016',
                    '2016',
                    '2016'
                ),
                new FormHandlerElementTestDataObject(
                    '12',
                    '2012',
                    '2012'
                ),
                new FormHandlerElementTestDataObject(
                    'Kasper',
                    date('Y'),
                    date('Y'),
                    date('Y'),
                    false,
                    'Invalid character is converted to current year'
                )
            ]
        ];

        $this->runTests($formhandler, $testData);
    }

    /**
     * @param \TYPO3\Components\TestingFramework\Core\Acceptance\Step\Backend\Admin $I
     * @param \TYPO3\Components\TestingFramework\Core\Acceptance\Support\Helper\Formhandler $formhandler
     * @skip
     */
    public function checkThatBrowserSideValidationWorks_EvalDate(Admin $I, Formhandler $formhandler)
    {
        $this->skip('Instable Test is skipped due to repeated failure');
        //@todo fix this test

        $this->waitForFormReady($I);
        $fieldData = [
            'input_6 eval=date' => [
                new FormHandlerElementTestDataObject(
                    '29-01-2016',
                    '29-01-2016',
                    '2016-01-29T00:00:00Z',
                    '2016-01-29T00:00:00+00:00'
                ),
                new FormHandlerElementTestDataObject(
                    '13-13-2016',
                    '13-01-2017',
                    '2017-01-13T00:00:00Z',
                    '2017-01-13T00:00:00+00:00'
                ),
                new FormHandlerElementTestDataObject(
                    '29-02-2016',
                    '29-02-2016',
                    '2016-02-29T00:00:00Z',
                    '2016-02-29T00:00:00+00:00',
                    false,
                    'Check valid leap year input'
                ),
                new FormHandlerElementTestDataObject(
                    '29-02-2015',
                    '01-03-2015',
                    '2015-03-01T00:00:00Z',
                    '2015-03-01T00:00:00+00:00',
                    false,
                    'Check invalid leap year transformation'
                )
            ]
        ];
        $this->runTests($formhandler, $fieldData);
    }

    public function checkThatValidationWorks_EvalDate_TypeDate(Admin $I, Formhandler $formhandler)
    {
        $this->waitForFormReady($I);
        $testData = [
            'input_36 dbType=date eval=date' => [
                new FormHandlerElementTestDataObject(
                    '29-01-2016',
                    '29-01-2016',
                    '2016-01-29T00:00:00Z',
                    '2016-01-29T00:00:00+00:00'
                ),
                new FormHandlerElementTestDataObject(
                    '13-13-2016',
                    '13-01-2017',
                    '2017-01-13T00:00:00Z',
                    '2017-01-13T00:00:00+00:00'
                ),
                new FormHandlerElementTestDataObject(
                    '29-02-2016',
                    '29-02-2016',
                    '2016-02-29T00:00:00Z',
                    '2016-02-29T00:00:00+00:00',
                    false,
                    'Check valid leap year input'
                ),
                new FormHandlerElementTestDataObject(
                    '29-02-2015',
                    '01-03-2015',
                    '2015-03-01T00:00:00Z',
                    '2015-03-01T00:00:00+00:00',
                    false,
                    'Check invalid leap year transformation'
                ),
            ]
        ];
        $this->runTests($formhandler, $testData);
    }

    public function checkThatValidationWorks_EvalDateTime(Admin $I, Formhandler $formhandler)
    {
        $this->waitForFormReady($I);
        $testData = [
            'input_7 eval=datetime' => [
                new FormHandlerElementTestDataObject(
                    '05:23 29-01-2016',
                    '05:23 29-01-2016',
                    '2016-01-29T05:23:00Z',
                    '2016-01-29T05:23:00+00:00'
                ),
                new FormHandlerElementTestDataObject(
                    '05:23 13-13-2016',
                    '05:23 13-01-2017',
                    '2017-01-13T05:23:00Z',
                    '2017-01-13T05:23:00+00:00'
                ),
                new FormHandlerElementTestDataObject(
                    '05:23 29-02-2016',
                    '05:23 29-02-2016',
                    '2016-02-29T05:23:00Z',
                    '2016-02-29T05:23:00+00:00',
                    false,
                    'Check valid leap year input'
                ),
                new FormHandlerElementTestDataObject(
                    '05:23 29-02-2015',
                    '05:23 01-03-2015',
                    '2015-03-01T05:23:00Z',
                    '2015-03-01T05:23:00+00:00',
                    false,
                    'Check invalid leap year transformation'
                )
            ]
        ];
        $this->runTests($formhandler, $testData);
    }

    public function checkThatValidationWorks_EvalDateTime_DbTypeDateTime(Admin $I, Formhandler $formhandler)
    {
        $this->skip('Instable Test is skipped due to repeated failure');
        //@todo fix this test
        $this->waitForFormReady($I);
        $testData = [
            'input_37 dbType=datetime eval=datetime' => [
                new FormHandlerElementTestDataObject(
                    '05:23 29-01-2016',
                    '05:23 29-01-2016',
                    '2016-01-29T05:23:00Z',
                    '2016-01-29T05:23:00+00:00'
                ),
                new FormHandlerElementTestDataObject(
                    '05:23 13-13-2016',
                    '05:23 13-01-2017',
                    '2017-01-13T05:23:00Z',
                    '2017-01-13T05:23:00+00:00'
                ),
                new FormHandlerElementTestDataObject(
                    '05:23 29-02-2016',
                    '05:23 29-02-2016',
                    '2016-02-29T05:23:00Z',
                    '2016-02-29T05:23:00+00:00',
                    false,
                    'Check valid leap year input'
                ),
                new FormHandlerElementTestDataObject(
                    '05:23 29-02-2015',
                    '05:23 01-03-2015',
                    '2015-03-01T05:23:00Z',
                    '2015-03-01T05:23:00+00:00',
                    false,
                    'Check invalid leap year transformation'
                ),
            ],
        ];
        $this->runTests($formhandler, $testData);
    }

    public function checkThatValidationWorks_evalTime(Admin $I, Formhandler $formhandler)
    {
        $this->waitForFormReady($I);
        $testData = [
            'input_17 eval=time' => [
                new FormHandlerElementTestDataObject(
                    '13:30',
                    '13:30',
                    '13:30',
                    (new \DateTime('13:30'))->getTimestamp()
                ),
                new FormHandlerElementTestDataObject(
                    '123',
                    '12:03',
                    '12:03',
                    (new \DateTime('12:03'))->getTimestamp()
                ),
                new FormHandlerElementTestDataObject(
                    '12345',
                    '12:34',
                    '12:34',
                    (new \DateTime('12:34'))->getTimestamp()
                ),
                new FormHandlerElementTestDataObject(
                    '12:04+5',
                    '12:09',
                    '12:09',
                    (new \DateTime('12:09'))->getTimestamp()
                ),
                new FormHandlerElementTestDataObject(
                    '12:09-3',
                    '12:06',
                    '12:06',
                    (new \DateTime('12:06'))->getTimestamp()
                )
            ],
        ];
        $this->runTests($formhandler, $testData);
    }

    public function checkThatValidationWorks_evalTimesec(Admin $I, Formhandler $formhandler)
    {
        $this->skip('Instable Test is skipped due to repeated failure');
        //@todo fix this test

        $this->waitForFormReady($I);
        $testData = [
            'input_18 eval=timesec' => [
                new FormHandlerElementTestDataObject(
                    '13:30:00',
                    '13:30:00',
                    '13:30:00',
                    (new \DateTime('13:30:00'))->getTimestamp()
                ),
                new FormHandlerElementTestDataObject(
                    '12345',
                    '12:34:05',
                    '12:34:05',
                    (new \DateTime('12:34:05'))->getTimestamp()
                ),
                new FormHandlerElementTestDataObject(
                    '12:04:04+5',
                    '12:09:04',
                    '12:09:04',
                    (new \DateTime('12:09:04'))->getTimestamp()
                )
            ],
        ];
        $this->runTests($formhandler, $testData);
    }

    /**
     * @param \TYPO3\Components\TestingFramework\Core\Acceptance\Support\Helper\Formhandler $formhandler
     * @param $fieldData
     */
    protected function runTests(Formhandler $formhandler, $fieldData)
    {
        foreach ($fieldData as $fieldLabel => $testData) {
            $formhandler->fillSeeSaveAndClearInputField(
                $fieldLabel,
                $testData
            );
        }
    }

    /**
     * @param \TYPO3\Components\TestingFramework\Core\Acceptance\Step\Backend\Admin $I
     */
    protected function waitForFormReady(Admin $I)
    {
        $editRecordLinkCssPath = self::$listViewRecordSelector . ' a[data-original-title="Edit record"]';
        $I->waitForElement($editRecordLinkCssPath, 30);
        $I->click($editRecordLinkCssPath);
        $I->waitForText('Edit Form', 3, 'h1');
    }

    /**
     * From Codeception/Scenario
     */
    protected function skip($message)
    {
        throw new \PHPUnit_Framework_SkippedTestError($message);
    }
}
