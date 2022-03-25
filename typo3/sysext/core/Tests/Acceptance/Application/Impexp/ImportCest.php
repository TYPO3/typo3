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
class ImportCest extends AbstractCest
{
    /** Absolute path to files that must be removed */
    protected array $testFilesToDelete = [];

    protected string $inPageTree = '#typo3-pagetree-treeContainer .nodes';
    protected string $inModuleHeader = '.module-docheader';
    protected string $inModuleTabs = '#ImportExportController .nav-tabs';
    protected string $inModuleTabsBody = '#ImportExportController .tab-content';
    protected string $inTabImport = '#import-import';
    protected string $inFlashMessages = '.typo3-messages';

    protected string $contextMenuMore = '#contentMenu0 li.list-group-item-submenu';
    protected string $contextMenuImport = '#contentMenu1 li.list-group-item[data-callback-action=importT3d]';
    protected string $tabUpload = 'a[href="#import-upload"]';
    protected string $tabMessages = 'a[href="#import-errors"]';
    protected string $inputUploadFile = 'input[type=file]';
    protected string $checkboxOverwriteFile = 'input#checkOverwriteExistingFiles';
    protected string $buttonUploadFile = '_upload';
    protected string $buttonPreview = '.btn[value=Preview]';
    protected string $buttonImport = 'button[name="tx_impexp[import_file]"]';
    protected string $buttonNewImport = 'input[name="tx_impexp[new_import]"]';

    /**
     * @throws \Exception
     */
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

    /**
     * @throws \Exception
     */
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

    /**
     * Skipping:
     *
     * Currently the unsupported file is still uploaded successfully..
     * In the future, the module should pay strict attention to the file format and reject all but XML and T3D..
     *
     * Skip this test by declaring it private instead of using skip annotation or $I->markTestSkipped()
     * as it seems to break the preceding test.
     *
     * @throws \Exception
     */
    private function rejectUploadedFileOfUnsupportedFileFormat(ApplicationTester $I): void
    {
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Application/Impexp/Fixtures/unsupported.json';

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
        $I->canSeeElement($this->inFlashMessages . ' .alert.alert-danger');
        $I->canSeeElement($this->inModuleTabsBody . ' .callout.callout-danger');
    }

    /**
     * @throws \Exception
     */
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

    /**
     * @throws \Exception
     */
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

    /**
     * @throws \Exception
     */
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

    /**
     * @throws \Exception
     */
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
