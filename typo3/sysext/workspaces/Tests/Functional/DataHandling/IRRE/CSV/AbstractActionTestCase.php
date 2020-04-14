<?php

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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\CSV;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE\CSV\AbstractActionTestCase
{
    const VALUE_WorkspaceId = 1;

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/IRRE/CSV/DataSet/';

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = [
        'fluid',
        'workspaces',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importScenarioDataSet('ReferenceIndex');
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    /**
     * Parent content records
     */

    /**
     * See DataSet/deleteParentContentRecordAndDiscardDeletedParentRecord.csv
     */
    public function deleteParentContentAndDiscardDeletedParent()
    {
        $newTableIds = $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $versionedDeletedContentId = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedDeletedContentId);
    }

    /**
     * IRRE Child Records
     */

    /**
     * See DataSet/createAndCopyParentContentRecordWithHotelAndOfferChildRecordsAndDiscardCopiedParentRecord.csv
     */
    public function createAndCopyParentContentWithHotelAndOfferChildrenAndDiscardCopiedParent()
    {
        // @todo Copying the new child records is broken in the Core
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'],
                self::TABLE_Hotel => ['title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'],
                self::TABLE_Offer => ['title' => 'Offer #1'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds['tt_content'][0];
        $copiedTableIds = $this->actionService->copyRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $copiedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
        $versionedCopiedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedCopiedContentId);
    }

    /**
     * See DataSet/createNLocParentNHotelNOfferChildrenNDiscardCreatedParent.csv
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardCreatedParent()
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
        $this->recordIds['versionedNewContentId'] = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['newContentId']);
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['versionedNewContentId']);
    }

    /**
     * See DataSet/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecordsAndDiscardLocalizedParentRecord.csv
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent()
    {
        // @todo Localizing the new child records is broken in the Core
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Testing #1', self::FIELD_ContentHotel => '__nextUid'],
                self::TABLE_Hotel => ['title' => 'Hotel #1', self::FIELD_HotelOffer => '__nextUid'],
                self::TABLE_Offer => ['title' => 'Offer #1'],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
        $versionedLocalizedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedLocalizedContentId);
    }

    /**
     * See DataSet/modifyParentRecordWithHotelChildRecordAndDiscardModifiedParentRecord.csv
     */
    public function modifyParentWithHotelChildAndDiscardModifiedParent()
    {
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdFirst, self::FIELD_ContentHotel => '3,4'],
                self::TABLE_Hotel => ['uid' => 4, 'title' => 'Testing #1'],
            ]
        );
        $modifiedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $modifiedContentId);
    }

    /**
     * See DataSet/modifyParentRecordWithHotelChildRecordAndDiscardAllModifiedRecords.csv
     */
    public function modifyParentWithHotelChildAndDiscardAll()
    {
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdFirst, self::FIELD_ContentHotel => '3,4'],
                self::TABLE_Hotel => ['uid' => 4, 'title' => 'Testing #1'],
            ]
        );
        $modifiedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $modifiedHotelId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Hotel, 4);
        $this->actionService->clearWorkspaceRecords(
            [
                    self::TABLE_Content => [$modifiedContentId],
                    self::TABLE_Hotel => [$modifiedHotelId],
                ]
        );
    }

    /**
     * See DataSet/modifyNDiscardNModifyParentWHotelChild.csv
     */
    public function modifyAndDiscardAndModifyParentWithHotelChild()
    {
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdFirst, 'header' => 'Testing #1', self::FIELD_ContentHotel => '3,4'],
                self::TABLE_Hotel => ['uid' => 4, 'title' => 'Testing #1'],
            ]
        );
        $this->recordIds['versionedContentId'] = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->recordIds['versionedHotelIdFirst'] = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Hotel, 3);
        $this->recordIds['versionedHotelIdSecond'] = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Hotel, 4);
        $this->actionService->clearWorkspaceRecords(
            [
                self::TABLE_Content => [$this->recordIds['versionedContentId']],
                self::TABLE_Hotel => [$this->recordIds['versionedHotelIdSecond']],
            ]
        );
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdFirst, 'header' => 'Testing #2', self::FIELD_ContentHotel => '3,4'],
                self::TABLE_Hotel => ['uid' => 4, 'title' => 'Testing #2'],
            ]
        );
    }
}
