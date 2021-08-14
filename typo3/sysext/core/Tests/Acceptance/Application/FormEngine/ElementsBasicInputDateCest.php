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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\FormEngine;

use Codeception\Example;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for "elements_basic" date and time related input fields of ext:styleguide
 */
class ElementsBasicInputDateCest extends AbstractElementsBasicCest
{
    /**
     * Open list module of styleguide elements basic page
     *
     * @param ApplicationTester $I
     * @param PageTree $pageTree
     * @throws \Exception
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        $I->switchToContentFrame();

        // Open record and wait until form is ready
        $I->waitForText('elements basic', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_basic a[data-bs-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->waitForText('Edit Form', 3, 'h1');
        // scroll up all the way to get a clean shot to the tab panel
        $this->ensureTopOfFrameIsUsedAndClickTab($I, 'inputDateTime', 'input_23');

        $I->click('inputDateTime');
        $I->waitForText('inputDateTime', 3);
    }

    /**
     * dbTypeDateEvalDate data provider
     */
    protected function dbTypeDateEvalDateDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_2',
                'inputValue' => '29-01-2016',
                'expectedValue' => '29-01-2016',
                'expectedInternalValue' => '2016-01-29T00:00:00Z',
                'expectedValueAfterSave' => '2016-01-29T00:00:00+00:00',
                'comment' => 'inputdatetime_2 dbType=date eval=date simple input',
            ],
            [
                'label' => 'inputdatetime_2',
                'inputValue' => '13-13-2016',
                'expectedValue' => '13-01-2017',
                'expectedInternalValue' => '2017-01-13T00:00:00Z',
                'expectedValueAfterSave' => '2017-01-13T00:00:00+00:00',
                'comment' => 'inputdatetime_2 dbType=date eval=date month transformation',
            ],
            [
                'label' => 'inputdatetime_2',
                'inputValue' => '29-02-2016',
                'expectedValue' => '29-02-2016',
                'expectedInternalValue' => '2016-02-29T00:00:00Z',
                'expectedValueAfterSave' => '2016-02-29T00:00:00+00:00',
                'comment' => 'inputdatetime_2 dbType=date eval=date Check valid leap year input',
            ],
        ];
    }

    /**
     * @dataProvider dbTypeDateEvalDateDataProvider
     * @param ApplicationTester $I
     * @param Example $testData
     * @throws \Exception
     */
    public function dbTypeDateEvalDate(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    /**
     * dbType date eval datetime data provider
     */
    protected function dbTypeDateEvalDatetimeDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_3',
                'inputValue' => '05:23 29-01-2016',
                'expectedValue' => '05:23 29-01-2016',
                'expectedInternalValue' => '2016-01-29T05:23:00Z',
                'expectedValueAfterSave' => '2016-01-29T05:23:00+00:00',
                'comment' => 'inputdatetime_3 eval=datetime simple input',
            ],
            [
                'label' => 'inputdatetime_3',
                'inputValue' => '05:23 29-02-2016',
                'expectedValue' => '05:23 29-02-2016',
                'expectedInternalValue' => '2016-02-29T05:23:00Z',
                'expectedValueAfterSave' => '2016-02-29T05:23:00+00:00',
                'comment' => 'inputdatetime_3 eval=datetime Check valid leap year input',
            ],
            [
                'label' => 'inputdatetime_11',
                'inputValue' => '',
                'expectedValue' => '',
                'expectedInternalValue' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_11 eval=datetime range.lower=1627208536 Check range validation is ignored',
            ],
        ];
    }

    /**
     * @dataProvider dbTypeDateEvalDatetimeDataProvider
     * @param ApplicationTester $I
     * @param Example $testData
     * @throws \Exception
     */
    public function dbTypeDateEvalDatetime(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    /**
     * db type date eval time data provider
     */
    protected function dbTypeDateEvalTimeDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_5',
                'inputValue' => '13:30',
                'expectedValue' => '13:30',
                'expectedInternalValue' => '1970-01-01T13:30:00Z',
                'expectedValueAfterSave' => '1970-01-01T13:30:00+00:00',
                'comment' => 'inputdatetime_5 eval=time time input',
            ],
        ];
    }

    /**
     * @dataProvider dbTypeDateEvalTimeDataProvider
     * @param ApplicationTester $I
     * @param Example $testData
     * @throws \Exception
     */
    public function dbTypeDateEvalTime(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    /**
     * db type date eval time data provider
     */
    protected function dbTypeDateEvalTimeDataProvider_DbTypeDateTime(): array
    {
        return [
            [
                'label' => 'inputdatetime_4',
                'inputValue' => '05:23 29-01-2016',
                'expectedValue' => '05:23 29-01-2016',
                'expectedInternalValue' => '2016-01-29T05:23:00Z',
                'expectedValueAfterSave' => '2016-01-29T05:23:00+00:00',
                'comment' => 'inputdatetime_4 dbType=datetime eval=datetime no transformation',
            ],
            [
                'label' => 'inputdatetime_4',
                'inputValue' => '05:23 29-02-2016',
                'expectedValue' => '05:23 29-02-2016',
                'expectedInternalValue' => '2016-02-29T05:23:00Z',
                'expectedValueAfterSave' => '2016-02-29T05:23:00+00:00',
                'comment' => 'inputdatetime_4 dbType=datetime eval=datetime Check valid leap year input',
            ],
            [
                'label' => 'inputdatetime_6',
                'inputValue' => '13:30:00',
                'expectedValue' => '13:30:00',
                'expectedInternalValue' => '1970-01-01T13:30:00Z',
                'expectedValueAfterSave' => '1970-01-01T13:30:00+00:00',
                'comment' => 'inputdatetime_6 eval=timesec as time',
            ],
        ];
    }

    /**
     * @dataProvider dbTypeDateEvalTimeDataProvider_DbTypeDateTime
     * @param ApplicationTester $I
     * @param Example $testData
     * @throws \Exception
     */
    public function checkThatValidationWorks_EvalDateTime_DbTypeDateTime(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }
}
