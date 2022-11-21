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
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Cases concerning sys_file_metadata records
 */
class FileOperationsCest extends AbstractFileCest
{
    public function fileCrud(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $fileTextareaSelector = 'textarea[name="data[editfile][0][data]"]';
        $fileName = 'typo3-test.txt';
        $flashMessageSelector = '.typo3-messages';

        // Create file
        $I->amGoingTo('create a file with content');
        $I->click('.module-docheader .btn[title="Create File"]');
        $I->wait(0.2);
        $I->see('Create File', 'h1');
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
        $I->see($fileName, '[data-multi-record-selection-element="true"]');

        // Delete file
        $I->amGoingTo('delete the file');
        $I->clickWithRightButton('a[data-contextmenu-uid="1:/' . $fileName . '"]');
        $I->click('[data-title="Delete"]');
        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('Yes, delete this file');
        $I->waitForElementNotVisible('a[data-contextmenu-uid="1:/' . $fileName . '"]');
        $I->switchToContentFrame();
        $I->see('File deleted', $flashMessageSelector);
        $I->dontSee($fileName, '[data-multi-record-selection-element="true"]');
    }

    /**
     * @throws \Exception
     */
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
}
