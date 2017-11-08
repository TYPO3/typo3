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

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;
use TYPO3\TestingFramework\Core\Acceptance\Support\Helper\ModalDialog;
use TYPO3\TestingFramework\Core\Acceptance\Support\Page\PageTree;

/**
 * Tests for inline 1n
 */
class Inline1nCest
{
    public function _before(Admin $I, PageTree $pageTree)
    {
        $I->useExistingSession();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->switchToIFrame('list_frame');
        $I->waitForText('Web Content Management System');
        $I->switchToIFrame();

        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'inline 1n']);
        $I->switchToIFrame('list_frame');

        $I->waitForText('inline 1n');
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_inline_1n a[data-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForText('Edit Form', 3, 'h1');
    }

    /**
     * @param Admin $I
     */
    public function checkIfExpandsAndCollapseShowInput(Admin $I)
    {
        $I->wantTo('Expands the inline Elemnet');
        $I->click('div[data-toggle="formengine-inline"]', '#data-12-tx_styleguide_inline_1n-1-inline_1-tx_styleguide_inline_1n_child-1_div');
        $I->waitForElement('input[data-formengine-input-name="data[tx_styleguide_inline_1n_child][1][input_1]"]');
        $I->wantTo('check is the value in input');
        $I->seeInField('input[data-formengine-input-name="data[tx_styleguide_inline_1n_child][1][input_1]"]', 'lipsum');
        $I->wantTo('Collapse the inline Elemnet');
        $I->click('div[data-toggle="formengine-inline"]', '#data-12-tx_styleguide_inline_1n-1-inline_1-tx_styleguide_inline_1n_child-1_div');
        $I->waitForElementNotVisible('#data-12-tx_styleguide_inline_1n-1-inline_1-tx_styleguide_inline_1n_child-1_fields.panel-collapse');
    }

    /**
     * @param Admin $I
     */
    public function hideAndUnhideInline1nInlineElement(Admin $I)
    {
        $I->wantTo('Can hide a Inline Element');
        $I->click('a span[data-identifier="actions-edit-hide"]', '#data-12-tx_styleguide_inline_1n-1-inline_1-tx_styleguide_inline_1n_child-1_div');
        $I->waitForElement('#data-12-tx_styleguide_inline_1n-1-inline_1_records .t3-form-field-container-inline-hidden');
        $I->wantTo('Can unhide a Inline Element');
        $I->click('a span[data-identifier="actions-edit-unhide"]', '#data-12-tx_styleguide_inline_1n-1-inline_1-tx_styleguide_inline_1n_child-1_div');
        $I->waitForElementNotVisible('#data-12-tx_styleguide_inline_1n-1-inline_1_records .t3-form-field-container-inline-hidden', 2);
    }

    /**
     * @param Admin $I
     */
    public function createInline1nInlineElement(Admin $I)
    {
        $I->click('span[data-identifier="actions-document-new"]', 'div.typo3-newRecordLink');

        $fieldLabel = 'input_1';
        $testValue = 'Fo Bar';

        $this->fillFieldByLabel($I, $fieldLabel, $testValue);

        $I->click('button[name="_savedok"]');
        $I->wait(3);
        $I->click('a[title="Close"]');
        $I->wait(3);

        $I->executeJS('$(\'a[data-table="pages_language_overlay"] .icon-actions-view-list-collapse\').click();');
        $I->wait(1);

        $I->see('lipsum', '#recordlist-tx_styleguide_inline_1n_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > a');
        $I->see('Fo Bar', '#recordlist-tx_styleguide_inline_1n_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(2) > td:nth-child(2) > a');
    }

    /**
     * @depends createInline1nInlineElement
     * @param Admin $I
     */
    public function checkIfCanSortingInlineElement(Admin $I)
    {
        $I->wantTo('Can sort an Inline Element');
        $I->click('a span[data-identifier="actions-move-down"]', '#data-12-tx_styleguide_inline_1n-1-inline_1-tx_styleguide_inline_1n_child-1_div');
        $I->click('button[name="_savedok"]');
        $I->wait(3);
        $I->click('a[title="Close"]');
        $I->wait(3);

        $I->executeJS('$(\'a[data-table="pages_language_overlay"] .icon-actions-view-list-collapse\').click();');
        $I->wait(1);

        $I->wantTo('Check new sorting');
        $I->see('Fo Bar', '#recordlist-tx_styleguide_inline_1n_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > a');
        $I->see('lipsum', '#recordlist-tx_styleguide_inline_1n_child > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(2) > td:nth-child(2) > a');
    }

    /**
     * @param Admin $I
     */
    public function changeInline1nInlineInput(Admin $I)
    {
        $I->click('div[data-toggle="formengine-inline"]', '#data-12-tx_styleguide_inline_1n-1-inline_1-tx_styleguide_inline_1n_child-1_div');
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
    public function deleteInline1nInlineElement(Admin $I, ModalDialog $modalDialog)
    {
        $inlineElmentToDelete = '#data-12-tx_styleguide_inline_1n-1-inline_1-tx_styleguide_inline_1n_child-1_div';
        $I->wantTo('Cancel the delete dialog');
        $I->click('a span[data-identifier="actions-edit-delete"]', $inlineElmentToDelete);
        $modalDialog->clickButtonInDialog('button[name="no"]');
        // switch form Dialogbox back to IFrame
        $I->switchToIFrame('list_frame');
        $I->seeElement($inlineElmentToDelete);

        $I->wantTo('Accept the delete dialog');
        $I->click('a span[data-identifier="actions-edit-delete"]', $inlineElmentToDelete);
        $modalDialog->clickButtonInDialog('button[name="yes"]');
        // switch form Dialogbox back to IFrame
        $I->switchToIFrame('list_frame');
        $I->waitForElementNotVisible($inlineElmentToDelete);
    }

    /**
     * @param Admin $I
     * @param $fieldLabel
     * @param $testValue
     */
    protected function fillFieldByLabel(Admin $I, $fieldLabel, $testValue)
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
        $inputField = $fieldContext->findElement(\WebDriverBy::xpath($visibleFieldXpath));
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
