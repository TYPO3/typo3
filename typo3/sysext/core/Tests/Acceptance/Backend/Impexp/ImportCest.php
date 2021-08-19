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
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Various import related tests
 */
class ImportCest extends AbstractCest
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
    protected $inTabImport = '#import-import';
    protected $inFlashMessages = '.typo3-messages';

    protected $tabUpload = 'a[href="#import-upload"]';
    protected $tabMessages = 'a[href="#import-errors"]';
    protected $inputUploadFile = 'input[type=file]';
    protected $checkboxOverwriteFile = 'input#checkOverwriteExistingFiles';
    protected $buttonUploadFile = '_upload';
    protected $buttonPreview = '.btn[value=Preview]';
    protected $buttonImport = 'button[name="tx_impexp[import_file]"]';
    protected $buttonNewImport = 'input[name="tx_impexp[new_import]"]';

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
    public function importDisplaysTitleOfSelectedPageInModuleHeader(BackendTester $I): void
    {
        $pageInPageTreeTitle = 'elements t3editor';
        $pageInPageTreeIcon = '//*[text()=\'' . $pageInPageTreeTitle . '\']/../*[contains(@class, \'node-icon-container\')]';

        $I->click($pageInPageTreeIcon);
        $this->selectInContextMenu($I, [$this->contextMenuMore, $this->contextMenuImport]);
        $I->switchToContentFrame();
        $I->see($pageInPageTreeTitle, $this->inModuleHeader);

        $I->click($this->buttonPreview, $this->inTabImport);
        $this->waitForAjaxRequestToFinish($I);
        $I->see($pageInPageTreeTitle, $this->inModuleHeader);
    }

    public function uploadFileConsidersOverwritingFlag(BackendTester $I): void
    {
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Backend/Impexp/Fixtures/404_page_and_records.xml';

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
     * @param BackendTester $I
     *
     * @throws \Exception
     */
    private function rejectUploadedFileOfUnsupportedFileFormat(BackendTester $I): void
    {
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Backend/Impexp/Fixtures/unsupported.json';

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
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @param PageTree $pageTree
     *
     * @throws \Exception
     */
    public function rejectImportIfPrerequisitesNotMet(BackendTester $I, ModalDialog $modalDialog, PageTree $pageTree)
    {
        $I->wantToTest('rejecting import if prerequisites not met.');

        $sysCategoryTable = '#recordlist-sys_category';
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Backend/Impexp/Fixtures/sys_category_table_with_bootstrap_package.xml';

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
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     *
     * @throws \Exception
     */
    public function importPageAndRecords(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->wantToTest('importing a page with records.');

        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Backend/Impexp/Fixtures/404_page_and_records.xml';

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
        $I->see('404', $this->inPageTree . ' .node-name');
        $I->switchToContentFrame();
        $I->seeElement($this->buttonNewImport);
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @param PageTree $pageTree
     *
     * @throws \Exception
     */
    public function importTable(BackendTester $I, ModalDialog $modalDialog, PageTree $pageTree)
    {
        $I->wantToTest('importing a table of records.');

        $sysCategoryTable = '#recordlist-sys_category';
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Backend/Impexp/Fixtures/sys_category_table.xml';

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
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @param PageTree $pageTree
     *
     * @throws \Exception
     */
    public function importRecord(BackendTester $I, ModalDialog $modalDialog, PageTree $pageTree)
    {
        $I->wantToTest('importing a single record.');

        $sysCategoryTable = '#recordlist-sys_category';
        $page1Title = 'styleguide TCA demo';
        $page1Icon = '//*[text()=\'' . $page1Title . '\']/../*[contains(@class, \'node-icon-container\')]';
        $fixtureFilePath = 'Acceptance/Backend/Impexp/Fixtures/sys_category_record.xml';

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
