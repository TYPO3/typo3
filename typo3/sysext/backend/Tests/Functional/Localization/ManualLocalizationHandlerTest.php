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

namespace TYPO3\CMS\Backend\Tests\Functional\Localization;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Localization\Finisher\NoopLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\LocalizationInstructions;
use TYPO3\CMS\Backend\Localization\LocalizationMode;
use TYPO3\CMS\Backend\Localization\ManualLocalizationHandler;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for ManualLocalizationHandler
 *
 * Tests the core localization functionality (translate and copy operations)
 * using the new ManualLocalizationHandler which replaced the old LocalizationController process method.
 */
final class ManualLocalizationHandlerTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/../Controller/Page/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Controller/Page/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Controller/Page/Fixtures/tt_content-default-language.csv');
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
    public function recordsGetTranslatedFromDefaultLanguage(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(ManualLocalizationHandler::class);

        // Translate records 1, 2, 3 from language 0 to language 1
        foreach ([1, 2, 3] as $uid) {
            $result = $subject->processLocalization(new LocalizationInstructions(
                mainRecordType: 'tt_content',
                recordUid: $uid,
                sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
                targetLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
                mode: LocalizationMode::TRANSLATE,
                additionalData: []
            ));

            self::assertTrue($result->isSuccess(), 'Localization should succeed for uid ' . $uid);
        }

        $this->assertCSVDataSet(__DIR__ . '/../Controller/Page/Localization/CSV/DataSet/TranslatedFromDefault.csv');
    }

    #[Test]
    public function recordsGetTranslatedFromDifferentTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Controller/Page/Fixtures/tt_content-danish-language.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(ManualLocalizationHandler::class);

        // Translate records 4, 5, 6 (Danish) to language 2 (German)
        foreach ([4, 5, 6] as $uid) {
            $result = $subject->processLocalization(new LocalizationInstructions(
                mainRecordType: 'tt_content',
                recordUid: $uid,
                sourceLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
                targetLanguageId: self::LANGUAGE_PRESETS['DE']['id'],
                mode: LocalizationMode::TRANSLATE,
                additionalData: []
            ));

            self::assertTrue($result->isSuccess(), 'Localization should succeed for uid ' . $uid);
        }

        $this->assertCSVDataSet(__DIR__ . '/../Controller/Page/Localization/CSV/DataSet/TranslatedFromTranslation.csv');
    }

    #[Test]
    public function recordsGetCopiedFromDefaultLanguage(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(ManualLocalizationHandler::class);

        // Copy records 1, 2, 3 from language 0 to language 2
        foreach ([1, 2, 3] as $uid) {
            $result = $subject->processLocalization(new LocalizationInstructions(
                mainRecordType: 'tt_content',
                recordUid: $uid,
                sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
                targetLanguageId: self::LANGUAGE_PRESETS['DE']['id'],
                mode: LocalizationMode::COPY,
                additionalData: []
            ));

            self::assertTrue($result->isSuccess(), 'Copy should succeed for uid ' . $uid);
        }

        $this->assertCSVDataSet(__DIR__ . '/../Controller/Page/Localization/CSV/DataSet/CopiedFromDefault.csv');
    }

    #[Test]
    public function recordsGetCopiedFromAnotherLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Controller/Page/Fixtures/tt_content-danish-language.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(ManualLocalizationHandler::class);

        // Copy records 4, 5, 6 (Danish) to language 2 (German)
        foreach ([4, 5, 6] as $uid) {
            $result = $subject->processLocalization(new LocalizationInstructions(
                mainRecordType: 'tt_content',
                recordUid: $uid,
                sourceLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
                targetLanguageId: self::LANGUAGE_PRESETS['DE']['id'],
                mode: LocalizationMode::COPY,
                additionalData: []
            ));

            self::assertTrue($result->isSuccess(), 'Copy should succeed for uid ' . $uid);
        }

        $this->assertCSVDataSet(__DIR__ . '/../Controller/Page/Localization/CSV/DataSet/CopiedFromTranslation.csv');
    }

    /**
     * This test:
     * - copies default language records 1,2,3, into language 1 ("free mode translation")
     * - creates new CE in default language after record 2, called 'Test content 2.5'
     * - copies into language record 9 ('Test content 2.5')
     * - checks if translated/copied record "[Translate to Dansk:] Test content 2.5" has sorting value after
     *   "[Translate to Dansk:] Test content 1", which is the previous record in the colpos.
     *
     * For detail about the sorting algorithm when translating records, see DataHandler->getPreviousLocalizedRecordUid
     */
    #[Test]
    public function copyingNewContentFromLanguageIntoExistingLocalizationHasSameOrdering(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(ManualLocalizationHandler::class);

        // Step 1: Copy records 1, 2, 3 from language 0 to language 1
        foreach ([1, 2, 3] as $uid) {
            $result = $subject->processLocalization(new LocalizationInstructions(
                mainRecordType: 'tt_content',
                recordUid: $uid,
                sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
                targetLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
                mode: LocalizationMode::COPY,
                additionalData: []
            ));

            self::assertTrue($result->isSuccess(), 'Copy should succeed for uid ' . $uid);
        }

        // Step 2: Create another content element in default language after record 2
        $data = [
            'tt_content' => [
                'NEW123456' => [
                    'sys_language_uid' => self::LANGUAGE_PRESETS['EN']['id'],
                    'header' => 'Test content 2.5',
                    'pid' => -2,
                    'colPos' => 0,
                ],
            ],
        ];
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
        $newContentElementUid = $dataHandler->substNEWwithIDs['NEW123456'];

        // Step 3: Copy the new content element to language 1
        $result = $subject->processLocalization(new LocalizationInstructions(
            mainRecordType: 'tt_content',
            recordUid: $newContentElementUid,
            sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
            targetLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
            mode: LocalizationMode::COPY,
            additionalData: []
        ));

        self::assertTrue($result->isSuccess(), 'Copy should succeed for new element');

        $this->assertCSVDataSet(__DIR__ . '/../Controller/Page/Localization/CSV/DataSet/CreatedElementOrdering.csv');
    }

    #[Test]
    public function pageLocalizationWithSelectedContent(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(ManualLocalizationHandler::class);

        // Localize page 1 to language 1 with selected content elements 1 and 2
        $result = $subject->processLocalization(new LocalizationInstructions(
            mainRecordType: 'pages',
            recordUid: 1,
            sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
            targetLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
            mode: LocalizationMode::TRANSLATE,
            additionalData: [
                'selectedRecordUids' => [1, 2],
            ]
        ));

        self::assertTrue($result->isSuccess(), 'Page localization should succeed');
        self::assertNotNull($result->finisher, 'Result should have a finisher');

        // Verify only records 1 and 2 were translated (not 3)
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $translatedContent = $queryBuilder
            ->select('uid', 'l18n_parent')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(self::LANGUAGE_PRESETS['DA']['id'], Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(2, $translatedContent, 'Should have 2 translated content elements');
        self::assertEquals(1, $translatedContent[0]['l18n_parent'], 'First record should be translation of record 1');
        self::assertEquals(2, $translatedContent[1]['l18n_parent'], 'Second record should be translation of record 2');
    }

    #[Test]
    public function pageLocalizationInCopyModeWithSelectedContent(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(ManualLocalizationHandler::class);

        // Localize page 1 to language 2 with copy mode for selected content
        $result = $subject->processLocalization(new LocalizationInstructions(
            mainRecordType: 'pages',
            recordUid: 1,
            sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
            targetLanguageId: self::LANGUAGE_PRESETS['DE']['id'],
            mode: LocalizationMode::COPY,
            additionalData: [
                'selectedRecordUids' => [1, 3],
            ]
        ));

        self::assertTrue($result->isSuccess(), 'Page localization should succeed');
        self::assertNotNull($result->finisher, 'Result should have a finisher');

        // Verify content elements were copied (l18n_parent = 0 for copy mode)
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $copiedContent = $queryBuilder
            ->select('uid', 'l18n_parent', 'header')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(self::LANGUAGE_PRESETS['DE']['id'], Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(2, $copiedContent, 'Should have 2 copied content elements');
        self::assertEquals(0, $copiedContent[0]['l18n_parent'], 'Copied records should have l18n_parent = 0');
        self::assertEquals(0, $copiedContent[1]['l18n_parent'], 'Copied records should have l18n_parent = 0');
    }

    #[Test]
    public function pageLocalizationWithExistingPageTranslationButNoContentSelection(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Create a translated page manually (simulates a page that was translated previously)
        $connection = $this->getConnectionPool()->getConnectionForTable('pages');
        $connection->insert(
            'pages',
            [
                'pid' => 0,
                'sys_language_uid' => self::LANGUAGE_PRESETS['DA']['id'],
                'l10n_parent' => 1,
                'l10n_source' => 1,
                'title' => 'Localization DA',
                'deleted' => 0,
                'perms_everybody' => 15,
            ]
        );

        $subject = $this->get(ManualLocalizationHandler::class);

        // Now attempt to localize the same page without selecting any content elements
        // This simulates a user opening the translation wizard for an already-translated page but not selecting any content
        $result = $subject->processLocalization(new LocalizationInstructions(
            mainRecordType: 'pages',
            recordUid: 1,
            sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
            targetLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
            mode: LocalizationMode::TRANSLATE,
            additionalData: [
                'selectedRecordUids' => [], // No content elements selected
            ]
        ));

        // The operation should succeed with a finisher because:
        // 1. The page translation already exists (no error)
        // 2. No content elements were selected (nothing to do, but not an error)
        // The wizard should simply finish with a no-op finisher
        self::assertTrue($result->isSuccess(), 'Localization should succeed when page exists and no content is selected');
        self::assertNotNull($result->finisher, 'Result should have a finisher');
        self::assertInstanceOf(NoopLocalizationFinisher::class, $result->finisher, 'Should use NoopLocalizationFinisher when no operation was performed');

        // Verify the finisher has the correct labels
        $finisherData = $result->finisher->jsonSerialize();
        self::assertEquals('noop', $finisherData['identifier'], 'Finisher type should be noop');
        self::assertStringContainsString('No records were selected that need processing', $finisherData['labels']['successDescription'], 'Should have correct message about no records selected');

        // Verify no content elements were translated
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $translatedContent = $queryBuilder
            ->select('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(self::LANGUAGE_PRESETS['DA']['id'], Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(0, $translatedContent, 'Should have no translated content elements');
    }

    #[Test]
    public function contentElementLocalizationReturnsSuccessForAlreadyTranslatedElement(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Create a translated content element manually (simulates content that was already translated)
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $connection->insert(
            'tt_content',
            [
                'pid' => 1,
                'sys_language_uid' => self::LANGUAGE_PRESETS['DA']['id'],
                'l18n_parent' => 1,
                'l10n_source' => 1,
                'header' => 'Test content 1 DA',
                'deleted' => 0,
                'colPos' => 0,
                'sorting' => 1,
            ]
        );

        $subject = $this->get(ManualLocalizationHandler::class);

        // Attempt to localize the already-translated content element
        $result = $subject->processLocalization(new LocalizationInstructions(
            mainRecordType: 'tt_content',
            recordUid: 1,
            sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
            targetLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
            mode: LocalizationMode::TRANSLATE,
            additionalData: []
        ));

        // The handler should detect that translation already exists and return a no-op finisher
        self::assertTrue($result->isSuccess(), 'Localization should succeed when translation exists');
        self::assertNotNull($result->finisher, 'Result should have a finisher');
        self::assertInstanceOf(NoopLocalizationFinisher::class, $result->finisher, 'Should use NoopLocalizationFinisher when translation already exists');

        // Verify the finisher has the correct labels
        $finisherData = $result->finisher->jsonSerialize();
        self::assertEquals('noop', $finisherData['identifier'], 'Finisher type should be noop');
        self::assertStringContainsString('No records were selected that need processing', $finisherData['labels']['successDescription'], 'Should have correct message about no records selected');
    }

    #[Test]
    public function processingReturnsErrorForInvalidRecordType(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(ManualLocalizationHandler::class);

        $result = $subject->processLocalization(new LocalizationInstructions(
            mainRecordType: 'tt_content',
            recordUid: 99999, // Non-existent record
            sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
            targetLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
            mode: LocalizationMode::TRANSLATE,
            additionalData: []
        ));

        // When an invalid/non-existent record is processed, DataHandler should return errors
        self::assertFalse($result->isSuccess(), 'Result should not be successful for non-existent record');
        self::assertTrue($result->hasErrors(), 'Result should have errors');
        self::assertNotEmpty($result->errors, 'Errors array should not be empty');
        self::assertNull($result->finisher, 'Failed result should not have a finisher');
    }

    #[Test]
    public function processingReturnsErrorForInvalidTargetLanguage(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(ManualLocalizationHandler::class);

        // Try to localize to an invalid/non-existent language (999)
        $result = $subject->processLocalization(new LocalizationInstructions(
            mainRecordType: 'tt_content',
            recordUid: 1,
            sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
            targetLanguageId: 999,
            mode: LocalizationMode::TRANSLATE,
            additionalData: []
        ));

        // DataHandler should reject the invalid language and return errors
        self::assertFalse($result->isSuccess(), 'Result should not be successful for invalid target language');
        self::assertTrue($result->hasErrors(), 'Result should have errors');
        self::assertNotEmpty($result->errors, 'Errors array should not be empty');
        self::assertNull($result->finisher, 'Failed result should not have a finisher');
    }

    #[Test]
    public function isAvailableAlwaysReturnsTrue(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = $this->get(ManualLocalizationHandler::class);

        // Manual handler should always be available regardless of context
        $result = $subject->isAvailable(new LocalizationInstructions(
            mainRecordType: 'tt_content',
            recordUid: 1,
            sourceLanguageId: self::LANGUAGE_PRESETS['EN']['id'],
            targetLanguageId: self::LANGUAGE_PRESETS['DA']['id'],
            mode: LocalizationMode::TRANSLATE,
            additionalData: []
        ));

        self::assertTrue($result, 'Manual handler should always be available');
    }

    #[Test]
    public function handlerReturnsCorrectMetadata(): void
    {
        $subject = $this->get(ManualLocalizationHandler::class);

        self::assertEquals('manual', $subject->getIdentifier());
        self::assertEquals('backend.wizards.localization:handler.manual.label', $subject->getLabel());
        self::assertEquals('backend.wizards.localization:handler.manual.description', $subject->getDescription());
        self::assertEquals('actions-translate', $subject->getIconIdentifier());
    }
}
