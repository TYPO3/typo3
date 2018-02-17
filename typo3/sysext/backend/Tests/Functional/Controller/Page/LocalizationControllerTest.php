<?php
namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Page;

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

use TYPO3\CMS\Backend\Controller\Page\LocalizationController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;

/**
 * Test case for TYPO3\CMS\Backend\Controller\Page\LocalizationController
 */
class LocalizationControllerTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var LocalizationController
     */
    protected $subject;

    /**
     * @var \TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService
     */
    protected $actionService;

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
    protected function setUp()
    {
        parent::setUp();

        $this->backendUser = $this->setUpBackendUserFromFixture(1);
        $this->backendUser->workspace = 0;

        Bootstrap::initializeLanguageObject();
        $this->actionService = GeneralUtility::makeInstance(ActionService::class);

        $this->importDataSet(__DIR__ . '/Fixtures/pages.xml');
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_language.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Controller/Page/Fixtures/tt_content-default-language.xml');

        $this->subject = new LocalizationController();
    }

    /**
     * @test
     */
    public function recordsGetTranslatedFromDefaultLanguage()
    {
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 1,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_LOCALIZE,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        $expectedResults = [
            [
                'pid' => 1,
                'sys_language_uid' => 1,
                'l18n_parent' => 1,
                'header' => '[Translate to Dansk:] Test content 1',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 1,
                'l18n_parent' => 2,
                'header' => '[Translate to Dansk:] Test content 2',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 1,
                'l18n_parent' => 3,
                'header' => '[Translate to Dansk:] Test content 3',
            ],
        ];
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $results = $queryBuilder
            ->select('pid', 'sys_language_uid', 'l18n_parent', 'header')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    )
                )
            )
            ->orderBy('uid')
            ->execute()
            ->fetchAll();
        $this->assertEquals($expectedResults, $results);
    }

    /**
     * @test
     */
    public function recordsGetTranslatedFromDifferentTranslation()
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Controller/Page/Fixtures/tt_content-danish-language.xml');

        $params = [
            'pageId' => 1,
            'srcLanguageId' => 1,
            'destLanguageId' => 2,
            'uidList' => [4, 5, 6], // uids of tt_content-danish-language
            'action' => LocalizationController::ACTION_LOCALIZE,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        $expectedResults = [
            [
                'pid' => 1,
                'sys_language_uid' => 2,
                'l18n_parent' => 1,
                'header' => '[Translate to Deutsch:] Test indhold 1',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 2,
                'l18n_parent' => 2,
                'header' => '[Translate to Deutsch:] Test indhold 2',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 2,
                'l18n_parent' => 3,
                'header' => '[Translate to Deutsch:] Test indhold 3',
            ],
        ];
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $results = $queryBuilder
            ->select('pid', 'sys_language_uid', 'l18n_parent', 'header')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                    )
                )
            )
            ->orderBy('uid')
            ->execute()
            ->fetchAll();
        $this->assertEquals($expectedResults, $results);
    }

    /**
     * @test
     */
    public function recordsGetCopiedFromDefaultLanguage()
    {
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 2,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_COPY,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        $expectedResults = [
            [
                'pid' => 1,
                'sys_language_uid' => 2,
                'l18n_parent' => 0,
                'header' => '[Translate to Deutsch:] Test content 1',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 2,
                'l18n_parent' => 0,
                'header' => '[Translate to Deutsch:] Test content 2',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 2,
                'l18n_parent' => 0,
                'header' => '[Translate to Deutsch:] Test content 3',
            ],
        ];
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $results = $queryBuilder
            ->select('pid', 'sys_language_uid', 'l18n_parent', 'header')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                    )
                )
            )
            ->orderBy('uid')
            ->execute()
            ->fetchAll();
        $this->assertEquals($expectedResults, $results);
    }

    /**
     * @test
     */
    public function recordsGetCopiedFromAnotherLanguage()
    {
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Controller/Page/Fixtures/tt_content-danish-language.xml');

        $params = [
            'pageId' => 1,
            'srcLanguageId' => 1,
            'destLanguageId' => 2,
            'uidList' => [4, 5, 6], // uids of tt_content-danish-language
            'action' => LocalizationController::ACTION_COPY,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        $expectedResults = [
            [
                'pid' => 1,
                'sys_language_uid' => 2,
                'l18n_parent' => 0,
                'header' => '[Translate to Deutsch:] Test indhold 1',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 2,
                'l18n_parent' => 0,
                'header' => '[Translate to Deutsch:] Test indhold 2',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 2,
                'l18n_parent' => 0,
                'header' => '[Translate to Deutsch:] Test indhold 3',
            ],
        ];
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $results = $queryBuilder
            ->select('pid', 'sys_language_uid', 'l18n_parent', 'header')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                    )
                )
            )
            ->orderBy('uid')
            ->execute()
            ->fetchAll();
        $this->assertEquals($expectedResults, $results);
    }

    /**
     * @test
     */
    public function copyingNewContentFromLanguageIntoExistingLocalizationHasSameOrdering()
    {
        $params = [
            'pageId' => 1,
            'srcLanguageId' => 0,
            'destLanguageId' => 1,
            'uidList' => [1, 2, 3],
            'action' => LocalizationController::ACTION_COPY,
        ];
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        // Create another content element in default language
        $data = [
            'tt_content' => [
                'NEW123456' => [
                    'sys_language_uid' => 0,
                    'header' => 'Test content 2.5',
                    'pid' => -2,
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
        $this->callInaccessibleMethod($this->subject, 'process', $params);

        $expectedResults = [
            [
                'pid' => 1,
                'sys_language_uid' => 1,
                'l18n_parent' => 0,
                'header' => '[Translate to Dansk:] Test content 1',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 1,
                'l18n_parent' => 0,
                'header' => '[Translate to Dansk:] Test content 2.5',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 1,
                'l18n_parent' => 0,
                'header' => '[Translate to Dansk:] Test content 2',
            ],
            [
                'pid' => 1,
                'sys_language_uid' => 1,
                'l18n_parent' => 0,
                'header' => '[Translate to Dansk:] Test content 3',
            ],
        ];
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $results = $queryBuilder
            ->select('pid', 'sys_language_uid', 'l18n_parent', 'header')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    )
                )
            )
            ->orderBy('sorting', 'ASC')
            ->execute()
            ->fetchAll();
        $this->assertEquals($expectedResults, $results);
    }

    /**
     * @test
     */
    public function recordLocalizeSummaryRespectsWorkspaceEncapsulationForDeletedRecords()
    {
        // Delete record 2 within workspace 1
        $this->backendUser->workspace = 1;
        $this->actionService->deleteRecord('tt_content', 2);

        $expectedRecordUidList = [
            ['uid' => 1],
            ['uid' => 3]
        ];

        $this->assertEquals($expectedRecordUidList, $this->getReducedRecordLocalizeSummary());
    }

    /**
     * @test
     */
    public function recordLocalizeSummaryRespectsWorkspaceEncapsulationForMovedRecords()
    {
        // Move record 2 to page 2 within workspace 1
        $this->backendUser->workspace = 1;
        $this->actionService->moveRecord('tt_content', 2, 2);

        $expectedRecordUidList = [
            ['uid' => 1],
            ['uid' => 3]
        ];

        $this->assertEquals($expectedRecordUidList, $this->getReducedRecordLocalizeSummary());
    }

    /**
     * Get record localized summary list reduced to list of uids
     *
     * @return array
     */
    protected function getReducedRecordLocalizeSummary()
    {
        $request = (new ServerRequest())->withQueryParams([
            'pageId'         => 1, // page uid, the records are stored on
            'colPos'         => 0, // column position, the records are to be taken from
            'destLanguageId' => 1, // destination language uid
            'languageId'     => 0  // source language uid
        ]);

        $recordLocalizeSummaryResponse = $this->subject->getRecordLocalizeSummary($request, new Response());

        // Reduce the fetched record summary to list of uids
        if ($recordLocalizeSummary = json_decode((string) $recordLocalizeSummaryResponse->getBody(), true)) {
            foreach ($recordLocalizeSummary as &$record) {
                if (is_array($record)) {
                    $record = array_intersect_key($record, ['uid' => '']);
                }
            }
        }

        return $recordLocalizeSummary;
    }
}
