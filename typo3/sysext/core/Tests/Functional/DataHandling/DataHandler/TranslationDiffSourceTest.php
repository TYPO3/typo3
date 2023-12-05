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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\History\RecordHistoryRollback;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TranslationDiffSourceTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const PAGE_DATAHANDLER = 88;
    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/TranslationDiffSourceTest.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();
    }

    /**
     * @test
     */
    public function transOrigDiffSourceFieldWrittenAfterTranslation(): void
    {
        $actionService = new ActionService();
        $map = $actionService->localizeRecord('pages', self::PAGE_DATAHANDLER, 1);
        $newPageId = $map['pages'][self::PAGE_DATAHANDLER];
        $originalLanguageRecord = BackendUtility::getRecord('pages', self::PAGE_DATAHANDLER);
        $translatedRecord = BackendUtility::getRecord('pages', $newPageId);
        $transOrigDiffSourceField = json_decode($translatedRecord[$GLOBALS['TCA']['pages']['ctrl']['transOrigDiffSourceField']], true);

        self::assertEquals('DataHandlerTest', $originalLanguageRecord['title']);
        self::assertEquals('DataHandlerTest', $transOrigDiffSourceField['title']);
    }

    /**
     * @test
     */
    public function transOrigDiffSourceNotUpdatedAfterUndo(): void
    {
        $actionService = new ActionService();
        $map = $actionService->localizeRecord('pages', self::PAGE_DATAHANDLER, 1);
        $newPageId = $map['pages'][self::PAGE_DATAHANDLER];
        $actionService->modifyRecord(
            'pages',
            self::PAGE_DATAHANDLER,
            [
                'title' => 'Modified dataHandler',
            ]
        );

        $element = 'pages:' . self::PAGE_DATAHANDLER;
        $recordHistory = GeneralUtility::makeInstance(RecordHistory::class, $element);
        $changeLog = $recordHistory->getChangeLog();
        $recordHistoryRollback = GeneralUtility::makeInstance(RecordHistoryRollback::class);
        $recordHistoryRollback->performRollback($element, $recordHistory->getDiff($changeLog));

        $record = BackendUtility::getRecord('pages', self::PAGE_DATAHANDLER);
        $translatedRecord = BackendUtility::getRecord('pages', $newPageId);
        $transOrigDiffSourceField = json_decode($translatedRecord[$GLOBALS['TCA']['pages']['ctrl']['transOrigDiffSourceField']], true);

        self::assertEmpty($record[$GLOBALS['TCA']['pages']['ctrl']['transOrigDiffSourceField']]);
        self::assertEquals('DataHandlerTest', $transOrigDiffSourceField['title']);
    }
}
