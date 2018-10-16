<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\FormEngine;

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

use Codeception\Example;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for "elements_basic" date and time related input fields of ext:styleguide
 */
class ElementsBasicInputDateCest extends AbstractElementsBasicCest
{
    /**
     * Open list module of styleguide elements basic page
     *
     * @param BackendTester $I
     * @param PageTree $pageTree
     */
    public function _before(BackendTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        $I->switchToContentFrame();

        // Open record and wait until form is ready
        $I->waitForText('elements basic', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_basic a[data-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Form', 3, 'h1');
        $I->click('inputDateTime');
        $I->waitForText('inputDateTime', 3);
    }

    /**
     * dbTypeDateEvalDate data provider
     */
    protected function dbTypeDateEvalDateDataProvider()
    {
        return [
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_2 dbType=date eval=date',
                'inputValue' => '29-01-2016',
                'expectedValue' => '29-01-2016',
                'expectedInternalValue' => '2016-01-29T00:00:00Z',
                'expectedValueAfterSave' => '2016-01-29T00:00:00+00:00',
                'comment' => '',
            ],
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_2 dbType=date eval=date',
                'inputValue' => '13-13-2016',
                'expectedValue' => '13-01-2017',
                'expectedInternalValue' => '2017-01-13T00:00:00Z',
                'expectedValueAfterSave' => '2017-01-13T00:00:00+00:00',
                'comment' => '',
            ],
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_2 dbType=date eval=date',
                'inputValue' => '29-02-2016',
                'expectedValue' => '29-02-2016',
                'expectedInternalValue' => '2016-02-29T00:00:00Z',
                'expectedValueAfterSave' => '2016-02-29T00:00:00+00:00',
                'comment' => 'Check valid leap year input',
            ],
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_2 dbType=date eval=date',
                'inputValue' => '29-02-2015',
                'expectedValue' => '01-03-2015',
                'expectedInternalValue' => '2015-03-01T00:00:00Z',
                'expectedValueAfterSave' => '2015-03-01T00:00:00+00:00',
                'comment' => 'Check invalid leap year transformation',
            ],
        ];
    }

    /**
     * @dataProvider dbTypeDateEvalDateDataProvider
     * @param BackendTester $I
     * @param Example $testData
     * @todo fix these unstable tests
    public function dbTypeDateEvalDate(BackendTester $I, Example $testData)
    {
        $this->runInputFieldTest($I, $testData);
    }
     */

    /**
     * dbType date eval datetime data provider
     */
    protected function dbTypeDateEvalDatetimeDataProvider()
    {
        return [
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_3 eval=datetime',
                'inputValue' => '05:23 29-01-2016',
                'expectedValue' => '05:23 29-01-2016',
                'expectedInternalValue' => '2016-01-29T05:23:00Z',
                'expectedValueAfterSave' => '2016-01-29T05:23:00+00:00',
                'comment' => '',
            ],
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_3 eval=datetime',
                'inputValue' => '05:23 13-13-2016',
                'expectedValue' => '05:23 13-01-2017',
                'expectedInternalValue' => '2017-01-13T05:23:00Z',
                'expectedValueAfterSave' => '2017-01-13T05:23:00+00:00',
                'comment' => '',
            ],
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_3 eval=datetime',
                'inputValue' => '05:23 29-02-2016',
                'expectedValue' => '05:23 29-02-2016',
                'expectedInternalValue' => '2016-02-29T05:23:00Z',
                'expectedValueAfterSave' => '2016-02-29T05:23:00+00:00',
                'comment' => 'Check valid leap year input',
            ],
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_3 eval=datetime',
                'inputValue' => '05:23 29-02-2015',
                'expectedValue' => '05:23 01-03-2015',
                'expectedInternalValue' => '2015-03-01T05:23:00Z',
                'expectedValueAfterSave' => '2015-03-01T05:23:00+00:00',
                'comment' => 'Check invalid leap year transformation',
            ],
        ];
    }

    /**
     * @dataProvider dbTypeDateEvalDatetimeDataProvider
     * @param BackendTester $I
     * @param Example $testData
     * @todo fix these unstable tests
    public function dbTypeDateEvalDatetime(BackendTester $I, Example $testData)
    {
        $this->runInputFieldTest($I, $testData);
    }
     */

    /**
     * db type date eval time data provider
     */
    protected function dbTypeDateEvalTimeDataProvider()
    {
        return [
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_5',
                'inputValue' => '13:30',
                'expectedValue' => '13:30',
                'expectedInternalValue' => '1970-01-01T13:30:00Z',
                'expectedValueAfterSave' => '1970-01-01T13:30:00+00:00',
                'comment' => '',
            ],
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_5',
                'inputValue' => '123',
                'expectedValue' => '12:03',
                'expectedInternalValue' => '1970-01-01T12:03:00Z',
                'expectedValueAfterSave' => '1970-01-01T12:03:00+00:00',
                'comment' => '',
            ],
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_5',
                'inputValue' => '12345',
                'expectedValue' => '12:34',
                'expectedInternalValue' => '1970-01-01T12:34:00Z',
                'expectedValueAfterSave' => '1970-01-01T12:34:00+00:00',
                'comment' => '',
            ],
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_5',
                'inputValue' => '12:04+5',
                'expectedValue' => '12:09',
                'expectedInternalValue' => '1970-01-01T12:09:00Z',
                'expectedValueAfterSave' => '1970-01-01T12:09:00+00:00',
                'comment' => '',
            ],
            [
                // @todo: breaks with too small browser size
                'label' => 'inputdatetime_5',
                'inputValue' => '12:09-3',
                'expectedValue' => '12:06',
                'expectedInternalValue' => '1970-01-01T12:06:00Z',
                'expectedValueAfterSave' => '1970-01-01T12:06:00+00:00',
                'comment' => '',
            ],
        ];
    }

    /**
     * @dataProvider dbTypeDateEvalTimeDataProvider
     * @param BackendTester $I
     * @param Example $testData
     * @todo fix these unstable tests
    public function dbTypeDateEvalTime(BackendTester $I, Example $testData)
    {
        $this->runInputFieldTest($I, $testData);
    }
     */

    /**
     * @param BackendTester $I
     */
    /*
    public function checkThatValidationWorks_EvalDateTime_DbTypeDateTime(AcceptanceTester $I)
    {
        // @todo fix these unstable tests
        $dataSets = [
            'input_37 dbType=datetime eval=datetime' => [
                [
                    'inputValue' => '05:23 29-01-2016',
                    'expectedValue' => '05:23 29-01-2016',
                    'expectedInternalValue' => '2016-01-29T05:23:00Z',
                    'expectedValueAfterSave' => '2016-01-29T05:23:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '05:23 13-13-2016',
                    'expectedValue' => '05:23 13-01-2017',
                    'expectedInternalValue' => '2017-01-13T05:23:00Z',
                    'expectedValueAfterSave' => '2017-01-13T05:23:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '05:23 29-02-2016',
                    'expectedValue' => '05:23 29-02-2016',
                    'expectedInternalValue' => '2016-02-29T05:23:00Z',
                    'expectedValueAfterSave' => '2016-02-29T05:23:00+00:00',
                    'comment' => 'Check valid leap year input',
                ],
                [
                    'inputValue' => '05:23 29-02-2015',
                    'expectedValue' => '05:23 01-03-2015',
                    'expectedInternalValue' => '2015-03-01T05:23:00Z',
                    'expectedValueAfterSave' => '2015-03-01T05:23:00+00:00',
                    'comment' => 'Check invalid leap year transformation',
                ],
            ],
            'input_18 eval=timesec' => [
                [
                    'inputValue' => '13:30:00',
                    'expectedValue' => '13:30:00',
                    'expectedInternalValue' => '13:30:00',
                    'expectedValueAfterSave' => (new \DateTime('13:30:00'))->getTimestamp(),
                    'comment' => '',
                ],
                [
                    'inputValue' => '12345',
                    'expectedValue' => '12:34:05',
                    'expectedInternalValue' => '12:34:05',
                    'expectedValueAfterSave' => (new \DateTime('12:34:05'))->getTimestamp(),
                    'comment' => '',
                ],
                [
                    'inputValue' => '12:04:04+5',
                    'expectedValue' => '12:09:04',
                    'expectedInternalValue' => '12:09:04',
                    'expectedValueAfterSave' => (new \DateTime('12:09:04'))->getTimestamp(),
                    'comment' => '',
                ],
            ],
            'input_6 eval=date' => [
                [
                    'inputValue' => '29-01-2016',
                    'expectedValue' => '29-01-2016',
                    'expectedInternalValue' => '2016-01-29T00:00:00Z',
                    'expectedValueAfterSave' => '2016-01-29T00:00:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '13-13-2016',
                    'expectedValue' => '13-01-2017',
                    'expectedInternalValue' => '2017-01-13T00:00:00Z',
                    'expectedValueAfterSave' => '2017-01-13T00:00:00+00:00',
                    'comment' => '',
                ],
                [
                    'inputValue' => '29-02-2016',
                    'expectedValue' => '29-02-2016',
                    'expectedInternalValue' => '2016-02-29T00:00:00Z',
                    'expectedValueAfterSave' => '2016-02-29T00:00:00+00:00',
                    'comment' => 'Check valid leap year input',
                ],
                [
                    'inputValue' => '29-02-2015',
                    'expectedValue' => '01-03-2015',
                    'expectedInternalValue' => '2015-03-01T00:00:00Z',
                    'expectedValueAfterSave' => '2015-03-01T00:00:00+00:00',
                    'comment' => 'Check invalid leap year transformation',
                ],
            ],
        ];
        $this->runTests($I, $dataSets);
    }
     */
}
