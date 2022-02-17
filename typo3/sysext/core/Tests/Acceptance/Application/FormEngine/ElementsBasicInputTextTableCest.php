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

use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for table wizard
 */
class ElementsBasicInputTextTableCest extends AbstractElementsBasicCest
{
    protected static string $saveButtonLink = '//*/button[@name="_savedok"][1]';

    /**
     * Open styleguide elements basic page in list module
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

        // Make sure the test operates on the "text" tab
        $I->click('text');
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeTableWizardWithContent(ApplicationTester $I): void
    {
        $I->amGoingTo('check for correct data in each column');
        foreach ($this->tableDataProvider() as $keyRow => $row) {
            foreach ($row as $keyCol => $col) {
                $input = $this->getTable($I)->findElement(WebDriverBy::cssSelector('input[name="TABLE[c][' . $keyRow . '][' . $keyCol . ']"]'));
                $value = $input->getAttribute('value');
                $I->assertEquals($col, $value);
            }
        }
    }

    /**
     * @param ApplicationTester $I
     */
    public function addAndRemoveTableColumnsAndRows(ApplicationTester $I): void
    {
        $formSection = $this->getTable($I);
        $formSection->getLocationOnScreenOnceScrolledIntoView();

        foreach ($this->addRemoveTableDataProvider() as $action) {
            $I->amGoingTo($action['description']);
            $formSection->findElement(WebDriverBy::cssSelector($action['click']))->click();
            $elementCountSelector = count($formSection->findElements(WebDriverBy::cssSelector('typo3-backend-table-wizard td input')));
            $formSection->getLocationOnScreenOnceScrolledIntoView();
            $I->assertEquals($elementCountSelector, $action['expected']);
        }
    }

    /**
     * @param ApplicationTester $I
     */
    public function moveTableColumnsAndRows(ApplicationTester $I): void
    {
        $formSection = $this->getTable($I);
        $formSection->getLocationOnScreenOnceScrolledIntoView();

        $I->fillField('input[name="TABLE[c][0][0]"]', 'Test Column 1');
        $I->fillField('input[name="TABLE[c][0][1]"]', 'Test Column 2');

        $I->amGoingTo('move column to the right');
        $textOriginColumn = $I->grabValueFrom('input[name="TABLE[c][0][0]"]');
        $I->click('typo3-backend-table-wizard tr > th:nth-child(2) button[title="Move right"]');
        $I->click(self::$saveButtonLink);
        $textNewColumn = $I->grabValueFrom('input[name="TABLE[c][0][1]"]');
        $I->assertEquals($textOriginColumn, $textNewColumn);

        $I->amGoingTo('move column to the left');
        $textOriginColumn = $I->grabValueFrom('input[name="TABLE[c][0][1]"]');
        $I->click('typo3-backend-table-wizard tr > th:nth-child(3) button[title="Move left"]');
        $I->click(self::$saveButtonLink);
        $textNewColumn = $I->grabValueFrom('input[name="TABLE[c][0][0]"]');
        $I->assertEquals($textOriginColumn, $textNewColumn);

        $I->amGoingTo('move row down');
        $textOriginColumn = $I->grabValueFrom('input[name="TABLE[c][0][0]"]');
        $I->click('typo3-backend-table-wizard tbody tr:first-child > th button[title="Move down"]');
        $I->click(self::$saveButtonLink);
        $textNewColumn = $I->grabValueFrom('input[name="TABLE[c][1][0]"]');
        $I->assertEquals($textOriginColumn, $textNewColumn);

        $I->amGoingTo('move row up');
        $textOriginColumn = $I->grabValueFrom('input[name="TABLE[c][2][0]"]');
        $I->click('typo3-backend-table-wizard tbody tr:nth-child(3) > th button[title="Move up"]');
        $I->click(self::$saveButtonLink);
        $textNewColumn = $I->grabValueFrom('input[name="TABLE[c][1][0]"]');
        $I->assertEquals($textOriginColumn, $textNewColumn);
    }

    /**
     * Click field resize button to see if
     * input fields change to textarea
     *
     * @param ApplicationTester $I
     * @throws \Exception
     */
    public function clickSmallFieldsButton(ApplicationTester $I): void
    {
        $fieldCount = 6;
        $formSection = $this->getTable($I);
        $formSection->getLocationOnScreenOnceScrolledIntoView();

        $smallFieldsButton = $this->getTable($I)->findElement(WebDriverBy::cssSelector('typo3-backend-table-wizard button[title="Small fields"]'));
        $smallFieldsButton->click();

        $textareaFields = $this->getTable($I)->findElements(WebDriverBy::cssSelector('typo3-backend-table-wizard td textarea'));
        $I->assertCount($fieldCount, $textareaFields);
        $smallFieldsButton->click();
        $inputFields = $this->getTable($I)->findElements(WebDriverBy::cssSelector('typo3-backend-table-wizard td input'));
        $I->assertCount($fieldCount, $inputFields);
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeTableWizardInitialWithoutContent(ApplicationTester $I): void
    {
        $I->amGoingTo('check for expected initial columns');
        $formSection = $this->getTable($I);
        $I->click('button[title="Remove column"]');
        $I->click('button[title="Remove row"]');

        $fieldCount = count($formSection->findElements(WebDriverBy::cssSelector('typo3-backend-table-wizard input')));

        // @todo: why the fuck are 2 expected when there is only one visible??!!
        $I->assertEquals(2, $fieldCount);
    }

    protected function addRemoveTableDataProvider(): array
    {
        return [
            [
                'description' => 'add a column',
                'click' => 'typo3-backend-table-wizard tr > th:nth-child(2) button[title="Add column to the right"]',
                'expected' => 9,
            ],
            [
                'description' => 'remove a column',
                'click' => 'typo3-backend-table-wizard tr > th:nth-child(2) button[title="Remove column"]',
                'expected' => 6,
            ],
            [
                'description' => 'add a row',
                'click' => 'typo3-backend-table-wizard tbody tr:first-child > th button[title="Add row below"]',
                'expected' => 8,
            ],
            [
                'description' => 'remove a row',
                'click' => 'typo3-backend-table-wizard tbody tr:first-child > th button[title="Remove row"]',
                'expected' => 6,
            ],
        ];
    }

    /**
     * Provide sample data for table cols/rows to compare with
     */
    protected function tableDataProvider(): array
    {
        return [
            ['row1 col1', 'row1 col2'],
            ['row2 col1', 'row2 col2'],
            ['row3 col1', 'row3 col2'],
        ];
    }

    private function getTable(ApplicationTester $I): RemoteWebElement
    {
        return $this->getFormSectionByFieldLabel($I, 'text_17');
    }
}
