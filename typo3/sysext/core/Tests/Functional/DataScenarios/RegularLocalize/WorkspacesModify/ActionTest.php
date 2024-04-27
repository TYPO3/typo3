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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\RegularLocalize\WorkspacesModify;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\RegularLocalize\AbstractActionWorkspacesTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

final class ActionTest extends AbstractActionWorkspacesTestCase
{
    #[Test]
    public function verifyCleanReferenceIndex(): void
    {
        // Fix refindex, then compare with import csv again to verify nothing changed.
        // This is to make sure the import csv is 'clean' - important for the other tests.
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(self::SCENARIO_DataSet);
    }

    #[Test]
    public function localizeContentFromNonDefaultLanguageWithAllContentElements(): void
    {
        parent::localizeContentFromNonDefaultLanguageWithAllContentElements();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentFromNonDefaultLanguageWithAllContentElements.csv');
    }

    #[Test]
    public function localizePageAndContentsAndDeletePageLocalization(): void
    {
        // Create localized page and localize content elements first
        parent::localizePageAndContentsAndDeletePageLocalization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageAndContentsAndDeletePageLocalization.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['localizedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        self::assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function localizeNestedPagesAndContents(): void
    {
        parent::localizeNestedPagesAndContents();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeNestedPagesAndContents.csv');
    }

    #[Test]
    public function localizePage(): void
    {
        parent::localizePage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePage.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('[Translate to Dansk:] Relations'));
    }

    #[Test]
    public function localizePageHiddenHideAtCopyFalse(): void
    {
        parent::localizePageHiddenHideAtCopyFalse();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyFalse.csv');
    }

    #[Test]
    public function localizePageNotHiddenHideAtCopyFalse(): void
    {
        parent::localizePageNotHiddenHideAtCopyFalse();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyFalse.csv');
    }

    #[Test]
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset.csv');
    }

    #[Test]
    public function localizePageHiddenHideAtCopyDisableHideAtCopyUnset(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopyUnset();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyDisableHideAtCopyUnset.csv');
    }

    #[Test]
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse.csv');
    }

    #[Test]
    public function localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse.csv');
    }

    #[Test]
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue.csv');
    }

    #[Test]
    public function localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue.csv');
    }

    #[Test]
    public function movePageLocalizedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedToDifferentPageTwice();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedToDifferentPageTwice.csv');
    }

    #[Test]
    public function movePageLocalizedInLiveToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveToDifferentPageTwice();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedInLiveToDifferentPageTwice.csv');
    }

    #[Test]
    public function movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice.csv');
    }

    #[Test]
    public function movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice.csv');
    }
}
