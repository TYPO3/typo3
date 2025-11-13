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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Wizard;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\Wizard\LocalizationController;
use TYPO3\CMS\Backend\Localization\LocalizationMode;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for LocalizationController (Translation Wizard)
 *
 * Tests the JSON API endpoints of the new translation wizard controller.
 * For actual localization logic tests, see ManualLocalizationHandlerTest.
 */
final class LocalizationControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
        'DE' => ['id' => 2, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF-8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Controller/Page/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Controller/Page/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Controller/Page/Fixtures/tt_content-default-language.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('DE', '/de'),
            ]
        );
    }

    #[Test]
    public function getRecordReturnsRecordInformation(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'tt_content',
            'recordUid' => 1,
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getRecord($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        self::assertEquals(1, $result['uid']);
        self::assertEquals('tt_content', $result['type']);
        self::assertArrayHasKey('title', $result);
        self::assertArrayHasKey('icon', $result);
        self::assertArrayHasKey('typeName', $result);
    }

    #[Test]
    public function getRecordReturns404ForNonExistentRecord(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'tt_content',
            'recordUid' => 99999,
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getRecord($request);

        self::assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function getRecordReturns400WithoutRequiredParameters(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'tt_content',
            // Missing recordUid
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getRecord($request);

        self::assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function getTargetsReturnsAvailableLanguages(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'tt_content',
            'recordUid' => 1,
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getTargets($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        // Should return languages 1 and 2 (DA and DE), but not 0 (default)
        self::assertCount(2, $result);
        self::assertEquals(1, $result[0]['uid']);
        self::assertEquals(2, $result[1]['uid']);
    }

    #[Test]
    public function getSourcesReturnsLanguagesWithContent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Controller/Page/Fixtures/tt_content-danish-language.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Record 1 exists in default language (0) and has translation to Danish (1)
        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'tt_content',
            'recordUid' => 1,
            'targetLanguage' => self::LANGUAGE_PRESETS['DE']['id'],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getSources($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        // Should return languages 0 and 1 (EN and DA) as sources for German translation
        self::assertCount(2, $result);
    }

    #[Test]
    public function getModesReturnsAvailableLocalizationModes(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'tt_content',
            'recordUid' => 1,
            'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getModes($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        // Should return both TRANSLATE and COPY modes by default
        self::assertCount(2, $result);

        // Convert keys to enum instances for comparison
        $modes = array_map(fn($item) => LocalizationMode::from($item['key']), $result);
        self::assertContains(LocalizationMode::TRANSLATE, $modes);
        self::assertContains(LocalizationMode::COPY, $modes);
    }

    #[Test]
    public function getModesRespectsPageTSconfig(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Update page 1 with TSconfig to disable copy mode
        $this->getConnectionPool()->getConnectionForTable('pages')->update(
            'pages',
            ['TSconfig' => 'mod.web_layout.localization.enableCopy = 0'],
            ['uid' => 1]
        );

        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'tt_content',
            'recordUid' => 1,
            'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getModes($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        // Should only return TRANSLATE mode since COPY was disabled via PageTSconfig
        self::assertCount(1, $result);
        self::assertEquals(LocalizationMode::TRANSLATE, LocalizationMode::from($result[0]['key']));
    }

    #[Test]
    public function getModesDetectsExistingLocalizationMode(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Insert a connected translation directly (TRANSLATE mode: l18n_parent > 0)
        // This simulates that page 1 already has content in connected translation mode
        $this->getConnectionPool()->getConnectionForTable('tt_content')->insert(
            'tt_content',
            [
                'pid' => 1,
                'sys_language_uid' => self::LANGUAGE_PRESETS['DA']['id'],
                'l18n_parent' => 1, // Connected to record 1 (TRANSLATE mode)
                'header' => '[Translate to Dansk:] Test content 1',
                'colPos' => 0,
            ]
        );

        // Now check available modes for a different record on the same page
        // Should only return TRANSLATE to prevent mixing modes
        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'tt_content',
            'recordUid' => 2, // Different record on same page
            'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getModes($request);
        $result = json_decode((string)$response->getBody(), true);

        self::assertIsArray($result);
        // Should only return TRANSLATE mode since page already has connected translations
        self::assertCount(1, $result);
        self::assertEquals(LocalizationMode::TRANSLATE, LocalizationMode::from($result[0]['key']));
    }

    #[Test]
    public function getHandlersReturnsAvailableHandlers(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'tt_content',
            'recordUid' => 1,
            'sourceLanguage' => self::LANGUAGE_PRESETS['EN']['id'],
            'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
            'mode' => LocalizationMode::TRANSLATE->value,
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getHandlers($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        self::assertNotEmpty($result);
        // Should at least contain the manual handler
        $manualHandler = array_filter($result, fn($h) => $h['identifier'] === 'manual');
        self::assertNotEmpty($manualHandler);
    }

    #[Test]
    public function getHandlersReturns400WithInvalidMode(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'tt_content',
            'recordUid' => 1,
            'sourceLanguage' => self::LANGUAGE_PRESETS['EN']['id'],
            'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
            'mode' => 'invalid_mode',
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getHandlers($request);

        self::assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function getContentReturnsPageLayoutWithRecords(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withQueryParams([
            'pageUid' => 1,
            'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
            'sourceLanguage' => self::LANGUAGE_PRESETS['EN']['id'],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getContent($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        self::assertArrayHasKey('layout', $result);
        self::assertArrayHasKey('rows', $result['layout']);
        self::assertArrayHasKey('elementCount', $result['layout']);
        // Should have 3 elements from default language (records 1, 2, 3)
        self::assertEquals(3, $result['layout']['elementCount']);
    }

    #[Test]
    public function getContentRespectsWorkspaceForDeletedRecords(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $backendUser->workspace = 1;

        // Delete record 2 in workspace
        $actionService = new ActionService();
        $actionService->deleteRecord('tt_content', 2);

        $request = (new ServerRequest())->withQueryParams([
            'pageUid' => 1,
            'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
            'sourceLanguage' => self::LANGUAGE_PRESETS['EN']['id'],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getContent($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        // Should only show 2 elements (1 and 3), not the deleted record 2
        self::assertEquals(2, $result['layout']['elementCount']);
    }

    #[Test]
    public function getContentRespectsWorkspaceForMovedRecords(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $backendUser->workspace = 1;

        // Move record 2 to different page in workspace
        $actionService = new ActionService();
        $actionService->moveRecord('tt_content', 2, 2);

        $request = (new ServerRequest())->withQueryParams([
            'pageUid' => 1,
            'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
            'sourceLanguage' => self::LANGUAGE_PRESETS['EN']['id'],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->getContent($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        // Should only show 2 elements (1 and 3), not the moved record 2
        self::assertEquals(2, $result['layout']['elementCount']);
    }

    #[Test]
    public function localizeExecutesLocalizationAndReturnsResult(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withParsedBody([
            'recordType' => 'tt_content',
            'recordUid' => 1,
            'data' => [
                'sourceLanguage' => self::LANGUAGE_PRESETS['EN']['id'],
                'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
                'localizationMode' => LocalizationMode::TRANSLATE->value,
                'localizationHandler' => 'manual',
            ],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->localize($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        self::assertTrue($result['success']);
        self::assertArrayHasKey('finisher', $result);
    }

    #[Test]
    public function localizeReturns400WithMissingParameters(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withParsedBody([
            'recordType' => 'tt_content',
            'recordUid' => 1,
            'data' => [
                'sourceLanguage' => self::LANGUAGE_PRESETS['EN']['id'],
                // Missing targetLanguage and localizationMode
            ],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->localize($request);

        self::assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function localizeReturns400WithInvalidMode(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withParsedBody([
            'recordType' => 'tt_content',
            'recordUid' => 1,
            'data' => [
                'sourceLanguage' => self::LANGUAGE_PRESETS['EN']['id'],
                'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
                'localizationMode' => 'invalid_mode',
                'localizationHandler' => 'manual',
            ],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->localize($request);

        self::assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function localizeHandlesPageWithContentSelection(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest())->withParsedBody([
            'recordType' => 'pages',
            'recordUid' => 1,
            'data' => [
                'sourceLanguage' => self::LANGUAGE_PRESETS['EN']['id'],
                'targetLanguage' => self::LANGUAGE_PRESETS['DA']['id'],
                'localizationMode' => LocalizationMode::TRANSLATE->value,
                'localizationHandler' => 'manual',
                'selectedRecordUids' => [1, 2], // Select only records 1 and 2
            ],
        ]);

        $subject = $this->get(LocalizationController::class);
        $response = $subject->localize($request);

        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertIsArray($result);
        self::assertTrue($result['success']);

        // Verify only selected content was translated
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $translatedContent = $queryBuilder
            ->select('uid', 'l18n_parent')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(self::LANGUAGE_PRESETS['DA']['id'], Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(2, $translatedContent, 'Should have translated 2 content elements');
    }

    #[Test]
    public function getSourcesForPageRestrictsToExistingContentSourceLanguages(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $actionService = new ActionService();
        $subject = $this->get(LocalizationController::class);
        $actionService->localizeRecord('pages', 1, self::LANGUAGE_PRESETS['DA']['id']);
        $actionService->localizeRecord('tt_content', 1, self::LANGUAGE_PRESETS['DA']['id']);
        $actionService->localizeRecord('tt_content', 2, self::LANGUAGE_PRESETS['DA']['id']);
        $actionService->localizeRecord('pages', 1, self::LANGUAGE_PRESETS['DE']['id']);

        // German page has no content yet, so all available languages should be offered
        $request = (new ServerRequest())->withQueryParams([
            'recordType' => 'pages',
            'recordUid' => 1,
            'targetLanguage' => self::LANGUAGE_PRESETS['DE']['id'],
        ]);

        $response = $subject->getSources($request);
        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        $languageUids = array_column($result, 'uid');
        self::assertContains(0, $languageUids, 'Should offer English as source');
        self::assertContains(1, $languageUids, 'Should offer Danish as source');

        // Since there's now content translated from English, only English should be available
        $actionService->localizeRecord('tt_content', 1, self::LANGUAGE_PRESETS['DE']['id']);
        $response = $subject->getSources($request);
        self::assertEquals(200, $response->getStatusCode());

        $result = json_decode((string)$response->getBody(), true);
        self::assertCount(1, $result, 'Should only return one source language after content is added');
        self::assertEquals(0, $result[0]['uid'], 'Should only offer English as source language');
    }
}
