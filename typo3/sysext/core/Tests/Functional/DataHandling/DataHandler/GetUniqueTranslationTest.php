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

class GetUniqueTranslationTest extends AbstractDataHandlerActionTestCase
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
    public function valueOfUniqueFieldExcludedInTranslationIsUntouchedInTranslation(): void
    {
        // Mis-using the "keywords" field in the scenario data-set to check for uniqueness
        $GLOBALS['TCA']['pages']['columns']['keywords']['l10n_mode'] = 'exclude';
        $GLOBALS['TCA']['pages']['columns']['keywords']['transOrigPointerField'] = 'l10n_parent';
        $GLOBALS['TCA']['pages']['columns']['keywords']['languageField'] = 'sys_language_uid';
        $GLOBALS['TCA']['pages']['columns']['keywords']['config']['eval'] = 'unique';
        $map = $this->actionService->localizeRecord('pages', self::PAGE_DATAHANDLER, 1);
        $newPageId = $map['pages'][self::PAGE_DATAHANDLER];

        $originalLanguageRecord = BackendUtility::getRecord('pages', self::PAGE_DATAHANDLER);
        $translatedRecord = BackendUtility::getRecord('pages', $newPageId);

        self::assertEquals('datahandler', $originalLanguageRecord['keywords']);
        self::assertEquals('datahandler', $translatedRecord['keywords']);
    }

    /**
     * @test
     */
    public function valueOfUniqueFieldExcludedInTranslationIsUntouchedInOriginalLanguage(): void
    {
        // Mis-using the "nav_title" field in the scenario data-set to check for uniqueness
        $GLOBALS['TCA']['pages']['columns']['nav_title']['l10n_mode'] = 'exclude';
        $GLOBALS['TCA']['pages']['columns']['nav_title']['transOrigPointerField'] = 'l10n_parent';
        $GLOBALS['TCA']['pages']['columns']['nav_title']['languageField'] = 'sys_language_uid';
        $GLOBALS['TCA']['pages']['columns']['nav_title']['config']['eval'] = 'unique';
        $map = $this->actionService->localizeRecord('pages', self::PAGE_DATAHANDLER, 1);
        $newPageId = $map['pages'][self::PAGE_DATAHANDLER];

        $translatedRecord = BackendUtility::getRecord('pages', $newPageId);
        $this->actionService->modifyRecord('pages', self::PAGE_DATAHANDLER, [
            'title' => 'DataHandlerTest changed',
            'nav_title' => 'datahandler'
        ]);
        $originalLanguageRecord = BackendUtility::getRecord('pages', self::PAGE_DATAHANDLER);

        self::assertEquals('DataHandlerTest changed', $originalLanguageRecord['title']);
        self::assertEquals('datahandler', $originalLanguageRecord['nav_title']);
        self::assertEquals('datahandler', $translatedRecord['nav_title']);
    }

    /**
     * @test
     */
    public function valueOfUniqueFieldExcludedInTranslationIsIncrementedInNewOriginalRecord(): void
    {
        // Mis-using the "nav_title" field in the scenario data-set to check for uniqueness
        $GLOBALS['TCA']['pages']['columns']['nav_title']['l10n_mode'] = 'exclude';
        $GLOBALS['TCA']['pages']['columns']['nav_title']['transOrigPointerField'] = 'l10n_parent';
        $GLOBALS['TCA']['pages']['columns']['nav_title']['languageField'] = 'sys_language_uid';
        $GLOBALS['TCA']['pages']['columns']['nav_title']['config']['eval'] = 'unique';
        $map = $this->actionService->createNewRecord('pages', -self::PAGE_DATAHANDLER, [
            'title' => 'New Page',
            'doktype' => 1
        ]);
        $newPageId = $map['pages'][0];

        $this->actionService->modifyRecord('pages', $newPageId, [
            'nav_title' => 'datahandler'
        ]);
        $originalLanguageRecord = BackendUtility::getRecord('pages', self::PAGE_DATAHANDLER);
        $newRecord = BackendUtility::getRecord('pages', $newPageId);
        self::assertEquals('datahandler', $originalLanguageRecord['nav_title']);
        self::assertEquals('datahandler0', $newRecord['nav_title']);
    }

    /**
     * @test
     */
    public function valueOfUniqueFieldExcludedInTranslationIsIncrementedInNewTranslatedRecord(): void
    {
        // Mis-using the "nav_title" field in the scenario data-set to check for uniqueness
        $GLOBALS['TCA']['pages']['columns']['nav_title']['l10n_mode'] = 'exclude';
        $GLOBALS['TCA']['pages']['columns']['nav_title']['transOrigPointerField'] = 'l10n_parent';
        $GLOBALS['TCA']['pages']['columns']['nav_title']['languageField'] = 'sys_language_uid';
        $GLOBALS['TCA']['pages']['columns']['nav_title']['config']['eval'] = 'unique';
        $map = $this->actionService->createNewRecord('pages', -self::PAGE_DATAHANDLER, [
            'title' => 'New Page',
            'doktype' => 1,
            'nav_title' => 'datahandler',
            'sys_language_uid' => 1
        ]);
        $newPageId = $map['pages'][0];

        $defaultLanguageRecord = BackendUtility::getRecord('pages', self::PAGE_DATAHANDLER);
        $newRecord = BackendUtility::getRecord('pages', $newPageId);
        self::assertEquals('datahandler', $defaultLanguageRecord['nav_title']);
        self::assertEquals('datahandler0', $newRecord['nav_title']);
    }
}
