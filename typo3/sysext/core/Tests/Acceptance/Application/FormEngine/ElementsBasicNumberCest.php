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
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for "elements_basic" type number fields of ext:styleguide
 */
final class ElementsBasicNumberCest extends AbstractElementsBasicCest
{
    /**
     * Open list module of styleguide elements basic page
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        $I->switchToContentFrame();

        // Open record and wait until form is ready
        $I->waitForText('elements basic', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_basic a[aria-label="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Form', 3, 'h1');

        // Make sure the test operates on the "number" tab
        $I->click('number');
    }

    /**
     * Test various type=input fields having eval
     */
    private function simpleNumberFieldsDataProvider(): array
    {
        return [
            [
                'label' => 'number_1',
                'inputValue' => '12.335',
                'expectedValue' => '12.34',
                'expectedInternalValue' => '12.34',
                'expectedValueAfterSave' => '12.34',
                'comment' => '',
            ],
            // @todo Because of reasons, the sent value is not 12,335 but 12335 (without the comma)
            //       Probably the comma is removed (by the webdriver?) and this test fails then.
            //       This is also true for words like "TYPO3". Only the "3" is typed in.
            /*
            [
                'label' => 'number_1',
                'inputValue' => '12,335', // comma as delimiter
                'expectedValue' => '12.34',
                'expectedInternalValue' => '12.34',
                'expectedValueAfterSave' => '12.34',
                'comment' => '',
            ],
            */
            [
                'label' => 'number_1',
                'inputValue' => '1.1', // dot as delimiter
                'expectedValue' => '1.10',
                'expectedInternalValue' => '1.10',
                'expectedValueAfterSave' => '1.10',
                'comment' => '',
            ],
            // @todo see the todo above.
            /*
            [
                'label' => 'number_1',
                'inputValue' => 'TYPO3', // word having a number at end
                'expectedValue' => '3.00',
                'expectedInternalValue' => '3.00',
                'expectedValueAfterSave' => '3.00',
                'comment' => '',
            ],
            */
            // @todo see the todo above.
            /*
            [
                'label' => 'number_1',
                'inputValue' => '3TYPO', // word having a number in front
                'expectedValue' => '3.00',
                'expectedInternalValue' => '3.00',
                'expectedValueAfterSave' => '3.00',
                'comment' => '',
            ],
            */
            [
                'label' => 'number_2',
                'inputValue' => '12.335',
                'expectedValue' => '12',
                'expectedInternalValue' => '12',
                'expectedValueAfterSave' => '12',
                'comment' => '',
            ],
            // @todo This is nonsense. The comma should be replaced by a dot.
            //       See the todo above.
            /*
            [
                'label' => 'number_2',
                'inputValue' => '12,9',
                'expectedValue' => '129',
                'expectedInternalValue' => '129',
                'expectedValueAfterSave' => '129',
                'comment' => '',
            ],
            */
            // @todo see the todo above.
            /*
            [
                'label' => 'number_2',
                'inputValue' => 'TYPO3',
                'expectedValue' => '0',
                'expectedInternalValue' => '0',
                'expectedValueAfterSave' => '0',
                'comment' => '',
            ],
            */
            // @todo see the todo above.
            /*
            [
                'label' => 'number_2',
                'inputValue' => '3TYPO',
                'expectedValue' => '3',
                'expectedInternalValue' => '3',
                'expectedValueAfterSave' => '3',
                'comment' => '',
            ],
            */
        ];
    }

    #[DataProvider('simpleNumberFieldsDataProvider')]
    public function simpleNumberFields(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    /**
     * Data provider to run form validation
     */
    private function simpleNumberFieldsValidationDataProvider(): array
    {
        return [
            [
                'comment' => 'Check number field on browser-native validation-error bad-input',
                'label' => 'number_2',
                'testSequence' => [
                    [
                        // Prepare this special test-case:
                        // Set the input to empty string, thereby the `change`-event does not trigger on the next step.
                        'inputValue' => '',
                        'expectedValue' => '',
                        'expectedInternalValue' => '',
                        'expectError' => false,
                    ],
                    [
                        // `1-2` triggers browser-native validation-error "bad input" for number-fields in Chrome.
                        // Especially in Firefox "bad input" is also triggered for "more usual" user input like `123px`.
                        'inputValue' => '1-2',
                        'expectedValue' => '', // Actually Chrome still displays `1-2` to the user and marks this field as `invalid` here.
                        'expectedInternalValue' => '',
                        // @todo: This should show a FormEngine validation-error
                        'expectError' => false,
                    ],
                    [
                        // When user enters empty string, the displayed error must go away again.
                        'inputValue' => '',
                        'expectedValue' => '',
                        'expectedInternalValue' => '',
                        'expectError' => false,
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('simpleNumberFieldsValidationDataProvider')]
    public function simpleNumberFieldsValidation(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldValidationTest($I, $testData);
    }
}
