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
 * Various import related tests
 */
final class ImportCest extends AbstractCest
{
    private array $testFilesToDelete = [];

    private string $inPageTree = '#typo3-pagetree-treeContainer .nodes';
    private string $inModuleHeader = '.module-docheader';
    private string $inModuleTabs = '#ImportExportController .nav-tabs';
    private string $inModuleTabsBody = '#ImportExportController .tab-content';
    private string $inTabImport = '#import-import';
    private string $inFlashMessages = '.typo3-messages';
    private string $contextMenuMore = '#contentMenu0 li.context-menu-item-submenu';
    private string $contextMenuImport = '#contentMenu1 li.context-menu-item[data-callback-action=importT3d]';
    private string $tabUpload = 'a[href="#import-upload"]';
    private string $tabMessages = 'a[href="#import-errors"]';
    private string $inputUploadFile = 'input[type=file]';
    private string $checkboxOverwriteFile = 'input#checkOverwriteExistingFiles';
    private string $buttonUploadFile = '_upload';
    private string $buttonPreview = '.btn[value=Preview]';
    private string $buttonImport = 'button[name="tx_impexp[import_file]"]';
    private string $buttonNewImport = 'input[name="tx_impexp[new_import]"]';

    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement('svg .nodes .node');
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

    public function importDisplaysTitleOfSelectedPageInModuleHeader(ApplicationTester $I): void
    {
        $pageInPageTreeTitle = 'elements t3editor';
        $pageInPageTreeIcon = '//*[text()=\'' . $pageInPageTreeTitle . '\']/../*[contains(@class, \'node-icon-container\')]';

        $I->click($pageInPageTreeIcon);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->see($pageInPageTreeTitle, $this->inModuleHeader);

        $I->click($this->buttonPreview, $this->inTabImport);
        $this->timeoutForAjaxRequest($I);
        $I->see($pageInPageTreeTitle, $this->inModuleHeader);
    }

    public function uploadFileConsidersOverwritingFlag(ApplicationTester $I): void
    {
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Application/Impexp/Fixtures/404_page_and_records.xml';

        $I->click($page1Icon);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->waitForElementVisible($this->tabUpload);
        $I->click($this->tabUpload, $this->inModuleTabs);
        $I->waitForElementVisible($this->inputUploadFile, 5);
        $I->attachFile($this->inputUploadFile, $fixtureFilePath);
        $I->click($this->buttonUploadFile, $this->inModuleTabsBody);
        $I->wait(1);
        $I->click($this->tabUpload, $this->inModuleTabs);
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-success');
        $I->canSeeElement($this->inModuleTabsBody . ' .callout.callout-success');

        $I->waitForElementVisible($this->inputUploadFile, 5);
        $I->attachFile($this->inputUploadFile, $fixtureFilePath);
        $I->checkOption($this->checkboxOverwriteFile);
        $I->click($this->buttonUploadFile, $this->inModuleTabsBody);
        $I->wait(1);
        $I->click($this->tabUpload, $this->inModuleTabs);
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-success');
        $I->canSeeElement($this->inModuleTabsBody . ' .callout.callout-success');

        $I->waitForElementVisible($this->inputUploadFile, 5);
        $I->attachFile($this->inputUploadFile, $fixtureFilePath);
        $I->uncheckOption($this->checkboxOverwriteFile);
        $I->click($this->buttonUploadFile, $this->inModuleTabsBody);
        $I->wait(1);
        $I->click($this->tabUpload, $this->inModuleTabs);
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-danger');
        $I->canSeeElement($this->inModuleTabsBody . ' .callout.callout-danger');
    }

    public function rejectImportIfPrerequisitesNotMet(ApplicationTester $I, ModalDialog $modalDialog, PageTree $pageTree): void
    {
        $sysCategoryTable = '#recordlist-sys_category';
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Application/Impexp/Fixtures/sys_category_table_with_bootstrap_package.xml';

        $I->switchToContentFrame();
        $I->waitForText($page1Title);
        $sysCategoryRecordsBefore = $I->grabMultiple($sysCategoryTable . ' .t3js-entity', 'data-uid');
        $I->switchToMainFrame();

        $I->click($page1Icon);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->waitForElementVisible($this->tabUpload);
        $I->click($this->tabUpload, $this->inModuleTabs);
        $I->waitForElementVisible($this->inputUploadFile, 5);
        $I->attachFile($this->inputUploadFile, $fixtureFilePath);
        $I->click($this->buttonUploadFile, $this->inModuleTabsBody);
        $I->wait(1);
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-success');
        $I->canSee('Uploading file', $this->inFlashMessages . ' .alert.alert-success .alert-message');
        $I->seeElement($this->inFlashMessages . ' .alert.alert-danger');
        $I->see('Prerequisites for file import are not met.', $this->inFlashMessages);
        $I->canSeeElement($this->inModuleTabs . ' ' . $this->tabMessages);
        $flashMessage = $I->grabTextFrom($this->inFlashMessages . ' .alert.alert-success .alert-message');
        preg_match('/[^"]+"([^"]+)"[^"]+"([^"]+)"[^"]+/', $flashMessage, $flashMessageParts);
        $loadFilePath = Environment::getProjectPath() . '/fileadmin' . $flashMessageParts[2] . $flashMessageParts[1];
        $I->assertFileExists($loadFilePath);
        $this->testFilesToDelete[] = $loadFilePath;

        $I->click($this->buttonImport);
        $modalDialog->clickButtonInDialog('button[name="ok"]');

        $I->switchToMainFrame();
        $pageTree->openPath(['styleguide TCA demo']);
        $I->switchToContentFrame();
        $I->waitForText($page1Title);
        $sysCategoryRecords = $I->grabMultiple($sysCategoryTable . ' .t3js-entity', 'data-uid');
        $sysCategoryRecordsNew = array_diff($sysCategoryRecords, $sysCategoryRecordsBefore);
        $I->assertCount(0, $sysCategoryRecordsNew);
    }

    public function importPageAndRecords(ApplicationTester $I, ModalDialog $modalDialog, PageTree $pageTree): void
    {
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Application/Impexp/Fixtures/404_page_and_records.xml';

        $I->click($page1Icon);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->waitForElementVisible($this->tabUpload);
        $I->click($this->tabUpload, $this->inModuleTabs);
        $I->waitForElementVisible($this->inputUploadFile, 5);
        $I->attachFile($this->inputUploadFile, $fixtureFilePath);
        $I->click($this->buttonUploadFile, $this->inModuleTabsBody);
        $I->wait(1);
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-success');
        $I->canSee('Uploading file', $this->inFlashMessages . ' .alert.alert-success .alert-message');
        $I->cantSeeElement($this->inFlashMessages . ' .alert.alert-danger');
        $I->cantSeeElement($this->inModuleTabs . ' ' . $this->tabMessages);
        $flashMessage = $I->grabTextFrom($this->inFlashMessages . ' .alert.alert-success .alert-message');
        preg_match('/[^"]+"([^"]+)"[^"]+"([^"]+)"[^"]+/', $flashMessage, $flashMessageParts);
        $loadFilePath = Environment::getProjectPath() . '/fileadmin' . $flashMessageParts[2] . $flashMessageParts[1];
        $I->assertFileExists($loadFilePath);
        $this->testFilesToDelete[] = $loadFilePath;

        $I->click($this->buttonImport);
        $modalDialog->clickButtonInDialog('button[name="ok"]');

        $I->switchToMainFrame();
        $pageSelector = $pageTree->getPageXPathByPageName('404');
        $I->waitForElement($pageSelector);
        $I->switchToContentFrame();
        $I->seeElement($this->buttonNewImport);
    }

    public function importTable(ApplicationTester $I, ModalDialog $modalDialog, PageTree $pageTree): void
    {
        $sysCategoryTable = '#recordlist-sys_category';
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Application/Impexp/Fixtures/sys_category_table.xml';

        $I->switchToContentFrame();
        $I->waitForText($page1Title);
        $sysCategoryRecordsBefore = $I->grabMultiple($sysCategoryTable . ' .t3js-entity', 'data-uid');
        $I->switchToMainFrame();

        $I->click($page1Icon);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->waitForElementVisible($this->tabUpload);
        $I->click($this->tabUpload, $this->inModuleTabs);
        $I->waitForElementVisible($this->inputUploadFile, 5);
        $I->attachFile($this->inputUploadFile, $fixtureFilePath);
        $I->click($this->buttonUploadFile, $this->inModuleTabsBody);
        $I->wait(1);
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-success');
        $I->canSee('Uploading file', $this->inFlashMessages . ' .alert.alert-success .alert-message');
        $I->cantSeeElement($this->inFlashMessages . ' .alert.alert-danger');
        $I->cantSeeElement($this->inModuleTabs . ' ' . $this->tabMessages);
        $flashMessage = $I->grabTextFrom($this->inFlashMessages . ' .alert.alert-success .alert-message');
        preg_match('/[^"]+"([^"]+)"[^"]+"([^"]+)"[^"]+/', $flashMessage, $flashMessageParts);
        $loadFilePath = Environment::getProjectPath() . '/fileadmin' . $flashMessageParts[2] . $flashMessageParts[1];
        $I->assertFileExists($loadFilePath);
        $this->testFilesToDelete[] = $loadFilePath;

        $I->click($this->buttonImport);
        $modalDialog->clickButtonInDialog('button[name="ok"]');

        $I->switchToMainFrame();
        $pageTree->openPath(['styleguide TCA demo']);
        $I->switchToContentFrame();
        $I->waitForElementVisible($sysCategoryTable . ' .t3js-entity');
        $sysCategoryRecords = $I->grabMultiple($sysCategoryTable . ' .t3js-entity', 'data-uid');
        $sysCategoryRecordsNew = array_diff($sysCategoryRecords, $sysCategoryRecordsBefore);
        $I->assertCount(5, $sysCategoryRecordsNew);
    }

    public function importRecord(ApplicationTester $I, ModalDialog $modalDialog, PageTree $pageTree): void
    {
        $sysCategoryTable = '#recordlist-sys_category';
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Application/Impexp/Fixtures/sys_category_record.xml';

        $I->switchToContentFrame();
        $I->waitForText($page1Title);
        $sysCategoryRecordsBefore = $I->grabMultiple($sysCategoryTable . ' .t3js-entity', 'data-uid');
        $I->switchToMainFrame();

        $I->click($page1Icon);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->waitForElementVisible($this->tabUpload);
        $I->click($this->tabUpload, $this->inModuleTabs);
        $I->waitForElementVisible($this->inputUploadFile, 5);
        $I->attachFile($this->inputUploadFile, $fixtureFilePath);
        $I->click($this->buttonUploadFile, $this->inModuleTabsBody);
        $I->wait(1);
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-success');
        $I->canSee('Uploading file', $this->inFlashMessages . ' .alert.alert-success .alert-message');
        $I->cantSeeElement($this->inFlashMessages . ' .alert.alert-danger');
        $I->cantSeeElement($this->inModuleTabs . ' ' . $this->tabMessages);
        $flashMessage = $I->grabTextFrom($this->inFlashMessages . ' .alert.alert-success .alert-message');
        preg_match('/[^"]+"([^"]+)"[^"]+"([^"]+)"[^"]+/', $flashMessage, $flashMessageParts);
        $loadFilePath = Environment::getProjectPath() . '/fileadmin' . $flashMessageParts[2] . $flashMessageParts[1];
        $I->assertFileExists($loadFilePath);
        $this->testFilesToDelete[] = $loadFilePath;

        $I->click($this->buttonImport);
        $modalDialog->clickButtonInDialog('button[name="ok"]');

        $I->switchToMainFrame();
        $pageTree->openPath(['styleguide TCA demo']);
        $I->switchToContentFrame();
        $I->waitForElementVisible($sysCategoryTable . ' .t3js-entity');
        $sysCategoryRecords = $I->grabMultiple($sysCategoryTable . ' .t3js-entity', 'data-uid');
        $sysCategoryRecordsNew = array_diff($sysCategoryRecords, $sysCategoryRecordsBefore);
        $I->assertCount(1, $sysCategoryRecordsNew);
    }
}
