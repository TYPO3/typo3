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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\RecordList;

use Codeception\Util\Locator;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Cases concerning sys_note records
 */
class SysNoteCest
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
     * @param PageTree $pageTree
     */
    public function notesEntryCanBeEdited(BackendTester $I, PageTree $pageTree)
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

        $I->scrollTo(Locator::find('span', ['data-table' => 'sys_note']));
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
        $I->click('.note-list > .note > .note-header > .note-header-bar > .note-actions a:nth-child(1)');
        $I->wait(0.2);
        $I->canSee('Edit Internal note "new sys_note"');
    }
}
