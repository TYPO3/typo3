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

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for "elements_basic" simple input fields of ext:styleguide
 */
final class ElementsBasicEmailCest extends AbstractElementsBasicCest
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

        // Make sure the test operates on the "email" tab
        $I->click('email');
    }

    /**
     * Data provider to check various type=email variants
     */
    private function emailFieldsDataProvider(): array
    {
        return [
            [
                'label' => 'email_1',
                'inputValue' => 'foo@example.com',
                'expectedValue' => 'foo@example.com',
                'expectedInternalValue' => 'foo@example.com',
                'expectedValueAfterSave' => 'foo@example.com',
                'comment' => '',
            ],
        ];
    }

    #[DataProvider('emailFieldsDataProvider')]
    public function emailFields(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    /**
     * Data provider to run form validation
     */
    private function emailFieldsValidationDataProvider(): array
    {
        return [
            [
                'label' => 'email_1',
                'testSequence' => [
                    [
                        'inputValue' => '',
                        'expectedValue' => '',
                        'expectedInternalValue' => '',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => ' ',
                        'expectedValue' => '',
                        'expectedInternalValue' => '',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => ' spaces-around@example.com  ',
                        'expectedValue' => 'spaces-around@example.com',
                        'expectedInternalValue' => 'spaces-around@example.com',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'invalid-email-syntax',
                        'expectedValue' => 'invalid-email-syntax',
                        'expectedInternalValue' => 'invalid-email-syntax',
                        // @todo: This should show a FormEngine validation-error
                        'expectError' => false,
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('emailFieldsValidationDataProvider')]
    public function emailFieldsValidation(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldValidationTest($I, $testData);
    }

    public function canSelectValueFromValuePicker(ApplicationTester $I): void
    {
        $formSection = $this->getFormSectionByFieldLabel($I, 'email_5');
        $select = $formSection->findElement(WebDriverBy::xpath('.//*/typo3-formengine-valuepicker/select'));
        $input = $this->getInputField($formSection);
        // Select second option from value picker.
        $select->sendKeys(WebDriverKeys::ARROW_DOWN);
        $select->sendKeys(WebDriverKeys::ARROW_DOWN);
        $I->seeInField($input, 'info@example.org');
    }
}
