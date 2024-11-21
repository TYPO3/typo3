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

/**
 * Category tree tests
 */
final class CategoryTreeCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function checkIfCategoryListIsAvailable(ApplicationTester $I): void
    {
        // A sub-element of web module is show
        $I->waitForElementVisible('[data-modulemenu-identifier="web"] + .modulemenu-group-container .modulemenu-action');
        $I->click('[data-modulemenu-identifier="web_list"]');
        $I->switchToContentFrame();
        $I->waitForElement('#recordlist-sys_category');
        $I->seeNumberOfElements('#recordlist-sys_category table > tbody > tr', [5, 100]);
    }

    public function editCategoryItem(ApplicationTester $I): void
    {
        // A sub-element of web module is show
        $I->waitForElementVisible('[data-modulemenu-identifier="web"] + .modulemenu-group-container .modulemenu-action');
        $I->click('[data-modulemenu-identifier="web_list"]');
        $I->switchToContentFrame();
        // Collapse all tables to ensure sys_category table fits into window
        $I->click('button[data-table="pages"] .icon-actions-view-list-collapse');
        $I->wait(1);
        $I->click('button[data-table="be_groups"] .icon-actions-view-list-collapse');
        $I->wait(1);
        $I->click('button[data-table="be_users"] .icon-actions-view-list-collapse');
        $I->wait(1);
        $I->waitForElementVisible('#recordlist-sys_category tr[data-uid="7"] a[aria-label="Edit record"]');
        // Select category with id 7
        $I->click('#recordlist-sys_category tr[data-uid="7"] a[aria-label="Edit record"]');
        $I->waitForText('Category', 20);
        // Change title and level to root
        $I->fillField('input[data-formengine-input-name="data[sys_category][7][title]"]', 'level-1-4');
        $I->click('#identifier-0_7 text.node-name');
        $I->click('#identifier-0_3 text.node-name');
        $I->click('button[name="_savedok"]');
        // Wait for tree and check if isset level-1-4
        $I->waitForElement('.svg-tree-wrapper svg');
        $I->waitForText('Category');
        $I->see('level-1-4');

        $I->click('.t3js-editform-close');
        // Reset all collapsed tables
        $I->click('button[data-table="be_users"] .icon-actions-view-list-expand');
        $I->wait(1);
        $I->click('button[data-table="be_groups"] .icon-actions-view-list-expand');
        $I->wait(1);
        $I->click('button[data-table="pages"] .icon-actions-view-list-expand');
        $I->wait(1);
    }
}
