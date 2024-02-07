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
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for "elements_basic" fields with EMPTY radio/select fields of ext:styleguide
 */
final class ElementsEmptyElementsCest extends AbstractElementsBasicCest
{
    /**
     * Open list module of styleguide elements basic page
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        // Wait until DOM actually rendered everything
        $I->switchToContentFrame();

        // Open record and wait until form is ready
        $I->waitForText('elements basic', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_basic a[aria-label="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Form', 3, 'h1');

        // Make sure the test operates on the "radio" tab
        $I->click('radio');
    }

    /**
     * Method to run basic elements input type=radio field test details
     */
    private function runRadioFieldTest(ApplicationTester $I, Example $testData): void
    {
        $fieldLabel = $testData['label'];
        $waitElementXpath = '(//legend/code[contains(text(),"[' . $fieldLabel . ']")]/..)';
        $initializedInputFieldXpath = $waitElementXpath
            . '[1]/parent::*//*/input[@value="' . $testData['inputValue'] . '"]';

        // If we expect to not find a radio button, enforce this step to succeed and quit.
        // Else, we expect a valid radio element and can continue.
        if ($testData['expectedValue'] === false) {
            $I->dontSeeElement($initializedInputFieldXpath);
            return;
        }

        // Wait until JS initialized everything
        $I->waitForElement($waitElementXpath, 30);

        if ($testData['comment'] !== '') {
            $I->comment($testData['comment']);
        }

        $formSection = $this->getFormSectionByFieldLabel($I, $fieldLabel);

        $I->comment('Check radio button ' . $fieldLabel . ' with value "' . $testData['inputValue'] . '"');
        $I->seeElement($initializedInputFieldXpath);

        $radioField = $formSection->findElement(WebDriverBy::xpath($initializedInputFieldXpath));
        $radioField->click();
        // Change focus to trigger validation
        $radioField->sendKeys(WebDriverKeys::TAB);
        // Press ESC so that any opened popup (potentially from the field below) is closed
        $radioField->sendKeys(WebDriverKeys::ESCAPE);
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->comment('Test value radio field before saving');
        $I->assertEquals($testData['expectedValue'], $radioField->isSelected());

        $I->comment('Save the form');
        $saveButtonLink = '//*/button[@name="_savedok"][1]';
        $I->waitForElement($saveButtonLink, 30);
        $I->click($saveButtonLink);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElement('//*/button[@name="_savedok"][not(@disabled)][1]', 30);
        $I->waitForElement($waitElementXpath, 30);

        // Find the fields again (after reload of iFrame)
        $formSection = $this->getFormSectionByFieldLabel($I, $fieldLabel);
        $I->seeElement($initializedInputFieldXpath);
        $radioField = $formSection->findElement(WebDriverBy::xpath($initializedInputFieldXpath));

        // Validate save was successful
        $I->comment('Compare value radio state after saving');
        $I->assertEquals($testData['expectedValue'], $radioField->isSelected());
    }

    /**
     * Data provider to check various type=input variants.
     * expectedValue=boolean only, reflected radio selection state.
     */
    private function simpleRadioFieldsDataProvider(): array
    {
        return [
            [
                'label' => 'radio_4',
                'inputValue' => 'foo',
                'expectedValue' => true,
                'comment' => 'Existing radio, selectable',
            ],
            [
                'label' => 'radio_4',
                'inputValue' => '',
                'expectedValue' => true,
                'comment' => 'Existing radio, empty selectable',
            ],
            [
                'label' => 'radio_4',
                'inputValue' => 'foob',
                'expectedValue' => false,
                'comment' => 'Existing radio, invalid value',
            ],
            [
                'label' => 'non_existing_radio_4',
                'inputValue' => 'foo',
                'expectedValue' => false,
                'comment' => 'Non-existing radio',
            ],
        ];
    }

    /**
     * @dataProvider simpleRadioFieldsDataProvider
     */
    public function simpleRadioFields(ApplicationTester $I, Example $testData): void
    {
        $this->runRadioFieldTest($I, $testData);
    }
}
