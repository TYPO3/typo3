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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Various export related tests
 */
class ExportCest extends AbstractCest
{
    /**
     * Absolute path to files that must be removed
     * after a test - handled in _after
     *
     * @var array
     */
    protected array $testFilesToDelete = [];

    protected string $inPageTree = '#typo3-pagetree-treeContainer .nodes';
    protected string $inModuleHeader = '.module-docheader';
    protected string $inModuleTabs = '#ImportExportController .nav-tabs';
    protected string $inModuleTabsBody = '#ImportExportController .tab-content';
    protected string $inModulePreview = '#ImportExportController > div:last-child';
    protected string $inTabConfiguration = '#export-configuration';
    protected string $inFlashMessages = '.typo3-messages';

    /**
     * @throws \Exception
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->waitForElement($this->inPageTree . ' .node', 5);
    }

    public function _after(ApplicationTester $I): void
    {
        $I->amGoingTo('clean up created files');

        foreach ($this->testFilesToDelete as $filePath) {
            unlink($filePath);
            $I->dontSeeFileFound($filePath);
        }
        $this->testFilesToDelete = [];
    }

    /**
     * @throws \Exception
     */
    public function exportPageAndRecordsDisplaysTitleOfSelectedPageInModuleHeader(ApplicationTester $I): void
    {
        $selectedPageTitle = 'elements t3editor';
        $selectedPageIcon = '//*[text()=\'' . $selectedPageTitle . '\']/../*[contains(@class, \'node-icon-container\')]';
        $buttonUpdate = '.btn[value=Update]';

        $I->click($selectedPageIcon);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuExport]);
        $I->switchToContentFrame();
        $I->waitForText($selectedPageTitle);
        $I->waitForElementNotVisible('#nprogress');
        $I->see($selectedPageTitle, $this->inModuleHeader);

        $I->click($buttonUpdate, $this->inTabConfiguration);
        $this->waitForAjaxRequestToFinish($I);
        $I->see($selectedPageTitle, $this->inModuleHeader);
    }

    /**
     * @throws \Exception
     */
    public function exportTableDisplaysTitleOfRootPageInModuleHeader(ApplicationTester $I, PageTree $pageTree): void
    {
        $rootPageTitle = 'New TYPO3 site';
        $tablePageTitle = 'elements t3editor';
        $tableTitle = 'Form engine elements - t3editor';
        $listModuleHeader = '.module-docheader';
        $listModuleBtnExport = 'a[title="Export"]';
        $buttonUpdate = '.btn[value=Update]';

        $pageTree->openPath([$tablePageTitle]);
        $I->switchToContentFrame();
        // List module single table mode
        $I->waitForText($tableTitle);
        $I->waitForElementNotVisible('#nprogress');
        $I->click($tableTitle);

        $I->waitForElementVisible($listModuleHeader . ' ' . $listModuleBtnExport, 5);
        $I->click($listModuleBtnExport, $listModuleHeader);
        $I->waitForElementVisible($this->inTabConfiguration, 5);
        $I->see($rootPageTitle, $this->inModuleHeader);
        $I->dontSee($tablePageTitle, $this->inModuleHeader);

        $I->click($buttonUpdate, $this->inTabConfiguration);
        $this->waitForAjaxRequestToFinish($I);
        $I->see($rootPageTitle, $this->inModuleHeader);
        $I->dontSee($tablePageTitle, $this->inModuleHeader);
    }

    /**
     * @throws \Exception
     */
    public function exportRecordDisplaysTitleOfRootPageInModuleHeader(ApplicationTester $I, PageTree $pageTree): void
    {
        $rootPageTitle = 'New TYPO3 site';
        $recordPageTitle = 'elements t3editor';
        $recordTable = '#recordlist-tx_styleguide_elements_t3editor';
        $recordIcon = 'tr:first-child a.t3js-contextmenutrigger';
        $buttonUpdate = '.btn[value=Update]';

        $pageTree->openPath([$recordPageTitle]);
        $I->switchToContentFrame();
        // List module single table mode
        $I->waitForText($recordPageTitle);
        $I->waitForElementNotVisible('#nprogress');
        $I->click($recordIcon, $recordTable);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuExport]);
        $I->waitForElementVisible($this->inTabConfiguration, 5);
        $I->see($rootPageTitle, $this->inModuleHeader);
        $I->dontSee($recordPageTitle, $this->inModuleHeader);

        $I->click($buttonUpdate, $this->inTabConfiguration);
        $this->waitForAjaxRequestToFinish($I);
        $I->see($rootPageTitle, $this->inModuleHeader);
        $I->dontSee($recordPageTitle, $this->inModuleHeader);
    }

    public function saveAndDeletePresetSucceeds(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $pageTitle = 'staticdata';
        $exportPageTitle = 'Export pagetree configuration';
        $pageIcon = '//*[text()=\'' . $pageTitle . '\']/../*[contains(@class, \'node-icon-container\')]';
        $tabExport = 'a[href="#export-filepreset"]';
        $contentExport = '#export-filepreset';
        $presetTitle = 'My First Preset';
        $inputPresetTitle = 'input[name="tx_impexp[preset][title]"]';
        $buttonSavePreset = 'button[name="preset[save]"]';
        $buttonDeletePreset = 'button[name="preset[delete]"]';
        $selectPreset = 'select[name="preset[select]"]';

        $I->click($pageIcon);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuExport]);
        $I->switchToContentFrame();
        $I->waitForText($exportPageTitle);
        $I->waitForElementNotVisible('#nprogress');

        $I->click($tabExport, $this->inModuleTabs);
        $I->waitForElementVisible($contentExport, 5);
        $I->fillField($this->inModuleTabsBody . ' ' . $inputPresetTitle, $presetTitle);
        $I->click($buttonSavePreset, $this->inModuleTabsBody);

        // don't use $modalDialog->clickButtonInDialog due to too low timeout
        $modalDialog->canSeeDialog();
        $I->click('OK', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $this->waitForAjaxRequestToFinish($I);

        $I->switchToContentFrame();
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-info');
        $I->canSee(sprintf('New preset "%s" is created', $presetTitle), $this->inFlashMessages . ' .alert.alert-info .alert-message');

        $I->click($tabExport, $this->inModuleTabs);
        $I->waitForElementVisible($contentExport, 5);
        $I->selectOption($this->inModuleTabsBody . ' ' . $selectPreset, $presetTitle);
        $I->click($buttonDeletePreset, $this->inModuleTabsBody);

        // don't use $modalDialog->clickButtonInDialog due to too low timeout
        $modalDialog->canSeeDialog();
        $I->click('OK', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $this->waitForAjaxRequestToFinish($I);

        $I->switchToContentFrame();
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-info');
        $flashMessage = $I->grabTextFrom($this->inFlashMessages . ' .alert.alert-info .alert-message');
        $I->assertMatchesRegularExpression('/Preset #[0-9]+ deleted!/', $flashMessage);
    }

    /**
     * @throws \Exception
     */
    public function exportPageAndRecordsFromPageTree(ApplicationTester $I): void
    {
        $I->wantToTest('exporting a page with records.');

        $pageTitle = 'staticdata';
        $exportPageTitle = 'Export pagetree configuration';
        $pageIcon = '//*[text()=\'' . $pageTitle . '\']/../*[contains(@class, \'node-icon-container\')]';
        $tabExport = 'a[href="#export-filepreset"]';
        $contentExport = '#export-filepreset';
        $buttonSaveToFile = 'tx_impexp[save_export]';

        $I->click($pageIcon);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuExport]);
        $I->switchToContentFrame();
        $I->waitForText($exportPageTitle);
        $I->waitForElementNotVisible('#nprogress');

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
        $I->assertMatchesRegularExpression('/Saved in ["][^"]+["], bytes/', $flashMessage);
        // TODO: find out how to clean this up, as it is impossible to determine the absolute file path from an url
//        preg_match('/[^"]+"([^"]+)"[^"]+/', $flashMessage, $flashMessageParts);
//        $saveFilePath = Environment::getProjectPath() . '/' . $flashMessageParts[1];
//        $this->testFilesToDelete[] = $saveFilePath;
    }

    /**
     * @throws \Exception
     */
    public function exportTable(ApplicationTester $I): void
    {
        $I->wantToTest('exporting a table of records.');

        $rootPage = '.node.identifier-0_0 .node-name';
        $rootPageTitle = 'New TYPO3 site';
        $sysLanguageTableTitle = 'Website Language';
        $listModuleHeader = '.module-docheader';
        $listModuleBtnExport = 'a[title="Export"]';
        $tabExport = 'a[href="#export-filepreset"]';
        $contentExport = '#export-filepreset';
        $buttonSaveToFile = 'tx_impexp[save_export]';

        $I->canSeeElement($rootPage);
        $I->click($rootPage);
        $I->switchToContentFrame();
        $I->waitForText($rootPageTitle);
        $I->waitForElementNotVisible('#nprogress');

        $I->click($sysLanguageTableTitle);
        $I->waitForElementVisible($listModuleHeader . ' ' . $listModuleBtnExport, 5);
        $I->click($listModuleBtnExport, $listModuleHeader);

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
        $I->assertMatchesRegularExpression('/Saved in ["][^"]+["], bytes/', $flashMessage);
        // TODO: find out how to clean this up, as it is impossible to determine the absolute file path from an url
//        preg_match('/[^"]+"([^"]+)"[^"]+/', $flashMessage, $flashMessageParts);
//        $saveFilePath = Environment::getProjectPath() . '/' . $flashMessageParts[1];
//        $this->testFilesToDelete[] = $saveFilePath;
    }

    /**
     * @throws \Exception
     */
    public function exportRecord(ApplicationTester $I): void
    {
        $I->wantToTest('exporting a single record.');

        $rootPage = '#identifier-0_0 .node-name';
        $rootPageTitle = 'New TYPO3 site';
        $sysLanguageTable = '#recordlist-sys_language';
        $sysLanguageIcon = 'tr:first-child a.t3js-contextmenutrigger';
        $tabExport = 'a[href="#export-filepreset"]';
        $contentExport = '#export-filepreset';
        $buttonSaveToFile = 'tx_impexp[save_export]';

        // select root page in list module
        $I->canSeeElement($rootPage);
        $I->click($rootPage);
        $I->switchToContentFrame();
        $I->waitForElementNotVisible('#nprogress');
        $I->waitForText($rootPageTitle);
        $I->click($sysLanguageIcon, $sysLanguageTable);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuExport]);

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
        $I->assertMatchesRegularExpression('/Saved in ["][^"]+["], bytes/', $flashMessage);
        // TODO: find out how to clean this up, as it is impossible to determine the absolute file path from an url
//        preg_match('/[^"]+"([^"]+)"[^"]+/', $flashMessage, $flashMessageParts);
//        $saveFilePath = Environment::getProjectPath() . '/' . $flashMessageParts[1];
//        $this->testFilesToDelete[] = $saveFilePath;
    }
}
