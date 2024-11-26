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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\FAL\WorkspacesPublishAll;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\FAL\AbstractActionWorkspacesTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\DoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureDoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureHasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

final class ActionTest extends AbstractActionWorkspacesTestCase
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
    public function modifyContent(): void
    {
        parent::modifyContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(true));
    }

    #[Test]
    public function deleteContent(): void
    {
        parent::deleteContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        self::assertThat($responseSections, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    #[Test]
    public function copyContent(): void
    {
        parent::copyContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2 (copy 1)'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['copiedContentId'])->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(true));
    }

    #[Test]
    public function localizeContent(): void
    {
        parent::localizeContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', '[Translate to Dansk:] Regular Element #2'));

        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('[Translate to Dansk:] This is Kasper', '[Translate to Dansk:] Taken at T3BOARD')->setStrict(true));
    }

    #[Test]
    public function localizeLiveModifyWsDefaultLang(): void
    {
        parent::localizeLiveModifyWsDefaultLang();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeLiveModifyWsDefaultLang.csv');
    }

    #[Test]
    public function localizeLiveModifyWsLocalization(): void
    {
        parent::localizeLiveModifyWsLocalization();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeLiveModifyWsLocalization.csv');
    }

    #[Test]
    public function localizeLiveModifyWsLocalizationAddLive(): void
    {
        parent::localizeLiveModifyWsLocalizationAddLive();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeLiveModifyWsLocalizationAddLive.csv');
    }

    #[Test]
    public function localizeLiveModifyWsLocalizationAddLiveWsSync(): void
    {
        parent::localizeLiveModifyWsLocalizationAddLiveWsSync();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeLiveModifyWsLocalizationAddLiveWsSync.csv');
    }

    #[Test]
    public function modifyContentLocalize(): void
    {
        parent::modifyContentLocalize();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentLocalize.csv');
    }

    #[Test]
    public function modifyContentLocalizeAddDefaultLangRelation(): void
    {
        parent::modifyContentLocalizeAddDefaultLangRelation();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentLocalizeAddDefaultLangRelation.csv');
    }

    #[Test]
    public function modifyContentLocalizeAddDefaultLangRelationSynchronize(): void
    {
        parent::modifyContentLocalizeAddDefaultLangRelationSynchronize();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentLocalizeAddDefaultLangRelationSynchronize.csv');
    }

    #[Test]
    public function changeContentSorting(): void
    {
        parent::changeContentSorting();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentSorting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Kasper', 'T3BOARD'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(true));
    }

    #[Test]
    public function moveContentToDifferentPage(): void
    {
        parent::moveContentToDifferentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSectionsSource = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsSource, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        self::assertThat($responseSectionsSource, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Kasper', 'T3BOARD')->setStrict(true));
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSectionsTarget = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsTarget, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        self::assertThat($responseSectionsTarget, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(true));
    }

    #[Test]
    public function moveContentToDifferentPageAndChangeSorting(): void
    {
        parent::moveContentToDifferentPageAndChangeSorting();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPageNChangeSorting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Kasper', 'T3BOARD')->setStrict(true));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper', 'Taken at T3BOARD')->setStrict(true));
    }

    #[Test]
    public function createContentWithFileReference(): void
    {
        parent::createContentWithFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentWFileReference.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Image #1')->setStrict(true));
    }

    #[Test]
    public function modifyContentWithFileReference(): void
    {
        parent::modifyContentWithFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentWFileReference.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD', 'Image #1')->setStrict(true));
    }

    #[Test]
    public function modifyContentAndAddFileReference(): void
    {
        parent::modifyContentAndAddFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentNAddFileReference.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD', 'This is Kasper', 'Image #3')->setStrict(true));
    }

    #[Test]
    public function modifyContentAndDeleteFileReference(): void
    {
        parent::modifyContentAndDeleteFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentNDeleteFileReference.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('This is Kasper')->setStrict(true));
        self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD'));
    }

    #[Test]
    public function modifyContentAndDeleteAllFileReference(): void
    {
        parent::modifyContentAndDeleteAllFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentNDeleteAllFileReference.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField(self::FIELD_ContentImage)
            ->setTable(self::TABLE_FileReference)->setField('title')->setValues('Taken at T3BOARD', 'This is Kasper'));
    }

    #[Test]
    public function modifyContentDeleteFileRefAddFileRefCopyElement(): void
    {
        parent::modifyContentDeleteFileRefAddFileRefCopyElement();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentDeleteFileRefAddFileRefCopyElement.csv');
    }

    #[Test]
    public function createContentWithFileReferenceAndDeleteFileReference(): void
    {
        parent::createContentWithFileReferenceAndDeleteFileReference();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentWFileReferenceNDeleteFileReference.csv');
        // No FE test: Create and delete scenarios have FE coverage, this test is only about DB state.
    }
}
