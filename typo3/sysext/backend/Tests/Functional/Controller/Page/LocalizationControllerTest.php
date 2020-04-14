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

use TYPO3\CMS\Backend\Controller\Page\LocalizationController;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

/**
 * Test case for TYPO3\CMS\Backend\Controller\Page\LocalizationController
 */
class LocalizationControllerTest extends AbstractDataHandlerActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/backend/Tests/Functional/Controller/Page/Localization/CSV/DataSet/';

    /**
     * @var LocalizationController|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['workspaces'];

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/Fixtures/pages.xml');
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_language.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Controller/Page/Fixtures/tt_content-default-language.xml');
        $this->setUpFrontendRootPage(1);
        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);

        $this->subject = $this->getAccessibleMock(LocalizationController::class, ['getPageColumns']);
    }

    /**
     * @test
     * see DataSet/TranslatedFromDefault.csv
     */
    public function recordsGetTranslatedFromDefaultLanguage(): void
    {
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 1,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_LOCALIZE,
        ];
        $this->subject->_call('process', $params);
        $this->assertAssertionDataSet('TranslatedFromDefault');
    }

    /**
     * @test
     */
    public function recordsGetTranslatedFromDifferentTranslation(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Controller/Page/Fixtures/tt_content-danish-language.xml');

        $params = [
            'pageId' => 1,
            'srcLanguageId' => 1,
            'destLanguageId' => 2,
            'uidList' => [4, 5, 6], // uids of tt_content-danish-language
            'action' => LocalizationController::ACTION_LOCALIZE,
        ];
        $this->subject->_call('process', $params);
        $this->assertAssertionDataSet('TranslatedFromTranslation');
    }

    /**
     * @test
     */
    public function recordsGetCopiedFromDefaultLanguage(): void
    {
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 2,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_COPY,
        ];
        $this->subject->_call('process', $params);
        $this->assertAssertionDataSet('CopiedFromDefault');
    }

    /**
     * @test
     */
    public function recordsGetCopiedFromAnotherLanguage(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Controller/Page/Fixtures/tt_content-danish-language.xml');

        $params = [
            'pageId' => 1,
            'srcLanguageId' => 1,
            'destLanguageId' => 2,
            'uidList' => [4, 5, 6], // uids of tt_content-danish-language
            'action' => LocalizationController::ACTION_COPY,
        ];
        $this->subject->_call('process', $params);
        $this->assertAssertionDataSet('CopiedFromTranslation');
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
     *
     * @test
     */
    public function copyingNewContentFromLanguageIntoExistingLocalizationHasSameOrdering(): void
    {
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 1,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_COPY,
        ];
        $this->subject->_call('process', $params);

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
        $dataHandler = new DataHandler();
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
        $this->subject->_call('process', $params);
        $this->assertAssertionDataSet('CreatedElementOrdering');
    }

    /**
     * @test
     */
    public function defaultLanguageIsFoundAsOriginLanguage(): void
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Controller/Page/Fixtures/tt_content-danish-language.xml');

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
        $dataHandler = new DataHandler();
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $request = (new ServerRequest())->withQueryParams([
            'pageId'         => 1, // page uid, the records are stored on
            'languageId'     => 1  // current language id
        ]);

        $usedLanguages = (string)$this->subject->getUsedLanguagesInPage($request)->getBody();
        self::assertThat($usedLanguages, self::stringContains('"uid":0'));
    }

    /**
     * @test
     */
    public function recordLocalizeSummaryRespectsWorkspaceEncapsulationForDeletedRecords(): void
    {
        // Delete record 2 within workspace 1
        $this->backendUser->workspace = 1;
        $this->actionService->deleteRecord('tt_content', 2);

        $expectedRecords = [
            '0' => [
                ['uid' => 1],
            ],
            '1' => [
                ['uid' => 3],
            ],
        ];

        $localizeSummary = $this->getReducedRecordLocalizeSummary();

        self::assertEquals($expectedRecords, $localizeSummary);
    }

    /**
     * @test
     */
    public function recordLocalizeSummaryRespectsWorkspaceEncapsulationForMovedRecords(): void
    {
        // Move record 2 to page 2 within workspace 1
        $this->backendUser->workspace = 1;
        $this->actionService->moveRecord('tt_content', 2, 2);

        $expectedRecords = [
            '0' => [
                ['uid' => 1],
            ],
            '1' => [
                ['uid' => 3],
            ],
        ];

        $localizeSummary = $this->getReducedRecordLocalizeSummary();

        self::assertEquals($expectedRecords, $localizeSummary);
    }

    /**
     * Get record localized summary list reduced to list of uids
     *
     * @return array
     */
    protected function getReducedRecordLocalizeSummary(): array
    {
        $request = (new ServerRequest())->withQueryParams([
            'pageId'         => 1, // page uid, the records are stored on
            'destLanguageId' => 1, // destination language uid
            'languageId'     => 0  // source language uid
        ]);

        $this->subject->method('getPageColumns')->willReturn([
            0 => 'Column 0',
            1 => 'Column 1',
        ]);

        $recordLocalizeSummaryResponse = $this->subject->getRecordLocalizeSummary($request);

        // Reduce the fetched record summary to list of uids
        if ($recordLocalizeSummary = json_decode((string)$recordLocalizeSummaryResponse->getBody(), true)) {
            foreach ($recordLocalizeSummary['records'] as $colPos => $records) {
                foreach ($records as $key => $record) {
                    $recordLocalizeSummary['records'][$colPos][$key] = array_intersect_key($record, ['uid' => '']);
                }
            }
        }

        return $recordLocalizeSummary['records'];
    }
}
