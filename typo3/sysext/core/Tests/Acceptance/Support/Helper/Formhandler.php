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
    public function getContextForFormhandlerField(string $fieldName)
    {
        $I = $this->tester;
        $I->comment('Get context for field "' . $fieldName . '"');

        return $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) use ($fieldName) {
            // TODO FIX THAT! MUST JUST BE ONE XPATH (and maybe it should work)
            return $webdriver->findElement(\WebDriverBy::xpath('//label[contains(text(),"' . $fieldName . '")]'))->findElement(
                \WebDriverBy::xpath('ancestor::fieldset[@class="form-section"][1]')
            );
        });
    }

    /**
     * @param RemoteWebElement $fieldContext
     * @param array $testValues An array of arrays that contains the values to validate.
     *  * First value is the input value
     *  * second value is the value that is expected after the validation
     *  * optional third value is the "internal" value like required for date fields (value is internally
     *      represented by a timestamp). If this value is not defined the second value will be used.
     *  Example for field with alpha validation: [['foo', 'foo'], ['bar1'], ['bar']]
     *  Example for field with date validation: [['29-01-2016', '29-01-2016', '1454025600']]
     */
    public function fillSeeDeleteInputField(RemoteWebElement $fieldContext, array $testValues)
    {
        $I = $this->tester;
        $I->wantTo('Fill field, check the fieldvalue after evaluation and delete the value.');

        $inputField = $fieldContext->findElement(\WebDriverBy::xpath('.//*/input[@data-formengine-input-name]'));
        $internalInputField = $fieldContext->findElement(\WebDriverBy::xpath('.//*/input[@name="' . $inputField->getAttribute('data-formengine-input-name') . '"]'));

        foreach ($testValues as $comment => $testValue) {
            if (!empty($comment)) {
                $I->comment($comment);
            }
            $I->comment('Fill the field and switch focus to trigger validation.');
            $I->fillField($inputField, $testValue[0]);
            // change the focus to trigger validation
            $fieldContext->sendKeys("\n");

            $I->comment('Test value of "visible" field');
            $I->canSeeInField($inputField, $testValue[1]);
            $I->comment('Test value of the internal field');
            $I->canSeeInField($internalInputField, (isset($testValue[2]) ? $testValue[2] : $testValue[1]));
        }

        $inputField->findElement(\WebDriverBy::xpath('parent::*/button[@class="close"]'))->click();
        // change the context from the field
        $fieldContext->sendKeys("\n");
        $I->canSeeInField($inputField, '');
        $I->canSeeInField($internalInputField, '');
    }
}
