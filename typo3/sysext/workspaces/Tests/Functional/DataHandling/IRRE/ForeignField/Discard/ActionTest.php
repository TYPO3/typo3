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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\ForeignField\Discard;

use TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\ForeignField\AbstractActionTestCase;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/IRRE/ForeignField/Discard/DataSet/';

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
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createParentContent');
    }

    /**
     * @test
     */
    public function modifyParentContent(): void
    {
        parent::modifyParentContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyParentContent');
    }

    /**
     * @test
     */
    public function deleteParentContent(): void
    {
        parent::deleteParentContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('deleteParentContent');
    }

    /**
     * @test
     */
    public function copyParentContent(): void
    {
        parent::copyParentContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('copyParentContent');
    }

    /**
     * @test
     */
    public function copyParentContentToDifferentPage(): void
    {
        parent::copyParentContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('copyParentContentToDifferentPage');
    }

    /**
     * @test
     */
    public function localizeParentContentWithAllChildren(): void
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
    public function changeParentContentSorting(): void
    {
        parent::changeParentContentSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeParentContentSorting');
    }

    /**
     * @test
     */
    public function moveParentContentToDifferentPage(): void
    {
        parent::moveParentContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('moveParentContentToDifferentPage');
    }

    /**
     * @test
     */
    public function moveParentContentToDifferentPageTwice(): void
    {
        parent::moveParentContentToDifferentPageTwice();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('moveParentContentToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function moveParentContentToDifferentPageAndChangeSorting(): void
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
    public function modifyPage(): void
    {
        parent::modifyPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('modifyPage');
    }

    /**
     * @test
     */
    public function deletePage(): void
    {
        parent::deletePage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('deletePage');
    }

    /**
     * @test
     */
    public function copyPage(): void
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
    public function copyPageWithHotelBeforeParentContent(): void
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
    public function createParentContentWithHotelAndOfferChildren(): void
    {
        parent::createParentContentWithHotelAndOfferChildren();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createParentContentNHotelNOfferChildren');
    }

    /**
     * @test
     */
    public function createAndCopyParentContentWithHotelAndOfferChildren(): void
    {
        parent::createAndCopyParentContentWithHotelAndOfferChildren();
        $versionedCopiedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $versionedCopiedContentId);
        $this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildren');
    }

    /**
     * @test
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildren(): void
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
    public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent(): void
    {
        parent::createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent();
        $this->assertAssertionDataSet('createNLocParentNHotelNOfferChildrenNDiscardLocParent');
    }

    /**
     * @test
     */
    public function modifyOnlyHotelChild(): void
    {
        parent::modifyOnlyHotelChild();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Hotel, 4);
        $this->assertAssertionDataSet('modifyOnlyHotelChild');
    }

    /**
     * @test
     */
    public function modifyParentAndChangeHotelChildrenSorting(): void
    {
        parent::modifyParentAndChangeHotelChildrenSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('modifyParentNChangeHotelChildrenSorting');
    }

    /**
     * @test
     */
    public function modifyParentWithHotelChild(): void
    {
        parent::modifyParentWithHotelChild();
        $modifiedContentId = $this->actionService->getDataHandler()->getAutoVersionId(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $modifiedContentId);
        $this->assertAssertionDataSet('modifyParentNHotelChild');
    }

    /**
     * @test
     */
    public function modifyParentAndAddHotelChild(): void
    {
        parent::modifyParentAndAddHotelChild();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyParentNAddHotelChild');
    }

    /**
     * @test
     */
    public function modifyParentAndDeleteHotelChild(): void
    {
        parent::modifyParentAndDeleteHotelChild();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('modifyParentNDeleteHotelChild');
    }

    /**
     * @test
     */
    public function modifyAndDiscardAndModifyParentWithHotelChild(): void
    {
        parent::modifyAndDiscardAndModifyParentWithHotelChild();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst],
            self::TABLE_Hotel => [3, 4],
        ]);
        $this->assertAssertionDataSet('modifyNDiscardNModifyParentWHotelChild');
    }
}
