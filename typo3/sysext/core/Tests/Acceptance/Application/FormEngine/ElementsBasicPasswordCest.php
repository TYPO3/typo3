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
 * Tests for "elements_basic" password input fields of ext:styleguide
 */
class ElementsBasicPasswordCest extends AbstractElementsBasicCest
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
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_basic a[data-bs-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Form', 3, 'h1');
        $I->click('password');
    }

    /**
     * Data provider to check various type=password variants
     */
    protected function passwordInputFieldsDataProvider(): array
    {
        return [
            // @todo add other password field variants
            [
                'label' => 'password_2',
                'inputValue' => 'Kasper',
                'expectedValue' => '********',
                'expectedInternalValue' => 'Kasper',
                'expectedValueAfterSave' => 'Kasper',
                'comment' => '',
            ],
        ];
    }

    /**
     * @dataProvider passwordInputFieldsDataProvider
     * @param ApplicationTester $I
     * @param Example $testData
     */
    public function passwordInputFields(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }
}
