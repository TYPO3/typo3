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

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for inline 1n
 */
final class Inline1nCest
{
    /**
     * Open styleguide inline 1n page in list module
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'inline 1n']);
        $I->switchToContentFrame();

        $I->waitForText('inline 1n', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_inline_1n a[aria-label="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForText('Edit Form', 3, 'h1');
    }

    public function checkIfExpandsAndCollapseShowInput(ApplicationTester $I): void
    {
        $I->click('div[data-bs-toggle="formengine-inline"]', '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["]');
        $I->waitForElement('input[data-formengine-input-name="data[tx_styleguide_inline_1n_inline_1_child][1][input_1]"]');
        $I->seeInField('input[data-formengine-input-name="data[tx_styleguide_inline_1n_inline_1_child][1][input_1]"]', 'lipsum');
        $I->click('div[data-bs-toggle="formengine-inline"]', '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["]');
        $I->waitForElementNotVisible('[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["] .panel');
    }

    public function hideAndUnhideInline1nInlineElement(ApplicationTester $I): void
    {
        $I->click('button span[data-identifier="actions-edit-hide"]', '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["]');
        $I->waitForElement('[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["].t3-form-field-container-inline-hidden');
        $I->waitForElement('[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["] button span[data-identifier="actions-edit-unhide"]');
        $I->click('button span[data-identifier="actions-edit-unhide"]', '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["]');
        $I->waitForElementNotVisible('[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["].t3-form-field-container-inline-hidden', 2);
    }

    public function createInline1nInlineElement(ApplicationTester $I): void
    {
        $I->click('button[data-type="newRecord"]');

        $fieldLabel = 'input_1';
        $testValue = 'Fo Bar';
        $I->wait(2);

        $this->fillFieldByLabel($I, $fieldLabel, $testValue);

        $I->click('button[name="_savedok"]');
        $I->wait(3);
        $I->click('a[title="Close"]');
        $I->wait(3);

        $I->click('button[data-table="pages_translated"] .icon-actions-view-list-collapse');
        $I->wait(1);
        $I->click('button[data-table="tx_styleguide_inline_1n"] .icon-actions-view-list-collapse');
        $I->wait(1);

        $I->see('lipsum', '#recordlist-tx_styleguide_inline_1n_inline_1_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(3) > a');
        $I->see('Fo Bar', '#recordlist-tx_styleguide_inline_1n_inline_1_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(4) > td:nth-child(3) > a');

        $I->click('button[data-table="tx_styleguide_inline_1n"] .icon-actions-view-list-expand');
        $I->wait(1);
        $I->click('button[data-table="pages_translated"] .icon-actions-view-list-expand');
        $I->wait(1);
    }

    /**
     * @depends createInline1nInlineElement
     */
    public function checkIfCanSortingInlineElement(ApplicationTester $I): void
    {
        $I->click('button span[data-identifier="actions-move-down"]', '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["]');
        $I->click('button[name="_savedok"]');
        $I->wait(3);
        $I->click('a[title="Close"]');
        $I->wait(3);

        $I->click('button[data-table="pages_translated"] .icon-actions-view-list-collapse');
        $I->wait(1);
        $I->click('button[data-table="tx_styleguide_inline_1n"] .icon-actions-view-list-collapse');
        $I->wait(1);

        $I->see('Fo Bar', '#recordlist-tx_styleguide_inline_1n_inline_1_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(3) > a');
        $I->see('lipsum', '#recordlist-tx_styleguide_inline_1n_inline_1_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(2) > td:nth-child(3) > a');

        $I->click('button[data-table="tx_styleguide_inline_1n"] .icon-actions-view-list-expand');
        $I->wait(1);
        $I->click('button[data-table="pages_translated"] .icon-actions-view-list-expand');
        $I->wait(1);
    }

    public function changeInline1nInlineInput(ApplicationTester $I): void
    {
        $I->click('div[data-bs-toggle="formengine-inline"]', '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child][1"]');
        $I->waitForElement('input[data-formengine-input-name="data[tx_styleguide_inline_1n_inline_1_child][1][input_1]"]');
        $I->fillField('input[data-formengine-input-name="data[tx_styleguide_inline_1n_inline_1_child][1][input_1]"]', 'hello world');
        $I->click('button[name="_savedok"]');
        $I->wait(3);
        $I->click('a[title="Close"]');
        $I->wait(3);
        $I->see('hello world');
    }

    public function deleteInline1nInlineElement(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $inlineElementToDelete = '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child][1"]';
        $I->click('button span[data-identifier="actions-edit-delete"]', $inlineElementToDelete);
        $modalDialog->clickButtonInDialog('button[name="no"]');
        // switch form Dialogbox back to IFrame
        $I->switchToContentFrame();
        $I->seeElement($inlineElementToDelete);

        $I->click('button span[data-identifier="actions-edit-delete"]', $inlineElementToDelete);

        // don't use $modalDialog->clickButtonInDialog due to too low timeout
        $modalDialog->canSeeDialog();
        $I->click('button[name="yes"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        // switch form Dialogbox back to IFrame
        $I->switchToContentFrame();
        $I->waitForElementNotVisible($inlineElementToDelete);
    }

    public function disableInline1nInlineElementWithoutRenderedDisableField(ApplicationTester $I): void
    {
        // Switch to "inline_2" tab.
        $I->click('inline_2');

        // Open the inline element.
        $I->click('div[data-bs-toggle="formengine-inline"]', '[data-field-name^="[tx_styleguide_inline_1n_inline_2_child]["]');
        $I->waitForElement('input[data-formengine-input-name="data[tx_styleguide_inline_1n_inline_2_child][1][input_1]"]');

        // Hide the inline element.
        $I->click('button span[data-identifier="actions-edit-hide"]', '[data-field-name^="[tx_styleguide_inline_1n_inline_2_child]["]');
        $I->waitForElement('[data-field-name^="[tx_styleguide_inline_1n_inline_2_child]["].t3-form-field-container-inline-hidden');
        $I->waitForElement('[data-field-name^="[tx_styleguide_inline_1n_inline_2_child]["] button span[data-identifier="actions-edit-unhide"]');

        // Save the inline element.
        $I->click('button[name="_savedok"]');
        $I->wait(3);

        // Unhide the previously hidden inline element.
        $I->click('button span[data-identifier="actions-edit-unhide"]', '[data-field-name^="[tx_styleguide_inline_1n_inline_2_child]["]');
        $I->waitForElementNotVisible('[data-field-name^="[tx_styleguide_inline_1n_inline_2_child]["].t3-form-field-container-inline-hidden', 2);
    }

    private function fillFieldByLabel(ApplicationTester $I, $fieldLabel, $testValue): void
    {
        $fieldContext = $I->executeInSelenium(static function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) use (
            $fieldLabel
        ) {
            return $webdriver->findElement(
                \Facebook\WebDriver\WebDriverBy::xpath('(//label[contains(text(),"' . $fieldLabel . '")])[1]/ancestor::fieldset[@class="form-section"][1]')
            );
        });

        $visibleFieldXpath = './/*/input[@data-formengine-input-name]';
        $clearButtonXpath = '(//label[contains(text(),"' . $fieldLabel . '")])[1]/parent::*//*/button[@class="close"]';
        $initializedInputFieldXpath = '(//label[contains(text(),"' . $fieldLabel . '")])[1]/parent::*//*/input[@data-formengine-input-name][@data-formengine-input-initialized]';
        $I->waitForElement($initializedInputFieldXpath, 30);
        $inputField = $fieldContext->findElement(\Facebook\WebDriver\WebDriverBy::xpath($visibleFieldXpath));
        $internalInputFieldXpath = '(//label[contains(text(),"' . $fieldLabel . '")])[1]/parent::*//*/input[@name="' . $inputField->getAttribute('data-formengine-input-name') . '"]';

        $I->waitForElement($internalInputFieldXpath, 30);
        $I->waitForElement($clearButtonXpath, 30);

        $I->fillField($inputField, $testValue);
        $inputField->sendKeys(WebDriverKeys::TAB);
        $fieldContext->click();
        $I->comment('Test value of "visible" field');
        $I->canSeeInField($inputField, $testValue);
    }
}
