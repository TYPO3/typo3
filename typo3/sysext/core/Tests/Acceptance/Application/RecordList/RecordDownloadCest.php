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

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Cases concerning the record download functionality
 */
final class RecordDownloadCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function recordsCanBeExported(ApplicationTester $I, PageTree $pageTree, ModalDialog $modalDialog): void
    {
        $I->amGoingTo('download a record');
        $I->click('List');
        $I->waitForElementNotVisible('#nprogress');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->wait(0.2);
        $I->switchToContentFrame();
        $I->canSee('Download');
        $I->click('typo3-recordlist-record-download-button');
        $modalDialog->canSeeDialog();
        $I->canSee('Download Page:', ModalDialog::$openedModalSelector . ' .t3js-modal-title');
        $I->fillField(ModalDialog::$openedModalSelector . ' input[name="filename"]', 'test-download');
        $I->canSee('CSV options', ModalDialog::$openedModalSelector . ' .modal-body h2');
        $I->selectOption(ModalDialog::$openedModalSelector . ' select[name="format"]', 'json');
        $I->dontSee('CSV options', ModalDialog::$openedModalSelector . ' .modal-body h2');
        $I->see('JSON options', ModalDialog::$openedModalSelector . ' .modal-body h2');
        $I->selectOption(ModalDialog::$openedModalSelector . ' select[name="json[meta]"]', 'full');
        $I->click('button[name="download"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
    }
}
