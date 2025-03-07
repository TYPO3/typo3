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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignFieldNonWs;

use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\StringUtility;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageId = 89;
    protected const VALUE_PageIdTarget = 90;
    protected const VALUE_PageIdWebsite = 1;
    protected const VALUE_ContentIdFirst = 297;
    protected const VALUE_ContentIdLast = 298;
    protected const VALUE_HotelIdFirst = 3;
    protected const VALUE_HotelIdSecond = 4;
    protected const VALUE_HotelIdThird = 5;
    protected const VALUE_OfferIdLast = 8;
    protected const VALUE_LanguageId = 1;
    protected const VALUE_LanguageIdSecond = 2;

    protected const TABLE_Page = 'pages';
    protected const TABLE_Content = 'tt_content';
    protected const TABLE_Hotel = 'tx_testirreforeignfieldnonws_hotel';
    protected const TABLE_Offer = 'tx_testirreforeignfieldnonws_offer';
    protected const TABLE_Price = 'tx_testirreforeignfieldnonws_price';

    protected const FIELD_PageHotel = 'tx_testirreforeignfieldnonws_hotels';
    protected const FIELD_ContentHotel = 'tx_testirreforeignfieldnonws_hotels';
    protected const FIELD_HotelOffer = 'offers';
    protected const FIELD_OfferPrice = 'prices';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield_non_ws',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(static::SCENARIO_DataSet);
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', ['EN']),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
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
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See DataSet/localizeParentContentWAllChildrenSelect.csv
     */
    public function localizeParentContentChainLanguageSynchronizationSource(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
     * @todo: Note there is a subtle difference between "copyToLanguage" command here,
     *        and "supposedly the same" using "copy" below. It should be decided if
     *        having two different results is what we really want in this case, or
     *        if this should be streamlined.
     */
    public function copyParentContentToLanguageWithAllChildren(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * @todo: Note there is a subtle difference between "copy" command here, and
     *        "supposedly the same" using "copyToLanguage" above. It should be decided
     *        if having two different results is what we really want in this case, or
     *        if this should be streamlined.
     */
    public function copyParentContentToDifferentLanguageWAllChildren(): void
    {
        $newTableIds = $this->actionService->copyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::VALUE_PageId,
            [
                'sys_language_uid' => self::VALUE_LanguageId,
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See DataSet/copyParentContentToLanguageWAllChildrenWExclude.csv
     */
    public function copyParentContentToLanguageWithAllChildrenWithLocalizationExclude(): void
    {
        // l10n_mode=exclude actually currently has no relevance in the DataHandler operation as it
        // only acts as display condition, meaning that the default records value is just copied into
        // the translation and kept in sync. The field is not shown in e.g. FormEngine.
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_PageHotel]['l10n_mode'] = 'exclude';
        $GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['l10n_mode'] = 'exclude';
        $GLOBALS['TCA'][self::TABLE_Offer]['columns'][self::FIELD_OfferPrice]['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeParentContentWithAllChildren(): void
    {
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See DataSet/localizeParentContentWAllChildrenWExclude.csv
     */
    public function localizeParentContentWithAllChildrenWithLocalizationExclude(): void
    {
        // l10n_mode=exclude actually currently has no relevance in the DataHandler operation as it
        // only acts as display condition, meaning that the default records value is just copied into
        // the translation and kept in sync. The field is not shown in e.g. FormEngine.
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_PageHotel]['l10n_mode'] = 'exclude';
        $GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['l10n_mode'] = 'exclude';
        $GLOBALS['TCA'][self::TABLE_Offer]['columns'][self::FIELD_OfferPrice]['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

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
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

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
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $this->recordIds['localizedHotelId'] = $newTableIds[self::TABLE_Hotel][self::VALUE_HotelIdThird];
        $this->recordIds['localizedOfferId'] = $newTableIds[self::TABLE_Offer][self::VALUE_OfferIdLast];
        // now enable language synchronization
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $GLOBALS['TCA'][self::TABLE_Offer]['columns'][self::FIELD_OfferPrice]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

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
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageTwiceWithLocalizationExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageIdFirst'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedPageIdSecond'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageAndAddHotelChildWithLocalizationExclude(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageAndAddHotelChildWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $localizedTableIds[self::TABLE_Page][self::VALUE_PageId];
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    public function localizePageWithSynchronizationAndCustomLocalizedHotel(): void
    {
        $GLOBALS['TCA'][self::TABLE_Page]['columns'][self::FIELD_PageHotel]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $this->recordIds['localizedHotelId'] = $newTableIds['tx_testirreforeignfieldnonws_hotel'][self::VALUE_HotelIdFirst];
        // Delete one localized hotel (and its children) again.
        // We end up with having one localized hotel child and a missing one, while both exist in default language.
        $this->actionService->deleteRecord('tx_testirreforeignfieldnonws_hotel', $this->recordIds['localizedHotelId']);
        // Now inlineLocalizeSynchronize->localize - This is the 'localize all records' button when inline
        // 'appearance' 'showAllLocalizationLink' has been enabled. It should re-localize the missing hotel again.
        $this->actionService->invoke(
            [],
            [
                'tt_content' => [
                    $this->recordIds['localizedContentId'] => [
                        'inlineLocalizeSynchronize' => [
                            'field' => 'tx_testirreforeignfieldnonws_hotels',
                            'language' => 1,
                            'action' => 'localize',
                        ],
                    ],
                ],
            ]
        );
    }
}
