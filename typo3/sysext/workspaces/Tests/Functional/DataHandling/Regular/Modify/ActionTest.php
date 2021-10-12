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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Regular\Modify;

use TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Regular\AbstractActionTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Regular/Modify/DataSet/';

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
    public function createContents(): void
    {
        parent::createContents();
        $this->assertAssertionDataSet('createContents');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1', 'Testing #2'));
    }

    /**
     * @test
     */
    public function createContentAndCopyContent(): void
    {
        parent::createContentAndCopyContent();
        $this->assertAssertionDataSet('createContentAndCopyContent');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1', 'Testing #1 (copy 1)'));
    }

    /**
     * @test
     */
    public function createContentAndLocalize(): void
    {
        parent::createContentAndLocalize();
        $this->assertAssertionDataSet('createContentAndLocalize');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Testing #1'));
    }

    /**
     * @test
     */
    public function modifyContent(): void
    {
        parent::modifyContent();
        $this->assertAssertionDataSet('modifyContent');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function hideContent(): void
    {
        parent::hideContent();
        $this->assertAssertionDataSet('hideContent');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     */
    public function hideContentAndMoveToDifferentPage(): void
    {
        parent::hideContent();
        parent::moveContentToDifferentPage();
        $this->assertAssertionDataSet('hideContentAndMoveToDifferentPage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsSource = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsSource, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        self::assertThat($responseSectionsSource, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdTarget),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsTarget = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsTarget, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     */
    public function deleteContent(): void
    {
        parent::deleteContent();
        $this->assertAssertionDataSet('deleteContent');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     */
    public function deleteLocalizedContentAndDeleteContent(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::deleteLocalizedContentAndDeleteContent();
        $this->assertAssertionDataSet('deleteLocalizedContentNDeleteContent');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #3', '[Translate to Dansk:] Regular Element #3', 'Regular Element #1'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function copyContent(): void
    {
        parent::copyContent();
        $this->assertAssertionDataSet('copyContent');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2 (copy 1)'));
    }

    /**
     * @test
     */
    public function copyContentToLanguage(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::copyContentToLanguage();
        $this->assertAssertionDataSet('copyContentToLanguage');

        // Set up "dk" to not have overlays
        $languageConfiguration = $this->siteLanguageConfiguration;
        $languageConfiguration[self::VALUE_LanguageId]['fallbackType'] = 'free';
        $this->setUpFrontendSite(1, $languageConfiguration);
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #3', '[Translate to Dansk:] Regular Element #2'));
    }

    /**
     * @test
     */
    public function copyContentToLanguageFromNonDefaultLanguage(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageIdSecond);
        parent::copyContentToLanguageFromNonDefaultLanguage();
        $this->assertAssertionDataSet('copyContentToLanguageFromNonDefaultLanguage');

        // Set up "de" to not have overlays
        $languageConfiguration = $this->siteLanguageConfiguration;
        $languageConfiguration[self::VALUE_LanguageIdSecond]['fallbackType'] = 'free';
        $this->setUpFrontendSite(1, $languageConfiguration);
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageIdSecond),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Regular Element #3'));
    }

    /**
     * @test
     */
    public function localizeContent(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeContent();
        $this->assertAssertionDataSet('localizeContent');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1', '[Translate to Dansk:] Regular Element #2'));
    }

    /**
     * @test
     */
    public function localizeContentAfterMovedContent(): void
    {
        parent::localizeContentAfterMovedContent();
        $this->assertAssertionDataSet('localizeContentAfterMovedContent');
    }

    /**
     * @test
     */
    public function localizeContentAfterMovedInLiveContent(): void
    {
        parent::localizeContentAfterMovedInLiveContent();
        $this->assertAssertionDataSet('localizeContentAfterMovedInLiveContent');
    }

    /**
     * @test
     * @see \TYPO3\CMS\Core\Migrations\TcaMigration::sanitizeControlSectionIntegrity()
     */
    public function localizeContentWithEmptyTcaIntegrityColumns(): void
    {
        parent::localizeContentWithEmptyTcaIntegrityColumns();
        $this->assertAssertionDataSet('localizeContentWithEmptyTcaIntegrityColumns');
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1', '[Translate to Dansk:] Regular Element #2'));
    }

    /**
     * @test
     */
    public function localizeContentWithHideAtCopy(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        parent::localizeContentWithHideAtCopy();
        $this->assertAssertionDataSet('localizeContentWHideAtCopy');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #1', '[Translate to Dansk:] Regular Element #2'));
    }

    /**
     * @test
     */
    public function localizeContentFromNonDefaultLanguage(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageIdSecond);
        parent::localizeContentFromNonDefaultLanguage();

        $this->assertAssertionDataSet('localizeContentFromNonDefaultLanguage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageIdSecond),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1', '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #3'));
    }

    /**
     * @test
     */
    public function changeContentSorting(): void
    {
        parent::changeContentSorting();
        $this->assertAssertionDataSet('changeContentSorting');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function changeContentSortingAfterSelf(): void
    {
        parent::changeContentSortingAfterSelf();
        $this->assertAssertionDataSet('changeContentSortingAfterSelf');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     * @todo: Publish and PublishAll for this are missing - TF throws an exception on publish due to deleted state
     */
    public function changeContentSortingAndDeleteMovedRecord(): void
    {
        parent::changeContentSortingAndDeleteMovedRecord();
        $this->assertAssertionDataSet('changeContentSortingNDeleteMovedRecord');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function changeContentSortingAndDeleteLiveRecord(): void
    {
        parent::changeContentSortingAndDeleteLiveRecord();
        $this->assertAssertionDataSet('changeContentSortingNDeleteLiveRecord');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
    }

    /**
     * @test
     */
    public function moveContentToDifferentPage(): void
    {
        parent::moveContentToDifferentPage();
        $this->assertAssertionDataSet('moveContentToDifferentPage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsSource = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsSource, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdTarget),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsTarget = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsTarget, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndChangeSorting(): void
    {
        parent::moveContentToDifferentPageAndChangeSorting();
        $this->assertAssertionDataSet('moveContentToDifferentPageNChangeSorting');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdTarget),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndHide(): void
    {
        parent::moveContentToDifferentPageAndHide();
        $this->assertAssertionDataSet('moveContentToDifferentPageAndHide');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdTarget),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * Page records
     */

    /**
     * @test
     */
    public function createPage(): void
    {
        parent::createPage();
        $this->assertAssertionDataSet('createPage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['newPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function createPageAndSubPageAndSubPageContent(): void
    {
        parent::createPageAndSubPageAndSubPageContent();
        $this->assertAssertionDataSet('createPageAndSubPageAndSubPageContent');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['newSubPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1 #1'));
    }

    /**
     * @test
     */
    public function createPageAndContentWithTcaDefaults(): void
    {
        parent::createPageAndContentWithTcaDefaults();
        $this->assertAssertionDataSet('createPageNContentWDefaults');

        // first, assert that page cannot be opened without using backend user (since it's hidden)
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId($this->recordIds['newPageId'])
        );
        self::assertSame(404, $response->getStatusCode());

        // then, assert if preview is possible using a backend user
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId($this->recordIds['newPageId']),
            (new InternalRequestContext())
                ->withBackendUserId(self::VALUE_BackendUserId)
                ->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())
            ->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function modifyPage(): void
    {
        parent::modifyPage();
        $this->assertAssertionDataSet('modifyPage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function deletePage(): void
    {
        parent::deletePage();
        $this->assertAssertionDataSet('deletePage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function deleteContentAndPage(): void
    {
        parent::deleteContentAndPage();
        $this->assertAssertionDataSet('deleteContentAndPage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     * Publish tests missing since TF throws exception if publishing deleted records
     */
    public function localizePageAndContentsAndDeletePageLocalization(): void
    {
        // Create localized page and localize content elements first
        parent::localizePageAndContentsAndDeletePageLocalization();
        $this->assertAssertionDataSet('localizePageAndContentsAndDeletePageLocalization');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['localizedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function localizeNestedPagesAndContents(): void
    {
        parent::localizeNestedPagesAndContents();
        $this->assertAssertionDataSet('localizeNestedPagesAndContents');
    }

    /**
     * @test
     */
    public function copyPage(): void
    {
        parent::copyPage();
        $this->assertAssertionDataSet('copyPage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['newPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
    }

    /**
     * @test
     */
    public function copyPageFreeMode(): void
    {
        parent::copyPageFreeMode();
        $this->assertAssertionDataSet('copyPageFreeMode');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['newPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Target'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #10'));
    }

    /**
     * @test
     */
    public function localizePage(): void
    {
        parent::localizePage();
        $this->assertAssertionDataSet('localizePage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('[Translate to Dansk:] Relations'));
    }

    /**
     * @test
     */
    public function localizePageHiddenHideAtCopyFalse(): void
    {
        parent::localizePageHiddenHideAtCopyFalse();
        $this->assertAssertionDataSet('localizePageHiddenHideAtCopyFalse');
    }

    /**
     * @test
     */
    public function localizePageNotHiddenHideAtCopyFalse(): void
    {
        parent::localizePageNotHiddenHideAtCopyFalse();
        $this->assertAssertionDataSet('localizePageNotHiddenHideAtCopyFalse');
    }

    /**
     * @test
     */
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset();
        $this->assertAssertionDataSet('localizePageNotHiddenHideAtCopyDisableHideAtCopyUnset');
    }

    /**
     * @test
     */
    public function localizePageHiddenHideAtCopyDisableHideAtCopyUnset(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopyUnset();
        $this->assertAssertionDataSet('localizePageHiddenHideAtCopyDisableHideAtCopyUnset');
    }

    /**
     * @test
     */
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse();
        $this->assertAssertionDataSet('localizePageNotHiddenHideAtCopyDisableHideAtCopySetToFalse');
    }

    /**
     * @test
     */
    public function localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse();
        $this->assertAssertionDataSet('localizePageHiddenHideAtCopyDisableHideAtCopySetToFalse');
    }

    /**
     * @test
     */
    public function localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue(): void
    {
        parent::localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue();
        $this->assertAssertionDataSet('localizePageNotHiddenHideAtCopyDisableHideAtCopySetToTrue');
    }

    /**
     * @test
     */
    public function localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue(): void
    {
        parent::localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue();
        $this->assertAssertionDataSet('localizePageHiddenHideAtCopyDisableHideAtCopySetToTrue');
    }

    /**
     * @test
     */
    public function createPageAndChangePageSorting(): void
    {
        parent::createPageAndChangePageSorting();
        $this->assertAssertionDataSet('createPageAndChangePageSorting');
    }

    /**
     * @test
     */
    public function createPageAndMoveCreatedPage(): void
    {
        parent::createPageAndMoveCreatedPage();
        $this->assertAssertionDataSet('createPageAndMoveCreatedPage');
    }

    /**
     * @test
     */
    public function changePageSorting(): void
    {
        parent::changePageSorting();
        $this->assertAssertionDataSet('changePageSorting');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function changePageSortingAfterSelf(): void
    {
        parent::changePageSortingAfterSelf();
        $this->assertAssertionDataSet('changePageSortingAfterSelf');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function movePageToDifferentPage(): void
    {
        parent::movePageToDifferentPage();
        $this->assertAssertionDataSet('movePageToDifferentPage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
    }

    /**
     * @test
     */
    public function movePageToDifferentPageTwice(): void
    {
        parent::movePageToDifferentPageTwice();
        $this->assertAssertionDataSet('movePageToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedToDifferentPageTwice();
        $this->assertAssertionDataSet('movePageLocalizedToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveToDifferentPageTwice();
        $this->assertAssertionDataSet('movePageLocalizedInLiveToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice();
        $this->assertAssertionDataSet('movePageLocalizedInLiveWorkspaceChangedToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice(): void
    {
        parent::movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice();
        $this->assertAssertionDataSet('movePageLocalizedInLiveWorkspaceDeletedToDifferentPageTwice');
    }

    /**
     * @test
     */
    public function movePageToDifferentPageAndChangeSorting(): void
    {
        parent::movePageToDifferentPageAndChangeSorting();
        $this->assertAssertionDataSet('movePageToDifferentPageNChangeSorting');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsPage = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsPage, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        self::assertThat($responseSectionsPage, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdWebsite),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsWebsite = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsWebsite, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageIdWebsite)->setRecordField('__pages')
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Target', 'Relations', 'DataHandlerTest'));
    }

    /**
     * @test
     * @see https://forge.typo3.org/issues/33104
     * @see https://forge.typo3.org/issues/55573
     */
    public function movePageToDifferentPageAndCreatePageAfterMovedPage(): void
    {
        parent::movePageToDifferentPageAndCreatePageAfterMovedPage();
        $this->assertAssertionDataSet('movePageToDifferentPageNCreatePageAfterMovedPage');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageIdWebsite),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Page . ':' . self::VALUE_PageIdWebsite)->setRecordField('__pages')
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Target', 'Testing #1', 'DataHandlerTest'));
    }

    /*************************************
     * Copying page contents and sub-pages
     *************************************/

    /**
     * @test
     */
    public function createContentAndCopyDraftPage(): void
    {
        parent::createContentAndCopyDraftPage();
        $this->assertAssertionDataSet('createContentAndCopyDraftPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        self::assertStringContainsString('The requested page does not exist', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionHasRecordConstraint()
            ->setTable(static::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * Test does not make sense for Publish, PublishAll and Discard
     */
    public function createContentAndCopyLivePage(): void
    {
        parent::createContentAndCopyLivePage();
        $this->assertAssertionDataSet('createContentAndCopyLivePage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        $responseSectionsLive = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsLive, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(static::TABLE_Content)->setField('header')->setValues('Testing #1'));
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(static::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     */
    public function createPageAndCopyDraftParentPage(): void
    {
        parent::createPageAndCopyDraftParentPage();
        $this->assertAssertionDataSet('createPageAndCopyDraftParentPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        self::assertStringContainsString('The requested page does not exist', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionHasRecordConstraint()
            ->setTable(static::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     * Test does not make sense for Publish, PublishAll and Discard
     */
    public function createPageAndCopyLiveParentPage(): void
    {
        parent::createPageAndCopyLiveParentPage();
        $this->assertAssertionDataSet('createPageAndCopyLiveParentPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        $responseSectionsLive = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsLive, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(static::TABLE_Page)->setField('title')->setValues('Testing #1'));
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(static::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     * Skipping test in Publish but it is available in PublishAll
     */
    public function createNestedPagesAndCopyDraftParentPage(): void
    {
        parent::createNestedPagesAndCopyDraftParentPage();
        $this->assertAssertionDataSet('createNestedPagesAndCopyDraftParentPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        self::assertStringContainsString('The requested page does not exist', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionHasRecordConstraint()
            ->setTable(static::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     * Test does not make sense for Publish, PublishAll and Discard
     */
    public function createNestedPagesAndCopyLiveParentPage(): void
    {
        parent::createNestedPagesAndCopyLiveParentPage();
        $this->assertAssertionDataSet('createNestedPagesAndCopyLiveParentPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        $responseSectionsLive = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsLive, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(static::TABLE_Page)->setField('title')->setValues('Testing #1'));
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(static::TABLE_Page)->setField('title')->setValues('Testing #1'));
    }

    /**
     * @test
     * Skipped test in Publish, PublishAll and Discard: It's only interesting if the copy operation copies the deleted record
     */
    public function deleteContentAndCopyDraftPage(): void
    {
        parent::deleteContentAndCopyDraftPage();
        $this->assertAssertionDataSet('deleteContentAndCopyDraftPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        self::assertStringContainsString('The requested page does not exist', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     * Skipped test in Publish, PublishAll and Discard: It's only interesting if the copy operation copies the deleted record
     */
    public function deleteContentAndCopyLivePage(): void
    {
        parent::deleteContentAndCopyLivePage();
        $this->assertAssertionDataSet('deleteContentAndCopyLivePage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        $responseSectionsLive = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsLive, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
    }

    /**
     * @test
     * Test skipped in publish and discard, but exists in PublishAll
     */
    public function changeContentSortingAndCopyDraftPage(): void
    {
        parent::changeContentSortingAndCopyDraftPage();
        $this->assertAssertionDataSet('changeContentSortingAndCopyDraftPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        self::assertStringContainsString('The requested page does not exist', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
    }

    /**
     * @test
     * Test does not make sense for Publish, PublishAll and Discard
     */
    public function changeContentSortingAndCopyLivePage(): void
    {
        parent::changeContentSortingAndCopyLivePage();
        $this->assertAssertionDataSet('changeContentSortingAndCopyLivePage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        $responseSectionsLive = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsLive, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1'));
    }

    /**
     * @test
     * Skipped test in Publish, PublishAll and Discard: It's only interesting if the move operation does sane things
     */
    public function moveContentAndCopyDraftPage(): void
    {
        parent::moveContentAndCopyDraftPage();
        $this->assertAssertionDataSet('moveContentAndCopyDraftPage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        self::assertStringContainsString('The requested page does not exist', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        self::assertThat($responseSectionsDraft, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #0'));
    }

    /**
     * @test
     * Test does not make sense for Publish, PublishAll and Discard
     */
    public function moveContentAndCopyLivePage(): void
    {
        parent::moveContentAndCopyLivePage();
        $this->assertAssertionDataSet('moveContentAndCopyLivePage');

        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($this->recordIds['copiedPageId']));
        $responseSectionsLive = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsLive, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        self::assertThat($responseSectionsLive, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #0'));
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId($this->recordIds['copiedPageId']),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSectionsDraft = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSectionsDraft, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #2'));
        self::assertThat($responseSectionsDraft, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #0'));
    }

    /**
     * @test
     */
    public function createPlaceholdersAndDeleteDraftParentPage(): void
    {
        parent::createPlaceholdersAndDeleteDraftParentPage();
        $this->assertAssertionDataSet('createPlaceholdersAndDeleteDraftParentPage');
    }

    /**
     * @test
     * Test does not make much sense in Publish and Discard and is skipped there
     */
    public function createPlaceholdersAndDeleteLiveParentPage(): void
    {
        parent::createPlaceholdersAndDeleteLiveParentPage();
        $this->assertAssertionDataSet('createPlaceholdersAndDeleteLiveParentPage');
    }

    /**
     * @test
     * Test does not make sense in Publish, PublishAll and Discard scenarios and is skipped there
     */
    public function createLocalizedNotHiddenWorkspaceContentHiddenInLive(): void
    {
        // Create translated page first
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        // Have a hidden live content element
        $this->setWorkspaceId(0);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, ['hidden' => 1]);
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
        // Create a non-hidden workspace overlay
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, ['hidden' => 0]);
        // Confirm db state is as expected for this scenario
        $this->assertAssertionDataSet('createLocalizedNotHiddenWorkspaceContentHiddenInLive');
        // Get the FE preview and verify content element is shown
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(self::VALUE_PageId)->withLanguageId(self::VALUE_LanguageId),
            (new InternalRequestContext())->withBackendUserId(self::VALUE_BackendUserId)->withWorkspaceId(self::VALUE_WorkspaceId)
        );
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections();
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('[Translate to Dansk:] Regular Element #3'));
    }
}
