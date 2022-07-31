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

use Codeception\Example;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for "elements_basic" simple input fields of ext:styleguide
 */
class ElementsBasicInputSimpleCest extends AbstractElementsBasicCest
{
    /**
     * Open list module of styleguide elements basic page
     *
     * @param ApplicationTester $I
     * @param PageTree $pageTree
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        // Wait until DOM actually rendered everything
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
        $I->switchToContentFrame();

        // Open record and wait until form is ready
        $I->waitForText('elements basic', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_basic a[aria-label="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Form', 3, 'h1');
    }

    /**
     * Data provider to check various type=input variants
     */
    protected function simpleInputFieldsDataProvider(): array
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
                'label' => 'input_16',
                'inputValue' => 'Kasper',
                'expectedValue' => '********',
                'expectedInternalValue' => 'Kasper',
                'expectedValueAfterSave' => 'Kasper',
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

    /**
     * @dataProvider simpleInputFieldsDataProvider
     * @param ApplicationTester $I
     * @param Example $testData
     */
    public function simpleInputFields(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }
}
