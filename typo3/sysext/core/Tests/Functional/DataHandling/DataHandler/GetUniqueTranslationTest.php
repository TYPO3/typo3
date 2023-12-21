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
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class GetUniqueTranslationTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultElements.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
            ],
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
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
        $actionService = new ActionService();
        $map = $actionService->localizeRecord('pages', self::PAGE_DATAHANDLER, 1);
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
        $actionService = new ActionService();
        $map = $actionService->localizeRecord('pages', self::PAGE_DATAHANDLER, 1);
        $newPageId = $map['pages'][self::PAGE_DATAHANDLER];
        $translatedRecord = BackendUtility::getRecord('pages', $newPageId);
        $actionService->modifyRecord('pages', self::PAGE_DATAHANDLER, [
            'title' => 'DataHandlerTest changed',
            'nav_title' => 'datahandler',
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
        $actionService = new ActionService();
        $map = $actionService->createNewRecord('pages', -self::PAGE_DATAHANDLER, [
            'title' => 'New Page',
            'doktype' => 1,
        ]);
        $newPageId = $map['pages'][0];
        $actionService->modifyRecord('pages', $newPageId, [
            'nav_title' => 'datahandler',
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
        $actionService = new ActionService();
        $map = $actionService->createNewRecord('pages', -self::PAGE_DATAHANDLER, [
            'title' => 'New Page',
            'doktype' => 1,
            'nav_title' => 'datahandler',
            'sys_language_uid' => 1,
        ]);
        $newPageId = $map['pages'][0];
        $defaultLanguageRecord = BackendUtility::getRecord('pages', self::PAGE_DATAHANDLER);
        $newRecord = BackendUtility::getRecord('pages', $newPageId);
        self::assertEquals('datahandler', $defaultLanguageRecord['nav_title']);
        self::assertEquals('datahandler0', $newRecord['nav_title']);
    }
}
