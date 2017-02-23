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

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverKeys;
use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;

/**
 * Abstract class for "elements_basic" tests of styleguide
 */
abstract class AbstractElementsBasicCest
{
    /**
     * Execute given test sets.
     * Incoming array operates on fields of ext:styleguide, each field can have multiple test tests.
     *
     * @param Admin $I
     * @param array $dataSets
     */
    protected function runTests(Admin $I, array $dataSets)
    {
        foreach ($dataSets as $fieldLabel => $testData) {
            $initializedInputFieldXpath = '(//label[contains(text(),"' . $fieldLabel . '")])'
                . '[1]/parent::*//*/input[@data-formengine-input-name][@data-formengine-input-initialized]';
            foreach ($testData as $data) {
                // Wait until JS initialized everything
                $I->waitForElement($initializedInputFieldXpath, 30);

                $formSection = $this->getFormSectionByFieldLabel($I, $fieldLabel);
                $inputField = $this->getInputField($formSection);
                $hiddenField = $this->getHiddenField($formSection, $inputField);

                if ($data['comment'] !== '') {
                    $I->comment($data['comment']);
                }

                $I->fillField($inputField, $data['inputValue']);
                // Change focus to trigger validation
                $inputField->sendKeys(WebDriverKeys::TAB);
                // Click on the div so that any opened popup (potentially from the field below) is closed
                $formSection->click();

                $I->comment('Test value of visible and hidden field');
                $I->canSeeInField($inputField, $data['expectedValue']);
                $I->canSeeInField($hiddenField, $data['expectedInternalValue']);

                $I->comment('Save the form');
                $saveButtonLink = '//*/button[@name="_savedok"][1]';
                $I->waitForElement($saveButtonLink, 30);
                $I->click($saveButtonLink);
                $I->waitForElement('//*/button[@name="_savedok"][not(@disabled)][1]', 30);
                $I->waitForElement($initializedInputFieldXpath, 30);

                // Find the fields again (after reload of iFrame)
                $formSection = $this->getFormSectionByFieldLabel($I, $fieldLabel);
                $inputField = $this->getInputField($formSection);
                $hiddenField = $this->getHiddenField($formSection, $inputField);

                // Validate save was successful
                $I->comment('Test value of visible and hidden field');
                $I->canSeeInField($inputField, $data['expectedValue']);
                $I->canSeeInField($hiddenField, $data['expectedValueAfterSave']);
            }
        }
    }

    /**
     * Return the visible input field of element in question.
     *
     * @param $formSection
     * @return RemoteWebElement
     */
    protected function getInputField(RemoteWebElement $formSection)
    {
        return $formSection->findElement(\WebDriverBy::xpath('.//*/input[@data-formengine-input-name]'));
    }

    /**
     * Return the hidden input field of element in question.
     *
     * @param RemoteWebElement $formSection
     * @param RemoteWebElement $inputField
     * @return RemoteWebElement
     */
    protected function getHiddenField(RemoteWebElement $formSection, RemoteWebElement $inputField)
    {
        $hiddenFieldXPath = './/*/input[@name="' . $inputField->getAttribute('data-formengine-input-name') . '"]';
        return $formSection->findElement(\WebDriverBy::xpath($hiddenFieldXPath));
    }

    /**
     * Find this element in form.
     *
     * @param Admin $I
     * @param string $fieldLabel
     * @return RemoteWebElement
     */
    protected function getFormSectionByFieldLabel(Admin $I, string $fieldLabel)
    {
        $I->comment('Get context for field "' . $fieldLabel . '"');
        return $I->executeInSelenium(
            function (RemoteWebDriver $webDriver) use ($fieldLabel) {
                return $webDriver->findElement(
                    \WebDriverBy::xpath(
                        '(//label[contains(text(),"' . $fieldLabel . '")])[1]/ancestor::fieldset[@class="form-section"][1]'
                    )
                );
            }
        );
    }
}
