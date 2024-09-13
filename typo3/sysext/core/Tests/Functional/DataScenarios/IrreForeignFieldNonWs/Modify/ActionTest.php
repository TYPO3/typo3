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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignFieldNonWs\Modify;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignFieldNonWs\AbstractActionTestCase;

final class ActionTest extends AbstractActionTestCase
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
    public function createParentContent(): void
    {
        parent::createParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createParentContent.csv');
    }

    #[Test]
    public function modifyParentContent(): void
    {
        parent::modifyParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentContent.csv');
    }

    #[Test]
    public function deleteParentContent(): void
    {
        parent::deleteParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteParentContent.csv');
    }

    #[Test]
    public function copyParentContent(): void
    {
        parent::copyParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyParentContent.csv');
    }

    #[Test]
    public function copyParentContentToDifferentPage(): void
    {
        parent::copyParentContentToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyParentContentToDifferentPage.csv');
    }

    #[Test]
    public function copyParentContentToDifferentLanguageWAllChildren(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        // Use copy
        parent::copyParentContentToDifferentLanguageWAllChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyParentContentToDiffLanguageWAllChildren.csv');
    }

    #[Test]
    public function copyParentContentToLanguageWithAllChildren(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        // Use copyToLanguage
        parent::copyParentContentToLanguageWithAllChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyParentContentToLanguageWAllChildren.csv');
    }

    #[Test]
    public function copyParentContentToLanguageWithAllChildrenWithLocalizationExclude(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::copyParentContentToLanguageWithAllChildrenWithLocalizationExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyParentContentToLanguageWAllChildrenWExclude.csv');
    }

    #[Test]
    public function localizeParentContentWithAllChildren(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentWithAllChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentWAllChildren.csv');
    }

    #[Test]
    public function localizeParentContentWithAllChildrenWithLocalizationExclude(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentWithAllChildrenWithLocalizationExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentWAllChildrenWExclude.csv');
    }

    #[Test]
    public function localizeParentContentWithLanguageSynchronization(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentSynchronization.csv');
    }

    #[Test]
    public function localizeParentContentChainLanguageSynchronizationSource(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageIdSecond);
        parent::localizeParentContentChainLanguageSynchronizationSource();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentChainLanguageSynchronizationSource.csv');
    }

    #[Test]
    public function localizeParentContentAndCreateNestedChildrenWithLanguageSynchronization(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentAndCreateNestedChildrenWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentNCreateNestedChildrenWLanguageSynchronization.csv');
    }

    #[Test]
    public function localizeParentContentAndSetInvalidChildReferenceWithLanguageSynchronization(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentAndSetInvalidChildReferenceWithLanguageSynchronization();
        // the assertion is the same as for localizeParentContentWithLanguageSynchronization()
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentSynchronization.csv');
    }

    #[Test]
    public function localizeParentContentAndSetInvalidChildReferenceWithLateLanguageSynchronization(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentAndSetInvalidChildReferenceWithLateLanguageSynchronization();
        // the assertion is the same as for localizeParentContentWithLanguageSynchronization()
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentSynchronization.csv');
    }

    #[Test]
    public function changeParentContentSorting(): void
    {
        parent::changeParentContentSorting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeParentContentSorting.csv');
    }

    #[Test]
    public function moveParentContentToDifferentPage(): void
    {
        parent::moveParentContentToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveParentContentToDifferentPage.csv');
    }

    #[Test]
    public function moveParentContentToDifferentPageAndChangeSorting(): void
    {
        parent::moveParentContentToDifferentPageAndChangeSorting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveParentContentToDifferentPageNChangeSorting.csv');
    }

    #[Test]
    public function modifyPage(): void
    {
        parent::modifyPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyPage.csv');
    }

    #[Test]
    public function deletePage(): void
    {
        parent::deletePage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deletePage.csv');
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');
    }

    #[Test]
    public function copyPageWithHotelBeforeParentContent(): void
    {
        parent::copyPageWithHotelBeforeParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPageWHotelBeforeParentContent.csv');
    }

    #[Test]
    public function createParentContentWithHotelAndOfferChildren(): void
    {
        parent::createParentContentWithHotelAndOfferChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createParentContentNHotelNOfferChildren.csv');
    }

    #[Test]
    public function createAndCopyParentContentWithHotelAndOfferChildren(): void
    {
        parent::createAndCopyParentContentWithHotelAndOfferChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createNCopyParentContentNHotelNOfferChildren.csv');
    }

    #[Test]
    public function createAndLocalizeParentContentWithHotelAndOfferChildren(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::createAndLocalizeParentContentWithHotelAndOfferChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createNLocalizeParentContentNHotelNOfferChildren.csv');
    }

    #[Test]
    public function createAndLocalizeParentContentWithHotelAndOfferChildrenWithoutSortByConfiguration(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::createAndLocalizeParentContentWithHotelAndOfferChildrenWithoutSortByConfiguration();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createNLocalizeParentContentNHotelNOfferChildrenWOSortBy.csv');
    }

    #[Test]
    public function modifyOnlyHotelChild(): void
    {
        parent::modifyOnlyHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyOnlyHotelChild.csv');
    }

    #[Test]
    public function modifyParentAndChangeHotelChildrenSorting(): void
    {
        parent::modifyParentAndChangeHotelChildrenSorting();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNChangeHotelChildrenSorting.csv');
    }

    #[Test]
    public function modifyParentWithHotelChild(): void
    {
        parent::modifyParentWithHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNHotelChild.csv');
    }

    #[Test]
    public function modifyParentAndAddHotelChild(): void
    {
        parent::modifyParentAndAddHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNAddHotelChild.csv');
    }

    #[Test]
    public function modifyParentAndDeleteHotelChild(): void
    {
        parent::modifyParentAndDeleteHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNDeleteHotelChild.csv');
    }

    #[Test]
    public function localizePageWithLocalizationExclude(): void
    {
        parent::localizePageWithLocalizationExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageWExclude.csv');
    }

    #[Test]
    public function localizePageTwiceWithLocalizationExclude(): void
    {
        parent::localizePageTwiceWithLocalizationExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageTwiceWExclude.csv');
    }

    #[Test]
    public function localizePageAndAddHotelChildWithLocalizationExclude(): void
    {
        parent::localizePageAndAddHotelChildWithLocalizationExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNAddHotelChildWExclude.csv');
    }

    #[Test]
    public function localizePageWithLanguageSynchronization(): void
    {
        parent::localizePageWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageWSynchronization.csv');
    }

    #[Test]
    public function localizePageAndAddHotelChildWithLanguageSynchronization(): void
    {
        parent::localizePageAndAddHotelChildWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNAddHotelChildWSynchronization.csv');
    }

    #[Test]
    public function localizePageAndAddMonoglotHotelChildWithLanguageSynchronization(): void
    {
        parent::localizePageAndAddMonoglotHotelChildWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNAddMonoglotHotelChildWSynchronization.csv');
    }

    #[Test]
    public function localizeAndCopyPageWithLanguageSynchronization(): void
    {
        parent::localizeAndCopyPageWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeNCopyPageWSynchronization.csv');
    }

    /**
     * Checks for a page having an IRRE record. The page is then localized and
     * an IRRE record is then added to the localized page
     */
    #[Test]
    public function localizePageWithSynchronizationAndCustomLocalizedHotel(): void
    {
        parent::localizePageWithSynchronizationAndCustomLocalizedHotel();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageWithSynchronizationAndCustomLocalizedHotel.csv');
    }

    #[Test]
    public function localizePageAddMonoglotHotelChildAndCopyPageWithLanguageSynchronization(): void
    {
        parent::localizePageAndAddMonoglotHotelChildWithLanguageSynchronization();
        parent::copyPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageAddMonoglotHotelChildNCopyPageWSynchronization.csv');
    }

    #[Test]
    public function inlineLocalizeSynchronizeLocalizeMissing(): void
    {
        parent::inlineLocalizeSynchronizeLocalizeMissing();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/inlineLocalizeSynchronizeLocalizeMissing.csv');
    }
}
