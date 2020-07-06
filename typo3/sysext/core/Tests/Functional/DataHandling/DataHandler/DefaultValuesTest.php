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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Tests various places to set default values properly for new records
 */
class DefaultValuesTest extends AbstractDataHandlerActionTestCase
{
    /**
     * @var int
     */
    const PAGE_DATAHANDLER = 88;

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/DataHandler/DataSet/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');
        $this->backendUser->workspace = 0;
    }

    /**
     * @test
     */
    public function defaultValuesFromTCAForNewRecordsIsRespected(): void
    {
        $GLOBALS['TCA']['pages']['columns']['keywords']['config']['default'] = 'a few,random,keywords';
        $map = $this->actionService->createNewRecord('pages', self::PAGE_DATAHANDLER, [
            'title' => 'A new age'
        ]);
        $newPageId = reset($map['pages']);
        $newPageRecord = BackendUtility::getRecord('pages', $newPageId);
        self::assertEquals($newPageRecord['keywords'], $GLOBALS['TCA']['pages']['columns']['keywords']['config']['default']);

        $GLOBALS['TCA']['tt_content']['columns']['header']['config']['default'] = 'Pre-set header';
        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'header' => '',
            'bodytext' => 'Random bodytext'
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        // Empty header is used, because it was handed in
        self::assertEquals($newContentRecord['header'], '');

        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'bodytext' => 'Random bodytext'
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        self::assertEquals($newContentRecord['header'], $GLOBALS['TCA']['tt_content']['columns']['header']['config']['default']);
    }

    /**
     * @test
     */
    public function defaultValuesFromGlobalTSconfigForNewRecordsIsRespected(): void
    {
        ExtensionManagementUtility::addPageTSConfig('
TCAdefaults.pages.keywords = from pagets, with love
TCAdefaults.tt_content.header = global space');
        $map = $this->actionService->createNewRecord('pages', self::PAGE_DATAHANDLER, [
            'title' => 'A new age'
        ]);
        $newPageId = reset($map['pages']);
        $newPageRecord = BackendUtility::getRecord('pages', $newPageId);
        self::assertEquals($newPageRecord['keywords'], 'from pagets, with love');

        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'header' => '',
            'bodytext' => 'Random bodytext'
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        // Empty header is used, because it was handed in
        self::assertEquals($newContentRecord['header'], '');

        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'bodytext' => 'Random bodytext'
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        self::assertEquals($newContentRecord['header'], 'global space');
    }

    /**
     * @test
     */
    public function defaultValuesFromPageSpecificTSconfigForNewRecordsIsRespected(): void
    {
        ExtensionManagementUtility::addPageTSConfig('
TCAdefaults.pages.keywords = from pagets, with love
TCAdefaults.tt_content.header = global space');
        $this->actionService->modifyRecord('pages', self::PAGE_DATAHANDLER, [
            'TSconfig' => '

TCAdefaults.pages.keywords = I am specific, not generic
TCAdefaults.tt_content.header = local space

']);
        $map = $this->actionService->createNewRecord('pages', self::PAGE_DATAHANDLER, [
            'title' => 'A new age'
        ]);
        $newPageId = reset($map['pages']);
        $newPageRecord = BackendUtility::getRecord('pages', $newPageId);
        self::assertEquals($newPageRecord['keywords'], 'I am specific, not generic');

        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'header' => '',
            'bodytext' => 'Random bodytext'
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        // Empty header is used, because it was handed in
        self::assertEquals($newContentRecord['header'], '');

        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'bodytext' => 'Random bodytext'
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        self::assertEquals($newContentRecord['header'], 'local space');
    }

    /**
     * @test
     */
    public function defaultValueForNullTextfieldsIsConsidered(): void
    {
        // New content element without bodytext
        $GLOBALS['TCA']['tt_content']['columns']['bodytext']['l10n_mode'] = 'exclude';
        $GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['enableRichtext'] = true;
        $map = $this->actionService->createNewRecord('tt_content', self::PAGE_DATAHANDLER, [
            'header' => 'Random header',
            'bodytext' => null
        ]);
        $newContentId = reset($map['tt_content']);
        $map = $this->actionService->localizeRecord('tt_content', $newContentId, 1);
        $translatedContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $translatedContentId);
        self::assertNull($newContentRecord['bodytext']);
    }
}
