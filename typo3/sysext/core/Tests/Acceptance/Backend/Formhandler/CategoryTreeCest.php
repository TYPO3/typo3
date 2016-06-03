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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Admin;

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
    }

    /**
     * @param Admin $I
     */
    public function checkIfCategoryListIsAvailable(Admin $I)
    {
        // A sub-element of web module is show
        $I->waitForElementVisible('#web .typo3-module-menu-group-container .typo3-module-menu-item');
        $I->click('#web_list');
        $I->switchToIFrame('content');
        $I->waitForElement('#recordlist-sys_category');
        $I->seeNumberOfElements('#recordlist-sys_category table > tbody > tr', [5, 100]);
    }

    /**
     * @param Admin $I
     */
    public function editCategoryItem(Admin $I)
    {
        // A sub-element of web module is show
        $I->waitForElementVisible('#web .typo3-module-menu-group-container .typo3-module-menu-item');
        $I->click('#web_list');
        $I->switchToIFrame('content');
        // Select category with id 7
        $I->click('#recordlist-sys_category tr[data-uid="7"] a[data-original-title="Edit record"]');
        // Change title and level to root
        $I->fillField('input[data-formengine-input-name="data[sys_category][7][title]"]', 'level-1-4');
        $I->click('div[ext\:tree-node-id="7"]');
        $I->click('div[ext\:tree-node-id="3"]');
        $I->click('button[name="_savedok"]');
        // Wait for tree and check if isset level-1-4
        $I->waitForElement('.x-panel.x-tree');
        $I->see('level-1-4', '.x-panel.x-tree');
    }
}
