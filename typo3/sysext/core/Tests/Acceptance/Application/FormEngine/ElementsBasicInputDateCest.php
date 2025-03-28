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

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for "elements_basic" date and time related input fields of ext:styleguide
 */
final class ElementsBasicInputDateCest extends AbstractElementsBasicCest
{
    /**
     * Open list module of styleguide elements basic page
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        $I->switchToContentFrame();

        // Open record and wait until form is ready
        $I->waitForText('elements basic', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_basic a[aria-label="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->waitForText('Edit Form', 3, 'h1');

        // Make sure the test operates on the "inputDateTime" tab
        $I->click('inputDateTime');
    }

    private function dbTypeDateEvalDateDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_2',
                'inputValue' => '2016-01-29',
                'expectedValue' => '2016-01-29',
                'expectedInternalValue' => '2016-01-29T00:00:00Z',
                'expectedValueAfterSave' => '2016-01-29T00:00:00+00:00',
                'comment' => 'inputdatetime_2 dbType=date eval=date simple input',
            ],
            [
                'label' => 'inputdatetime_2',
                'inputValue' => '2016-02-29',
                'expectedValue' => '2016-02-29',
                'expectedInternalValue' => '2016-02-29T00:00:00Z',
                'expectedValueAfterSave' => '2016-02-29T00:00:00+00:00',
                'comment' => 'inputdatetime_2 dbType=date eval=date Check valid leap year input',
            ],
        ];
    }

    #[DataProvider('dbTypeDateEvalDateDataProvider')]
    public function dbTypeDateEvalDate(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    private function dbTypeDateEvalDatetimeDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_3',
                'inputValue' => '2016-01-29 05:23',
                'expectedValue' => '2016-01-29 05:23',
                'expectedInternalValue' => '2016-01-29T05:23:00Z',
                'expectedValueAfterSave' => '2016-01-29T05:23:00+00:00',
                'comment' => 'inputdatetime_3 eval=datetime simple input',
            ],
            [
                'label' => 'inputdatetime_3',
                'inputValue' => '2016-02-29 05:23',
                'expectedValue' => '2016-02-29 05:23',
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
                'comment' => 'inputdatetime_11 eval=datetime range.lower=1627208536 Check range validation is ignored on empty value',
            ],
            [
                'label' => 'inputdatetime_23',
                'inputValue' => '1970-01-01 00:00',
                'expectedValue' => '1970-01-01 00:00',
                'expectedInternalValue' => '1970-01-01T00:00:00Z',
                'expectedValueAfterSave' => '1970-01-01T00:00:00+00:00',
                'comment' => 'inputdatetime_23 format=datetime nullable=true Change unix epoch time is not considered empty',
            ],
            [
                'label' => 'inputdatetime_31',
                'inputValue' => '1970-01-01 00:00',
                'expectedValue' => '1970-01-01 00:00',
                'expectedInternalValue' => '1970-01-01T00:00:00Z',
                'expectedValueAfterSave' => '1970-01-01T00:00:00+00:00',
                'comment' => 'inputdatetime_31 nullable=true default=0 Change unix epoch time is not considered empty',
            ],
            [
                'label' => 'inputdatetime_35',
                'inputValue' => '2022-03-31 05:23',
                'expectedValue' => '2022-03-31 05:23',
                'expectedInternalValue' => '2022-03-31T05:23:00Z',
                'expectedValueAfterSave' => '2022-03-31T05:23:00+00:00',
                'comment' => 'inputdatetime_35 eval=datetime range.lower=1627208536 Check range validation',
            ],
        ];
    }

    #[DataProvider('dbTypeDateEvalDatetimeDataProvider')]
    public function dbTypeDateEvalDatetime(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    private function dbTypeDateEvalTimeDataProvider(): array
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

    #[DataProvider('dbTypeDateEvalTimeDataProvider')]
    public function dbTypeDateEvalTime(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    private function dbTypeDateEvalTimeDataProvider_DbTypeDateTime(): array
    {
        return [
            [
                'label' => 'inputdatetime_4',
                'inputValue' => '2016-01-29 05:23',
                'expectedValue' => '2016-01-29 05:23',
                'expectedInternalValue' => '2016-01-29T05:23:00Z',
                'expectedValueAfterSave' => '2016-01-29T05:23:00+00:00',
                'comment' => 'inputdatetime_4 dbType=datetime eval=datetime no transformation',
            ],
            [
                'label' => 'inputdatetime_4',
                'inputValue' => '2016-02-29 05:23',
                'expectedValue' => '2016-02-29 05:23',
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

    #[DataProvider('dbTypeDateEvalTimeDataProvider_DbTypeDateTime')]
    public function checkThatValidationWorks_EvalDateTime_DbTypeDateTime(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    private function typeDatetimeFormatTimeMidnightDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_5',
                'inputValue' => '00:00',
                'expectedValue' => '00:00',
                'expectedInternalValue' => '1970-01-01T00:00:00Z',
                // Field is not nullable, 00:00 is therefore interpred as empty
                'expectedInternalValueAfterSave' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_5 format=time',
            ],
            [
                'label' => 'inputdatetime_12',
                'inputValue' => '00:00',
                'expectedValue' => '00:00',
                'expectedInternalValue' => '1970-01-01T00:00:00Z',
                'expectedValueAfterSave' => '1970-01-01T00:00:00+00:00',
                'comment' => 'inputdatetime_12 format=time dbType=time',
            ],
            [
                'label' => 'inputdatetime_25',
                'inputValue' => '00:00',
                'expectedValue' => '00:00',
                'expectedInternalValue' => '1970-01-01T00:00:00Z',
                'expectedValueAfterSave' => '1970-01-01T00:00:00+00:00',
                'comment' => 'inputdatetime_25 format=time nullable=true',
            ],
            [
                'label' => 'inputdatetime_32',
                'inputValue' => '00:00',
                'expectedValue' => '00:00',
                'expectedInternalValue' => '1970-01-01T00:00:00Z',
                'expectedValueAfterSave' => '1970-01-01T00:00:00+00:00',
                'comment' => 'inputdatetime_32 format=time dbType=time nullable=true',
            ],
        ];
    }

    #[DataProvider('typeDatetimeFormatTimeMidnightDataProvider')]
    public function typeDatetimeFormatTimeMidnight(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    private function typeDatetimeFormatTimeAnteMeridiemDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_5',
                'inputValue' => '05:43',
                'expectedValue' => '05:43',
                'expectedInternalValue' => '1970-01-01T05:43:00Z',
                'expectedValueAfterSave' => '1970-01-01T05:43:00+00:00',
                'comment' => 'inputdatetime_5 format=time',
            ],
            [
                'label' => 'inputdatetime_12',
                'inputValue' => '05:43',
                'expectedValue' => '05:43',
                'expectedInternalValue' => '1970-01-01T05:43:00Z',
                'expectedValueAfterSave' => '1970-01-01T05:43:00+00:00',
                'comment' => 'inputdatetime_12 format=time dbType=time',
            ],
            [
                'label' => 'inputdatetime_25',
                'inputValue' => '05:43',
                'expectedValue' => '05:43',
                'expectedInternalValue' => '1970-01-01T05:43:00Z',
                'expectedValueAfterSave' => '1970-01-01T05:43:00+00:00',
                'comment' => 'inputdatetime_25 format=time nullable=true',
            ],
            [
                'label' => 'inputdatetime_32',
                'inputValue' => '05:43',
                'expectedValue' => '05:43',
                'expectedInternalValue' => '1970-01-01T05:43:00Z',
                'expectedValueAfterSave' => '1970-01-01T05:43:00+00:00',
                'comment' => 'inputdatetime_32 format=time dbType=time nullable=true',
            ],
        ];
    }

    #[DataProvider('typeDatetimeFormatTimeAnteMeridiemDataProvider')]
    public function typeDatetimeFormatTimeAnteMeridiem(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    private function typeDatetimeFormatTimeEmptyDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_5',
                'inputValue' => '',
                'expectedValue' => '',
                'expectedInternalValue' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_5 format=time',
            ],
            [
                'label' => 'inputdatetime_12',
                'inputValue' => '',
                'expectedValue' => '',
                'expectedInternalValue' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_12 format=time dbType=time',
            ],
            [
                'label' => 'inputdatetime_25',
                'inputValue' => '',
                'expectedValue' => '',
                'expectedInternalValue' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_25 format=time nullable=true',
            ],
            [
                'label' => 'inputdatetime_32',
                'inputValue' => '',
                'expectedValue' => '',
                'expectedInternalValue' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_32 format=time dbType=time nullable=true',
            ],
        ];
    }

    #[DataProvider('typeDatetimeFormatTimeEmptyDataProvider')]
    public function typeDatetimeFormatTimeEmpty(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    private function typeDatetimeFormatTimesecMidnightDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_6',
                'inputValue' => '00:00:00',
                'expectedValue' => '00:00:00',
                'expectedInternalValue' => '1970-01-01T00:00:00Z',
                // Field is not nullable, 00:00 is therefore interpred as empty
                'expectedInternalValueAfterSave' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_6 format=timesec',
            ],
            [
                'label' => 'inputdatetime_13',
                'inputValue' => '00:00:00',
                'expectedValue' => '00:00:00',
                'expectedInternalValue' => '1970-01-01T00:00:00Z',
                'expectedValueAfterSave' => '1970-01-01T00:00:00+00:00',
                'comment' => 'inputdatetime_13 format=timesec dbType=time',
            ],
            [
                'label' => 'inputdatetime_26',
                'inputValue' => '00:00:00',
                'expectedValue' => '00:00:00',
                'expectedInternalValue' => '1970-01-01T00:00:00Z',
                'expectedValueAfterSave' => '1970-01-01T00:00:00+00:00',
                'comment' => 'inputdatetime_26 format=timesec nullable=true',
            ],
            [
                'label' => 'inputdatetime_33',
                'inputValue' => '00:00:00',
                'expectedValue' => '00:00:00',
                'expectedInternalValue' => '1970-01-01T00:00:00Z',
                'expectedValueAfterSave' => '1970-01-01T00:00:00+00:00',
                'comment' => 'inputdatetime_33 format=timesec dbType=time nullable=true',
            ],
        ];
    }

    #[DataProvider('typeDatetimeFormatTimesecMidnightDataProvider')]
    public function typeDatetimeFormatTimesecMidnight(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    private function typeDatetimeFormatTimesecAnteMeridiemDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_6',
                'inputValue' => '05:43:21',
                'expectedValue' => '05:43:21',
                'expectedInternalValue' => '1970-01-01T05:43:21Z',
                'expectedValueAfterSave' => '1970-01-01T05:43:21+00:00',
                'comment' => 'inputdatetime_6 format=timesec',
            ],
            [
                'label' => 'inputdatetime_13',
                'inputValue' => '05:43:21',
                'expectedValue' => '05:43:21',
                'expectedInternalValue' => '1970-01-01T05:43:21Z',
                'expectedValueAfterSave' => '1970-01-01T05:43:21+00:00',
                'comment' => 'inputdatetime_13 format=timesec dbType=time',
            ],
            [
                'label' => 'inputdatetime_26',
                'inputValue' => '05:43:21',
                'expectedValue' => '05:43:21',
                'expectedInternalValue' => '1970-01-01T05:43:21Z',
                'expectedValueAfterSave' => '1970-01-01T05:43:21+00:00',
                'comment' => 'inputdatetime_26 format=timesec nullable=true',
            ],
            [
                'label' => 'inputdatetime_33',
                'inputValue' => '05:43:21',
                'expectedValue' => '05:43:21',
                'expectedInternalValue' => '1970-01-01T05:43:21Z',
                'expectedValueAfterSave' => '1970-01-01T05:43:21+00:00',
                'comment' => 'inputdatetime_33 format=timesec dbType=time nullable=true',
            ],
        ];
    }

    #[DataProvider('typeDatetimeFormatTimesecAnteMeridiemDataProvider')]
    public function typeDatetimeFormatTimesecAnteMeridiem(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    private function typeDatetimeFormatTimesecEmptyDataProvider(): array
    {
        return [
            [
                'label' => 'inputdatetime_6',
                'inputValue' => '',
                'expectedValue' => '',
                'expectedInternalValue' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_6 format=timesec',
            ],
            [
                'label' => 'inputdatetime_13',
                'inputValue' => '',
                'expectedValue' => '',
                'expectedInternalValue' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_13 format=timesec dbType=time',
            ],
            [
                'label' => 'inputdatetime_26',
                'inputValue' => '',
                'expectedValue' => '',
                'expectedInternalValue' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_26 format=timesec nullable=true',
            ],
            [
                'label' => 'inputdatetime_33',
                'inputValue' => '',
                'expectedValue' => '',
                'expectedInternalValue' => '',
                'expectedValueAfterSave' => '',
                'comment' => 'inputdatetime_33 format=timesec dbType=time nullable=true',
            ],
        ];
    }

    #[DataProvider('typeDatetimeFormatTimesecEmptyDataProvider')]
    public function typeDatetimeFormatTimesecEmpty(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    /**
     * Overridden from AbstractElementsInBasicCest to cope with flatpickr, until it's gone
     */
    protected function runInputFieldTest(ApplicationTester $I, Example $testData, ?string $initializedInputFieldXpath = null): void
    {
        $initializedInputFieldXpath = '(//label/code[contains(text(),"[' . $testData['label'] . ']")]/..)'
            . '[1]/parent::*//*/input[@data-formengine-datepicker-real-input-name]';

        parent::runInputFieldTest($I, $testData, $initializedInputFieldXpath);
    }

    /**
     * Return the visible input field of element in question.
     * Overridden from AbstractElementsInBasicCest to cope with flatpickr, until it's gone
     */
    protected function getInputField(RemoteWebElement $formSection): RemoteWebElement
    {
        return $formSection->findElement(WebDriverBy::xpath('.//*/input[@data-formengine-datepicker-real-input-name]'));
    }

    /**
     * Overridden from AbstractElementsInBasicCest to cope with flatpickr, until it's gone
     */
    protected function getHiddenField(RemoteWebElement $formSection, RemoteWebElement $inputField): RemoteWebElement
    {
        $hiddenFieldXPath = './/*/input[@name="' . $inputField->getAttribute('data-formengine-datepicker-real-input-name') . '"]';
        return $formSection->findElement(WebDriverBy::xpath($hiddenFieldXPath));
    }
}
