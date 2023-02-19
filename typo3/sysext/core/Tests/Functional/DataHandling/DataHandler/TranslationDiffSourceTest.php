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
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TranslationDiffSourceTest extends AbstractDataHandlerActionTestCase
{
    protected const PAGE_DATAHANDLER = 88;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/TranslationDiffSourceTest.csv');
        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
        $this->backendUser->workspace = 0;
    }

    /**
     * @test
     */
    public function transOrigDiffSourceFieldWrittenAfterTranslation(): void
    {
        $map = $this->actionService->localizeRecord('pages', self::PAGE_DATAHANDLER, 1);

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
        $map = $this->actionService->localizeRecord('pages', self::PAGE_DATAHANDLER, 1);
        $newPageId = $map['pages'][self::PAGE_DATAHANDLER];
        $this->actionService->modifyRecord(
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
