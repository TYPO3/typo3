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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Impexp;

use Codeception\Exception\MalformedLocatorException;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Various context menu related tests
 */
final class UsersCest extends AbstractCest
{
    private string $inPageTree = '#typo3-pagetree-treeContainer .nodes-list';
    private string $inModuleHeader = '.module-docheader';
    private string $inModuleTabs = '#ImportExportController .nav-tabs';
    private string $inModuleTabsBody = '#ImportExportController .tab-content';
    private string $contextMenuMore = 'button[data-contextmenu-id="root_more"]';
    private string $contextMenuExport = 'button[data-contextmenu-id="root_more_exportT3d"]';
    private string $contextMenuImport = 'button[data-contextmenu-id="root_more_importT3d"]';
    private string $buttonViewPage = 'span[data-identifier="actions-view-page"]';
    private string $tabUpload = 'button[data-bs-target="#import-upload"]';
    private string $checkboxForceAllUids = 'input#checkForce_all_UIDS';

    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->click('List');
    }

    public function doNotShowImportAndExportInContextMenuForNonAdminUser(ApplicationTester $I, PageTree $pageTree): void
    {
        $selectedPageTitle = 'Root';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../../*[contains(@class, \'node-icon\')]';

        $this->setPageAccess($I, $pageTree, [$selectedPageTitle], 1);
        $this->setModAccess($I, 1, ['web_list' => true]);
        $this->setUserTsConfig($I, 2, '');
        $I->useExistingSession('editor');

        $I->click($selectedPageIcon);
        $I->switchToMainFrame();
        $this->selectInContextMenu($I, [$this->contextMenuMore]);
        $I->waitForElementVisible('#contextmenu-root_more', 5);
        $I->dontSeeElement($this->contextMenuExport);
        $I->dontSeeElement($this->contextMenuImport);

        $I->useExistingSession('admin');
    }

    public function showImportExportInContextMenuForNonAdminUserIfFlagSet(ApplicationTester $I): void
    {
        $selectedPageTitle = 'Root';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../../*[contains(@class, \'node-icon\')]';

        $this->setUserTsConfig($I, 2, 'options.impexp.enableImportForNonAdminUser = 1\noptions.impexp.enableExportForNonAdminUser = 1');
        $I->useExistingSession('editor');

        $I->click($selectedPageIcon);
        $this->selectInContextMenu($I, [$this->contextMenuMore]);
        $I->waitForElementVisible('#contextmenu-root_more', 5);
        $I->seeElement($this->contextMenuImport);
        $I->seeElement($this->contextMenuExport);

        $I->useExistingSession('admin');
    }

    /**
     * @depends showImportExportInContextMenuForNonAdminUserIfFlagSet
     */
    public function hideImportCheckboxForceAllUidsForNonAdmin(ApplicationTester $I): void
    {
        $selectedPageTitle = 'Root';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../../*[contains(@class, \'node-icon\')]';
        $importPageSectionTitle = 'Select file to import';

        $I->click($selectedPageIcon);
        $I->switchToMainFrame();
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->seeElement($this->checkboxForceAllUids);

        $I->useExistingSession('editor');

        $I->click($selectedPageIcon);
        $I->switchToMainFrame();
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->waitForText($importPageSectionTitle);
        $I->dontSeeElement($this->checkboxForceAllUids);

        $I->useExistingSession('admin');
    }

    /**
     * @depends showImportExportInContextMenuForNonAdminUserIfFlagSet
     */
    public function hideUploadTabAndImportPathIfNoImportFolderAvailable(ApplicationTester $I, PageTree $pageTree): void
    {
        $selectedPageTitle = 'Root';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../../*[contains(@class, \'node-icon\')]';
        $importPageSectionTitle = 'Select file to import';

        $I->click($selectedPageIcon);
        $I->switchToMainFrame();
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->see('From path:', $this->inModuleTabsBody);
        $I->seeElement($this->inModuleTabs . ' ' . $this->tabUpload);

        $I->useExistingSession('editor');

        $I->click($selectedPageIcon);
        $I->switchToMainFrame();
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->waitForText($importPageSectionTitle);
        $I->dontSee('From path:', $this->inModuleTabsBody);
        $I->dontSeeElement($this->inModuleTabs . ' ' . $this->tabUpload);

        $I->useExistingSession('admin');

        $this->setPageAccess($I, $pageTree, ['Root'], 0);
        $this->setModAccess($I, 1, ['web_list' => false]);
        $this->setUserTsConfig($I, 2, '');
    }

    public function checkVisualElements(ApplicationTester $I, PageTree $pageTree): void
    {
        $selectedPageTitle = 'Root';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../../*[contains(@class, \'node-icon\')]';
        $importPageSectionTitle = 'Select file to import';

        $I->click($this->inPageTree . ' [role="treeitem"][data-id="0"] .node-icon');
        $I->switchToMainFrame();
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->waitForText($importPageSectionTitle);
        $I->dontSeeElement($this->inModuleHeader . ' ' . $this->buttonViewPage);

        $I->switchToMainFrame();

        $I->click('List');
        $I->click($selectedPageIcon);
        $I->switchToMainFrame();
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->seeElement($this->inModuleHeader . ' ' . $this->buttonViewPage);

        $this->setPageAccess($I, $pageTree, ['Root'], 1);
        $this->setModAccess($I, 1, ['web_list' => true]);
        $this->setUserTsConfig($I, 2, 'options.impexp.enableImportForNonAdminUser = 1');
        $I->useExistingSession('editor');

        $I->click($selectedPageIcon);
        $I->switchToMainFrame();
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->seeElement($this->inModuleHeader . ' ' . $this->buttonViewPage);

        $I->useExistingSession('admin');

        $this->setPageAccess($I, $pageTree, ['Root'], 0);
        $this->setModAccess($I, 1, ['web_list' => false]);
        $this->setUserTsConfig($I, 2, '');
    }

    private function setPageAccess(ApplicationTester $I, PageTree $pageTree, array $pagePath, int $userGroupId, int $recursionLevel = 1): void
    {
        $I->switchToMainFrame();
        $I->click('Permissions');
        $pageTree->openPath($pagePath);
        $I->switchToContentFrame();
        $I->wait(1);
        $I->waitForElementVisible('#typo3-permissionList tr:nth-child(2) [title="Change permissions"]');
        $I->click('#typo3-permissionList tr:nth-child(2) [title="Change permissions"]');
        $I->waitForElementVisible('#PermissionControllerEdit');
        $I->wait(1);
        $I->selectOption('//select[@id="selectGroup"]', ['value' => $userGroupId]);
        $recursionLevelOption = $I->grabTextFrom('//select[@id="recursionLevel"]/option[' . $recursionLevel . ']');
        $I->selectOption('//select[@id="recursionLevel"]', ['value' => $recursionLevelOption]);
        $I->click($this->inModuleHeader . ' .btn[title="Save and close"]');
        $I->wait(0.5);
        $I->switchToMainFrame();
        try {
            $needsStepUp = count($I->grabMultiple('.modal-sudo-mode-verification')) > 0;
        } catch (MalformedLocatorException) {
            $needsStepUp = false;
        }
        if ($needsStepUp) {
            $I->see('Verify with user password');
            $I->fillField('//input[@name="password"]', 'password');
            $I->click('//button[@name="verify"]');
        }
        $I->switchToContentFrame();
        $I->wait(0.5);
    }

    private function setModAccess(ApplicationTester $I, int $userGroupId, array $modAccessByName): void
    {
        $I->switchToMainFrame();
        $I->click('Backend Users');
        $I->switchToContentFrame();

        $I->waitForElementVisible($this->inModuleHeader . ' .module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->click($this->inModuleHeader . ' .module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->waitForElementVisible($this->inModuleHeader . ' .module-docheader-bar-buttons .dropdown-menu');
        $I->click('Backend user groups', $this->inModuleHeader . ' .module-docheader-bar-buttons .dropdown-menu');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Backend user groups');
        $I->click('//table/tbody/tr[descendant::button[@data-contextmenu-uid="' . $userGroupId . '"]]/td[2]/a');
        $I->waitForElementVisible('#EditDocumentController');
        $I->click('//form[@id="EditDocumentController"]//ul/li[3]/button');

        foreach ($modAccessByName as $modName => $modAccess) {
            if ((bool)$modAccess) {
                $I->checkOption('//input[@value="' . $modName . '"]');
            } else {
                $I->uncheckOption('//input[@value="' . $modName . '"]');
            }
        }

        $I->click($this->inModuleHeader . ' .btn[title="Save"]');
        $I->wait(0.5);
        $I->switchToMainFrame();
        try {
            $needsStepUp = count($I->grabMultiple('.modal-sudo-mode-verification')) > 0;
        } catch (MalformedLocatorException) {
            $needsStepUp = false;
        }
        if ($needsStepUp) {
            $I->see('Verify with user password');
            $I->fillField('//input[@name="password"]', 'password');
            $I->click('//button[@name="verify"]');
        }
        $I->switchToContentFrame();
        $I->wait(0.5);
        $I->click($this->inModuleHeader . ' .btn[title="Close"]');
        $I->waitForText('Backend user groups');
    }

    private function setUserTsConfig(ApplicationTester $I, int $userId, string $userTsConfig): void
    {
        try {
            $I->seeElement($this->inModuleHeader . ' .btn-group button.dropdown-toggle');
        } catch (\Exception $e) {
            $I->switchToMainFrame();
            $I->click('Backend Users');
            $I->switchToContentFrame();
        }

        $codeMirrorSelector = 'typo3-t3editor-codemirror[name="data[be_users][' . $userId . '][TSconfig]"]';

        $I->waitForElementVisible($this->inModuleHeader . ' .module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->click($this->inModuleHeader . ' .module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->waitForElementVisible($this->inModuleHeader . ' .module-docheader-bar-buttons .dropdown-menu');
        $I->click('Backend users', $this->inModuleHeader . ' .module-docheader-bar-buttons .dropdown-menu');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElement('#typo3-backend-user-list');
        $I->click('//table[@id="typo3-backend-user-list"]/tbody/tr[descendant::button[@data-contextmenu-uid="' . $userId . '"]]//a[@title="Edit"]');
        $I->waitForElement('#EditDocumentController');
        $I->click('//form[@id="EditDocumentController"]//ul/li[6]/button');
        $I->waitForElementVisible($codeMirrorSelector);
        $I->executeJS("document.querySelector('" . $codeMirrorSelector . "').setContent('" . $userTsConfig . "')");
        $I->click($this->inModuleHeader . ' .btn[title="Save"]');
        $I->wait(0.5);
        $I->switchToMainFrame();
        try {
            $needsStepUp = count($I->grabMultiple('.modal-sudo-mode-verification')) > 0;
        } catch (MalformedLocatorException) {
            $needsStepUp = false;
        }
        if ($needsStepUp) {
            $I->see('Verify with user password');
            $I->fillField('//input[@name="password"]', 'password');
            $I->click('//button[@name="verify"]');
        }
        $I->switchToContentFrame();
        $I->wait(0.5);
        $I->click($this->inModuleHeader . ' .btn[title="Close"]');
        $I->waitForElement('#typo3-backend-user-list');
    }
}
