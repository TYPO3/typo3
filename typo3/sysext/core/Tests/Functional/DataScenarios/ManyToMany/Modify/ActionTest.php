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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\ManyToMany\Modify;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\ManyToMany\AbstractActionTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\DoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureDoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureHasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

final class ActionTest extends AbstractActionTestCase
{
    #[Test]
    public function verifyCleanReferenceIndex(): void
    {
        // Fix refindex, then compare with import csv again to verify nothing changed.
        // This is to make sure the import csv is 'clean' - important for the other tests.
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(self::SCENARIO_DataSet);
    }

    #[Test]
    public function addSurfRelation(): void
    {
        parent::addSurfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/addSurfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf A', 'Surf B', 'Surf A.A'));
    }

    #[Test]
    public function createSurfAndAddRelation(): void
    {
        parent::createSurfAndAddRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createSurfAndAddRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf A', 'Surf B', 'Surfing #1'));
    }

    #[Test]
    public function deleteSurfRelation(): void
    {
        parent::deleteSurfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteSurfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf A'));
        self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf B', 'Surf C', 'Surf A.A'));
    }

    #[Test]
    public function changeSurfRelationSorting(): void
    {
        // @todo: Needs patch. Import data set should have sorting 1 for 29-298 in mm, then DH needs to
        //        be fixed to trigger update of refindex properly on local-side resort. Workspaces may or
        //        may not need adaption as well, at least the import has a dupe sorting as well.
        self::markTestSkipped('currently disabled since DH does not update refindex properly');
        parent::changeSurfRelationSorting(); // @phpstan-ignore-line
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeSurfRelationSorting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf A', 'Surf B'));
    }

    #[Test]
    public function modifySurfOfRelation(): void
    {
        parent::modifySurfOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifySurfOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surfing #1', 'Surf B'));
    }

    #[Test]
    public function modifyContentOfRelation(): void
    {
        parent::modifyContentOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Surfing #1'));
    }

    #[Test]
    public function modifyBothsOfRelation(): void
    {
        parent::modifyBothsOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyBothsOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surfing #1', 'Surf B'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Surfing #1'));
    }

    #[Test]
    public function deleteContentOfRelation(): void
    {
        parent::deleteContentOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Surfing #1'));
    }

    #[Test]
    public function deleteSurfOfRelation(): void
    {
        parent::deleteSurfOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteSurfOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf A'));
    }

    #[Test]
    public function copyContentOfRelation(): void
    {
        parent::copyContentOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function copySurfOfRelation(): void
    {
        parent::copySurfOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copySurfOfRelation.csv');
    }

    #[Test]
    public function copyContentToLanguageOfRelation(): void
    {
        parent::copyContentToLanguageOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguageOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function copySurfToLanguageOfRelation(): void
    {
        parent::copySurfToLanguageOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copySurfToLanguageOfRelation.csv');
        //in this case the translated element is orphaned (no CE with relation to it, and it has no l10n_parent)
        //so on frontend there is no change.
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf A', 'Surf B'));
    }

    #[Test]
    public function localizeContentOfRelation(): void
    {
        parent::localizeContentOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function localizeContentOfRelationWithLanguageSynchronization(): void
    {
        parent::localizeContentOfRelationWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationWSynchronization.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function localizeContentOfRelationWithLanguageExclude(): void
    {
        parent::localizeContentOfRelationWithLanguageExclude();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationWExclude.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function localizeContentOfRelationAndAddSurfWithLanguageSynchronization(): void
    {
        parent::localizeContentOfRelationAndAddSurfWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentOfRelationNAddSurfWSynchronization.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf B', 'Surf C', 'Surf A.A'));
    }

    #[Test]
    public function localizeContentChainOfRelationAndAddSurfWithLanguageSynchronization(): void
    {
        parent::localizeContentChainOfRelationAndAddSurfWithLanguageSynchronization();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentChainOfRelationNAddSurfWSynchronization.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageIdSecond));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf B', 'Surf C', 'Surf A.A'));
    }

    #[Test]
    public function localizeSurfOfRelation(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeSurfOfRelation();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeSurfOfRelation.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('[Translate to Dansk:] Surf A', 'Surf B'));
    }

    #[Test]
    public function moveContentOfRelationToDifferentPage(): void
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentOfRelationToDifferentPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf B', 'Surf C'));
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdFirst'])->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf A', 'Surf B'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdLast'])->setRecordField(self::FIELD_Surfing)
            ->setTable(self::TABLE_Surf)->setField('title')->setValues('Surf B', 'Surf C'));
    }
}
