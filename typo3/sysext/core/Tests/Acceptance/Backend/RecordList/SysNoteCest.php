<?php
declare(strict_types = 1);

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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\RecordList;

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;
use TYPO3\TestingFramework\Core\Acceptance\Support\Page\PageTree;

/**
 * Cases concerning sys_note records
 */
class SysNoteCest
{
    /**
     * @param Admin $I
     *
     * @throws \Exception
     */
    public function _before(Admin $I)
    {
        $I->useExistingSession();
    }

    /**
     * @param Admin $I
     * @param PageTree $pageTree
     *
     * @throws \Exception
     */
    public function notesEntryCanBeEdited(Admin $I, PageTree $pageTree)
    {
        $I->wantToTest('whether sysnote entries can be edited via the Internal Notes section in List View');

        $I->amGoingTo('create a record');
        $I->click('List');
        $I->waitForElementNotVisible('#nprogress');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->wait(0.2);
        $I->switchToContentFrame();

        $I->click('.module-docheader .btn[title="Create new record"]');
        $I->wait(0.2);
        $I->canSee('New record');

        $I->click('ul.list-tree');
        // it takes two strokes to get all the way down
        $I->pressKey('body', WebDriverKeys::PAGE_DOWN);
        $I->pressKey('body', WebDriverKeys::PAGE_DOWN);
        $I->click('Internal note');

        $I->fillField('//input[contains(@data-formengine-input-name, "data[sys_note]") and contains(@data-formengine-input-name, "[subject]")]', 'new sys_note');
        $I->click('button[name="_savedok"]');
        $I->wait(0.2);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->click('a[title="Close"]');
        $I->wait(1);
        $I->canSee('styleguide TCA demo', 'h1');
        $I->click('a.t3js-toggle-recordlist[data-table="pages"]');
        $I->canSee('Internal notes', 'h2');
        $I->canSee('new sys_note');
        $I->click('div.typo3-dblist-sysnotes > div > div.panel-heading.clearfix > div > a:nth-child(1)');
        $I->wait(0.2);
        $I->canSee('Edit Internal note "new sys_note"');
    }
}
