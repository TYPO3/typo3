<?php

declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\PageTree;

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

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\SiteConfiguration;

class PageTreeFilterCest
{
    protected $filterInputFieldClearButton = '#typo3-pagetree #svg-toolbar span[data-identifier=actions-close]';
    protected $filterButton = '#typo3-pagetree #svg-toolbar button[data-tree-icon=actions-filter]';
    protected $filterInputField = '#typo3-pagetree #svg-toolbar .search-input';
    protected $pageTreeReloadButton = '#typo3-pagetree #svg-toolbar button[data-tree-icon=actions-refresh]';
    protected $inPageTree = '#typo3-pagetree-treeContainer .nodes';

    public function _before(BackendTester $I, PageTree $pageTree, SiteConfiguration $siteConfiguration)
    {
        $siteConfiguration->adjustSiteConfiguration();
        $I->useExistingSession('admin');
        $I->click('List');

        $pageTree->openPath(['styleguide TCA demo']);
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function filterTreeForPage(BackendTester $I)
    {
        $I->click($this->filterButton);
        $I->cantSeeElement($this->filterInputFieldClearButton);

        $I->fillField($this->filterInputField, 'Group');
        $this->waitForAjaxRequestToFinish($I);

        $I->amGoingTo('prove filter reset button is visible upon input');
        $I->canSeeElement($this->filterInputFieldClearButton);

        // [#91884] no Enter key press on purpose. The search should start by itself without additional Enter key press
        // and this assertion makes sure the filter worked
        $I->cantSee('inline expandsingle', $this->inPageTree);

        $I->canSee('elements group', $this->inPageTree);
        $I->canSee('inline mngroup', $this->inPageTree);

        // [#91883] this happens, when translated pages are also part of the result set
        $I->amGoingTo('prove translated pages are not shown in the filtered page tree');
        $I->cantSee('inline mngroup - language 3', $this->inPageTree);

        $I->click($this->pageTreeReloadButton);
        $this->waitForAjaxRequestToFinish($I);

        // [#91885] filter must still apply after page tree reload
        $I->amGoingTo('prove the filter applies after page tree reload');
        $I->cantSee('flex', $this->inPageTree);
        $I->seeInField($this->filterInputField, 'Group');
    }

    /**
     * @param BackendTester $I
     */
    public function clearFilterReloadsPageTreeWithoutFilterApplied(BackendTester $I)
    {
        $I->click($this->filterButton);
        $I->fillField($this->filterInputField, 'Group');
        $this->waitForAjaxRequestToFinish($I);

        $I->canSee('elements group', $this->inPageTree);
        $I->canSee('inline mngroup', $this->inPageTree);
        $I->cantSee('inline expandsingle', $this->inPageTree);

        $I->click($this->filterInputFieldClearButton);
        $this->waitForAjaxRequestToFinish($I);

        $I->canSee('elements group', $this->inPageTree);
        $I->canSee('inline mngroup', $this->inPageTree);
        $I->canSee('inline expandsingle', $this->inPageTree);
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog   $modalDialog
     *
     * @throws \Exception
     */
    public function deletingPageWithFilterAppliedRespectsFilterUponPageTreeReload(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click($this->filterButton);
        $I->fillField($this->filterInputField, 'Group');
        $this->waitForAjaxRequestToFinish($I);

        $I->canSee('elements group', $this->inPageTree);
        $I->canSee('inline mngroup', $this->inPageTree);

        $inlineMnGroupIcon = '#identifier-0_92 > g.node-icon-container';
        $I->click($inlineMnGroupIcon);
        $I->canSeeElement('#contentMenu0');
        $I->click('[data-callback-action="deleteRecord"]', '#contentMenu0');

        // don't use $modalDialog->clickButtonInDialog due to too low timeout
        $modalDialog->canSeeDialog();
        $I->click('button[name="delete"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $this->waitForAjaxRequestToFinish($I);

        $I->canSee('elements group', $this->inPageTree);
        $I->cantSee('inline mngroup', $this->inPageTree);
        $I->cantSee('inline expandsingle', $this->inPageTree);
    }

    /**
     * @param BackendTester $I
     */
    protected function clearPageTreeFilters(
        BackendTester $I
    ): void {
        $I->click($this->filterInputFieldClearButton);
        $I->click($this->pageTreeReloadButton);
        $I->cantSeeElement($this->filterInputFieldClearButton);
    }

    /**
     * @param BackendTester $I
     */
    protected function waitForAjaxRequestToFinish(BackendTester $I): void
    {
        $I->waitForJS('return $.active == 0;', 10);
        // sometimes rendering is still slower that ajax being finished.
        $I->wait(0.5);
    }
}
