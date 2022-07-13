<?php

declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\PageTree;

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

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

class PageTreeFilterCest
{
    protected string $filterInputField = '#typo3-pagetree #typo3-pagetree-toolbar .search-input';
    protected string $pageTreeSecondaryOptions = '#typo3-pagetree #typo3-pagetree-toolbar .dropdown-toggle';
    protected string $pageTreeReloadButton = '#typo3-pagetree #typo3-pagetree-toolbar typo3-backend-icon[identifier=actions-refresh]';
    protected string $inPageTree = '#typo3-pagetree-tree .nodes';

    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('List');

        $I->waitForElement('#typo3-pagetree-tree .nodes .node');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->waitForElement('#typo3-pagetree-tree .nodes .node');
    }

    /**
     * @throws \Exception
     */
    public function filterTreeForPage(ApplicationTester $I): void
    {
        $I->fillField($this->filterInputField, 'Group');
        $this->waitForPageTreeLoad($I);
        // [#91884] no Enter key press on purpose. The search should start by itself without additional Enter key press
        // and this assertion makes sure the filter worked
        $I->waitForElementNotVisible('//*[text()=\'inline expandsingle\']');

        $I->canSee('elements group', $this->inPageTree);
        $I->canSee('inline mngroup', $this->inPageTree);

        // [#91883] this happens, when translated pages are also part of the result set
        $I->amGoingTo('prove translated pages are not shown in the filtered page tree');
        $I->cantSee('inline mngroup - language 3', $this->inPageTree);

        $I->click($this->pageTreeSecondaryOptions);
        $I->click($this->pageTreeReloadButton);
        $this->waitForPageTreeLoad($I);

        // [#91885] filter must still apply after page tree reload
        $I->amGoingTo('prove the filter applies after page tree reload');
        $I->cantSee('flex', $this->inPageTree);
        $I->seeInField($this->filterInputField, 'Group');
    }

    public function clearFilterReloadsPageTreeWithoutFilterApplied(ApplicationTester $I): void
    {
        $I->fillField($this->filterInputField, 'Group');
        $this->waitForPageTreeLoad($I);
        $I->waitForElementNotVisible('//*[text()=\'inline expandsingle\']');

        $I->canSee('elements group', $this->inPageTree);
        $I->canSee('inline mngroup', $this->inPageTree);

        $I->pressKey($this->filterInputField, WebDriverKeys::ESCAPE);
        $this->waitForPageTreeLoad($I);

        $I->waitForElementVisible('//*[text()=\'inline expandsingle\']');
        $I->canSee('elements group', $this->inPageTree);
        $I->canSee('inline mngroup', $this->inPageTree);
    }

    /**
     * @throws \Exception
     */
    public function deletingPageWithFilterAppliedRespectsFilterUponPageTreeReload(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->fillField($this->filterInputField, 'Group');
        $this->waitForPageTreeLoad($I);

        $I->canSee('elements group', $this->inPageTree);
        $I->canSee('inline mngroup', $this->inPageTree);

        $this->waitForPageTreeLoad($I);
        $I->waitForText('inline mn', 5);
        $I->waitForElementClickable('//*[text()=\'inline mn\']');
        $I->clickWithRightButton('//*[text()=\'inline mn\']');
        $I->waitForElement('[data-callback-action="deleteRecord"]');
        $I->click('[data-callback-action="deleteRecord"]', '#contentMenu0');

        // don't use $modalDialog->clickButtonInDialog due to too low timeout
        $modalDialog->canSeeDialog();
        $I->click('button[name="delete"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $this->waitForPageTreeLoad($I);

        $I->canSee('elements group', $this->inPageTree);
        $I->waitForElementNotVisible('//*[text()=\'inline mn\']');
        $I->waitForElementNotVisible('//*[text()=\'inline mngroup\']');
        $I->waitForElementNotVisible('//*[text()=\'inline expandsingle\']');
    }

    protected function clearPageTreeFilters(ApplicationTester $I): void
    {
        $I->pressKey($this->filterInputField, WebDriverKeys::ESCAPE);
        $I->click($this->pageTreeSecondaryOptions);
        $I->click($this->pageTreeReloadButton);
    }

    protected function waitForPageTreeLoad(ApplicationTester $I): void
    {
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 10);
        $I->waitForElementNotVisible('#typo3-pagetree .svg-tree-loader', 10);
    }
}
