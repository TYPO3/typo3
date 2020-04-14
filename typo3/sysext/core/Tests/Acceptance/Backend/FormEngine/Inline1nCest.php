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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\FormEngine;

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for inline 1n
 */
class Inline1nCest
{
    /**
     * Open styleguide inline 1n page in list module
     *
     * @param BackendTester $I
     * @param PageTree $pageTree
     * @throws \Exception
     */
    public function _before(BackendTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'inline 1n']);
        $I->switchToContentFrame();

        $I->waitForText('inline 1n', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_inline_1n a[data-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForText('Edit Form', 3, 'h1');
    }

    /**
     * @param Admin $I
     */
    public function checkIfExpandsAndCollapseShowInput(BackendTester $I)
    {
        $I->wantTo('Expands the inline Element');
        $I->click('div[data-toggle="formengine-inline"]', '[data-field-name^="[tx_styleguide_inline_1n_child]["]');
        $I->waitForElement('input[data-formengine-input-name="data[tx_styleguide_inline_1n_child][1][input_1]"]');
        $I->wantTo('check is the value in input');
        $I->seeInField('input[data-formengine-input-name="data[tx_styleguide_inline_1n_child][1][input_1]"]', 'lipsum');
        $I->wantTo('Collapse the inline Element');
        $I->click('div[data-toggle="formengine-inline"]', '[data-field-name^="[tx_styleguide_inline_1n_child]["]');
        $I->waitForElementNotVisible('[data-field-name^="[tx_styleguide_inline_1n_child]["] .panel-collapse');
    }

    /**
     * @param Admin $I
     */
    public function hideAndUnhideInline1nInlineElement(BackendTester $I)
    {
        $I->wantTo('Can hide a Inline Element');
        $I->click('a span[data-identifier="actions-edit-hide"]', '[data-field-name^="[tx_styleguide_inline_1n_child]["]');
        $I->waitForElement('[data-field-name^="[tx_styleguide_inline_1n_child]["].t3-form-field-container-inline-hidden');
        $I->waitForElement('[data-field-name^="[tx_styleguide_inline_1n_child]["] a span[data-identifier="actions-edit-unhide"]');
        $I->wantTo('Can unhide a Inline Element');
        $I->click('a span[data-identifier="actions-edit-unhide"]', '[data-field-name^="[tx_styleguide_inline_1n_child]["]');
        $I->waitForElementNotVisible('[data-field-name^="[tx_styleguide_inline_1n_child]["].t3-form-field-container-inline-hidden', 2);
    }

    /**
     * @param Admin $I
     */
    public function createInline1nInlineElement(BackendTester $I)
    {
        $I->click('span[data-identifier="actions-add"]', 'div.typo3-newRecordLink');

        $fieldLabel = 'input_1';
        $testValue = 'Fo Bar';
        $I->wait(2);

        $this->fillFieldByLabel($I, $fieldLabel, $testValue);

        $I->click('button[name="_savedok"]');
        $I->wait(3);
        $I->click('a[title="Close"]');
        $I->wait(3);

        $I->executeJS('$(\'a[data-table="pages"] .icon-actions-view-list-collapse\').click();');
        $I->wait(1);
        $I->executeJS('$(\'a[data-table="tx_styleguide_inline_1n"] .icon-actions-view-list-collapse\').click();');
        $I->wait(1);

        $I->see('lipsum', '#recordlist-tx_styleguide_inline_1n_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > a');
        $I->see('Fo Bar', '#recordlist-tx_styleguide_inline_1n_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(4) > td:nth-child(2) > a');
    }

    /**
     * @depends createInline1nInlineElement
     * @param Admin $I
     */
    public function checkIfCanSortingInlineElement(BackendTester $I)
    {
        $I->wantTo('Can sort an Inline Element');
        $I->click('a span[data-identifier="actions-move-down"]', '[data-field-name^="[tx_styleguide_inline_1n_child]["]');
        $I->click('button[name="_savedok"]');
        $I->wait(3);
        $I->click('a[title="Close"]');
        $I->wait(3);

        $I->executeJS('$(\'a[data-table="pages"] .icon-actions-view-list-collapse\').click();');
        $I->wait(1);
        $I->executeJS('$(\'a[data-table="tx_styleguide_inline_1n"] .icon-actions-view-list-collapse\').click();');
        $I->wait(1);

        $I->wantTo('Check new sorting');
        $I->see('Fo Bar', '#recordlist-tx_styleguide_inline_1n_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > a');
        $I->see('lipsum', '#recordlist-tx_styleguide_inline_1n_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(2) > td:nth-child(2) > a');
    }

    /**
     * @param Admin $I
     */
    public function changeInline1nInlineInput(BackendTester $I)
    {
        $I->click('div[data-toggle="formengine-inline"]', '[data-field-name^="[tx_styleguide_inline_1n_child][1"]');
        $I->waitForElement('input[data-formengine-input-name="data[tx_styleguide_inline_1n_child][1][input_1]"]');
        $I->fillField('input[data-formengine-input-name="data[tx_styleguide_inline_1n_child][1][input_1]"]', 'hello world');
        $I->click('button[name="_savedok"]');
        $I->wait(3);
        $I->click('a[title="Close"]');
        $I->wait(3);
        $I->see('hello world');
    }

    /**
     * @param Admin $I
     * @param ModalDialog $modalDialog
     */
    public function deleteInline1nInlineElement(BackendTester $I, ModalDialog $modalDialog)
    {
        $inlineElementToDelete = '[data-field-name^="[tx_styleguide_inline_1n_child][1"]';
        $I->wantTo('Cancel the delete dialog');
        $I->click('a span[data-identifier="actions-edit-delete"]', $inlineElementToDelete);
        $modalDialog->clickButtonInDialog('button[name="no"]');
        // switch form Dialogbox back to IFrame
        $I->switchToContentFrame();
        $I->seeElement($inlineElementToDelete);

        $I->wantTo('Accept the delete dialog');
        $I->click('a span[data-identifier="actions-edit-delete"]', $inlineElementToDelete);

        // don't use $modalDialog->clickButtonInDialog due to too low timeout
        $modalDialog->canSeeDialog();
        $I->click('button[name="yes"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        // switch form Dialogbox back to IFrame
        $I->switchToContentFrame();
        $I->waitForElementNotVisible($inlineElementToDelete);
    }

    /**
     * @param Admin $I
     * @param $fieldLabel
     * @param $testValue
     */
    protected function fillFieldByLabel(BackendTester $I, $fieldLabel, $testValue)
    {
        $fieldContext = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) use (
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
