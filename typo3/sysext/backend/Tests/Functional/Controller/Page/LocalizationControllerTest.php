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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Page;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\Page\LocalizationController;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content-default-language.csv');
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
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 1,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_LOCALIZE,
        ];
        $subject = $this->getAccessibleMock(LocalizationController::class, null);
        $subject->_call('process', $params);
        $this->assertCSVDataSet(__DIR__ . '/Localization/CSV/DataSet/TranslatedFromDefault.csv');
    }

    #[Test]
    public function recordsGetTranslatedFromDifferentTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content-danish-language.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 1,
            'destLanguageId' => 2,
            'uidList' => [4, 5, 6], // uids of tt_content-danish-language
            'action' => LocalizationController::ACTION_LOCALIZE,
        ];
        $subject = $this->getAccessibleMock(LocalizationController::class, null);
        $subject->_call('process', $params);
        $this->assertCSVDataSet(__DIR__ . '/Localization/CSV/DataSet/TranslatedFromTranslation.csv');
    }

    #[Test]
    public function recordsGetCopiedFromDefaultLanguage(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 2,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_COPY,
        ];
        $subject = $this->getAccessibleMock(LocalizationController::class, null);
        $subject->_call('process', $params);
        $this->assertCSVDataSet(__DIR__ . '/Localization/CSV/DataSet/CopiedFromDefault.csv');
    }

    #[Test]
    public function recordsGetCopiedFromAnotherLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content-danish-language.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 1,
            'destLanguageId' => 2,
            'uidList' => [4, 5, 6], // uids of tt_content-danish-language
            'action' => LocalizationController::ACTION_COPY,
        ];
        $subject = $this->getAccessibleMock(LocalizationController::class, null);
        $subject->_call('process', $params);
        $this->assertCSVDataSet(__DIR__ . '/Localization/CSV/DataSet/CopiedFromTranslation.csv');
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
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 1,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_COPY,
        ];
        $subject = $this->getAccessibleMock(LocalizationController::class, null);
        $subject->_call('process', $params);
        // Create another content element in default language
        $data = [
            'tt_content' => [
                'NEW123456' => [
                    'sys_language_uid' => 0,
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
        // Copy the new content element
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 1,
            'uidList' => [$newContentElementUid],
            'action' => LocalizationController::ACTION_COPY,
        ];
        $subject->_call('process', $params);
        $this->assertCSVDataSet(__DIR__ . '/Localization/CSV/DataSet/CreatedElementOrdering.csv');
    }

    #[Test]
    public function defaultLanguageIsFoundAsOriginLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content-danish-language.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        // Create another content element in default language
        $data = [
            'tt_content' => [
                'NEW123456' => [
                    'sys_language_uid' => 0,
                    'header' => 'New content element',
                    'pid' => 1,
                    'colPos' => 0,
                ],
            ],
        ];
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
        $request = (new ServerRequest())->withQueryParams([
            'pageId'         => 1, // page uid, the records are stored on
            'languageId'     => 1,  // current language id
        ]);
        $subject = $this->getAccessibleMock(LocalizationController::class, null);
        $usedLanguages = (string)$subject->getUsedLanguagesInPage($request)->getBody();
        self::assertThat($usedLanguages, self::stringContains('"uid":0'));
    }
    #[Test]
    public function deletedDefaultLanguageItemIsHandledAsIfNoRecordsExistAndReturnsAllOriginLanguages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content-default-language-deleted-element.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content-danish-language-deleted-source.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $request = (new ServerRequest())->withQueryParams([
            'pageId' => 2, // page uid, the records are stored on
            'languageId' => 1,  // current language id
        ]);
        $subject = $this->getAccessibleMock(LocalizationController::class, null);
        $usedLanguages = (string)$subject->getUsedLanguagesInPage($request)->getBody();
        self::assertThat($usedLanguages, self::stringContains('"uid":0'));
    }

    #[Test]
    public function recordLocalizeSummaryRespectsWorkspaceEncapsulationForDeletedRecords(): void
    {
        // Delete record 2 within workspace 1
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $backendUser->workspace = 1;
        $actionService = new ActionService();
        $actionService->deleteRecord('tt_content', 2);
        $expectedRecords = [
            '0' => [
                ['uid' => 1],
            ],
            '1' => [
                ['uid' => 3],
            ],
        ];
        $request = (new ServerRequest())->withQueryParams([
            'pageId' => 1, // page uid, the records are stored on
            'destLanguageId' => 1, // destination language uid
            'languageId' => 0,  // source language uid
        ]);
        $subject = $this->getAccessibleMock(LocalizationController::class, ['getPageColumns']);
        $subject->method('getPageColumns')->willReturn([
            0 => 'Column 0',
            1 => 'Column 1',
        ]);
        $recordLocalizeSummaryResponse = $subject->getRecordLocalizeSummary($request);
        // Reduce the fetched record summary to list of uids
        if ($recordLocalizeSummary = json_decode((string)$recordLocalizeSummaryResponse->getBody(), true)) {
            foreach ($recordLocalizeSummary['records'] as $colPos => $records) {
                foreach ($records as $key => $record) {
                    $recordLocalizeSummary['records'][$colPos][$key] = array_intersect_key($record, ['uid' => '']);
                }
            }
        }
        $localizeSummary = $recordLocalizeSummary['records'];
        self::assertEquals($expectedRecords, $localizeSummary);
    }

    #[Test]
    public function recordLocalizeSummaryRespectsWorkspaceEncapsulationForMovedRecords(): void
    {
        // Move record 2 to page 2 within workspace 1
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $backendUser->workspace = 1;
        $actionService = new ActionService();
        $actionService->moveRecord('tt_content', 2, 2);
        $expectedRecords = [
            '0' => [
                ['uid' => 1],
            ],
            '1' => [
                ['uid' => 3],
            ],
        ];
        $request = (new ServerRequest())->withQueryParams([
            'pageId' => 1, // page uid, the records are stored on
            'destLanguageId' => 1, // destination language uid
            'languageId' => 0,  // source language uid
        ]);
        $subject = $this->getAccessibleMock(LocalizationController::class, ['getPageColumns']);
        $subject->method('getPageColumns')->willReturn([
            0 => 'Column 0',
            1 => 'Column 1',
        ]);
        $recordLocalizeSummaryResponse = $subject->getRecordLocalizeSummary($request);
        // Reduce the fetched record summary to list of uids
        if ($recordLocalizeSummary = json_decode((string)$recordLocalizeSummaryResponse->getBody(), true)) {
            foreach ($recordLocalizeSummary['records'] as $colPos => $records) {
                foreach ($records as $key => $record) {
                    $recordLocalizeSummary['records'][$colPos][$key] = array_intersect_key($record, ['uid' => '']);
                }
            }
        }
        $localizeSummary = $recordLocalizeSummary['records'];
        self::assertEquals($expectedRecords, $localizeSummary);
    }
}
