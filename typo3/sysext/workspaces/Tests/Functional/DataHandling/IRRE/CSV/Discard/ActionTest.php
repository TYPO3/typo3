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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\CSV\Discard;

use TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\CSV\AbstractActionTestCase;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/IRRE/CSV/Discard/DataSet/';

    /**
     * Parent content records
     */

    /**
     * @test
     */
    public function createParentContent()
    {
        parent::createParentContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createParentContent');
    }

    /**
     * @test
     */
    public function modifyParentContent()
    {
        parent::modifyParentContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyParentContent');
    }

    /**
     * @test
     */
    public function deleteParentContent()
    {
        parent::deleteParentContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('deleteParentContent');
    }

    /**
     * @test
     */
    public function copyParentContent()
    {
        parent::copyParentContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('copyParentContent');
    }

    /**
     * @test
     */
    public function copyParentContentToDifferentPage()
    {
        parent::copyParentContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('copyParentContentToDifferentPage');
    }

    /**
     * @test
     */
    public function localizeParentContentWithAllChildren()
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeParentContentWithAllChildren();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeParentContentWAllChildren');
    }

    /**
     * @test
     */
    public function changeParentContentSorting()
    {
        parent::changeParentContentSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeParentContentSorting');
    }

    /**
     * @test
     */
    public function moveParentContentToDifferentPage()
    {
        $newRecordIds = parent::moveParentContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $newRecordIds[self::TABLE_Content][self::VALUE_ContentIdLast]);
        $this->assertAssertionDataSet('moveParentContentToDifferentPage');
    }

    /**
     * @test
     */
    public function moveParentContentToDifferentPageAndChangeSorting()
    {
        parent::moveParentContentToDifferentPageAndChangeSorting();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst, self::VALUE_ContentIdLast],
        ]);
        $this->assertAssertionDataSet('moveParentContentToDifferentPageNChangeSorting');
    }

    /**
     * Page records
     */

    /**
     * @test
     */
    public function modifyPage()
    {
        parent::modifyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('modifyPage');
    }

    /**
     * @test
     */
    public function deletePage()
    {
        parent::deletePage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('deletePage');
    }

    /**
     * @test
     */
    public function copyPage()
    {
        parent::copyPage();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [$this->recordIds['newPageId']],
        ]);
        $this->assertAssertionDataSet('copyPage');
    }

    /**
     * @test
     */
    public function copyPageWithHotelBeforeParentContent()
    {
        parent::copyPageWithHotelBeforeParentContent();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Page => [$this->recordIds['newPageId']],
        ]);
        $this->assertAssertionDataSet('copyPageWHotelBeforeParentContent');
    }

    /**
     * IRRE Child Records
     */

    /**
     * @test
     */
    public function createParentContentWithHotelAndOfferChildren()
    {
        parent::createParentContentWithHotelAndOfferChildren();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createParentContentNHotelNOfferChildren');
    }

    /**
     * @test
     */
    public function createAndCopyParentContentWithHotelAndOfferChildren()
    {
        parent::createAndCopyParentContentWithHotelAndOfferChildren();
        $versionedCopiedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedCopiedContentId);
        $this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildren');
    }

    /**
     * @test
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildren()
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::createAndLocalizeParentContentWithHotelAndOfferChildren();
        // Discard created default language parent
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildren');
    }

    /**
     * @test
     * No pair tests in Modify, Publish, PublishAll
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent()
    {
        parent::createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent();
        $this->assertAssertionDataSet('createNLocParentNHotelNOfferChildrenNDiscardLocParent');
    }

    /**
     * @test
     */
    public function modifyOnlyHotelChild()
    {
        parent::modifyOnlyHotelChild();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Hotel, 4);
        $this->assertAssertionDataSet('modifyOnlyHotelChild');
    }

    /**
     * @test
     */
    public function modifyParentAndChangeHotelChildrenSorting()
    {
        parent::modifyParentAndChangeHotelChildrenSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('modifyParentNChangeHotelChildrenSorting');
    }

    /**
     * @test
     */
    public function modifyParentWithHotelChild()
    {
        parent::modifyParentWithHotelChild();
        $modifiedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $modifiedContentId);
        $this->assertAssertionDataSet('modifyParentNHotelChild');
    }

    /**
     * @test
     */
    public function modifyParentAndAddHotelChild()
    {
        parent::modifyParentAndAddHotelChild();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyParentNAddHotelChild');
    }

    /**
     * @test
     */
    public function modifyParentAndDeleteHotelChild()
    {
        parent::modifyParentAndDeleteHotelChild();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('modifyParentNDeleteHotelChild');
    }

    /**
     * @test
     */
    public function modifyAndDiscardAndModifyParentWithHotelChild()
    {
        parent::modifyAndDiscardAndModifyParentWithHotelChild();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst],
            self::TABLE_Hotel => [3, 4],
        ]);
        $this->assertAssertionDataSet('modifyNDiscardNModifyParentWHotelChild');
    }
}
