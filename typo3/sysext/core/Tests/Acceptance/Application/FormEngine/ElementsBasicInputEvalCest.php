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
 * Tests for "elements_basic" eval input fields of ext:styleguide
 */
class ElementsBasicInputEvalCest extends AbstractElementsBasicCest
{
    /**
     * Open list module of styleguide elements basic page
     *
     * @param ApplicationTester $I
     * @param PageTree $pageTree
     */
    public function _before(ApplicationTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        $I->switchToContentFrame();

        // Open record and wait until form is ready
        $I->waitForText('elements basic', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_basic a[data-bs-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Form', 3, 'h1');

        // scroll up all the way to get a clean shot to the tab panel
        $this->ensureTopOfFrameIsUsedAndClickTab($I, 'input', 'input_23');
    }

    /**
     * Test various type=input fields having eval
     */
    protected function simpleEvalInputFieldsDataProvider()
    {
        return [
            [
                'label' => 'input_8',
                'inputValue' => '12.335',
                'expectedValue' => '12.34',
                'expectedInternalValue' => '12.34',
                'expectedValueAfterSave' => '12.34',
                'comment' => '',
            ],
            [
                'label' => 'input_8',
                'inputValue' => '12,335', // comma as delimiter
                'expectedValue' => '12.34',
                'expectedInternalValue' => '12.34',
                'expectedValueAfterSave' => '12.34',
                'comment' => '',
            ],
            [
                'label' => 'input_8',
                'inputValue' => '1.1', // dot as delimiter
                'expectedValue' => '1.10',
                'expectedInternalValue' => '1.10',
                'expectedValueAfterSave' => '1.10',
                'comment' => '',
            ],
            [
                'label' => 'input_8',
                'inputValue' => 'TYPO3', // word having a number at end
                'expectedValue' => '3.00',
                'expectedInternalValue' => '3.00',
                'expectedValueAfterSave' => '3.00',
                'comment' => '',
            ],
            [
                'label' => 'input_8',
                'inputValue' => '3TYPO', // word having a number in front
                'expectedValue' => '3.00',
                'expectedInternalValue' => '3.00',
                'expectedValueAfterSave' => '3.00',
                'comment' => '',
            ],
            [
                'label' => 'input_9',
                'inputValue' => '12.335',
                'expectedValue' => '12',
                'expectedInternalValue' => '12',
                'expectedValueAfterSave' => '12',
                'comment' => '',
            ],
            [
                'label' => 'input_9',
                'inputValue' => '12,9',
                'expectedValue' => '129',
                'expectedInternalValue' => '129',
                'expectedValueAfterSave' => '129',
                'comment' => '',
            ],
            /**
            [
                // @todo this one probably broke with the html type="number" patch
                'label' => 'input_9',
                'inputValue' => 'TYPO3',
                'expectedValue' => '0',
                'expectedInternalValue' => '0',
                'expectedValueAfterSave' => '0',
                'comment' => '',
            ],
            */
            [
                'label' => 'input_9',
                'inputValue' => '3TYPO',
                'expectedValue' => '3',
                'expectedInternalValue' => '3',
                'expectedValueAfterSave' => '3',
                'comment' => '',
            ],
            [
                'label' => 'input_15',
                'inputValue' => '12.335',
                'expectedValue' => '12335',
                'expectedInternalValue' => '12335',
                'expectedValueAfterSave' => '12335',
                'comment' => '',
            ],
            [
                'label' => 'input_15',
                'inputValue' => '12,9',
                'expectedValue' => '129',
                'expectedInternalValue' => '129',
                'expectedValueAfterSave' => '129',
                'comment' => '',
            ],
            [
                'label' => 'input_15',
                'inputValue' => 'TYPO3',
                'expectedValue' => '3',
                'expectedInternalValue' => '3',
                'expectedValueAfterSave' => '3',
                'comment' => '',
            ],
            [
                'label' => 'input_15',
                'inputValue' => '3TYPO',
                'expectedValue' => '3',
                'expectedInternalValue' => '3',
                'expectedValueAfterSave' => '3',
                'comment' => '',
            ],
        ];
    }

    /**
     * @dataProvider simpleEvalInputFieldsDataProvider
     * @param ApplicationTester $I
     * @param Example $testData
     */
    public function simpleEvalInputFields(ApplicationTester $I, Example $testData)
    {
        $this->runInputFieldTest($I, $testData);
    }
}
