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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\RecordList;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Cases concerning the record export functionality
 */
class RecordExportCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * @param BackendTester $I
     * @param PageTree $pageTree
     * @param ModalDialog $modalDialog
     */
    public function recordsCanBeExported(BackendTester $I, PageTree $pageTree, ModalDialog $modalDialog): void
    {
        $I->wantToTest('whether records can be exported in the recordlist');

        $I->amGoingTo('export a record');
        $I->click('List');
        $I->waitForElementNotVisible('#nprogress');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->wait(0.2);
        $I->switchToContentFrame();
        $I->canSee('Export');
        $I->click('typo3-recordlist-record-export-button button');
        $modalDialog->canSeeDialog();
        $I->canSee('Export Page:', ModalDialog::$openedModalSelector . ' .modal-title');
        $I->fillField(ModalDialog::$openedModalSelector . ' input[name="filename"]', 'test-export');
        $I->canSee('CSV options', ModalDialog::$openedModalSelector . ' .modal-body h5');
        $I->selectOption(ModalDialog::$openedModalSelector . ' select[name="format"]', 'json');
        $I->dontSee('CSV options', ModalDialog::$openedModalSelector . ' .modal-body h5');
        $I->see('JSON options', ModalDialog::$openedModalSelector . ' .modal-body h5');
        $I->selectOption(ModalDialog::$openedModalSelector . ' select[name="json[meta]"]', 'full');
        $I->click('button[name="export"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
    }
}
