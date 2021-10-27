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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Recycler;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

class RecyclerModuleCest
{
    protected PageTree $pageTree;
    protected ModalDialog $modalDialog;

    protected static string $rootPageTitle = 'styleguide TCA demo';
    protected static string $treeNode = '#typo3-pagetree-tree .nodes .node';
    protected static string $dragNode = '#typo3-pagetree-toolbar .svg-toolbar__drag-node';
    protected static string $nodeEditInput = '.node-edit';
    protected static string $contentTitle = 'Dummy Recycler Content';
    protected static string $pageTitle = 'Dummy 1-styleguide TCA demo-new';

    /**
     * @param ApplicationTester $I
     */
    public function _before(ApplicationTester $I, PageTree $pageTree, ModalDialog $modalDialog): void
    {
        $this->pageTree = $pageTree;
        $this->modalDialog = $modalDialog;

        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement(static::$treeNode, 5);
        $I->waitForElement(static::$dragNode, 5);
        $this->pageTree->openPath([static::$rootPageTitle]);

        // Wait until DOM actually rendered everything
        $I->waitForElement(static::$treeNode, 5);

        $this->pageTree->dragAndDropNewPage(static::$rootPageTitle, static::$dragNode, static::$nodeEditInput);
        $newPage = $this->pageTree->getPageXPathByPageName(static::$pageTitle);
        $I->click($newPage);
        $I->switchToContentFrame();
        $I->waitForElement('[title="Create new record"]');
        $I->click('a[title="Create new record"]');
        $I->click('//a[text()[normalize-space(.) = "Page Content"]]');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[tt_content]") and contains(@data-formengine-input-name, "[header]")]', static::$contentTitle);
        $I->click('button[name="_savedok"]');
        $I->click('a[title="Close"]');
    }

    /**
     * @param ApplicationTester $I
     * @throws \Exception
     */
    public function deleteAndRecoverRecords(ApplicationTester $I): void
    {
        $this->deletePage($I);
        $this->goToRecyclerModule($I);

        // Select depth infinite
        $I->selectOption('select[name="depth"]', 999);

        $I->amGoingTo('See if the deleted page its content appear in the recycler');
        $I->waitForText(static::$pageTitle);
        $I->waitForText(static::$contentTitle);

        $I->amGoingTo('Recover the page and its contents');
        $I->click('tr[data-recordtitle="' . static::$pageTitle . '"] .t3js-multi-record-selection-check');
        $I->click('button[data-multi-record-selection-action="massundo"]');
        $this->modalDialog->canSeeDialog();
        $I->click('#undo-recursive');
        $this->modalDialog->clickButtonInDialog('Recover');

        $I->amGoingTo('See if page and content got restored');
        $I->switchToMainFrame();
        $I->click('List');
        $newPage = $this->pageTree->getPageXPathByPageName(static::$pageTitle);
        $I->click($newPage);
        $I->switchToContentFrame();
        $I->waitForText(static::$contentTitle, 10, 'a[aria-label="Edit record"]');
    }

    /**
     * @todo: Method protected! This means the test is disabled.
     *        There are at least two tests that confuse this test if they are executed earlier:
     *        - PageTreeFilterCest.php:deletingPageWithFilterAppliedRespectsFilterUponPageTreeReload
     *        - InlineFalCest.php:deleteFalRelation
     *        The main issue is that this Cest.php works on a main page within styleguide tree which
     *        is deleted and resurrected. This breaks as soon as other tests delete something within
     *        the same tree. Suggestion: Let _before() create *two* pages: A main page as tree page
     *        to use as recycler main page, plus a sub page of that page that can be deleted and
     *        resurrected at will. This should limit side effects from other tests.
     */
    protected function deleteAndWipeRecords(ApplicationTester $I): void
    {
        $this->deletePage($I);
        $this->goToRecyclerModule($I);

        // Select depth infinite
        $I->selectOption('select[name="depth"]', 999);

        $I->click('.t3js-multi-record-selection-check-actions-toggle');
        $I->click('button[data-multi-record-selection-check-action="check-all"]');

        $I->click('button[data-multi-record-selection-action="massdelete"]');
        $this->modalDialog->canSeeDialog();
        $this->modalDialog->clickButtonInDialog('Delete');

        $I->waitForElementVisible('#alert-container');
        $I->waitForText('2 records were deleted.', 10, '#alert-container');
        $I->click('#alert-container .close');
        $I->waitForElementNotVisible('#alert-container typo3-notification-message');
        // Reload recycler to make sure the record are actually delete from DB
        $I->switchToContentFrame();
        $I->click('[data-action="reload"]');

        $I->cantSee(static::$contentTitle);
        $I->cantSee(static::$pageTitle);
    }

    /**
     * @param ApplicationTester $I
     */
    private function deletePage(ApplicationTester $I): void
    {
        $I->switchToMainFrame();
        $I->click('List');
        $page = $this->pageTree->getPageXPathByPageName(static::$pageTitle);
        $I->click($page);

        $I->switchToContentFrame();
        $I->click('a[title="Edit page properties"]');
        $I->click('a[title="Delete"]');
        $this->modalDialog->clickButtonInDialog('Delete record (!)');
    }

    /**
     * @param ApplicationTester $I
     * @throws \Exception
     */
    private function goToRecyclerModule(ApplicationTester $I): void
    {
        $I->switchToMainFrame();
        $I->click('Recycler');
        $I->waitForElement(static::$treeNode, 5);
        $page = $this->pageTree->getPageXPathByPageName(static::$rootPageTitle);
        $I->click($page);
        $I->switchToContentFrame();
    }
}
