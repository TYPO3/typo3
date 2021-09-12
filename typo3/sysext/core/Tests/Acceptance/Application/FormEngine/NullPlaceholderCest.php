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

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for IRRE null placeholder fields
 */
class NullPlaceholderCest
{
    /**
     * Call backend and open list module
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $this->goToListModule($I, $pageTree);
    }

    /**
     * This scenario tests whether activating a null placeholder checkbox marks its state as "changed"
     *
     * @throws \Exception
     */
    public function checkIfDeactivatingNullCheckboxesMarksAsChanged(ApplicationTester $I): void
    {
        $I->amGoingTo('Check if deactivating null checkboxes marks as "changed"');

        $editRecordLinkCssPath = '#recordlist-tx_styleguide_inline_fal a[data-bs-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);

        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Form engine - inline fal "1" on page "inline fal"');
        $I->click('typical fal');
        $I->click('.form-irre-header');
        $I->waitForElementNotVisible('.nprogress-custom-parent');

        $I->amGoingTo('enable checkboxes and see whether the fields get marked as changed');
        foreach (['title', 'alternative', 'description'] as $fieldName) {
            $currentCheckboxSelector = '//input[contains(@name, "[' . $fieldName . ']") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]';
            $I->checkOption($currentCheckboxSelector);
            $I->seeElement($currentCheckboxSelector . '/./ancestor::div[contains(concat(\' \', @class, \' \'), \'has-change\')]');

            // Remove focus from field, otherwise codeception can't find other checkboxes
            $I->click('.form-irre-object .form-section');
        }
    }

    /**
     * Open list module
     *
     * @throws \Exception
     */
    private function goToListModule(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->switchToMainFrame();
        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'inline fal']);
        $I->switchToContentFrame();
        $I->waitForText('inline fal');
    }
}
