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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\RecordList;

use Codeception\Exception\MalformedLocatorException;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Cases concerning the record download functionality
 */
final class RecordDownloadWithPresetCest
{
    private string $inModuleHeader = '.module-docheader';

    public function recordsCanBeExportedWithPreset(ApplicationTester $I, PageTree $pageTree, ModalDialog $modalDialog): void
    {
        $I->useExistingSession('admin');
        $this->setUserTsConfig($I, 1, 'page.mod.web_list.downloadPresets.pages.10.label = Test-Preset\npage.mod.web_list.downloadPresets.pages.10.columns = uid,title,slug\npage.mod.web_list.downloadPresets.pages.10.identifier = download-preset');

        $I->useExistingSession('admin');

        $I->wantToTest('whether records can be downloaded in the recordlist from a preset');

        $I->amGoingTo('download a record using a preset');
        $I->click('List');
        $I->waitForElementNotVisible('#nprogress');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->wait(0.2);
        $I->switchToContentFrame();
        $I->canSee('Download');
        $I->click('typo3-recordlist-record-download-button');
        $modalDialog->canSeeDialog();
        $I->canSee('Download Page:', ModalDialog::$openedModalSelector . ' .t3js-modal-title');
        $I->canSee('Preset', ModalDialog::$openedModalSelector . ' .modal-body label');
        $I->fillField(ModalDialog::$openedModalSelector . ' input[name="filename"]', 'test-download');
        $I->selectOption(ModalDialog::$openedModalSelector . ' select[name="preset"]', 'Test-Preset');
        $I->click('button[name="download"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);

        $this->setUserTsConfig($I, 1, '');
    }

    public function recordsCanBeExportedNotHavingPreset(ApplicationTester $I, PageTree $pageTree, ModalDialog $modalDialog): void
    {
        $I->useExistingSession('admin');

        $I->wantToTest('whether records can be downloaded in the recordlist without showing a preset');

        $I->amGoingTo('download a record using a preset');
        $I->click('List');
        $I->waitForElementNotVisible('#nprogress');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->wait(0.2);
        $I->switchToContentFrame();
        $I->canSee('Download');
        $I->click('typo3-recordlist-record-download-button');
        $modalDialog->canSeeDialog();
        $I->canSee('Download Page:', ModalDialog::$openedModalSelector . ' .t3js-modal-title');
        $I->dontSee('Preset', ModalDialog::$openedModalSelector . ' .modal-body label');
        $I->fillField(ModalDialog::$openedModalSelector . ' input[name="filename"]', 'test-download');
        $I->click('button[name="download"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
    }

    // Taken from typo3/sysext/core/Tests/Acceptance/Application/Impexp/UsersCest.php
    private function setUserTsConfig(ApplicationTester $I, int $userId, string $userTsConfig): void
    {
        try {
            $I->seeElement($this->inModuleHeader . ' [name=BackendUserModuleMenu]');
        } catch (\Exception $e) {
            $I->switchToMainFrame();
            $I->click('Backend Users');
            $I->switchToContentFrame();
        }

        $codeMirrorSelector = 'typo3-t3editor-codemirror[name="data[be_users][' . $userId . '][TSconfig]"]';

        $I->waitForElementVisible($this->inModuleHeader . ' [name=BackendUserModuleMenu]');
        $I->selectOption($this->inModuleHeader . ' [name=BackendUserModuleMenu]', ['text' => 'Backend users']);
        $I->waitForElement('#typo3-backend-user-list');
        $I->click('//table[@id="typo3-backend-user-list"]/tbody/tr[descendant::button[@data-contextmenu-uid="' . $userId . '"]]//a[@title="Edit"]');
        $I->waitForElement('#EditDocumentController');
        // This was "li[5]" in UsersCest. Don't know why, for me the TSconfig is on the third tab...
        $I->click('//form[@id="EditDocumentController"]//ul/li[3]/button');
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
