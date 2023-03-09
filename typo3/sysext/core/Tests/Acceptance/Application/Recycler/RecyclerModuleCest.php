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

final class RecyclerModuleCest
{
    private PageTree $pageTree;
    private ModalDialog $modalDialog;

    private static string $rootPageTitle = 'styleguide TCA demo';
    private static string $treeNode = '#typo3-pagetree-tree .nodes .node';
    private static string $dragNode = '#typo3-pagetree-toolbar .svg-toolbar__drag-node';
    private static string $nodeEditInput = '.node-edit';
    private static string $sysNoteSubject = 'Dummy Recycler Content';
    private static string $pageTitle = 'Dummy 1-styleguide TCA demo-new';

    public function _before(ApplicationTester $I, PageTree $pageTree, ModalDialog $modalDialog): void
    {
        $this->pageTree = $pageTree;
        $this->modalDialog = $modalDialog;

        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement(self::$treeNode, 5);
        $I->waitForElement(self::$dragNode, 5);
        $this->pageTree->openPath([self::$rootPageTitle]);

        // Wait until DOM actually rendered everything
        $I->waitForElement(self::$treeNode, 5);

        $this->pageTree->dragAndDropNewPage(self::$rootPageTitle, self::$dragNode, self::$nodeEditInput);
        $newPage = $this->pageTree->getPageXPathByPageName(self::$pageTitle);
        $I->click($newPage);
        $I->switchToContentFrame();
        $I->waitForElement('[title="Create new record"]');
        $I->click('a[title="Create new record"]');
        $I->click('//a[text()[normalize-space(.) = "Internal note"]]');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[sys_note]") and contains(@data-formengine-input-name, "[subject]")]', self::$sysNoteSubject);
        $I->click('button[name="_savedok"]');
        $I->click('a[title="Close"]');
    }

    public function deleteAndRecoverRecords(ApplicationTester $I): void
    {
        $this->deletePage($I);
        $this->goToRecyclerModule($I);

        // Select depth infinite
        $I->selectOption('select[name="depth"]', 999);

        $I->amGoingTo('See if the deleted page and its content appear in the recycler');
        $I->waitForText(self::$pageTitle);
        $I->waitForText(self::$sysNoteSubject);

        $I->amGoingTo('Recover the page and its contents');
        $I->click('tr[data-recordtitle="' . self::$pageTitle . '"] .t3js-multi-record-selection-check');
        $I->click('button[data-multi-record-selection-action="massundo"]');
        $this->modalDialog->canSeeDialog();
        $I->click('#undo-recursive');
        $this->modalDialog->clickButtonInDialog('Recover');

        $I->amGoingTo('See if page and content got restored');
        $I->switchToMainFrame();
        $I->click('List');
        $newPage = $this->pageTree->getPageXPathByPageName(self::$pageTitle);
        $I->click($newPage);
        $I->switchToContentFrame();
        $I->waitForText(self::$sysNoteSubject, 10, 'a[aria-label="Edit record"]');
    }

    private function deletePage(ApplicationTester $I): void
    {
        $I->switchToMainFrame();
        $I->click('List');
        $page = $this->pageTree->getPageXPathByPageName(self::$pageTitle);
        $I->click($page);

        // Close all notifications to avoid click interceptions
        $I->click('#alert-container .close');

        $I->switchToContentFrame();
        $I->click('a[title="Edit page properties"]');
        $I->click('a[title="Delete"]');
        $this->modalDialog->clickButtonInDialog('Delete record (!)');
    }

    private function goToRecyclerModule(ApplicationTester $I): void
    {
        $I->switchToMainFrame();
        $I->click('Recycler');
        $I->waitForElement(self::$treeNode, 5);
        $page = $this->pageTree->getPageXPathByPageName(self::$rootPageTitle);
        $I->click($page);
        $I->switchToContentFrame();
    }
}
