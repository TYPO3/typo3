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
 * Tests for "elements_basic" password input fields of ext:styleguide
 */
final class ElementsBasicPasswordCest extends AbstractElementsBasicCest
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
        $I->click('password');
    }

    /**
     * Data provider to check various type=password variants
     */
    private function passwordInputFieldsDataProvider(): array
    {
        // @todo
        // + server-side password obfuscation value is `*********` (9 chars)
        // + client-side password obfuscation value is `********` (8 chars)
        return [
            // @todo add other password field variants
            [
                'label' => 'password_2',
                'inputValue' => 'Kasper',
                'expectedValue' => '********',
                'expectedInternalValue' => 'Kasper',
                // even if `password_2` is not hashed, it never should expose the value
                'expectedValueAfterSave' => '*********',
                'comment' => '',
            ],
        ];
    }

    #[DataProvider('passwordInputFieldsDataProvider')]
    public function passwordInputFields(ApplicationTester $I, Example $testData): void
    {
        $this->runInputFieldTest($I, $testData);
    }
}
