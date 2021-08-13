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
    public function _before(ApplicationTester $I, PageTree $pageTree)
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
        $I->executeJS('document.querySelector(".module h1").scrollIntoView({ block: "end" });');

        $I->click('text');
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeTableWizardWithContent(ApplicationTester $I)
    {
        $this->openTableWizard($I);
        $I->amGoingTo('check for correct data in each column');
        foreach ($this->tableDataProvider() as $keyRow => $row) {
            foreach ($row as $keyCol => $col) {
                $value = $I->grabValueFrom('input[name="TABLE[c][' . $keyRow . '][' . $keyCol . ']"]');
                $I->assertEquals($col, $value);
            }
        }
    }

    /**
     * @param ApplicationTester $I
     */
    public function addAndRemoveTableColumnsAndRows(ApplicationTester $I)
    {
        $this->openTableWizard($I);
        $elementCountSelector = '#typo3-tablewizard td input';

        foreach ($this->addRemoveTableDataProvider() as $action) {
            $I->amGoingTo($action['description']);
            $I->click($action['click']);
            $I->click(self::$saveButtonLink);
            $I->canSeeNumberOfElements($elementCountSelector, $action['expected']);
        }
    }

    /**
     * @param ApplicationTester $I
     */
    public function moveTableColumnsAndRows(ApplicationTester $I)
    {
        $this->openTableWizard($I);
        $I->fillField('input[name="TABLE[c][0][0]"]', 'Test Column 1');
        $I->fillField('input[name="TABLE[c][0][1]"]', 'Test Column 2');

        $I->amGoingTo('move column to the right');
        $textOriginColumn = $I->grabValueFrom('input[name="TABLE[c][0][0]"]');
        $I->click('#typo3-tablewizard tr > th:nth-child(2) button[title="Move right"]');
        $I->click(self::$saveButtonLink);
        $textNewColumn = $I->grabValueFrom('input[name="TABLE[c][0][1]"]');
        $I->assertEquals($textOriginColumn, $textNewColumn);

        $I->amGoingTo('move column to the left');
        $textOriginColumn = $I->grabValueFrom('input[name="TABLE[c][0][1]"]');
        $I->click('#typo3-tablewizard tr > th:nth-child(3) button[title="Move left"]');
        $I->click(self::$saveButtonLink);
        $textNewColumn = $I->grabValueFrom('input[name="TABLE[c][0][0]"]');
        $I->assertEquals($textOriginColumn, $textNewColumn);

        $I->amGoingTo('move row down');
        $textOriginColumn = $I->grabValueFrom('input[name="TABLE[c][0][0]"]');
        $I->click('#typo3-tablewizard tbody tr:first-child > th button[title="Move down"]');
        $I->click(self::$saveButtonLink);
        $textNewColumn = $I->grabValueFrom('input[name="TABLE[c][1][0]"]');
        $I->assertEquals($textOriginColumn, $textNewColumn);

        $I->amGoingTo('move row up');
        $textOriginColumn = $I->grabValueFrom('input[name="TABLE[c][2][0]"]');
        $I->click('#typo3-tablewizard tbody tr:nth-child(3) > th button[title="Move up"]');
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
    public function clickSmallFieldsButton(ApplicationTester $I)
    {
        $this->openTableWizard($I);
        $fieldCount = 6;

        $I->click('button[title="Small fields"]');
        $I->seeNumberOfElements('#typo3-tablewizard td textarea', $fieldCount);
        $I->click('button[title="Small fields"]');
        $I->seeNumberOfElements('#typo3-tablewizard td input', $fieldCount);
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeTableWizardInitialWithoutContent(ApplicationTester $I)
    {
        $expectedRowFields = ['', '', '', ''];

        $I->amGoingTo('check for expected initial columns');
        $formSection = $this->getFormSectionByFieldLabel($I, 'text_17');
        $textarea = $formSection->findElement(WebDriverBy::xpath('.//*/textarea[@data-formengine-input-name]'));
        $I->fillField($textarea, '');
        $I->click(self::$saveButtonLink);

        $this->openTableWizard($I);
        foreach ($expectedRowFields as $keyCol => $col) {
            $value = $I->grabValueFrom('input[name="TABLE[c][0][' . $keyCol . ']"]');
            $I->assertEquals($col, $value);
        }
    }

    protected function addRemoveTableDataProvider(): array
    {
        return [
            [
                'description' => 'add a column',
                'click' => '#typo3-tablewizard tr > th:nth-child(2) button[title="Add column to the right"]',
                'expected' => 9,
            ],
            [
                'description' => 'remove a column',
                'click' => '#typo3-tablewizard tr > th:nth-child(2) button[title="Remove column"]',
                'expected' => 6,
            ],
            [
                'description' => 'add a row',
                'click' => '#typo3-tablewizard tbody tr:first-child > th button[title="Add row below"]',
                'expected' => 8,
            ],
            [
                'description' => 'remove a row',
                'click' => '#typo3-tablewizard tbody tr:first-child > th button[title="Remove row"]',
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

    /**
     * @param ApplicationTester $I
     * @throws \Exception
     */
    private function openTableWizard(ApplicationTester $I)
    {
        $I->amGoingTo('open the table wizard');
        $formSection = $this->getFormSectionByFieldLabel($I, 'text_17');
        $tableWizardButton = $formSection->findElement(WebDriverBy::className('btn-default'));
        $tableWizardButton->click();
        $I->see('Table wizard', 'h2');
        $I->waitForElement('#typo3-tablewizard');
    }
}
