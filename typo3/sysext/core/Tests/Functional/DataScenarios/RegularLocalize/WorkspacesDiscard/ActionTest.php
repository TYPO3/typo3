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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\RegularLocalize\WorkspacesDiscard;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\RegularLocalize\AbstractActionWorkspacesTestCase;

final class ActionTest extends AbstractActionWorkspacesTestCase
{
    #[Test]
    public function verifyCleanReferenceIndex(): void
    {
        // The test verifies the imported data set has a clean reference index by the check in tearDown()
        self::assertTrue(true);
    }

    #[Test]
    public function localizePageAndContentsAndDeletePageLocalization(): void
    {
        // Create localized page and localize content elements first
        parent::localizePageAndContentsAndDeletePageLocalization();
        // Deleted records are not discarded
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageAndContentsAndDeletePageLocalization.csv');
    }

    #[Test]
    public function localizeNestedPagesAndContents(): void
    {
        parent::localizeNestedPagesAndContents();
        // Should discard the localized parent page and its content elements, but no sub page change or default lang content element
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedParentPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeNestedPagesAndContents.csv');
    }

    #[Test]
    public function localizePage(): void
    {
        parent::localizePage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePage.csv');
    }

    #[Test]
    public function localizePageHiddenHideAtCopyFalse(): void
    {
        parent::localizePageHiddenHideAtCopyFalse();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyFalse.csv');
    }

    #[Test]
    public function localizePageNotHiddenHideAtCopyFalse(): void
    {
        parent::localizePageNotHiddenHideAtCopyFalse();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyFalse.csv');
    }

    #[Test]
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset.csv');
    }

    #[Test]
    public function localizePageHiddenHideAtCopyDisableHideAtCopyUnset(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopyUnset();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyDisableHideAtCopyUnset.csv');
    }

    #[Test]
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse.csv');
    }

    #[Test]
    public function localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse.csv');
    }

    #[Test]
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue.csv');
    }

    #[Test]
    public function localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, $this->recordIds['localizedPageId']);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue.csv');
    }

    #[Test]
    public function movePageLocalizedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedToDifferentPageTwice.csv');
    }

    #[Test]
    public function movePageLocalizedInLiveToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedInLiveToDifferentPageTwice.csv');
    }

    #[Test]
    public function movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice.csv');
    }

    #[Test]
    public function movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice.csv');
    }
}
