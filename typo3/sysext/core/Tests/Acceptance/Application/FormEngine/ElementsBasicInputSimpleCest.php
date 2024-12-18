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
 * Tests for "elements_basic" simple input fields of ext:styleguide
 */
final class ElementsBasicInputSimpleCest extends AbstractElementsBasicCest
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

        // Make sure the test operates on the "input" tab
        $I->click('input');
    }

    /**
     * Data provider to check various type=input variants
     */
    private function simpleInputFieldsDataProvider(): array
    {
        return [
            [
                'label' => 'input_1',
                'inputValue' => 'This is a demo text',
                'expectedValue' => 'This is a demo text',
                'expectedInternalValue' => 'This is a demo text',
                'expectedValueAfterSave' => 'This is a demo text',
                'comment' => '',
            ],
            [
                'label' => 'input_2',
                'inputValue' => 'This is a demo text with numbers and other characters 42 #!',
                'expectedValue' => 'This is a demo text with numbers and other characters 42 #!',
                'expectedInternalValue' => 'This is a demo text with numbers and other characters 42 #!',
                'expectedValueAfterSave' => 'This is a demo text with numbers and other characters 42 #!',
                'comment' => '',
            ],
            [
                'label' => 'input_3',
                'inputValue' => 'Kasper',
                'expectedValue' => 'Kasp',
                'expectedInternalValue' => 'Kasp',
                'expectedValueAfterSave' => 'Kasp',
                'comment' => '',
            ],
            [
                'label' => 'input_4',
                'inputValue' => 'Kasper = TYPO3',
                'expectedValue' => 'KasperTYPO',
                'expectedInternalValue' => 'KasperTYPO',
                'expectedValueAfterSave' => 'KasperTYPO',
                'comment' => '',
            ],
            [
                'label' => 'input_4',
                'inputValue' => 'Non-latin characters: ŠĐŽĆČ',
                'expectedValue' => 'Nonlatincharacters',
                'expectedInternalValue' => 'Nonlatincharacters',
                'expectedValueAfterSave' => 'Nonlatincharacters',
                'comment' => '',
            ],
            [
                'label' => 'input_5',
                'inputValue' => 'Kasper = TYPO3',
                'expectedValue' => 'KasperTYPO3',
                'expectedInternalValue' => 'KasperTYPO3',
                'expectedValueAfterSave' => 'KasperTYPO3',
                'comment' => '',
            ],
            [
                'label' => 'input_10',
                'inputValue' => 'abcd1234',
                'expectedValue' => 'abc123',
                'expectedInternalValue' => 'abc123',
                'expectedValueAfterSave' => 'abc123',
                'comment' => '',
            ],
            [
                'label' => 'input_10',
                'inputValue' => 'Kasper TYPO3',
                'expectedValue' => 'a3',
                'expectedInternalValue' => 'a3',
                'expectedValueAfterSave' => 'a3',
                'comment' => '',
            ],
            [
                'label' => 'input_11',
                'inputValue' => 'Kasper TYPO3!',
                'expectedValue' => 'kasper typo3!',
                'expectedInternalValue' => 'kasper typo3!',
                'expectedValueAfterSave' => 'kasper typo3!',
                'comment' => '',
            ],
            [
                'label' => 'input_13',
                'inputValue' => ' Kasper TYPO3! ',
                'expectedValue' => 'KasperTYPO3!',
                'expectedInternalValue' => 'KasperTYPO3!',
                'expectedValueAfterSave' => 'KasperTYPO3!',
                'comment' => '',
            ],
            [
                'label' => 'input_19',
                'inputValue' => ' Kasper ',
                'expectedValue' => 'Kasper',
                'expectedInternalValue' => 'Kasper',
                'expectedValueAfterSave' => 'Kasper',
                'comment' => '',
            ],
            [
                'label' => 'input_19',
                'inputValue' => ' Kasper TYPO3 ',
                'expectedValue' => 'Kasper TYPO3',
                'expectedInternalValue' => 'Kasper TYPO3',
                'expectedValueAfterSave' => 'Kasper TYPO3',
                'comment' => '',
            ],
            [
                'label' => 'input_23',
                'inputValue' => 'Kasper TYPO3!',
                'expectedValue' => 'KASPER TYPO3!',
                'expectedInternalValue' => 'KASPER TYPO3!',
                'expectedValueAfterSave' => 'KASPER TYPO3!',
                'comment' => '',
            ],
            [
                'label' => 'input_24',
                'inputValue' => '2016',
                'expectedValue' => '2016',
                'expectedInternalValue' => '2016',
                'expectedValueAfterSave' => '2016',
                'comment' => '',
            ],
            [
                'label' => 'input_24',
                'inputValue' => '12',
                'expectedValue' => '12',
                'expectedInternalValue' => '12',
                'expectedValueAfterSave' => '12',
                'comment' => '',
            ],
            [
                'label' => 'input_24',
                'inputValue' => 'Kasper',
                'expectedValue' => date('Y'),
                'expectedInternalValue' => date('Y'),
                'expectedValueAfterSave' => date('Y'),
                'comment' => 'Invalid character is converted to current year',
            ],
        ];
    }

    #[DataProvider('simpleInputFieldsDataProvider')]
    public function simpleInputFields(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }

    /**
     * Data provider to run form validation
     */
    private function simpleInputFieldsValidationDataProvider(): array
    {
        return [
            [
                'comment' => 'Check simple field',
                'label' => 'input_1',
                'testSequence' => [
                    [
                        'inputValue' => '',
                        'expectedValue' => '',
                        'expectedInternalValue' => '',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abcdef',
                        'expectedValue' => 'abcdef',
                        'expectedInternalValue' => 'abcdef',
                        'expectError' => false,
                    ],
                ],
            ],
            // @todo: Implement special test for read-only field, as it is not testable by `runInputFieldValidationTest`.
            // [
            //     'comment' => 'Check field: readOnly',
            //     'label' => 'input_40',
            //     'testSequence' => [
            //         [
            //             'inputValue' => 'abcdef',
            //             'expectedValue' => 'lipsum',
            //         ],
            //     ],
            // ],
            [
                'comment' => 'Check field: size=10',
                'label' => 'input_2',
                'testSequence' => [
                    [
                        'inputValue' => '1234567890',
                        'expectedValue' => '1234567890',
                        'expectedInternalValue' => '1234567890',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => '1234567890a',
                        'expectedValue' => '1234567890a',
                        'expectedInternalValue' => '1234567890a',
                        'expectError' => false,
                    ],
                ],
            ],
            [
                'comment' => 'Check validation: max=4',
                'label' => 'input_3',
                'testSequence' => [
                    [
                        'inputValue' => '',
                        'expectedValue' => '',
                        'expectedInternalValue' => '',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => ' ',
                        'expectedValue' => ' ',
                        'expectedInternalValue' => ' ',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => '     ',
                        'expectedValue' => '    ', // browser blocks input of 5th character
                        'expectedInternalValue' => '    ',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'a',
                        'expectedValue' => 'a',
                        'expectedInternalValue' => 'a',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abc',
                        'expectedValue' => 'abc',
                        'expectedInternalValue' => 'abc',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abcd',
                        'expectedValue' => 'abcd',
                        'expectedInternalValue' => 'abcd',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abcde',
                        'expectedValue' => 'abcd', // browser blocks input of 5th character
                        'expectedInternalValue' => 'abcd',
                        'expectError' => false,
                    ],
                ],
            ],
            [
                'comment' => 'Check validation: min=4',
                'label' => 'input_41',
                'testSequence' => [
                    [
                        'inputValue' => '',
                        'expectedValue' => '',
                        'expectedInternalValue' => '',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => ' ',
                        'expectedValue' => ' ',
                        'expectedInternalValue' => ' ',
                        'expectError' => true,
                    ],
                    [
                        'inputValue' => '    ',
                        'expectedValue' => '    ',
                        'expectedInternalValue' => '    ',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'a',
                        'expectedValue' => 'a',
                        'expectedInternalValue' => 'a',
                        'expectError' => true,
                    ],
                    [
                        'inputValue' => 'abc',
                        'expectedValue' => 'abc',
                        'expectedInternalValue' => 'abc',
                        'expectError' => true,
                    ],
                    [
                        'inputValue' => 'abcd',
                        'expectedValue' => 'abcd',
                        'expectedInternalValue' => 'abcd',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abcde',
                        'expectedValue' => 'abcde',
                        'expectedInternalValue' => 'abcde',
                        'expectError' => false,
                    ],
                ],
            ],
            [
                'comment' => 'Check validation: min=4, max=8',
                'label' => 'input_42',
                'testSequence' => [
                    [
                        'inputValue' => '',
                        'expectedValue' => '',
                        'expectedInternalValue' => '',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abc',
                        'expectedValue' => 'abc',
                        'expectedInternalValue' => 'abc',
                        'expectError' => true,
                    ],
                    [
                        'inputValue' => 'abcd',
                        'expectedValue' => 'abcd',
                        'expectedInternalValue' => 'abcd',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abcde',
                        'expectedValue' => 'abcde',
                        'expectedInternalValue' => 'abcde',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abcdefg',
                        'expectedValue' => 'abcdefg',
                        'expectedInternalValue' => 'abcdefg',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abcdefgh',
                        'expectedValue' => 'abcdefgh',
                        'expectedInternalValue' => 'abcdefgh',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abcdefghi',
                        'expectedValue' => 'abcdefgh', // browser blocks input of 9th character
                        'expectedInternalValue' => 'abcdefgh',
                        'expectError' => false,
                    ],
                ],
            ],
            [
                'comment' => 'Check validation: min=4, max=4',
                'label' => 'input_43',
                'testSequence' => [
                    [
                        'inputValue' => '',
                        'expectedValue' => '',
                        'expectedInternalValue' => '',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abc',
                        'expectedValue' => 'abc',
                        'expectedInternalValue' => 'abc',
                        'expectError' => true,
                    ],
                    [
                        'inputValue' => 'abcd',
                        'expectedValue' => 'abcd',
                        'expectedInternalValue' => 'abcd',
                        'expectError' => false,
                    ],
                    [
                        'inputValue' => 'abcde',
                        'expectedValue' => 'abcd', // browser blocks input of 5th character
                        'expectedInternalValue' => 'abcd',
                        'expectError' => false,
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('simpleInputFieldsValidationDataProvider')]
    public function simpleInputFieldsValidation(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldValidationTest($I, $testData);
    }
}
