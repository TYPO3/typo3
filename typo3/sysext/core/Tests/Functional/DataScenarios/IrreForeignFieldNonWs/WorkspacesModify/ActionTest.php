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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignFieldNonWs\WorkspacesModify;

use TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignFieldNonWs\AbstractActionWorkspacesTestCase;

/**
 * @todo: All these workspace tests on not-ws aware hotel, child and price tables are funny.
 *        Even though 'live_edit' is true, editing for instance a ws-aware pages or tt_content table
 *        and deleting / adding a hotel child raises DH warnings "Record could not be created in this workspace"
 *        or "Versioning is not supported for this table". This is technically fine.
 *        However, having an inline relation from a ws-aware table to a non-ws aware table (even with live_edit=1) and
 *        then editing relations in a workspace is logically inconsistent.
 *        We may want to scan for these scenarios in TCA and notify / deprecate it by hinting integrators on this
 *        problematic situation when ext:workspaces is loaded?!
 *        Avoiding this would relax quite a few scenarios here.
 *        Note sys_file is a special scenario here as well (not covered by the tests below, though).
 *        In the end, all these cases below need a review and thoughts.
 *        For now, we're skipping "WorkspaceDiscard", "WorkspacePublish" and "WorkspacePublishAll" tests, since
 *        "WorkspaceModify" already show enough inconsistencies.
 */
class ActionTest extends AbstractActionWorkspacesTestCase
{
    /**
     * @test
     */
    public function verifyCleanReferenceIndex(): void
    {
        // The test verifies the imported data set has a clean reference index by the check in tearDown()
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function createParentContent(): void
    {
        parent::createParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createParentContent.csv');
    }

    /**
     * @test
     */
    public function modifyParentContent(): void
    {
        parent::modifyParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentContent.csv');
    }

    /**
     * @test
     */
    public function deleteParentContent(): void
    {
        parent::deleteParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteParentContent.csv');
    }

    /**
     * @todo: Leads to issues with sys_refindex which does not adapt sorting field for live changes.
     */
    public function copyParentContent(): void
    {
        parent::copyParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyParentContent.csv');
    }

    /**
     * @todo: Leads to issues with sys_refindex which does not adapt sorting field for live changes.
     */
    public function copyParentContentToDifferentPage(): void
    {
        parent::copyParentContentToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyParentContentToDifferentPage.csv');
    }

    /**
     * @test
     */
    public function localizeParentContentWithAllChildren(): void
    {
        $this->expectedErrorLogEntries = 3;
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentWithAllChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentWAllChildren.csv');
    }

    /**
     * @test
     */
    public function changeParentContentSorting(): void
    {
        parent::changeParentContentSorting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeParentContentSorting.csv');
    }

    /**
     * @todo: "Trying to access array offset on value of type bool" - workspaces/Classes/Hook/DataHandlerHook.php:366
     */
    public function moveParentContentToDifferentPage(): void
    {
        parent::moveParentContentToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveParentContentToDifferentPage.csv');
    }

    /**
     * @todo: "Trying to access array offset on value of type bool" - workspaces/Classes/Hook/DataHandlerHook.php:366
     */
    public function moveParentContentToDifferentPageTwice(): void
    {
        parent::moveParentContentToDifferentPageTwice();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveParentContentToDifferentPageTwice.csv');
    }

    /**
     * @todo: "Trying to access array offset on value of type bool" - workspaces/Classes/Hook/DataHandlerHook.php:366
     */
    public function moveParentContentToDifferentPageAndChangeSorting(): void
    {
        parent::moveParentContentToDifferentPageAndChangeSorting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveParentContentToDifferentPageNChangeSorting.csv');
    }

    /**
     * Page records
     */

    /**
     * @test
     */
    public function modifyPage(): void
    {
        parent::modifyPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyPage.csv');
    }

    /**
     * @test
     */
    public function deletePage(): void
    {
        parent::deletePage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deletePage.csv');
    }

    /**
     * @todo: Leads to issues with sys_refindex which does not adapt sorting field for live changes.
     */
    public function copyPage(): void
    {
        $this->expectedErrorLogEntries = 15;
        parent::copyPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');
    }

    /**
     * @todo: Leads to issues with sys_refindex which does not adapt sorting field for live changes.
     */
    public function copyPageWithHotelBeforeParentContent(): void
    {
        $this->expectedErrorLogEntries = 15;
        parent::copyPageWithHotelBeforeParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPageWHotelBeforeParentContent.csv');
    }

    /**
     * IRRE Child Records
     */

    /**
     * @test
     */
    public function createParentContentWithHotelAndOfferChildren(): void
    {
        $this->expectedErrorLogEntries = 2;
        parent::createParentContentWithHotelAndOfferChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createParentContentNHotelNOfferChildren.csv');
    }

    /**
     * @todo: Weird - this may / should localize the live record? What happens here?
     */
    public function createAndCopyParentContentWithHotelAndOfferChildren(): void
    {
        parent::createAndCopyParentContentWithHotelAndOfferChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createNCopyParentContentNHotelNOfferChildren.csv');
    }

    /**
     * @test
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildren(): void
    {
        $this->expectedErrorLogEntries = 2;
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::createAndLocalizeParentContentWithHotelAndOfferChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createNLocalizeParentContentNHotelNOfferChildren.csv');
    }

    /**
     * @test
     * Test not implemented for Publish, PublishAll and Discard
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildrenWithoutSortByConfiguration(): void
    {
        $this->expectedErrorLogEntries = 2;
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::createAndLocalizeParentContentWithHotelAndOfferChildrenWithoutSortByConfiguration();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createNLocalizeParentContentNHotelNOfferChildrenWOSortBy.csv');
    }

    /**
     * @test
     */
    public function modifyOnlyHotelChild(): void
    {
        $this->expectedErrorLogEntries = 1;
        parent::modifyOnlyHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyOnlyHotelChild.csv');
    }

    /**
     * @todo: Leads to issues with sys_refindex which does not adapt sorting field for live changes.
     */
    public function modifyParentAndChangeHotelChildrenSorting(): void
    {
        parent::modifyParentAndChangeHotelChildrenSorting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNChangeHotelChildrenSorting.csv');
    }

    /**
     * @test
     */
    public function modifyParentWithHotelChild(): void
    {
        $this->expectedErrorLogEntries = 1;
        parent::modifyParentWithHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNHotelChild.csv');
    }

    /**
     * @test
     */
    public function modifyParentAndAddHotelChild(): void
    {
        $this->expectedErrorLogEntries = 1;
        parent::modifyParentAndAddHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNAddHotelChild.csv');
    }

    /**
     * @test
     */
    public function modifyParentAndDeleteHotelChild(): void
    {
        $this->expectedErrorLogEntries = 1;
        parent::modifyParentAndDeleteHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNDeleteHotelChild.csv');
    }

    /**
     * @todo: This reveals another core bug "Undefined array key "t3ver_state"" in ElementEntityProcessor.php
     */
    public function modifyAndDiscardAndModifyParentWithHotelChild(): void
    {
        parent::modifyAndDiscardAndModifyParentWithHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyNDiscardNModifyParentWHotelChild.csv');
    }

    /**
     * @todo: Weird - this may / should localize the live record? What happens here?
     */
    public function inlineLocalizeSynchronizeLocalizeMissing(): void
    {
        parent::inlineLocalizeSynchronizeLocalizeMissing();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/inlineLocalizeSynchronizeLocalizeMissing.csv');
    }
}
