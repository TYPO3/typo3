<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Helper;

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

use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverKeys;

/**
 * Helper to interact with formhandler fields
 */
class Formhandler
{
    /**
     * Selector to select one formengine section
     *
     * @var string
     */
    public static $selectorFormSection = '.form-section';
    protected $visibleFieldPath = './/*/input[@data-formengine-input-name]';
    protected $initializedInputFieldXpath;
    protected $internalInputFieldXpath;
    protected $internalInputFieldXpath1;
    protected $internalFieldXpath;
    /**
     * @var \AcceptanceTester
     */
    protected $tester;

    /**
     * @param \AcceptanceTester $I
     */
    public function __construct(\AcceptanceTester $I)
    {
        $this->tester = $I;
    }

    /**
     * @param string $fieldLabel
     * @param FormHandlerElementTestDataObject[] $testData An array of Objects that contains the data to validate.
     */
    public function fillSeeSaveAndClearInputField($fieldLabel, array $testData)
    {
        $I = $this->tester;
        $I->wantTo('Fill field, check the fieldvalue after evaluation and delete the value.');

        $this->initializeFieldSelectors($fieldLabel);

        foreach ($testData as $data) {
            list($inputField, $internalInputField) = $this->getInputFields($fieldLabel);
            $fieldContext = $this->getContextForFormhandlerField($fieldLabel);

            $expectedInternal = $data->expectedInternalValue !== '' ? $data->expectedInternalValue : $data->expectedValue;
            $expectedInternalAfterSave = $data->expectedValueAfterSave !== '' ? $data->expectedValueAfterSave : $expectedInternal;
            $this->addComment($data->comment);

            $I->comment('Fill the field and switch focus to trigger validation.');
            $I->fillField($inputField, $data->inputValue);
            // change the focus to trigger validation
            $inputField->sendKeys(WebDriverKeys::TAB);
            // click on the div so that any opened popup (potentially from the field below) is closed
            $fieldContext->click();

            $this->testFieldValues($inputField, $data->expectedValue, $internalInputField, $expectedInternal);

            if ($data->notificationExpected) {
                $this->save();
                $this->closeNotification();
                return;
            } else {
                $this->save();
            }

            // wait for the save to be completed
            $this->waitForSaveToBeCompleted();

            // find the fields again (after reload of iframe)
            list($inputField, $internalInputField) = $this->getInputFields($fieldLabel);

            // validate that the save was successful
            $this->testFieldValues($inputField, $data->expectedValue, $internalInputField, $expectedInternalAfterSave);
        }

        list($inputField) = $this->getInputFields($fieldLabel);

        // clear the field
        $this->clearField($inputField);
        $this->save();
        $this->waitForSaveToBeCompleted();
    }

    /**
     * @param $comment
     */
    protected function addComment($comment)
    {
        if ($comment !== null) {
            $this->tester->comment($comment);
        }
    }

    protected function clearField($inputField)
    {
        $I = $this->tester;
        $I->comment('Clear the field');
        $I->waitForElementVisible($this->initializedInputFieldXpath);
        $I->fillField($inputField, '');
    }

    protected function closeNotification()
    {
        $I = $this->tester;
        $I->switchToWindow();
        $notificationCloseXpath = '//*[@class="modal-title"][contains(text(),"Alert")]/parent::*/button[@class="close"]';
        $I->waitForElement($notificationCloseXpath, 30);
        $I->click($notificationCloseXpath);
    }

    /**
     * @param string $fieldName
     * @return RemoteWebElement
     */
    protected function getContextForFormhandlerField(string $fieldName)
    {
        $I = $this->tester;
        $I->comment('Get context for field "' . $fieldName . '"');

        return $I->executeInSelenium(
            function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) use ($fieldName) {
                return $webdriver->findElement(
                    \WebDriverBy::xpath(
                        '(//label[contains(text(),"' .
                        $fieldName .
                        '")])[1]/ancestor::fieldset[@class="form-section"][1]'
                    )
                );
            }
        );
    }

    /**
     * @param $fieldLabel
     * @return array
     */
    protected function getInputFields($fieldLabel)
    {
        $I = $this->tester;
        $I->comment('get input fields');
        $I->waitForElement($this->initializedInputFieldXpath, 30);
        $fieldContext = $this->getContextForFormhandlerField($fieldLabel);
        $inputField = $fieldContext->findElement(\WebDriverBy::xpath($this->visibleFieldPath));
        $internalInputFieldXpath = '(//label[contains(text(),"' .
                                   $fieldLabel .
                                   '")])[1]/parent::*//*/input[@name="' .
                                   $inputField->getAttribute('data-formengine-input-name') .
                                   '"]';

        $I->waitForElement($internalInputFieldXpath, 30);

        $this->internalFieldXpath = './/*/input[@name="' .
                                    $inputField->getAttribute('data-formengine-input-name') .
                                    '"]';
        $internalInputField = $fieldContext->findElement(\WebDriverBy::xpath($this->internalFieldXpath));

        $this->internalInputFieldXpath = $internalInputFieldXpath;
        return [$inputField, $internalInputField];
    }

    /**
     * @param $fieldLabel
     * @return array
     */
    protected function initializeFieldSelectors($fieldLabel)
    {
        $this->initializedInputFieldXpath = '(//label[contains(text(),"' .
                                            $fieldLabel .
                                            '")])[1]/parent::*//*/input[@data-formengine-input-name]' .
                                            '[@data-formengine-input-initialized]';
    }

    /**
     */
    protected function save()
    {
        $I = $this->tester;
        $I->comment('Save the form');
        $saveButtonLink = '//*/button[@name="_savedok"][1]';
        $I->waitForElement($saveButtonLink, 30);
        $I->click($saveButtonLink);
    }

    /**
     * @param $inputField
     * @param $expectedAfterValidation
     * @param $internalInputField
     * @param $expectedInternal
     */
    protected function testFieldValues(
        $inputField,
        $expectedAfterValidation,
        $internalInputField,
        $expectedInternal
    ) {
        $I = $this->tester;
        $I->comment('Test value of "visible" field');
        $I->canSeeInField($inputField, $expectedAfterValidation);
        $I->comment('Test value of the internal field');
        $I->canSeeInField($internalInputField, $expectedInternal);
    }

    /**
     */
    protected function waitForSaveToBeCompleted()
    {
        $I = $this->tester;
        $I->comment('wait for save to be completed');
        $I->waitForElement('//*/button[@name="_savedok"][not(@disabled)][1]', 30);
        $I->waitForElement($this->initializedInputFieldXpath, 30);
        $I->waitForElement($this->internalInputFieldXpath, 30);
    }
}
