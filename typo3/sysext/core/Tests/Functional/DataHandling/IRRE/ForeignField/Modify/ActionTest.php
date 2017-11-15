<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE\ForeignField\Modify;

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
class ActionTest extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE\ForeignField\AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/IRRE/ForeignField/Modify/DataSet/';

    /**
     * Parent content records
     */

    /**
     * @test
     * @see DataSet/createParentContentRecord.csv
     */
    public function createParentContent()
    {
        parent::createParentContent();
        $this->assertAssertionDataSet('createParentContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('modifyParentContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('deleteParentContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     * @see DataSet/copyParentContentRecord.csv
     */
    public function copyParentContent()
    {
        parent::copyParentContent();
        $this->assertAssertionDataSet('copyParentContent');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('copyParentContentToDifferentPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/copyParentContentToLanguageKeep.csv
     */
    public function copyParentContentToLanguageInKeepMode()
    {
        parent::copyParentContentToLanguageInKeepMode();
        $this->assertAssertionDataSet('copyParentContentToLanguageKeep');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/copyParentContentToLanguageWAllChildrenKeep.csv
     */
    public function copyParentContentToLanguageWithAllChildrenInKeepMode()
    {
        parent::copyParentContentToLanguageWithAllChildrenInKeepMode();
        $this->assertAssertionDataSet('copyParentContentToLanguageWAllChildrenKeep');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/copyParentContentToLanguageSelect.csv
     */
    public function copyParentContentToLanguageInSelectMode()
    {
        parent::copyParentContentToLanguageInSelectMode();
        $this->assertAssertionDataSet('copyParentContentToLanguageSelect');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/copyParentContentToLanguageWAllChildrenSelect.csv
     */
    public function copyParentContentToLanguageWithAllChildrenInSelectMode()
    {
        parent::copyParentContentToLanguageWithAllChildrenInSelectMode();
        $this->assertAssertionDataSet('copyParentContentToLanguageWAllChildrenSelect');

        $this->setUpFrontendRootPage(1, [
            'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.ts',
            'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/ExtbaseJsonRenderer.ts',
            'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRendererNoOverlay.ts'
        ]);
        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['localizedContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/localizeParentContentKeep.csv
     */
    public function localizeParentContentInKeepMode()
    {
        parent::localizeParentContentInKeepMode();
        $this->assertAssertionDataSet('localizeParentContentKeep');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('localizeParentContentWAllChildrenKeep');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('localizeParentContentSelect');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/localizeParentContentSelectWSynchronization.csv
     */
    public function localizeParentContentInSelectModeWithLanguageSynchronization()
    {
        parent::localizeParentContentInSelectModeWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizeParentContentSelectWSynchronization');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
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
        $this->assertAssertionDataSet('localizeParentContentWAllChildrenSelect');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/localizeParentContentWAllChildrenSelectNLanguageSynchronization.csv
     */
    public function localizeParentContentWithAllChildrenInSelectModeAndLanguageSynchronization()
    {
        parent::localizeParentContentWithAllChildrenInSelectModeAndLanguageSynchronization();
        $this->assertAssertionDataSet('localizeParentContentWAllChildrenSelectNLanguageSynchronization');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1', '[Translate to Dansk:] Hotel #2'));
    }

    /**
     * @test
     * @see DataSet/localizeParentContentChainWAllChildrenSelectNLanguageSynchronizationSource.csv
     */
    public function localizeParentContentChainWithAllChildrenInSelectModeAndLanguageSynchronizationSource()
    {
        parent::localizeParentContentChainWithAllChildrenInSelectModeAndLanguageSynchronizationSource();
        $this->assertAssertionDataSet('localizeParentContentChainWAllChildrenSelectNLanguageSynchronizationSource');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageIdSecond)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Hotel #1', '[Translate to Deutsch:] [Translate to Dansk:] Hotel #2'));
    }

    /**
     * @test
     * @see DataSet/Modify/localizeParentContentNCreateNestedChildrenWLanguageSynchronization.csv
     */
    public function localizeParentContentAndCreateNestedChildrenWithLanguageSynchronization()
    {
        parent::localizeParentContentAndCreateNestedChildrenWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizeParentContentNCreateNestedChildrenWLanguageSynchronization');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1', '[Translate to Dansk:] New Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/localizeParentContentSelectWSynchronization.csv
     */
    public function localizeParentContentAndSetInvalidChildReferenceWithLanguageSynchronization()
    {
        parent::localizeParentContentAndSetInvalidChildReferenceWithLanguageSynchronization();
        // the assertion is the same as for localizeParentContentInSelectModeWithLanguageSynchronization()
        $this->assertAssertionDataSet('localizeParentContentSelectWSynchronization');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/localizeParentContentSelectWSynchronization.csv
     */
    public function localizeParentContentAndSetInvalidChildReferenceWithLateLanguageSynchronization()
    {
        parent::localizeParentContentAndSetInvalidChildReferenceWithLateLanguageSynchronization();
        // the assertion is the same as for localizeParentContentInSelectModeWithLanguageSynchronization()
        $this->assertAssertionDataSet('localizeParentContentSelectWSynchronization');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * @see DataSet/localizeChildrenHStandaloneChildrenWAllChildrenSelectNLanguageSynchronization.csv
     * @see https://forge.typo3.org/issues/81915
     */
    public function localizeChildrenHavingStandaloneChildrenInSelectModeAndLanguageSynchronization()
    {
        parent::localizeChildrenHavingStandaloneChildrenInSelectModeAndLanguageSynchronization();
        $this->assertAssertionDataSet('locChildrenHStandaloneChildrenWAllChildrenSelectNLanguageSynch');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1', '[Translate to Dansk:] Hotel #2'));
    }

    /**
     * @test
     * @see DataSet/changeParentContentRecordSorting.csv
     */
    public function changeParentContentSorting()
    {
        parent::changeParentContentSorting();
        $this->assertAssertionDataSet('changeParentContentSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('moveParentContentToDifferentPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('moveParentContentToDifferentPageNChangeSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('modifyPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     * @see DataSet/deletePageRecord.csv
     */
    public function deletePage()
    {
        parent::deletePage();
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
        $this->assertAssertionDataSet('copyPage');

        $responseSections = $this->getFrontendResponse($this->recordIds['newPageId'])->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('copyPageWHotelBeforeParentContent');

        $responseSections = $this->getFrontendResponse($this->recordIds['newPageId'])->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('createParentContentNHotelNOfferChildren');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildren');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
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
     * @see DataSet/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecords.csv
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildren()
    {
        parent::createAndLocalizeParentContentWithHotelAndOfferChildren();
        $this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildren');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        // Content record gets overlaid, thus using newContentId
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
        // Hotel record gets overlaid, thus using newHotelId
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Hotel . ':' . $this->recordIds['newHotelId'])->setRecordField(self::FIELD_HotelOffer)
            ->setTable(self::TABLE_Offer)->setField('title')->setValues('[Translate to Dansk:] Offer #1'));
    }

    /**
     * @test
     * @see DataSet/createNLocalizeParentContentNHotelNOfferChildrenWOSortBy.csv
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildrenWithoutSortByConfiguration()
    {
        parent::createAndLocalizeParentContentWithHotelAndOfferChildrenWithoutSortByConfiguration();
        $this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildrenWOSortBy');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Testing #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Hotel . ':' . $this->recordIds['newHotelId'])->setRecordField(self::FIELD_HotelOffer)
            ->setTable(self::TABLE_Offer)->setField('title')->setValues('[Translate to Dansk:] Offer #1'));
    }

    /**
     * @test
     * @see DataSet/modifyOnlyHotelChildRecord.csv
     */
    public function modifyOnlyHotelChild()
    {
        parent::modifyOnlyHotelChild();
        $this->assertAssertionDataSet('modifyOnlyHotelChild');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('modifyParentNChangeHotelChildrenSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('modifyParentNHotelChild');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Testing #1'));
    }

    /**
     * @test
     * @see DataSet/modifyParentRecordAndAddHotelChildRecord.csv
     */
    public function modifyParentAndAddHotelChild()
    {
        parent::modifyParentAndAddHotelChild();
        $this->assertAssertionDataSet('modifyParentNAddHotelChild');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
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
        $this->assertAssertionDataSet('modifyParentNDeleteHotelChild');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections('Default', 'Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #2'));
    }

    /**
     * @test
     * @see DataSet/localizePageWExclude.csv
     */
    public function localizePageWithLocalizationExclude()
    {
        parent::localizePageWithLocalizationExclude();
        $this->assertAssertionDataSet('localizePageWExclude');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #0'));
    }

    /**
     * @test
     * @see DataSet/localizePageNAddHotelChildWExclude.csv
     */
    public function localizePageAndAddHotelChildWithLocalizationExclude()
    {
        parent::localizePageAndAddHotelChildWithLocalizationExclude();
        $this->assertAssertionDataSet('localizePageNAddHotelChildWExclude');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #0', 'Hotel #007'));
    }

    /**
     * @test
     * @see DataSet/localizePageWSynchronization.csv
     */
    public function localizePageWithLanguageSynchronization()
    {
        parent::localizePageWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizePageWSynchronization');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #0'));
    }

    /**
     * @test
     * @see DataSet/localizePageNAddHotelChildWSynchronization.csv
     */
    public function localizePageAndAddHotelChildWithLanguageSynchronization()
    {
        parent::localizePageAndAddHotelChildWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizePageNAddHotelChildWSynchronization');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #0', '[Translate to Dansk:] Hotel #007'));
    }

    /**
     * @test
     * @see DataSet/localizePageNAddMonoglotHotelChildWSynchronization.csv
     */
    public function localizePageAndAddMonoglotHotelChildWithLanguageSynchronization()
    {
        parent::localizePageAndAddMonoglotHotelChildWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizePageNAddMonoglotHotelChildWSynchronization');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #0', 'Hotel #007'));
    }

    /**
     * @test
     * @see DataSet/localizeNCopyPageWSynchronization.csv
     */
    public function localizeAndCopyPageWithLanguageSynchronization()
    {
        parent::localizeAndCopyPageWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizeNCopyPageWSynchronization');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #0'));
    }

    /**
     * @test
     * @see DataSet/localizePageAddMonoglotHotelChildNCopyPageWSynchronization.csv
     */
    public function localizePageAddMonoglotHotelChildAndCopyPageWithLanguageSynchronization()
    {
        parent::localizePageAndAddMonoglotHotelChildWithLanguageSynchronization();
        parent::copyPage();
        $this->assertAssertionDataSet('localizePageAddMonoglotHotelChildNCopyPageWSynchronization');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #0', 'Hotel #007'));
    }
}
