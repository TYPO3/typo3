<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Formhandler;

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

use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;

/**
 * Category tree tests
 */
class CategoryTreeCest
{
    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $I->useExistingSession();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->switchToIFrame('list_frame');
        $I->waitForText('Web Content Management System');
        $I->switchToIFrame();
    }

    /**
     * @param Admin $I
     */
    public function checkIfCategoryListIsAvailable(Admin $I)
    {
        // A sub-element of web module is show
        $I->waitForElementVisible('#web .modulemenu-group-container .modulemenu-item');
        $I->click('#web_list');
        $I->switchToIFrame('list_frame');
        $I->waitForElement('#recordlist-sys_category');
        $I->seeNumberOfElements('#recordlist-sys_category table > tbody > tr', [5, 100]);
    }

    /**
     * @param Admin $I
     */
    public function editCategoryItem(Admin $I)
    {
        // A sub-element of web module is show
        $I->waitForElementVisible('#web .modulemenu-group-container .modulemenu-item');
        $I->click('#web_list');
        $I->switchToIFrame('list_frame');
        // Collapse all tables and expand category again - ensures category fits into window
        $I->executeJS('$(\'.icon-actions-view-list-collapse\').click();');
        $I->wait(1);
        $I->executeJS('$(\'a[data-table="sys_category"] .icon-actions-view-list-expand\').click();');
        $I->wait(1);
        // Select category with id 7
        $I->click('#recordlist-sys_category tr[data-uid="7"] a[data-original-title="Edit record"]');
        // Change title and level to root
        $I->fillField('input[data-formengine-input-name="data[sys_category][7][title]"]', 'level-1-4');
        $I->click('.identifier-7 text');
        $I->click('.identifier-3 text');
        $I->click('button[name="_savedok"]');
        // Wait for tree and check if isset level-1-4
        $I->waitForElement('.svg-tree-wrapper svg');
        $I->see('level-1-4', '.svg-tree-wrapper svg .node text');
    }
}
