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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Various export related tests
 */
class ExportCest
{
    /**
     * Absolute path to files that must be removed
     * after a test - handled in _after
     *
     * @var array
     */
    protected $testFilesToDelete = [];

    protected $inPageTree = '#typo3-pagetree-treeContainer .nodes';
    protected $inModuleHeader = '.module-docheader';
    protected $inModuleTabs = '#ImportExportController .nav-tabs';
    protected $inModuleTabsBody = '#ImportExportController .tab-content';
    protected $inModulePreview = '#ImportExportController > div:last-child';
    protected $inTabConfiguration = '#export-configuration';
    protected $inFlashMessages = '.typo3-messages';

    /**
     * @param BackendTester $I
     * @param PageTree $pageTree
     * @throws \Exception
     */
    public function _before(BackendTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->waitForElement($this->inPageTree . ' .node', 5);
    }

    /**
     * @param BackendTester $I
     */
    public function _after(BackendTester $I)
    {
        $I->amGoingTo('clean up created files');

        foreach ($this->testFilesToDelete as $filePath) {
            unlink($filePath);
            $I->dontSeeFileFound($filePath);
        }
        $this->testFilesToDelete = [];
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function exportPageAndRecordsDisplaysTitleOfSelectedPageInModuleHeader(BackendTester $I): void
    {
        $contextMenuMore = '#contentMenu0 li.list-group-item-submenu';
        $contextMenuExport = '#contentMenu1 li.list-group-item[data-callback-action=exportT3d]';
        $selectedPageTitle = 'elements t3editor';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../*[contains(@class, \'node-icon-container\')]';

        $I->click($selectedPageIcon);
        $I->waitForElementVisible($contextMenuMore, 5);
        $I->click($contextMenuMore);
        $I->waitForElementVisible($contextMenuExport, 5);
        $I->click($contextMenuExport);
        $I->switchToContentFrame();
        $I->see($selectedPageTitle, $this->inModuleHeader);

        $buttonUpdate = '.btn[value=Update]';
        $I->click($buttonUpdate, $this->inTabConfiguration);
        $this->waitForAjaxRequestToFinish($I);
        $I->see($selectedPageTitle, $this->inModuleHeader);
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function exportTableDisplaysTitleOfRootPageInModuleHeader(BackendTester $I, PageTree $pageTree): void
    {
        $rootPageTitle = 'New TYPO3 site';
        $tablePageTitle = 'elements t3editor';
        $tableTitle = 'Form engine elements - t3editor';

        $pageTree->openPath([$tablePageTitle]);
        $I->switchToContentFrame();
        $I->click($tableTitle);

        $listModuleHeader = '.module-docheader';
        $listModuleBtnExport = 'a[title="Export"]';

        $I->waitForElementVisible($listModuleHeader . ' ' . $listModuleBtnExport, 5);
        $I->click($listModuleBtnExport, $listModuleHeader);
        $I->waitForElementVisible($this->inTabConfiguration, 5);
        $I->see($rootPageTitle, $this->inModuleHeader);
        $I->dontSee($tablePageTitle, $this->inModuleHeader);

        $buttonUpdate = '.btn[value=Update]';
        $I->click($buttonUpdate, $this->inTabConfiguration);
        $this->waitForAjaxRequestToFinish($I);
        $I->see($rootPageTitle, $this->inModuleHeader);
        $I->dontSee($tablePageTitle, $this->inModuleHeader);
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function exportRecordDisplaysTitleOfRootPageInModuleHeader(BackendTester $I, PageTree $pageTree): void
    {
        $contextMenuMore = '#contentMenu0 li.list-group-item-submenu';
        $contextMenuExport = '#contentMenu1 li.list-group-item[data-callback-action=exportT3d]';
        $rootPageTitle = 'New TYPO3 site';
        $recordPageTitle = 'elements t3editor';
        $recordTable = '#recordlist-tx_styleguide_elements_t3editor';
        $recordIcon = 'tr:first-child a.t3js-contextmenutrigger';

        $pageTree->openPath([$recordPageTitle]);
        $I->switchToContentFrame();
        $I->click($recordIcon, $recordTable);
        $I->waitForElementVisible($contextMenuMore, 5);
        $I->click($contextMenuMore);
        $I->waitForElementVisible($contextMenuExport, 5);
        $I->click($contextMenuExport);
        $I->waitForElementVisible($this->inTabConfiguration, 5);
        $I->see($rootPageTitle, $this->inModuleHeader);
        $I->dontSee($recordPageTitle, $this->inModuleHeader);

        $buttonUpdate = '.btn[value=Update]';
        $I->click($buttonUpdate, $this->inTabConfiguration);
        $this->waitForAjaxRequestToFinish($I);
        $I->see($rootPageTitle, $this->inModuleHeader);
        $I->dontSee($recordPageTitle, $this->inModuleHeader);
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function exportPageAndRecords(BackendTester $I)
    {
        $I->wantToTest('exporting a page with records.');

        $pageTitle = 'staticdata';
        $pageIcon = '//*[text()=\'' . $pageTitle . '\']/../*[contains(@class, \'node-icon-container\')]';
        $contextMenuMore = '#contentMenu0 li.list-group-item-submenu';
        $contextMenuExport = '#contentMenu1 li.list-group-item[data-callback-action=exportT3d]';

        $I->click($pageIcon);
        $I->waitForElementVisible($contextMenuMore, 5);
        $I->click($contextMenuMore);
        $I->waitForElementVisible($contextMenuExport, 5);
        $I->click($contextMenuExport);

        $tabExport = 'a[href="#export-filepreset"]';
        $contentExport = '#export-filepreset';
        $buttonSaveToFile = 'tx_impexp[save_export]';

        $I->switchToContentFrame();
        $I->cantSee('No tree exported - only tables on the page.', $this->inModuleTabsBody);
        $I->see('Inside pagetree', $this->inModulePreview);
        $I->dontSee('Outside pagetree', $this->inModulePreview);
        $I->click($tabExport, $this->inModuleTabs);
        $I->waitForElementVisible($contentExport, 5);
        $I->click($buttonSaveToFile, $this->inModuleTabsBody);
        $I->wait(1);
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-success');
        $I->canSee('SAVED FILE', $this->inFlashMessages . ' .alert.alert-success .alert-title');
        $flashMessage = $I->grabTextFrom($this->inFlashMessages . ' .alert.alert-success .alert-message');
        preg_match('/[^"]+"([^"]+)"[^"]+/', $flashMessage, $flashMessageParts);
        $saveFilePath = Environment::getProjectPath() . '/' . $flashMessageParts[1];
        $I->assertFileExists($saveFilePath);

        $this->testFilesToDelete[] = $saveFilePath;
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function exportTable(BackendTester $I)
    {
        $I->wantToTest('exporting a table of records.');

        $rootPage = '.node.identifier-0_0 .node-name';
        $I->canSeeElement($rootPage);
        $I->click($rootPage);

        $sysLanguageTableTitle = 'Website Language';

        $I->switchToContentFrame();
        $I->click($sysLanguageTableTitle);

        $listModuleHeader = '.module-docheader';
        $listModuleBtnExport = 'a[title="Export"]';

        $I->waitForElementVisible($listModuleHeader . ' ' . $listModuleBtnExport, 5);
        $I->click($listModuleBtnExport, $listModuleHeader);

        $tabExport = 'a[href="#export-filepreset"]';
        $contentExport = '#export-filepreset';
        $buttonSaveToFile = 'tx_impexp[save_export]';

        $I->waitForElementVisible($tabExport, 5);
        $I->canSee('No tree exported - only tables on the page.', $this->inModuleTabsBody);
        $I->canSee('Export tables from pages', $this->inModuleTabsBody);
        $I->dontSee('Inside pagetree', $this->inModulePreview);
        $I->see('Outside pagetree', $this->inModulePreview);
        $I->click($tabExport, $this->inModuleTabs);
        $I->waitForElementVisible($contentExport, 5);
        $I->click($buttonSaveToFile, $this->inModuleTabsBody);
        $I->wait(1);
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-success');
        $I->canSee('SAVED FILE', $this->inFlashMessages . ' .alert.alert-success .alert-title');
        $flashMessage = $I->grabTextFrom($this->inFlashMessages . ' .alert.alert-success .alert-message');
        preg_match('/[^"]+"([^"]+)"[^"]+/', $flashMessage, $flashMessageParts);
        $saveFilePath = Environment::getProjectPath() . '/' . $flashMessageParts[1];
        $I->assertFileExists($saveFilePath);

        $this->testFilesToDelete[] = $saveFilePath;
    }

    /**
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    public function exportRecord(BackendTester $I)
    {
        $I->wantToTest('exporting a single record.');

        $rootPage = '.node.identifier-0_0 .node-name';
        $I->canSeeElement($rootPage);
        $I->click($rootPage);

        $sysLanguageTable = '#recordlist-sys_language';
        $sysLanguageIcon = 'tr:first-child a.t3js-contextmenutrigger';
        $contextMenuMore = '#contentMenu0 li.list-group-item-submenu';
        $contextMenuExport = '#contentMenu1 li.list-group-item[data-callback-action=exportT3d]';

        $I->switchToContentFrame();
        $I->click($sysLanguageIcon, $sysLanguageTable);
        $I->waitForElementVisible($contextMenuMore, 5);
        $I->click($contextMenuMore);
        $I->waitForElementVisible($contextMenuExport, 5);
        $I->click($contextMenuExport);

        $tabExport = 'a[href="#export-filepreset"]';
        $contentExport = '#export-filepreset';
        $buttonSaveToFile = 'tx_impexp[save_export]';

        $I->waitForElementVisible($tabExport, 5);
        $I->canSee('No tree exported - only tables on the page.', $this->inModuleTabsBody);
        $I->canSee('Export single record', $this->inModuleTabsBody);
        $I->dontSee('Inside pagetree', $this->inModulePreview);
        $I->see('Outside pagetree', $this->inModulePreview);
        $I->click($tabExport, $this->inModuleTabs);
        $I->waitForElementVisible($contentExport, 5);
        $I->click($buttonSaveToFile, $this->inModuleTabsBody);
        $I->wait(1);
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-success');
        $I->canSee('SAVED FILE', $this->inFlashMessages . ' .alert.alert-success .alert-title');
        $flashMessage = $I->grabTextFrom($this->inFlashMessages . ' .alert.alert-success .alert-message');
        preg_match('/[^"]+"([^"]+)"[^"]+/', $flashMessage, $flashMessageParts);
        $saveFilePath = Environment::getProjectPath() . '/' . $flashMessageParts[1];
        $I->assertFileExists($saveFilePath);

        $this->testFilesToDelete[] = $saveFilePath;
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
