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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE\CSV\Modify;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE\CSV\AbstractActionTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/IRRE/CSV/Modify/DataSet/';

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
     * See DataSet/createParentContentRecord.csv
     */
    public function createParentContent(): void
    {
        parent::createParentContent();
        $this->assertAssertionDataSet('createParentContent');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * See DataSet/modifyParentContentRecord.csv
     */
    public function modifyParentContent(): void
    {
        parent::modifyParentContent();
        $this->assertAssertionDataSet('modifyParentContent');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * See DataSet/deleteParentContentRecord.csv
     */
    public function deleteParentContent(): void
    {
        parent::deleteParentContent();
        $this->assertAssertionDataSet('deleteParentContent');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     * See DataSet/copyParentContentRecord.csv
     */
    public function copyParentContent(): void
    {
        parent::copyParentContent();
        $this->assertAssertionDataSet('copyParentContent');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * See DataSet/copyParentContentToDifferentPage.csv
     */
    public function copyParentContentToDifferentPage(): void
    {
        parent::copyParentContentToDifferentPage();
        $this->assertAssertionDataSet('copyParentContentToDifferentPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * See DataSet/copyParentContentToLanguageWithAllChildren.csv
     */
    public function copyParentContentToLanguageWithAllChildren(): void
    {
        parent::copyParentContentToLanguageWithAllChildren();
        $this->assertAssertionDataSet('copyParentContentToLanguageWithAllChildren');

        // Set up "dk" to not have overlays
        $languageConfiguration = $this->siteLanguageConfiguration;
        $languageConfiguration[self::VALUE_LanguageId]['fallbackType'] = 'free';
        $this->setUpFrontendSite(1, $languageConfiguration);

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Default');
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['localizedContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * See DataSet/localizeParentContentWAllChildren.csv
     */
    public function localizeParentContentWithAllChildren(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);

        parent::localizeParentContentWithAllChildren();
        $this->assertAssertionDataSet('localizeParentContentWAllChildren');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
    }

    /**
     * @test
     * See DataSet/localizeParentContentLanguageSynchronization.csv
     */
    public function localizeParentContentWithLanguageSynchronization(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);

        parent::localizeParentContentWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizeParentContentLanguageSynchronization');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1', '[Translate to Dansk:] Hotel #2'));
    }

    /**
     * @test
     * See DataSet/changeParentContentRecordSorting.csv
     */
    public function changeParentContentSorting(): void
    {
        parent::changeParentContentSorting();
        $this->assertAssertionDataSet('changeParentContentSorting');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * See DataSet/moveParentContentRecordToDifferentPage.csv
     */
    public function moveParentContentToDifferentPage(): void
    {
        parent::moveParentContentToDifferentPage();
        $this->assertAssertionDataSet('moveParentContentToDifferentPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * See DataSet/moveParentContentRecordToDifferentPageAndChangeSorting.csv
     */
    public function moveParentContentToDifferentPageAndChangeSorting(): void
    {
        parent::moveParentContentToDifferentPageAndChangeSorting();
        $this->assertAssertionDataSet('moveParentContentToDifferentPageNChangeSorting');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2', 'Regular Element #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * Page records
     */

    /**
     * @test
     * See DataSet/modifyPageRecord.csv
     */
    public function modifyPage(): void
    {
        parent::modifyPage();
        $this->assertAssertionDataSet('modifyPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     * See DataSet/deletePageRecord.csv
     */
    public function deletePage(): void
    {
        parent::deletePage();
        $this->assertAssertionDataSet('deletePage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     * See DataSet/copyPageRecord.csv
     */
    public function copyPage(): void
    {
        parent::copyPage();
        $this->assertAssertionDataSet('copyPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2', 'Hotel #1'));
    }

    /**
     * @test
     * See DataSet/copyPageWHotelBeforeParentContent.csv
     */
    public function copyPageWithHotelBeforeParentContent(): void
    {
        parent::copyPageWithHotelBeforeParentContent();
        $this->assertAssertionDataSet('copyPageWHotelBeforeParentContent');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2', 'Hotel #1'));
    }

    /**
     * IRRE Child Records
     */

    /**
     * @test
     * See DataSet/createParentContentRecordWithHotelAndOfferChildRecords.csv
     */
    public function createParentContentWithHotelAndOfferChildren(): void
    {
        parent::createParentContentWithHotelAndOfferChildren();
        $this->assertAssertionDataSet('createParentContentNHotelNOfferChildren');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
    }

    /**
     * @test
     * See DataSet/createAndCopyParentContentRecordWithHotelAndOfferChildRecords.csv
     */
    public function createAndCopyParentContentWithHotelAndOfferChildren(): void
    {
        parent::createAndCopyParentContentWithHotelAndOfferChildren();
        $this->assertAssertionDataSet('createNCopyParentContentNHotelNOfferChildren');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['copiedContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Hotel . ':' . $this->recordIds['copiedHotelId'])->setRecordField(self::FIELD_HotelOffer)
            ->setTable(self::TABLE_Offer)->setField('title')->setValues('Offer #1'));
    }

    /**
     * @test
     * See DataSet/createAndLocalizeParentContentRecordWithHotelAndOfferChildRecords.csv
     */
    public function createAndLocalizeParentContentWithHotelAndOfferChildren(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);

        parent::createAndLocalizeParentContentWithHotelAndOfferChildren();
        $this->assertAssertionDataSet('createNLocalizeParentContentNHotelNOfferChildren');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        // Content record gets overlaid, thus using newContentId
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #1'));
        // Content record directly points to localized child, thus using localizedHotelId
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Hotel . ':' . $this->recordIds['localizedHotelId'])->setRecordField(self::FIELD_HotelOffer)
            ->setTable(self::TABLE_Offer)->setField('title')->setValues('[Translate to Dansk:] Offer #1'));
    }

    /**
     * @test
     * See DataSet/modifyOnlyHotelChildRecord.csv
     */
    public function modifyOnlyHotelChild(): void
    {
        parent::modifyOnlyHotelChild();
        $this->assertAssertionDataSet('modifyOnlyHotelChild');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Testing #1'));
    }

    /**
     * @test
     * See DataSet/modifyParentRecordAndChangeHotelChildRecordsSorting.csv
     */
    public function modifyParentAndChangeHotelChildrenSorting(): void
    {
        parent::modifyParentAndChangeHotelChildrenSorting();
        $this->assertAssertionDataSet('modifyParentNChangeHotelChildrenSorting');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #2', 'Hotel #1'));
    }

    /**
     * @test
     * See DataSet/modifyParentRecordWithHotelChildRecord.csv
     */
    public function modifyParentWithHotelChild(): void
    {
        parent::modifyParentWithHotelChild();
        $this->assertAssertionDataSet('modifyParentNHotelChild');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Testing #1'));
    }

    /**
     * @test
     * See DataSet/modifyParentRecordAndAddHotelChildRecord.csv
     */
    public function modifyParentAndAddHotelChild(): void
    {
        parent::modifyParentAndAddHotelChild();
        $this->assertAssertionDataSet('modifyParentNAddHotelChild');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1', 'Hotel #2'));
    }

    /**
     * @test
     * See DataSet/modifyParentRecordAndDeleteHotelChildRecord.csv
     */
    public function modifyParentAndDeleteHotelChild(): void
    {
        parent::modifyParentAndDeleteHotelChild();
        $this->assertAssertionDataSet('modifyParentNDeleteHotelChild');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #2'));
    }

    /**
     * @test
     * See DataSet/localizePageWExclude.csv
     */
    public function localizePageWithLocalizationExclude(): void
    {
        parent::localizePageWithLocalizationExclude();
        $this->assertAssertionDataSet('localizePageWExclude');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #0'));
    }

    /**
     * @test
     * See DataSet/localizePageTwiceWExclude.csv
     */
    public function localizePageTwiceWithLocalizationExclude(): void
    {
        parent::localizePageTwiceWithLocalizationExclude();
        $this->assertAssertionDataSet('localizePageTwiceWExclude');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #0'));
    }

    /**
     * @test
     * See DataSet/localizePageNAddHotelChildWExclude.csv
     */
    public function localizePageAndAddHotelChildWithLocalizationExclude(): void
    {
        parent::localizePageAndAddHotelChildWithLocalizationExclude();
        $this->assertAssertionDataSet('localizePageNAddHotelChildWExclude');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #0', 'Hotel #007'));
    }

    /**
     * @test
     * See DataSet/localizePageWSynchronization.csv
     */
    public function localizePageWithLanguageSynchronization(): void
    {
        parent::localizePageWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizePageWSynchronization');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #0'));
    }

    /**
     * @test
     * See DataSet/localizePageNAddHotelChildWSynchronization.csv
     */
    public function localizePageAndAddHotelChildWithLanguageSynchronization(): void
    {
        parent::localizePageAndAddHotelChildWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizePageNAddHotelChildWSynchronization');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #0', '[Translate to Dansk:] Hotel #007'));
    }

    /**
     * @test
     * See DataSet/localizePageNAddMonoglotHotelChildWSynchronization.csv
     */
    public function localizePageAndAddMonoglotHotelChildWithLanguageSynchronization(): void
    {
        parent::localizePageAndAddMonoglotHotelChildWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizePageNAddMonoglotHotelChildWSynchronization');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #0', 'Hotel #007'));
    }

    /**
     * @test
     * See DataSet/localizeNCopyPageWSynchronization.csv
     */
    public function localizeAndCopyPageWithLanguageSynchronization(): void
    {
        parent::localizeAndCopyPageWithLanguageSynchronization();
        $this->assertAssertionDataSet('localizeNCopyPageWSynchronization');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #0'));
    }

    /**
     * Checks for a page having a IRRE record. The page is then localized and
     * an IRRE record is then added to the localized page
     *
     * @test
     * See DataSet/localizePageWithSynchronizationAndCustomLocalizedHotel.csv
     */
    public function localizePageWithSynchronizationAndCustomLocalizedHotel(): void
    {
        parent::localizePageWithSynchronizationAndCustomLocalizedHotel();
        $this->assertAssertionDataSet('localizePageWithSynchronizationAndCustomLocalizedHotel');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('[Translate to Dansk:] Hotel #0'));
    }

    /**
     * @test
     * See DataSet/localizePageAddMonoglotHotelChildNCopyPageWSynchronization.csv
     */
    public function localizePageAddMonoglotHotelChildAndCopyPageWithLanguageSynchronization(): void
    {
        parent::localizePageAndAddMonoglotHotelChildWithLanguageSynchronization();
        parent::copyPage();
        $this->assertAssertionDataSet('localizePageAddMonoglotHotelChildNCopyPageWSynchronization');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageId)->setRecordField(self::FIELD_PageHotel)
            ->setTable(self::TABLE_Hotel)->setField('title')->setValues('Hotel #0', 'Hotel #007'));
    }
}
