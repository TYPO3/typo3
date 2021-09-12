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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\FormEngine;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for inline fal
 */
class InlineFalCest
{
    protected static string $filenameSelector = '.form-irre-header-body > span > dl.row:first-child > dd:last-child';
    protected static string $saveButtonLink = '//*/button[@name="_savedok"][1]';

    /**
     * Open styleguide inline fal page in list module
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'inline fal']);
        $I->switchToContentFrame();

        $I->waitForText('inline fal', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_inline_fal a[data-bs-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForText('Edit Form', 3, 'h1');
    }

    public function seeFalRelationInfo(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $infoButtonSelector = '.tab-content button[data-action="infowindow"]';

        $filename = $I->grabTextFrom(self::$filenameSelector);
        $I->click($infoButtonSelector);
        $modalDialog->canSeeDialog();
        $I->switchToIFrame('.modal-iframe');
        $modalTitle = $I->grabTextFrom('.card-title');
        $I->assertStringContainsString($filename, $modalTitle);
    }

    public function hideFalRelation(ApplicationTester $I): void
    {
        $hideButtonSelector = '.tab-content .t3js-toggle-visibility-button';

        $I->click($hideButtonSelector);
        $I->click(self::$saveButtonLink);
        $I->seeElement('.tab-content .t3-form-field-container-inline-hidden');
    }

    public function deleteFalRelation(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $deleteButtonSelector = '.tab-content .t3js-editform-delete-inline-record';
        $filename = $I->grabTextFrom(self::$filenameSelector);

        $I->click($deleteButtonSelector);
        $modalDialog->canSeeDialog();
        $I->click('button[name="yes"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->switchToContentFrame();
        $I->click(self::$saveButtonLink);
        $I->dontSee($filename, '.tab-content .form-section:first-child');
    }
}
