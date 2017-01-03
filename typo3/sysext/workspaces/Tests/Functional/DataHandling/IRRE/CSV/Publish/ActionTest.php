<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\CSV\Publish;

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

/**
 * Functional test for the DataHandler
 */
class ActionTest extends \TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE\CSV\AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/IRRE/CSV/Publish/DataSet/';

    /**
     * Parent content records
     */
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     * @see DataSet/createParentContentRecord.csv
     */
    public function createParentContent()
    {
        parent::createParentContent();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createParentContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * @see DataSet/modifyParentContentRecord.csv
     */
    public function modifyParentContent()
    {
        parent::modifyParentContent();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyParentContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/deleteParentContentRecord.csv
     */
    public function deleteParentContent()
    {
        parent::deleteParentContent();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('deleteParentContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     * @see DataSet/deleteParentContentRecordAndDiscardDeletedParentRecord.csv
     */
    public function deleteParentContentAndDiscardDeletedParent()
    {
        parent::deleteParentContentAndDiscardDeletedParent();
        // Actually this is not required, since there's nothing to publish... but it's a test case!
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast, false);
        $this->assertAssertionDataSet('deleteParentContentNDiscardDeletedParent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     * @see DataSet/copyParentContentRecord.csv
     */
    public function copyParentContent()
    {
        parent::copyParentContent();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('copyParentContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/copyParentContentToDifferentPage.csv
     */
    public function copyParentContentToDifferentPage()
    {
        parent::copyParentContentToDifferentPage();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('copyParentContentToDifferentPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/localizeParentContentKeep.csv
     */
    public function localizeParentContentInKeepMode()
    {
        parent::localizeParentContentInKeepMode();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeParentContentKeep');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/localizeParentContentWAllChildrenKeep.csv
     */
    public function localizeParentContentWithAllChildrenInKeepMode()
    {
        parent::localizeParentContentWithAllChildrenInKeepMode();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeParentContentWAllChildrenKeep');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/localizeParentContentSelect.csv
     */
    public function localizeParentContentInSelectMode()
    {
        parent::localizeParentContentInSelectMode();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeParentContentSelect');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/localizeParentContentWAllChildrenSelect.csv
     */
    public function localizeParentContentWithAllChildrenInSelectMode()
    {
        parent::localizeParentContentWithAllChildrenInSelectMode();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeParentContentWAllChildrenSelect');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/changeParentContentRecordSorting.csv
     */
    public function changeParentContentSorting()
    {
        parent::changeParentContentSorting();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeParentContentSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/moveParentContentRecordToDifferentPage.csv
     */
    public function moveParentContentToDifferentPage()
    {
        parent::moveParentContentToDifferentPage();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('moveParentContentToDifferentPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/moveParentContentRecordToDifferentPageAndChangeSorting.csv
     */
    public function moveParentContentToDifferentPageAndChangeSorting()
    {
        parent::moveParentContentToDifferentPageAndChangeSorting();
        $this->actionService->publishRecords(
            [
                self::TABLE_Content => [self::VALUE_ContentIdFirst, self::VALUE_ContentIdLast],
            ]
        );
        $this->assertAssertionDataSet('moveParentContentToDifferentPageNChangeSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2', 'Regular Element #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * Page records
     */

    /**
     * @test
     * @see DataSet/modifyPageRecord.csv
     */
    public function modifyPage()
    {
        parent::modifyPage();
        $this->actionService->publishRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('modifyPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2'));
    }

    /**
     * @test
     * @see DataSet/deletePageRecord.csv
     */
    public function deletePage()
    {
        parent::deletePage();
        $this->actionService->publishRecord(self::TABLE_Page, self::VALUE_PageId);
        $this->assertAssertionDataSet('deletePage');

        $response = $this->getFrontendResponse(self::VALUE_PageId, 0, 0, 0, false);
        $this->assertContains('PageNotFoundException', $response->getError());
    }

    /**
     * @test
     * @see DataSet/copyPageRecord.csv
     */
    public function copyPage()
    {
        parent::copyPage();
        $this->actionService->publishRecords(
            [
                self::TABLE_Page => [$this->recordIds['newPageId']],
                self::TABLE_Content => [$this->recordIds['newContentIdFirst'], $this->recordIds['newContentIdLast']],
            ]
        );
        $this->assertAssertionDataSet('copyPage');

        $responseSections = $this->getFrontendResponse($this->recordIds['newPageId'], 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2', 'Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/copyPageWHotelBeforeParentContent.csv
     */
    public function copyPageWithHotelBeforeParentContent()
    {
        parent::copyPageWithHotelBeforeParentContent();
        $this->actionService->publishRecords(
            [
                self::TABLE_Page => [$this->recordIds['newPageId']],
                self::TABLE_Content => [$this->recordIds['newContentIdFirst'], $this->recordIds['newContentIdLast']],
            ]
        );
        $this->assertAssertionDataSet('copyPageWHotelBeforeParentContent');

        $responseSections = $this->getFrontendResponse($this->recordIds['newPageId'], 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2', 'Hotel #1'));
    }

    /**
     * IRRE Child Records
     */

    /**
     * @test
     * @see DataSet/createParentContentRecordWithHotelAndOfferChildRecords.csv
     */
    public function createParentContentWithHotelAndOfferChildren()
    {
        parent::createParentContentWithHotelAndOfferChildren();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createParentContentNHotelNOfferChildren');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/createAndCopyParentContentRecordWithHotelAndOfferChildRecords.csv
     */
    public function createAndCopyParentContentWithHotelAndOfferChildren()
    {
        parent::createAndCopyParentContentWithHotelAndOfferChildren();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildren');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1 (copy 1)'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['copiedContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Hotel . ':' . $this->recordIds['copiedHotelId'])->setRecordField(self::FIELD_HotelOffer)
            ->setTable(self::TABLE_Offer)->setField('title')->setValues('Offer #1'));
    }

    /**
     * @test
     * @see DataSet/createAndCopyParentContentRecordWithHotelAndOfferChildRecordsAndDiscardCopiedParentRecord.csv
     */
    public function createAndCopyParentContentWithHotelAndOfferChildrenAndDiscardCopiedParent()
    {
        parent::createAndCopyParentContentWithHotelAndOfferChildrenAndDiscardCopiedParent();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        // Actually this is not required, since there's nothing to publish... but it's a test case!
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['copiedContentId'], false);
        $this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildrenNDiscardCopiedParent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1 (copy 1)'));
    }

    /**
     * @test
     * @see DataSet/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecords.csv
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildren()
    {
        parent::createAndLocalizeParentContentWithHotelAndOfferChildren();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildren');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Hotel . ':' . $this->recordIds['localizedHotelId'])->setRecordField(self::FIELD_HotelOffer)
            ->setTable(self::TABLE_Offer)->setField('title')->setValues('[Translate to Dansk:] Offer #1'));
    }

    /**
     * @test
     * @see DataSet/createNLocalizeParentContentNHotelNOfferChildrenNDiscardCreatedParent.csv
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardCreatedParent()
    {
        parent::createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardCreatedParent();
        // Actually this is not required, since there's nothing to publish... but it's a test case!
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId'], false);
        $this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildrenNDiscardCreatedParent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1', '[Translate to Dansk:] Testing #1'));
    }

    /**
     * @test
     * @see DataSet/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecordsAndDiscardLocalizedParentRecord.csv
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent()
    {
        parent::createAndLocalizeParentContentWithHotelAndOfferChildrenAndDiscardLocalizedParent();
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        // Actually this is not required, since there's nothing to publish... but it's a test case!
        $this->actionService->publishRecord(self::TABLE_Content, $this->recordIds['localizedContentId'], false);
        $this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildrenNDiscardLocalizedParent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Testing #1'));
    }

    /**
     * @test
     * @see DataSet/modifyOnlyHotelChildRecord.csv
     */
    public function modifyOnlyHotelChild()
    {
        parent::modifyOnlyHotelChild();
        $this->actionService->publishRecord(self::TABLE_Hotel, 4);
        $this->assertAssertionDataSet('modifyOnlyHotelChild');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Testing #1'));
    }

    /**
     * @test
     * @see DataSet/modifyParentRecordAndChangeHotelChildRecordsSorting.csv
     */
    public function modifyParentAndChangeHotelChildrenSorting()
    {
        parent::modifyParentAndChangeHotelChildrenSorting();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('modifyParentNChangeHotelChildrenSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #2', 'Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/modifyParentRecordWithHotelChildRecord.csv
     */
    public function modifyParentWithHotelChild()
    {
        parent::modifyParentWithHotelChild();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('modifyParentNHotelChild');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Testing #1'));
    }

    /**
     * @test
     * @see DataSet/modifyParentRecordWithHotelChildRecordAndDiscardModifiedParentRecord.csv
     */
    public function modifyParentWithHotelChildAndDiscardModifiedParent()
    {
        parent::modifyParentWithHotelChildAndDiscardModifiedParent();
        // Actually this is not required, since there's nothing to publish... but it's a test case!
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, false);
        $this->assertAssertionDataSet('modifyParentNHotelChildNDiscardModifiedParent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        // Discarding the parent shall not discard the child records
        // Since the discarded parent does not need to be published, version children are not published as well
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     * @see DataSet/modifyParentRecordWithHotelChildRecordAndDiscardAllModifiedRecords.csv
     */
    public function modifyParentWithHotelChildAndDiscardAll()
    {
        parent::modifyParentWithHotelChildAndDiscardAll();
        // Actually this is not required, since there's nothing to publish... but it's a test case!
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, false);
        $this->assertAssertionDataSet('modifyParentNHotelChildNDiscardAll');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2'));
    }

    /**
     * @test
     * @see DataSet/modifyParentRecordAndAddHotelChildRecord.csv
     */
    public function modifyParentAndAddHotelChild()
    {
        parent::modifyParentAndAddHotelChild();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyParentNAddHotelChild');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2'));
    }

    /**
     * @test
     * @see DataSet/modifyParentRecordAndDeleteHotelChildRecord.csv
     */
    public function modifyParentAndDeleteHotelChild()
    {
        parent::modifyParentAndDeleteHotelChild();
        $this->actionService->publishRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('modifyParentNDeleteHotelChild');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #2'));
    }

    /**
     * @test
     * @see DataSet/modifyNDiscardNModifyParentWHotelChild.csv
     */
    public function modifyAndDiscardAndModifyParentWithHotelChild()
    {
        parent::modifyAndDiscardAndModifyParentWithHotelChild();
        $this->actionService->publishRecords(
            [
                self::TABLE_Content => [self::VALUE_ContentIdFirst],
                self::TABLE_Hotel => [3, 4],
            ]
        );
        $this->assertAssertionDataSet('modifyNDiscardNModifyParentWHotelChild');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, 0, self::VALUE_BackendUserId, self::VALUE_WorkspaceId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Hotel)->setField('header')->setValues('Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #2'));
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Testing #2'));
    }
}
