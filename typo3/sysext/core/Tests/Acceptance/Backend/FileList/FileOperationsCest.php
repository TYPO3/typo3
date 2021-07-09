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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\FileList;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Cases concerning sys_file_metadata records
 */
class FileOperationsCest extends AbstractFileCest
{
    /**
     * @param BackendTester $I
     */
    public function fileCrud(BackendTester $I, ModalDialog $modalDialog)
    {
        $fileTextareaSelector = 'textarea[name="data[editfile][0][data]"]';
        $fileName = 'typo3-test.txt';
        $flashMessageSelector = '.typo3-messages';

        // Create file
        $I->amGoingTo('create a file with content');
        $I->click('.module-docheader .btn[title="New"]');
        $I->wait(0.2);
        $I->see('New file or folder', 'h1');
        $I->fillField('#newfile', $fileName);
        $I->wait(0.2);
        $I->click('Create file');
        $I->see('File created:', $flashMessageSelector);
        $I->fillField($fileTextareaSelector, 'Some Text');

        // Save file
        $I->amGoingTo('save the file');
        $I->click('.module-docheader button[name="_save"]');
        $textareaValue = $I->grabValueFrom($fileTextareaSelector);
        $I->assertEquals('Some Text', $textareaValue);
        $I->see('File saved to', $flashMessageSelector);

        // Save file
        $I->amGoingTo('close the file and return to the list view');
        $I->click('.module-docheader .btn[title="Cancel"]');
        $I->see($fileName, '.col-title');

        // Delete file
        $I->amGoingTo('delete the file');
        $I->click('a[data-identifier="1:/' . $fileName . '"]');
        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('Yes, delete this file');
        $I->waitForElementNotVisible('a[data-identifier="1:/' . $fileName . '"]');
        $I->switchToContentFrame();
        $I->see('File deleted', $flashMessageSelector);
        $I->dontSee($fileName, '.col-title');
    }

    /**
     * @param BackendTester $I
     * @throws \Exception
     */
    public function seeUploadFile(BackendTester $I)
    {
        $alertContainer = '#alert-container';
        $fileName = 'blue_mountains.jpg';
        $this->uploadFile($I, $fileName);

        $I->switchToMainFrame();
        $I->waitForText($fileName, 10, $alertContainer);
        $I->click('.close', $alertContainer);
        $I->switchToContentFrame();
        $I->see($fileName, '.upload-queue-item');
        $I->click('a[title="Reload"]');
        $I->see($fileName, '.responsive-title');
    }
}
