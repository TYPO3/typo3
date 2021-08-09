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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Impexp;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Various context menu related tests
 */
class UsersCest extends AbstractCest
{
    protected $inPageTree = '#typo3-pagetree-treeContainer .nodes';
    protected $inModuleHeader = '.module-docheader';
    protected $inModuleTabs = '#ImportExportController .nav-tabs';
    protected $inModuleTabsBody = '#ImportExportController .tab-content';

    protected $buttonUser = '#typo3-cms-backend-backend-toolbaritems-usertoolbaritem';
    protected $buttonLogout = '#typo3-cms-backend-backend-toolbaritems-usertoolbaritem button.btn.btn-danger';
    protected $contextMenuMore = '#contentMenu0 li.list-group-item-submenu';
    protected $contextMenuExport = '#contentMenu1 li.list-group-item[data-callback-action=exportT3d]';
    protected $contextMenuImport = '#contentMenu1 li.list-group-item[data-callback-action=importT3d]';
    protected $buttonViewPage = 'span[data-identifier="actions-view-page"]';
    protected $tabUpload = 'a[href="#import-upload"]';
    protected $checkboxForceAllUids = 'input#checkForce_all_UIDS';

    /**
     * @param BackendTester $I
     * @throws \Exception
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement('svg .nodes .node');
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function doNotShowImportInContextMenuForNonAdminUser(BackendTester $I, PageTree $pageTree): void
    {
        $selectedPageTitle = 'Root';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../*[contains(@class, \'node-icon-container\')]';

        $this->setPageAccess($I, $pageTree, [$selectedPageTitle], 1);
        $this->setModAccess($I, 1, ['web_list' => true]);
        $this->setUserTsConfig($I, 2, '');
        $I->useExistingSession('editor');

        $I->click($selectedPageIcon);
        $I->waitForElementVisible($this->contextMenuMore, 5);
        $I->click($this->contextMenuMore);
        $I->waitForElementVisible($this->contextMenuExport, 5);
        $I->seeElement($this->contextMenuExport);
        $I->dontSeeElement($this->contextMenuImport);

        $I->useExistingSession('admin');
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function showImportInContextMenuForNonAdminUserIfFlagSet(BackendTester $I): void
    {
        $selectedPageTitle = 'Root';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../*[contains(@class, \'node-icon-container\')]';

        $this->setUserTsConfig($I, 2, 'options.impexp.enableImportForNonAdminUser = 1');
        $I->useExistingSession('editor');

        $I->click($selectedPageIcon);
        $I->waitForElementVisible($this->contextMenuMore, 5);
        $I->click($this->contextMenuMore);
        $I->waitForElementVisible($this->contextMenuExport, 5);
        $I->seeElement($this->contextMenuExport);
        $I->seeElement($this->contextMenuImport);

        $I->useExistingSession('admin');
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function hideImportCheckboxForceAllUidsForNonAdmin(BackendTester $I): void
    {
        $selectedPageTitle = 'Root';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../*[contains(@class, \'node-icon-container\')]';

        $I->click($selectedPageIcon);
        $I->waitForElementVisible($this->contextMenuMore, 5);
        $I->click($this->contextMenuMore);
        $I->waitForElementVisible($this->contextMenuImport, 5);
        $I->click($this->contextMenuImport);
        $I->switchToContentFrame();
        $I->seeElement($this->checkboxForceAllUids);

        $I->useExistingSession('editor');

        $I->click($selectedPageIcon);
        $I->waitForElementVisible($this->contextMenuMore, 5);
        $I->click($this->contextMenuMore);
        $I->waitForElementVisible($this->contextMenuImport, 5);
        $I->click($this->contextMenuImport);
        $I->switchToContentFrame();
        $I->dontSeeElement($this->checkboxForceAllUids);

        $I->useExistingSession('admin');
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function hideUploadTabAndImportPathIfNoImportFolderAvailable(BackendTester $I, PageTree $pageTree): void
    {
        $selectedPageTitle = 'Root';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../*[contains(@class, \'node-icon-container\')]';

        $I->click($selectedPageIcon);
        $I->waitForElementVisible($this->contextMenuMore, 5);
        $I->click($this->contextMenuMore);
        $I->waitForElementVisible($this->contextMenuImport, 5);
        $I->click($this->contextMenuImport);
        $I->switchToContentFrame();
        $I->see('From path:', $this->inModuleTabsBody);
        $I->seeElement($this->inModuleTabs . ' ' . $this->tabUpload);

        $I->useExistingSession('editor');

        $I->click($selectedPageIcon);
        $I->waitForElementVisible($this->contextMenuMore, 5);
        $I->click($this->contextMenuMore);
        $I->waitForElementVisible($this->contextMenuImport, 5);
        $I->click($this->contextMenuImport);
        $I->switchToContentFrame();
        $I->dontSee('From path:', $this->inModuleTabsBody);
        $I->dontSeeElement($this->inModuleTabs . ' ' . $this->tabUpload);

        $I->useExistingSession('admin');

        $this->setPageAccess($I, $pageTree, ['Root'], 0);
        $this->setModAccess($I, 1, ['web_list' => false]);
        $this->setUserTsConfig($I, 2, '');
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function checkVisualElements(BackendTester $I, PageTree $pageTree): void
    {
        $selectedPageTitle = 'Root';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../*[contains(@class, \'node-icon-container\')]';

        $I->click($this->inPageTree . ' .node.identifier-0_0 .node-icon-container');
        $I->waitForElementVisible($this->contextMenuMore, 5);
        $I->click($this->contextMenuMore);
        $I->waitForElementVisible($this->contextMenuExport, 5);
        $I->click($this->contextMenuImport);
        $I->switchToContentFrame();
        $I->dontSeeElement($this->inModuleHeader . ' ' . $this->buttonViewPage);

        $I->switchToMainFrame();

        $I->click('List');
        $I->click($selectedPageIcon);
        $I->waitForElementVisible($this->contextMenuMore, 5);
        $I->click($this->contextMenuMore);
        $I->waitForElementVisible($this->contextMenuExport, 5);
        $I->click($this->contextMenuImport);
        $I->switchToContentFrame();
        $I->seeElement($this->inModuleHeader . ' ' . $this->buttonViewPage);

        $this->setPageAccess($I, $pageTree, ['Root'], 1);
        $this->setModAccess($I, 1, ['web_list' => true]);
        $this->setUserTsConfig($I, 2, 'options.impexp.enableImportForNonAdminUser = 1');
        $I->useExistingSession('editor');

        $I->click($selectedPageIcon);
        $I->waitForElementVisible($this->contextMenuMore, 5);
        $I->click($this->contextMenuMore);
        $I->waitForElementVisible($this->contextMenuExport, 5);
        $I->click($this->contextMenuImport);
        $I->switchToContentFrame();
        $I->seeElement($this->inModuleHeader . ' ' . $this->buttonViewPage);

        $I->useExistingSession('admin');

        $this->setPageAccess($I, $pageTree, ['Root'], 0);
        $this->setModAccess($I, 1, ['web_list' => false]);
        $this->setUserTsConfig($I, 2, '');
    }
}
