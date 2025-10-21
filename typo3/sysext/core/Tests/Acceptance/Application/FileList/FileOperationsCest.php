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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\FileList;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\FileTree;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Cases concerning sys_file_metadata records
 */
final class FileOperationsCest
{
    public function _before(ApplicationTester $I, FileTree $tree): void
    {
        $I->useExistingSession('admin');
        $I->amOnPage('/typo3/module/file/list');
        $I->switchToContentFrame();
    }

    public function fileCrud(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $codeMirrorSelector = 'typo3-t3editor-codemirror[name="data[editfile][0][data]"]';
        $fileName = 'typo3-test.txt';
        $flashMessageSelector = '.typo3-messages';

        // Create file
        $I->amGoingTo('create a file with content');
        $I->click('.module-docheader .btn[title="New File"]');
        $modalDialog->canSeeDialog();
        $I->switchToWindow('typo3-backend');
        $I->switchToIFrame('modal_frame');
        $I->see('Create new textfile', 'h4');
        $I->fillField('input[name="data[newfile][0][data]"]', $fileName);
        $I->wait(0.2);
        $I->click('Create file');
        $I->see('File created:', $flashMessageSelector);
        $I->waitForElementVisible($codeMirrorSelector);
        $I->executeJS("document.querySelector('" . $codeMirrorSelector . "').setContent('Some Text')");

        // Save file
        $I->amGoingTo('save the file');
        $I->click('.module-docheader button[name="_save"]');
        $I->waitForElementVisible($codeMirrorSelector);
        $I->executeJS("console.assert(document.querySelector('" . $codeMirrorSelector . "').getContent() === 'Some Text')");
        $I->see('File saved to', $flashMessageSelector);

        // Close file
        $I->amGoingTo('close the file and return to the list view');
        $I->click('.module-docheader .btn[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->switchToIFrame('modal_frame');
        $modalDialog->canSeeDialog();
        $I->click('.t3js-modal-close');
        $I->waitForElementNotVisible('.t3js-modal');
        $I->switchToContentFrame();
        $I->see($fileName, '[data-multi-record-selection-element="true"]');

        // Delete file
        $I->amGoingTo('delete the file');
        $I->clickWithRightButton('[data-filelist-identifier="1:/' . $fileName . '"] [data-filelist-action="primary"]');
        $I->switchToMainFrame();
        $I->click('button[data-contextmenu-id="root_delete"]');
        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('Yes, delete this file');
        $I->waitForElementNotVisible('[data-filelist-identifier="1:/' . $fileName . '"]');
        $I->switchToContentFrame();
        $I->see('File deleted', $flashMessageSelector);
        $I->dontSee($fileName, '[data-multi-record-selection-element="true"]');
    }

    public function seeUploadFile(ApplicationTester $I): void
    {
        $alertContainer = '#alert-container';
        $fileName = 'blue_mountains.jpg';
        $this->uploadFile($I, $fileName);

        $I->switchToMainFrame();
        $I->waitForText($fileName, 12, $alertContainer);
        $I->click('.close', $alertContainer);
        $I->waitForText('Reload filelist', 15, $alertContainer);
        $I->click('a[title="Dismiss"]', $alertContainer);
        $I->switchToContentFrame();
        $I->see($fileName, '.upload-queue-item');
        $I->click('a[title="Reload"]');
        $I->see($fileName, '[data-multi-record-selection-element="true"]');
    }

    private function uploadFile(ApplicationTester $I, string $name): void
    {
        $I->attachFile('input.upload-file-picker', 'Acceptance/Fixtures/Images/' . $name);
        $I->waitForElementNotVisible('.upload-queue-item .upload-queue-progress');
    }
}
