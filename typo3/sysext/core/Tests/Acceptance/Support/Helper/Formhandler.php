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
     * @var string
     */
    public static $selectorFormSection = '.form-section';

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
     * @param string $fieldName
     * @return RemoteWebElement
     */
    protected function getContextForFormhandlerField(string $fieldName)
    {
        $I = $this->tester;
        $I->comment('Get context for field "' . $fieldName . '"');

        return $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) use ($fieldName) {
            return $webdriver->findElement(
                \WebDriverBy::xpath('(//label[contains(text(),"' . $fieldName . '")])[1]/ancestor::fieldset[@class="form-section"][1]')
            );
        });
    }

    /**
     * @param string $fieldLabel
     * @param array $testData An array of arrays that contains the data to validate.
     *  * First value is the input value
     *  * second value is the value that is expected after the validation
     *  * optional third value is the "internal" value like required for date fields (value is internally
     *      represented by a timestamp). If this value is not defined the second value will be used.
     *  Example for field with alpha validation: [['foo', 'foo'], ['bar1'], ['bar']]
     *  Example for field with date validation: [['29-01-2016', '29-01-2016', '1454025600']]
     */
    public function fillSeeSaveAndClearInputField($fieldLabel, array $testData)
    {
        $fieldContext = $this->getContextForFormhandlerField($fieldLabel);
        $I = $this->tester;
        $I->wantTo('Fill field, check the fieldvalue after evaluation and delete the value.');

        $visibleFieldXpath = './/*/input[@data-formengine-input-name]';
        $clearButtonXpath = '(//label[contains(text(),"' . $fieldLabel . '")])[1]/parent::*//*/button[@class="close"]';
        $initializedInputFieldXpath = '(//label[contains(text(),"' . $fieldLabel . '")])[1]/parent::*//*/input[@data-formengine-input-name][@data-formengine-input-initialized]';
        $I->waitForElement($initializedInputFieldXpath, 30);
        $inputField = $fieldContext->findElement(\WebDriverBy::xpath($visibleFieldXpath));
        $internalInputFieldXpath = '(//label[contains(text(),"' . $fieldLabel . '")])[1]/parent::*//*/input[@name="' . $inputField->getAttribute('data-formengine-input-name') . '"]';

        $I->waitForElement($internalInputFieldXpath, 30);
        $I->waitForElement($clearButtonXpath, 30);

        // the internal field name will not change during this function execution
        $internalFieldXpath = './/*/input[@name="' . $inputField->getAttribute('data-formengine-input-name') . '"]';
        $internalInputField = $fieldContext->findElement(\WebDriverBy::xpath($internalFieldXpath));

        foreach ($testData['tests'] as $testValue) {
            if (isset($testValue[4])) {
                $I->comment($testValue[4]);
            }
            $I->comment('Fill the field and switch focus to trigger validation.');
            $I->fillField($inputField, $testValue[0]);
            // change the focus to trigger validation
            $inputField->sendKeys(WebDriverKeys::TAB);
            // click on the div so that any opened popup (potentially from the field below) is closed
            $fieldContext->click();

            $I->comment('Test value of "visible" field');
            $I->canSeeInField($inputField, $testValue[1]);
            $I->comment('Test value of the internal field');
            $I->canSeeInField($internalInputField, (isset($testValue[2]) ? $testValue[2] : $testValue[1]));

            // save the change
            $saveButtonLink = '//*/button[@name="_savedok"][1]';
            $I->waitForElement($saveButtonLink, 30);
            if (isset($testValue[3]) && $testValue[3]) {
                $I->click($saveButtonLink);
                $I->switchToWindow();
                $notificationCloseXpath = '//*[@class="modal-title"][contains(text(),"Alert")]/parent::*/button[@class="close"]';
                $I->waitForElement($notificationCloseXpath, 30);
                $I->click($notificationCloseXpath);
                return;
            } else {
                $I->click($saveButtonLink);
            }

            // wait for the save to be completed
            $I->waitForElement('//*/button[@name="_savedok"][not(@disabled)][1]', 30);
            $I->waitForElement($initializedInputFieldXpath, 30);
            $I->waitForElement($internalInputFieldXpath, 30);
            $I->waitForElement($clearButtonXpath, 30);

            // find the input fields again
            $fieldContext = $this->getContextForFormhandlerField($fieldLabel);
            $inputField = $fieldContext->findElement(\WebDriverBy::xpath($visibleFieldXpath));
            $internalInputField = $fieldContext->findElement(\WebDriverBy::xpath($internalFieldXpath));

            // validate that the save was successfull
            $I->comment('Test value of "visible" field after the save');
            $I->canSeeInField($inputField, $testValue[1]);
            $I->comment('Test value of the internal field after the save');
            $I->canSeeInField($internalInputField, isset($testValue[2]) ? $testValue[2] : $testValue[1]);
        }

        // clear the field
        $I->waitForElement($clearButtonXpath, 30);
        $I->click($clearButtonXpath);
        $I->canSeeInField($inputField, '');

        // save the change again
        $saveButtonLink = '//*/button[@name="_savedok"][1]';
        $I->waitForElement($saveButtonLink, 30);
        $I->click($saveButtonLink);

        // wait for the save to be completed
        $I->waitForElement('//*/button[@name="_savedok"][not(@disabled)][1]', 30);
        $I->waitForElement($initializedInputFieldXpath, 30);
        $I->waitForElement($internalInputFieldXpath, 30);
        $I->waitForElement($clearButtonXpath, 30);

        // find the input fields again
        $fieldContext = $this->getContextForFormhandlerField($fieldLabel);
        $inputField = $fieldContext->findElement(\WebDriverBy::xpath($visibleFieldXpath));
        $internalInputField = $fieldContext->findElement(\WebDriverBy::xpath($internalFieldXpath));

        // validate that the save was successfull
        $I->canSeeInField($inputField, isset($testData['cleared'][1]) ? $testData['cleared'][1] : '');
        $I->canSeeInField($internalInputField, isset($testData['cleared'][0]) ? $testData['cleared'][0] : '');
    }
}
