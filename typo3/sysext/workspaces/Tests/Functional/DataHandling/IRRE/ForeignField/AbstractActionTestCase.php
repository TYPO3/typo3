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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\ForeignField;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE\ForeignField\AbstractActionTestCase
{
    const VALUE_WorkspaceId = 1;

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/IRRE/ForeignField/DataSet/';

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }

    public function changeHotelSortingWithOfferNotWorkspaceAware()
    {
        $GLOBALS['TCA']['tx_irretutorial_1nff_offer']['ctrl']['versioningWS'] = false;
        $this->actionService->moveRecord(self::TABLE_Hotel, self::VALUE_HotelIdFirst, -self::VALUE_HotelIdSecond);
    }

    public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent()
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
        $localizedTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['newContentId'], self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $localizedTableIds[self::TABLE_Content][$this->recordIds['newContentId']];
        $versionedLocalizedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedLocalizedContentId);
    }

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
