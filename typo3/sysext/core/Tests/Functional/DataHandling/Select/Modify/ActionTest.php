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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Select\Modify;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\Select\AbstractActionTestCase;
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
    protected $assertionDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Select/Modify/DataSet/';

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
     * See DataSet/addElementRelation.csv
     */
    public function addElementRelation(): void
    {
        parent::addElementRelation();
        $this->assertAssertionDataSet('addElementRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1', 'Element #2', 'Element #3'));
    }

    /**
     * @test
     * See DataSet/deleteElementRelation.csv
     */
    public function deleteElementRelation(): void
    {
        parent::deleteElementRelation();
        $this->assertAssertionDataSet('deleteElementRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #2', 'Element #3'));
    }

    /**
     * @test
     * See DataSet/changeElementSorting.csv
     */
    public function changeElementSorting(): void
    {
        parent::changeElementSorting();
        $this->assertAssertionDataSet('changeElementSorting');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1', 'Element #2'));
    }

    /**
     * @test
     * See DataSet/changeElementRelationSorting.csv
     */
    public function changeElementRelationSorting(): void
    {
        parent::changeElementRelationSorting();
        $this->assertAssertionDataSet('changeElementRelationSorting');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1', 'Element #2'));
    }

    /**
     * @test
     * See DataSet/createContentNAddRelation.csv
     */
    public function createContentAndAddElementRelation(): void
    {
        parent::createContentAndAddElementRelation();
        $this->assertAssertionDataSet('createContentNAddRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1'));
    }

    /**
     * @test
     * See DataSet/createContentNCreateRelation.csv
     */
    public function createContentAndCreateElementRelation(): void
    {
        parent::createContentAndCreateElementRelation();
        $this->assertAssertionDataSet('createContentNCreateRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     * See DataSet/modifyElementOfRelation.csv
     */
    public function modifyElementOfRelation(): void
    {
        parent::modifyElementOfRelation();
        $this->assertAssertionDataSet('modifyElementOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Testing #1', 'Element #2'));
    }

    /**
     * @test
     * See DataSet/modifyContentOfRelation.csv
     */
    public function modifyContentOfRelation(): void
    {
        parent::modifyContentOfRelation();
        $this->assertAssertionDataSet('modifyContentOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * See DataSet/modifyBothSidesOfRelation.csv
     */
    public function modifyBothSidesOfRelation(): void
    {
        parent::modifyBothSidesOfRelation();
        $this->assertAssertionDataSet('modifyBothSidesOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Testing #1', 'Element #2'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * See DataSet/deleteContentOfRelation.csv
     */
    public function deleteContentOfRelation(): void
    {
        parent::deleteContentOfRelation();
        $this->assertAssertionDataSet('deleteContentOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * See DataSet/deleteElementOfRelation.csv
     */
    public function deleteElementOfRelation(): void
    {
        parent::deleteElementOfRelation();
        $this->assertAssertionDataSet('deleteElementOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1'));
    }

    /**
     * @test
     * See DataSet/copyContentOfRelation.csv
     */
    public function copyContentOfRelation(): void
    {
        parent::copyContentOfRelation();
        $this->assertAssertionDataSet('copyContentOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['copiedContentId'])->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #2', 'Element #3'));
    }

    /**
     * @test
     * See DataSet/copyElementOfRelation.csv
     */
    public function copyElementOfRelation(): void
    {
        parent::copyElementOfRelation();
        $this->assertAssertionDataSet('copyElementOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1'));
        // Referenced elements are not updated at the "parent", which is expected and correct
        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1 (copy 1)'));
    }

    /**
     * @test
     * See DataSet/copyContentToLanguageOfRelation.csv
     */
    public function copyContentToLanguageOfRelation(): void
    {
        parent::copyContentToLanguageOfRelation();
        $this->assertAssertionDataSet('copyContentToLanguageOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #2', 'Element #3'));
    }

    /**
     * @test
     * See DataSet/copyElementToLanguageOfRelation.csv
     */
    public function copyElementToLanguageOfRelation(): void
    {
        parent::copyElementToLanguageOfRelation();
        $this->assertAssertionDataSet('copyElementToLanguageOfRelation');

        //in this case the translated element is orphaned (no CE with relation to it, and it has no l10n_parent)
        //so on frontend there is no change.
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #1', 'Element #2'));
    }

    /**
     * @test
     * See DataSet/localizeContentOfRelation.csv
     */
    public function localizeContentOfRelation(): void
    {
        parent::localizeContentOfRelation();
        $this->assertAssertionDataSet('localizeContentOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #2', 'Element #3'));
    }

    /**
     * @test
     * See DataSet/localizeElementOfRelation.csv
     */
    public function localizeElementOfRelation(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage('pages', self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeElementOfRelation();
        $this->assertAssertionDataSet('localizeElementOfRelation');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('[Translate to Dansk:] Element #1', 'Element #2'));
    }

    /**
     * @test
     */
    public function localizeContentOfRelationWithLocalizeReferencesAtParentLocalization()
    {
        parent::localizeContentOfRelationWithLocalizeReferencesAtParentLocalization();
        $this->assertAssertionDataSet('localizeContentOfRelationWLocalizeReferencesAtParentLocalization');
    }

    /**
     * @test
     * See DataSet/moveContentOfRelationToDifferentPage.csv
     */
    public function moveContentOfRelationToDifferentPage(): void
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->assertAssertionDataSet('moveContentOfRelationToDifferentPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentElement)
            ->setTable(self::TABLE_Element)->setField('title')->setValues('Element #2', 'Element #3'));
    }
}
