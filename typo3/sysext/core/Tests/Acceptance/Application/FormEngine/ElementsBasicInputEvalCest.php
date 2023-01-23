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
final class ElementsBasicInputEvalCest extends AbstractElementsBasicCest
{
    /**
     * Open list module of styleguide elements basic page
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
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
     * Test various type=input fields having eval
     */
    private function simpleEvalInputFieldsDataProvider(): array
    {
        return [
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
            [
                'label' => 'input_20',
                'inputValue' => 'test',
                'expectedValue' => 'JSfootestJSfoo',
                'expectedInternalValue' => 'JSfootestJSfoo',
                'expectedValueAfterSave' => 'JSfootestJSfooPHPfoo-evaluatePHPfoo-deevaluate',
                'expectedInternalValueAfterSave' => 'JSfootestJSfooPHPfoo-evaluatePHPfoo-deevaluate',
                'comment' => '',
            ],
        ];
    }

    /**
     * @dataProvider simpleEvalInputFieldsDataProvider
     */
    public function simpleEvalInputFields(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }
}
