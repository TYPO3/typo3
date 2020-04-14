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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\FormEngine;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Category tree tests
 */
class CategoryTreeCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * @param BackendTester $I
     */
    public function checkIfCategoryListIsAvailable(BackendTester $I)
    {
        // A sub-element of web module is show
        $I->waitForElementVisible('#web + .modulemenu-group-container .modulemenu-action');
        $I->click('#web_list');
        $I->switchToContentFrame();
        $I->waitForElement('#recordlist-sys_category');
        $I->seeNumberOfElements('#recordlist-sys_category table > tbody > tr', [5, 100]);
    }

    /**
     * @param BackendTester $I
     */
    public function editCategoryItem(BackendTester $I)
    {
        // A sub-element of web module is show
        $I->waitForElementVisible('#web + .modulemenu-group-container .modulemenu-action');
        $I->click('#web_list');
        $I->switchToContentFrame();
        // Collapse all tables and expand category again - ensures category fits into window
        $I->executeJS('$(\'.icon-actions-view-list-collapse\').click();');
        $I->wait(1);
        $I->executeJS('$(\'a[data-table="sys_category"] .icon-actions-view-list-expand\').click();');
        $I->waitForElementVisible('#recordlist-sys_category tr[data-uid="7"] a[data-original-title="Edit record"]');
        // Select category with id 7
        $I->click('#recordlist-sys_category tr[data-uid="7"] a[data-original-title="Edit record"]');
        $I->waitForText('Category', 20);
        // Change title and level to root
        $I->fillField('input[data-formengine-input-name="data[sys_category][7][title]"]', 'level-1-4');
        $I->click('.identifier-0_7 text.node-name');
        $I->click('.identifier-0_3 text.node-name');
        $I->click('button[name="_savedok"]');
        // Wait for tree and check if isset level-1-4
        $I->waitForElement('.svg-tree-wrapper svg');
        $I->waitForText('Category');
        $I->see('level-1-4');
    }
}
