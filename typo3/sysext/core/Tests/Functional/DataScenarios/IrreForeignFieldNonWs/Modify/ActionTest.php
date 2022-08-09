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

use TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignFieldNonWs\AbstractActionTestCase;

class ActionTest extends AbstractActionTestCase
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
     * @test
     */
    public function copyParentContent(): void
    {
        parent::copyParentContent();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyParentContent.csv');
    }

    /**
     * @test
     */
    public function copyParentContentToDifferentPage(): void
    {
        parent::copyParentContentToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyParentContentToDifferentPage.csv');
    }

    /**
     * @test
     */
    public function copyParentContentToLanguageWithAllChildren(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::copyParentContentToLanguageWithAllChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyParentContentToLanguageWAllChildren.csv');
    }

    /**
     * @test
     */
    public function localizeParentContentWithAllChildren(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentWithAllChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentWAllChildren.csv');
    }

    /**
     * @test
     */
    public function localizeParentContentWithLanguageSynchronization(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentSynchronization.csv');
    }

    /**
     * @test
     */
    public function localizeParentContentChainLanguageSynchronizationSource(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageIdSecond);
        parent::localizeParentContentChainLanguageSynchronizationSource();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentChainLanguageSynchronizationSource.csv');
    }

    /**
     * @test
     */
    public function localizeParentContentAndCreateNestedChildrenWithLanguageSynchronization(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentAndCreateNestedChildrenWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentNCreateNestedChildrenWLanguageSynchronization.csv');
    }

    /**
     * @test
     */
    public function localizeParentContentAndSetInvalidChildReferenceWithLanguageSynchronization(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentAndSetInvalidChildReferenceWithLanguageSynchronization();
        // the assertion is the same as for localizeParentContentWithLanguageSynchronization()
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentSynchronization.csv');
    }

    /**
     * @test
     */
    public function localizeParentContentAndSetInvalidChildReferenceWithLateLanguageSynchronization(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentAndSetInvalidChildReferenceWithLateLanguageSynchronization();
        // the assertion is the same as for localizeParentContentWithLanguageSynchronization()
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeParentContentSynchronization.csv');
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
     * @test
     */
    public function moveParentContentToDifferentPage(): void
    {
        parent::moveParentContentToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveParentContentToDifferentPage.csv');
    }

    /**
     * @test
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
     * @test
     */
    public function copyPage(): void
    {
        parent::copyPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');
    }

    /**
     * @test
     */
    public function copyPageWithHotelBeforeParentContent(): void
    {
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
        parent::createParentContentWithHotelAndOfferChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createParentContentNHotelNOfferChildren.csv');
    }

    /**
     * @test
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
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::createAndLocalizeParentContentWithHotelAndOfferChildren();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createNLocalizeParentContentNHotelNOfferChildren.csv');
    }

    /**
     * @test
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildrenWithoutSortByConfiguration(): void
    {
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
        parent::modifyOnlyHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyOnlyHotelChild.csv');
    }

    /**
     * @test
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
        parent::modifyParentWithHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNHotelChild.csv');
    }

    /**
     * @test
     */
    public function modifyParentAndAddHotelChild(): void
    {
        parent::modifyParentAndAddHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNAddHotelChild.csv');
    }

    /**
     * @test
     */
    public function modifyParentAndDeleteHotelChild(): void
    {
        parent::modifyParentAndDeleteHotelChild();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyParentNDeleteHotelChild.csv');
    }

    /**
     * @test
     */
    public function localizePageWithLocalizationExclude(): void
    {
        parent::localizePageWithLocalizationExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageWExclude.csv');
    }

    /**
     * @test
     */
    public function localizePageTwiceWithLocalizationExclude(): void
    {
        parent::localizePageTwiceWithLocalizationExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageTwiceWExclude.csv');
    }

    /**
     * @test
     */
    public function localizePageAndAddHotelChildWithLocalizationExclude(): void
    {
        parent::localizePageAndAddHotelChildWithLocalizationExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNAddHotelChildWExclude.csv');
    }

    /**
     * @test
     */
    public function localizePageWithLanguageSynchronization(): void
    {
        parent::localizePageWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageWSynchronization.csv');
    }

    /**
     * @test
     */
    public function localizePageAndAddHotelChildWithLanguageSynchronization(): void
    {
        parent::localizePageAndAddHotelChildWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNAddHotelChildWSynchronization.csv');
    }

    /**
     * @test
     */
    public function localizePageAndAddMonoglotHotelChildWithLanguageSynchronization(): void
    {
        parent::localizePageAndAddMonoglotHotelChildWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageNAddMonoglotHotelChildWSynchronization.csv');
    }

    /**
     * @test
     */
    public function localizeAndCopyPageWithLanguageSynchronization(): void
    {
        parent::localizeAndCopyPageWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeNCopyPageWSynchronization.csv');
    }

    /**
     * Checks for a page having a IRRE record. The page is then localized and
     * an IRRE record is then added to the localized page
     *
     * @test
     */
    public function localizePageWithSynchronizationAndCustomLocalizedHotel(): void
    {
        parent::localizePageWithSynchronizationAndCustomLocalizedHotel();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageWithSynchronizationAndCustomLocalizedHotel.csv');
    }

    /**
     * @test
     */
    public function localizePageAddMonoglotHotelChildAndCopyPageWithLanguageSynchronization(): void
    {
        parent::localizePageAndAddMonoglotHotelChildWithLanguageSynchronization();
        parent::copyPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizePageAddMonoglotHotelChildNCopyPageWSynchronization.csv');
    }

    /**
     * @test
     */
    public function inlineLocalizeSynchronizeLocalizeMissing(): void
    {
        parent::inlineLocalizeSynchronizeLocalizeMissing();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/inlineLocalizeSynchronizeLocalizeMissing.csv');
    }
}
