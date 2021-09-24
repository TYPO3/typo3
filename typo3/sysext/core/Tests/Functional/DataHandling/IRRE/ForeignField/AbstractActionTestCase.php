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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE\ForeignField;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    const VALUE_PageId = 89;
    const VALUE_PageIdTarget = 90;
    const VALUE_PageIdWebsite = 1;
    const VALUE_ContentIdFirst = 297;
    const VALUE_ContentIdLast = 298;
    const VALUE_HotelIdFirst = 3;
    const VALUE_HotelIdSecond = 4;
    const VALUE_HotelIdThird = 5;
    const VALUE_OfferIdLast = 8;
    const VALUE_LanguageId = 1;
    const VALUE_LanguageIdSecond = 2;

    const TABLE_Page = 'pages';
    const TABLE_Content = 'tt_content';
    const TABLE_Hotel = 'tx_irretutorial_1nff_hotel';
    const TABLE_Offer = 'tx_irretutorial_1nff_offer';
    const TABLE_Price = 'tx_irretutorial_1nff_price';

    const FIELD_PageHotel = 'tx_irretutorial_hotels';
    const FIELD_ContentHotel = 'tx_irretutorial_1nff_hotels';
    const FIELD_HotelOffer = 'offers';
    const FIELD_OfferPrice = 'prices';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/IRRE/ForeignField/DataSet/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importScenarioDataSet('ImportDefault');

        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
        $this->setUpFrontendRootPage(
            1,
            [
                'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
                'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/ExtbaseJsonRenderer.typoscript',
            ]
        );
    }

    /**
     * Parent content records
     */

    /**
     * Create a new parent content element
     */
    public function createParentContent(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    public function modifyParentContent(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, ['header' => 'Testing #1']);
    }

    public function deleteParentContent(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
    }

    public function copyParentContent(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function copyParentContentToDifferentPage(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See DataSet/localizeParentContentSynchronization.csv
     */
    public function localizeParentContentWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA'][self::TABLE_Offer]['columns'][self::FIELD_OfferPrice]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See DataSet/localizeParentContentWAllChildrenSelect.csv
     */
    public function localizeParentContentChainLanguageSynchronizationSource(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['localizedContentIdFirst'], self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedContentIdSecond'] = $newTableIds[self::TABLE_Content][$this->recordIds['localizedContentIdFirst']];
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            $this->recordIds['localizedContentIdSecond'],
            ['l10n_state' => [self::FIELD_ContentHotel => 'source']]
        );
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdLast, self::FIELD_ContentHotel => '5,__nextUid'],
                self::TABLE_Hotel => ['uid' => '__NEW', 'title' => 'Hotel #2'],
            ]
        );
    }

    /**
     * See DataSet/copyParentContentToLanguageWAllChildren.csv
     */
    public function copyParentContentToLanguageWithAllChildren(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeParentContentWithAllChildren(): void
    {
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See DataSet/Modify/localizeParentContentNCreateNestedChildrenWLanguageSynchronization.csv
     */
    public function localizeParentContentAndCreateNestedChildrenWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA'][self::TABLE_Offer]['columns'][self::FIELD_OfferPrice]['config']['behaviour']['allowLanguageSynchronization'] = true;

        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];

        $newHotelId = StringUtility::getUniqueId('NEW');
        $newOfferId = StringUtility::getUniqueId('NEW');
        $newPriceId = StringUtility::getUniqueId('NEW');
        $dataMap = [
            self::TABLE_Content => [
                self::VALUE_ContentIdLast => [self::FIELD_ContentHotel => '5,' . $newHotelId],
            ],
            self::TABLE_Hotel => [
                $newHotelId => ['pid' => self::VALUE_PageId, 'title' => 'New Hotel #1', 'offers' => $newOfferId],
            ],
            self::TABLE_Offer => [
                $newOfferId => ['pid' => self::VALUE_PageId, 'title' => 'New Offer #1.1', 'prices' => $newPriceId],
            ],
            self::TABLE_Price => [
                $newPriceId => ['pid' => self::VALUE_PageId, 'title' => 'New Price #1.1.1'],
            ],
        ];
        $this->actionService->invoke($dataMap, []);
        $this->recordIds['newHoteId'] = $this->actionService->getDataHandler()->substNEWwithIDs[$newHotelId];
        $this->recordIds['newOfferId'] = $this->actionService->getDataHandler()->substNEWwithIDs[$newOfferId];
        $this->recordIds['newPriceId'] = $this->actionService->getDataHandler()->substNEWwithIDs[$newPriceId];
    }

    /**
     * See DataSet/localizeParentContentSynchronization.csv
     */
    public function localizeParentContentAndSetInvalidChildReferenceWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA'][self::TABLE_Offer]['columns'][self::FIELD_OfferPrice]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        // modify IRRE relation value of localized record (which should be sanitized and filtered)
        $this->actionService->modifyRecord(self::TABLE_Content, $this->recordIds['localizedContentId'], [self::FIELD_ContentHotel => '0']);
    }

    /**
     * See DataSet/localizeParentContentSynchronization.csv
     */
    public function localizeParentContentAndSetInvalidChildReferenceWithLateLanguageSynchronization(): void
    {
        // disable language synchronization
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['allowLanguageSynchronization'] = false;
        $GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['allowLanguageSynchronization'] = false;
        $GLOBALS['TCA'][self::TABLE_Offer]['columns'][self::FIELD_OfferPrice]['config']['behaviour']['allowLanguageSynchronization'] = false;
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $this->recordIds['localizedHotelId'] = $newTableIds[self::TABLE_Hotel][self::VALUE_HotelIdThird];
        $this->recordIds['localizedOfferId'] = $newTableIds[self::TABLE_Offer][self::VALUE_OfferIdLast];
        // now enable language synchronization
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA'][self::TABLE_Offer]['columns'][self::FIELD_OfferPrice]['config']['behaviour']['allowLanguageSynchronization'] = true;
        // modify IRRE relation values of localized records (which should be sanitized and filtered)
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => $this->recordIds['localizedContentId'], self::FIELD_ContentHotel => '0'],
                self::TABLE_Hotel => ['uid' => $this->recordIds['localizedHotelId'], self::FIELD_ContentHotel => '0'],
                self::TABLE_Offer => ['uid' => $this->recordIds['localizedOfferId'], self::FIELD_ContentHotel => '0'],
            ]
        );
    }

    public function changeParentContentSorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
    }

    public function moveParentContentToDifferentPage(): void
    {
        $newRecordIds = $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
        // In workspaces new records are created and discard drops this one again, live creates no new record
        if (isset($newRecordIds[self::TABLE_Content][self::VALUE_ContentIdLast])) {
            $this->recordIds['newContentId'] = $newRecordIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        }
    }

    public function moveParentContentToDifferentPageTwice(): void
    {
        $newRecordIds = $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
        $this->recordIds['newContentId'] = $newRecordIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdWebsite);
    }

    public function moveParentContentToDifferentPageAndChangeSorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
    }

    /**
     * Page records
     */

    /**
     * Modify a page record
     */
    public function modifyPage(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, ['title' => 'Testing #1']);
    }

    public function deletePage(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
    }

    public function copyPage(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function copyPageWithHotelBeforeParentContent(): void
    {
        // Ensure hotels get processed first
        $GLOBALS['TCA'] = array_merge(
            [self::TABLE_Hotel => $GLOBALS['TCA'][self::TABLE_Hotel]],
            $GLOBALS['TCA']
        );

        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->recordIds['newContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $this->recordIds['newContentIdLast'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * IRRE Child Records
     */

    /**
     * Create a parent content and add a hotel and offer
     */
    public function createParentContentWithHotelAndOfferChildren(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'],
                self::TABLE_Hotel => ['title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'],
                self::TABLE_Offer => ['title' => 'Offer #1'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    public function createAndCopyParentContentWithHotelAndOfferChildren(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'],
                self::TABLE_Hotel => ['title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'],
                self::TABLE_Offer => ['title' => 'Offer #1'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $this->recordIds['newHotelId'] = $newTableIds[self::TABLE_Hotel][0];
        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
        $this->recordIds['copiedHotelId'] = $copiedTableIds[self::TABLE_Hotel][$this->recordIds['newHotelId']];
    }

    public function createAndLocalizeParentContentWithHotelAndOfferChildren(): void
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'],
                self::TABLE_Hotel => ['title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'],
                self::TABLE_Offer => ['title' => 'Offer #1'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $this->recordIds['newHotelId'] = $newTableIds[self::TABLE_Hotel][0];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
    }

    public function createAndLocalizeParentContentWithHotelAndOfferChildrenWithoutSortByConfiguration(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_Hotel]['ctrl']['sortby']);
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'],
                self::TABLE_Hotel => ['title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'],
                self::TABLE_Offer => ['title' => 'Offer #1'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $this->recordIds['newHotelId'] = $newTableIds[self::TABLE_Hotel][0];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
    }

    public function modifyOnlyHotelChild(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Hotel, 4, ['title' => 'Testing #1']);
    }

    public function modifyParentAndChangeHotelChildrenSorting(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, [self::FIELD_ContentHotel => '4,3']);
    }

    public function modifyParentWithHotelChild(): void
    {
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdFirst, self::FIELD_ContentHotel => '3,4'],
                self::TABLE_Hotel => ['uid' => 4, 'title' => 'Testing #1'],
            ]
        );
    }

    public function modifyParentAndAddHotelChild(): void
    {
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdLast, self::FIELD_ContentHotel => '5,__nextUid'],
                self::TABLE_Hotel => ['uid' => '__NEW', 'title' => 'Hotel #2'],
            ]
        );
    }

    public function modifyParentAndDeleteHotelChild(): void
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            [self::FIELD_ContentHotel => '3'],
            [self::TABLE_Hotel => [4]]
        );
    }

    public function localizePageWithLocalizationExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['l10n_mode'] = 'exclude';
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageTwiceWithLocalizationExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['l10n_mode'] = 'exclude';
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageIdFirst'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedPageIdSecond'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageAndAddHotelChildWithLocalizationExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['l10n_mode'] = 'exclude';
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Page => ['uid' => self::VALUE_PageId, self::FIELD_PageHotel => '2,__nextUid'],
                self::TABLE_Hotel => ['uid' => '__NEW', 'title' => 'Hotel #007'],
            ]
        );
    }

    public function localizePageWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageAndAddHotelChildWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Page => ['uid' => self::VALUE_PageId, self::FIELD_PageHotel => '2,__nextUid'],
                self::TABLE_Hotel => ['uid' => '__NEW', 'title' => 'Hotel #007'],
            ]
        );
    }

    public function localizePageAndAddMonoglotHotelChildWithLanguageSynchronization(): void
    {
        unset($GLOBALS['TCA'][self::TABLE_Hotel]['ctrl']['languageField']);
        unset($GLOBALS['TCA'][self::TABLE_Hotel]['ctrl']['transOrigPointerField']);
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Page => ['uid' => self::VALUE_PageId, self::FIELD_PageHotel => '2,__nextUid'],
                self::TABLE_Hotel => ['uid' => '__NEW', 'title' => 'Hotel #007'],
            ]
        );
    }

    public function localizeAndCopyPageWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageWithSynchronizationAndCustomLocalizedHotel(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        // Using "localized page ID" on purpose because BE editing uses a "page" record and data handler
        $this->actionService->modifyRecords(
            $this->recordIds['localizedPageId'],
            [
                self::TABLE_Page => ['uid' => $this->recordIds['localizedPageId'], self::FIELD_PageHotel => '6,__nextUid', 'l10n_state' => [self::FIELD_PageHotel => 'custom']],
                self::TABLE_Hotel => ['uid' => '__NEW', 'sys_language_uid' => self::VALUE_LanguageId, 'title' => 'Hotel in dansk page only'],
            ]
        );
    }

    public function inlineLocalizeSynchronizeLocalizeMissing(): void
    {
        // Translate page 89 first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        // Localize CE 297 which has two hotels, those are localized, too.
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds['tt_content'][self::VALUE_ContentIdFirst];
        $this->recordIds['localizedHotelId'] = $newTableIds['tx_irretutorial_1nff_hotel'][self::VALUE_HotelIdFirst];
        // Delete one localized hotel (and its children) again.
        // We end up with having one localized hotel child and a missing one, while both exist in default language.
        $this->actionService->deleteRecord('tx_irretutorial_1nff_hotel', $this->recordIds['localizedHotelId']);
        // Now inlineLocalizeSynchronize->localize - This is the 'localize all records' button when inline
        // 'appearance' 'showAllLocalizationLink' has been enabled. It should re-localize the missing hotel again.
        $this->actionService->invoke(
            [],
            [
                'tt_content' => [
                    $this->recordIds['localizedContentId'] => [
                        'inlineLocalizeSynchronize' => [
                            'field' => 'tx_irretutorial_1nff_hotels',
                            'language' => 1,
                            'action' => 'localize',
                        ],
                    ],
                ],
            ]
        );
    }
}
