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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Regular\WorkspacesPublishAll;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\Regular\AbstractActionWorkspacesTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\DoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureHasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
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
    public function createContents(): void
    {
        parent::createContents();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContents.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1', 'Testing #2'));
    }

    #[Test]
    public function createContentAndCopyContent(): void
    {
        parent::createContentAndCopyContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndCopyContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1', 'Testing #1 (copy 1)'));
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
    }

    #[Test]
    public function modifyContentWithTranslations(): void
    {
        parent::modifyContentWithTranslations();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyContentWithTranslations.csv');
    }

    #[Test]
    public function modifySoftDeletedContent(): void
    {
        parent::modifySoftDeletedContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifySoftDeletedContent.csv');
    }

    #[Test]
    public function modifyTranslatedContent(): void
    {
        parent::modifyTranslatedContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTranslatedContent.csv');
    }

    #[Test]
    public function modifyTranslatedContentThenModifyDefaultLanguageContent(): void
    {
        parent::modifyTranslatedContentThenModifyDefaultLanguageContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTranslatedContentThenModifyDefaultLanguageContent.csv');
    }

    #[Test]
    public function modifyTranslatedContentThenMoveDefaultLanguageContent(): void
    {
        parent::modifyTranslatedContentThenMoveDefaultLanguageContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTranslatedContentThenMoveDefaultLanguageContent.csv');
    }

    #[Test]
    public function hideContent(): void
    {
        parent::hideContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/hideContent.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    #[Test]
    public function hideContentAndMoveToDifferentPage(): void
    {
        parent::hideContentAndMoveToDifferentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/hideContentAndMoveToDifferentPage.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)
        );
        $responseSectionsSource = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsSource, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        self::assertThat($responseSectionsSource, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdTarget),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)
        );
        $responseSectionsTarget = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsTarget, (new HasRecordConstraint())
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
    }

    #[Test]
    public function copyContentToLanguage(): void
    {
        parent::copyContentToLanguage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguage.csv');

        // Set up "danish" to not have overlays - "free" mode
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', [], 'free'),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #3', '[Translate to Dansk:] Regular Element #2'));
    }

    #[Test]
    public function copyContentToLanguageFromNonDefaultLanguage(): void
    {
        parent::copyContentToLanguageFromNonDefaultLanguage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyContentToLanguageFromNonDefaultLanguage.csv');

        // Set up "german" to not have overlays - "free" mode
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', ['EN']),
                $this->buildLanguageConfiguration('DE', '/de/', [], 'free'),
            ]
        );

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageIdSecond));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Regular Element #3'));
    }

    #[Test]
    public function copyLocalizedContent(): void
    {
        parent::copyLocalizedContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyLocalizedContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1 (copy 1)'));

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1 (copy 1)'));

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageIdSecond));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1 (copy 1)'));
    }

    #[Test]
    public function copyLocalizedContentToLocalizedPage(): void
    {
        parent::copyLocalizedContentToLocalizedPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyLocalizedContentToLocalizedPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1'));

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget)->withLanguageId(self::VALUE_LanguageIdSecond));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1'));
    }

    #[Test]
    public function copyLocalizedContentToNonTranslatedPage(): void
    {
        parent::copyLocalizedContentToNonTranslatedPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyLocalizedContentToNonTranslatedPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1'));

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget)->withLanguageId(self::VALUE_LanguageIdSecond));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1'));
    }

    #[Test]
    public function copyLocalizedContentToPartiallyLocalizedPage(): void
    {
        parent::copyLocalizedContentToPartiallyLocalizedPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyLocalizedContentToPartiallyLocalizedPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1'));

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget)->withLanguageId(self::VALUE_LanguageIdSecond));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1'));
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
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1', '[Translate to Dansk:] Regular Element #2'));
    }

    #[Test]
    public function localizeContentWithLocalizationExclude(): void
    {
        parent::localizeContentWithLocalizationExclude();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentWExclude.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1', 'Testing #1'));
    }

    #[Test]
    public function localizeContentFromNonDefaultLanguage(): void
    {
        parent::localizeContentFromNonDefaultLanguage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/localizeContentFromNonDefaultLanguage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageIdSecond));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1', '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #3'));
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
    }

    #[Test]
    public function changeContentSortingAfterSelf(): void
    {
        parent::changeContentSortingAfterSelf();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentSortingAfterSelf.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
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
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdTarget));
        $responseSectionsTarget = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsTarget, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageIntoSiteModeFallback(): void
    {
        // Inherit site configuration from setUp(): DA fallback to EN
        parent::moveLanguageAllContentToDifferentPageInto();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageIntoSiteModeFallback.csv');
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        // Verify "Language all element" exists
        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())->setTable(self::TABLE_Content)->setField('header')->setValues('Language all element')
        );
        // Verify "Language all element" is output as first element
        self::assertEquals(
            self::VALUE_ContentLanguageAll,
            array_slice($responseSections[0]->getStructure()['pages:' . self::VALUE_PageId]['__contents'], 0, 1)['tt_content:' . self::VALUE_ContentLanguageAll]['uid']
        );
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageIntoSiteModeFree(): void
    {
        // Set up "danish" to not have overlays: "free" mode
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', [], 'free'),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        parent::moveLanguageAllContentToDifferentPageInto();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageIntoSiteModeFree.csv');
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        // Verify "Language all element" exists
        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())->setTable(self::TABLE_Content)->setField('header')->setValues('Language all element')
        );
        // Verify "Language all element" is output as first element
        self::assertEquals(
            self::VALUE_ContentLanguageAll,
            array_slice($responseSections[0]->getStructure()['pages:' . self::VALUE_PageId]['__contents'], 0, 1)['tt_content:' . self::VALUE_ContentLanguageAll]['uid']
        );
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageIntoSiteModeStrict(): void
    {
        // Set up "danish" to "strict" mode
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        parent::moveLanguageAllContentToDifferentPageInto();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageIntoSiteModeStrict.csv');
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        // Verify "Language all element" exists
        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())->setTable(self::TABLE_Content)->setField('header')->setValues('Language all element')
        );
        // Verify "Language all element" is output as first element
        self::assertEquals(
            self::VALUE_ContentLanguageAll,
            array_slice($responseSections[0]->getStructure()['pages:' . self::VALUE_PageId]['__contents'], 0, 1)['tt_content:' . self::VALUE_ContentLanguageAll]['uid']
        );
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageAfterSiteModeFallback(): void
    {
        // Inherit site configuration from setUp(): DA fallback to EN
        parent::moveLanguageAllContentToDifferentPageAfter();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageAfterSiteModeFallback.csv');
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        // Verify "Language all element" exists
        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())->setTable(self::TABLE_Content)->setField('header')->setValues('Language all element')
        );
        // Verify "Language all element" is output as second element
        self::assertEquals(
            self::VALUE_ContentLanguageAll,
            array_slice($responseSections[0]->getStructure()['pages:' . self::VALUE_PageId]['__contents'], 1, 1)['tt_content:' . self::VALUE_ContentLanguageAll]['uid']
        );
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageAfterSiteModeFree(): void
    {
        // Set up "danish" to not have overlays: "free" mode
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', [], 'free'),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        parent::moveLanguageAllContentToDifferentPageAfter();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageAfterSiteModeFree.csv');
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        // Verify "Language all element" exists
        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())->setTable(self::TABLE_Content)->setField('header')->setValues('Language all element')
        );
        // Verify "Language all element" is output as second element
        self::assertEquals(
            self::VALUE_ContentLanguageAll,
            array_slice($responseSections[0]->getStructure()['pages:' . self::VALUE_PageId]['__contents'], 1, 1)['tt_content:' . self::VALUE_ContentLanguageAll]['uid']
        );
    }

    #[Test]
    public function moveLanguageAllContentToDifferentPageAfterSiteModeStrict(): void
    {
        // Set up "danish" to "strict" mode
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        parent::moveLanguageAllContentToDifferentPageAfter();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLanguageAllContentToDifferentPageAfterSiteModeStrict.csv');
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        // Verify "Language all element" exists
        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())->setTable(self::TABLE_Content)->setField('header')->setValues('Language all element')
        );
        // Verify "Language all element" is output as second element
        self::assertEquals(
            self::VALUE_ContentLanguageAll,
            array_slice($responseSections[0]->getStructure()['pages:' . self::VALUE_PageId]['__contents'], 1, 1)['tt_content:' . self::VALUE_ContentLanguageAll]['uid']
        );
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
    }

    #[Test]
    public function moveContentToDifferentPageAndHide(): void
    {
        parent::moveContentToDifferentPageAndHide();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveContentToDifferentPageAndHide.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdTarget),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    #[Test]
    public function moveLocalizedContentToDifferentPage(): void
    {
        parent::moveLocalizedContentToDifferentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveLocalizedContentToDifferentPage.csv');

        // Check if the regular LIVE page does NOT contain the moved record anymore
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
        );
        $responseSectionsSource = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsSource, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #3'));

        // Check the regular LIVE translated page does NOT contain the moved record anymore
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
        );
        $responseSectionsSource = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsSource, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #3'));

        // Check if the target LIVE page DOES contain the moved record
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdTarget)
        );
        $responseSectionsTarget = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsTarget, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #3'));

        // Also test the translated LIVE page, and make sure the translated record is also returned
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdTarget)->withLanguageId(self::VALUE_LanguageId)
        );
        $responseSectionsTarget = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsTarget, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #3'));
    }

    #[Test]
    public function createPage(): void
    {
        parent::createPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    #[Test]
    public function createPageAndSubPageAndSubPageContent(): void
    {
        parent::createPageAndSubPageAndSubPageContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndSubPageAndSubPageContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newSubPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1 #1'));
    }

    #[Test]
    public function modifyPage(): void
    {
        parent::modifyPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    #[Test]
    public function modifyTranslatedPage(): void
    {
        parent::modifyTranslatedPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTranslatedPage.csv');
    }

    #[Test]
    public function modifyTranslatedPageThenModifyPage(): void
    {
        parent::modifyTranslatedPageThenModifyPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/modifyTranslatedPageThenModifyPage.csv');
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
    }
    #[Test]
    public function copyPageRecursively(): void
    {
        parent::copyPageRecursively();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/copyPageRecursively.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['newPageId']));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('DataHandlerTest (copy 1)'));
    }

    #[Test]
    public function createPageAndChangePageSorting(): void
    {
        parent::createPageAndChangePageSorting();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndChangePageSorting.csv');
    }

    #[Test]
    public function createPageAndMoveCreatedPage(): void
    {
        parent::createPageAndMoveCreatedPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndMoveCreatedPage.csv');
    }

    #[Test]
    public function changePageSorting(): void
    {
        parent::changePageSorting();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changePageSorting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    #[Test]
    public function changePageSortingAfterSelf(): void
    {
        parent::changePageSortingAfterSelf();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changePageSortingAfterSelf.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    #[Test]
    public function movePageToDifferentPage(): void
    {
        parent::movePageToDifferentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    #[Test]
    public function movePageToDifferentPageTwice(): void
    {
        parent::movePageToDifferentPageTwice();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPageTwice.csv');
    }

    #[Test]
    public function movePageToDifferentPageAndChangeSorting(): void
    {
        parent::movePageToDifferentPageAndChangeSorting();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPageNChangeSorting.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId));
        $responseSectionsPage = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsPage, (new HasRecordConstraint())
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSectionsPage, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdWebsite));
        $responseSectionsWebsite = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsWebsite, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageIdWebsite)->setRecordField('__pages')
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Target', 'Relations', 'DataHandlerTest'));
    }

    #[Test]
    public function movePageToDifferentPageAndCreatePageAfterMovedPage(): void
    {
        parent::movePageToDifferentPageAndCreatePageAfterMovedPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/movePageToDifferentPageNCreatePageAfterMovedPage.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageIdWebsite));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageIdWebsite)->setRecordField('__pages')
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Target', 'Testing #1', 'DataHandlerTest'));
    }

    #[Test]
    public function createContentAndCopyDraftPage(): void
    {
        parent::createContentAndCopyDraftPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndCopyDraftPage.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, (new HasRecordConstraint())
            ->setTable(static::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    #[Test]
    public function createPageAndCopyDraftParentPage(): void
    {
        parent::createPageAndCopyDraftParentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPageAndCopyDraftParentPage.csv');
    }

    #[Test]
    public function createNestedPagesAndCopyDraftParentPage(): void
    {
        parent::createNestedPagesAndCopyDraftParentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createNestedPagesAndCopyDraftParentPage.csv');
    }

    #[Test]
    public function createContentAndLocalize(): void
    {
        parent::createContentAndLocalize();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createContentAndLocalize.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Testing #1'));
    }

    #[Test]
    public function changeContentSortingAndCopyDraftPage(): void
    {
        parent::changeContentSortingAndCopyDraftPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/changeContentSortingAndCopyDraftPage.csv');
    }

    #[Test]
    public function createPlaceholdersAndDeleteDraftParentPage(): void
    {
        parent::createPlaceholdersAndDeleteDraftParentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPlaceholdersAndDeleteDraftParentPage.csv');
    }

    #[Test]
    public function createPlaceholdersAndDeleteLiveParentPage(): void
    {
        parent::createPlaceholdersAndDeleteLiveParentPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/createPlaceholdersAndDeleteLiveParentPage.csv');
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
    public function deletePage(): void
    {
        parent::deletePage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deletePage.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)
        );
        self::assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function deleteContentAndPage(): void
    {
        parent::deleteContentAndPage();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteContentAndPage.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)
        );
        self::assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function deleteLocalizedContentAndDeleteContent(): void
    {
        parent::deleteLocalizedContentAndDeleteContent();
        $this->actionService->publishWorkspace(self::VALUE_WorkspaceId);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/deleteLocalizedContentNDeleteContent.csv');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #3', '[Translate to Dansk:] Regular Element #3', 'Regular Element #1'));
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1', 'Regular Element #2'));
    }
}
