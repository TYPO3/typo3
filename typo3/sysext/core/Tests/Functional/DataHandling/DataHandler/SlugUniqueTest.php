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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests related to DataHandler slug unique handling
 */
final class SlugUniqueTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    public static function getEvalSettingDataProvider(): array
    {
        return [
            'uniqueInSite' => ['uniqueInSite'],
            'unique' => ['unique'],
            'uniqueInPid' => ['uniqueInPid'],
        ];
    }

    #[DataProvider('getEvalSettingDataProvider')]
    #[Test]
    public function differentUniqueEvalSettingsDeDuplicateSlug(string $uniqueSetting): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/TestSlugUniqueBase.csv');
        $GLOBALS['TCA']['pages']['columns']['slug']['config']['eval'] = $uniqueSetting;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->start(
            [
                'pages' => [
                    3 => [
                        'title' => 'Page One',
                        'slug' => 'page-one',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/TestSlugUniqueResult.csv');
    }

    #[DataProvider('getEvalSettingDataProvider')]
    #[Test]
    public function currentRecordIsExcludedWhenDeDuplicateSlug(string $uniqueSetting): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/TestSlugUniqueWithDeduplicatedSlugBase.csv');
        $GLOBALS['TCA']['pages']['columns']['slug']['config']['eval'] = $uniqueSetting;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->start(
            [
                'pages' => [
                    3 => [
                        'slug' => 'page-one-1',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/TestSlugUniqueResult.csv');
    }

    #[DataProvider('getEvalSettingDataProvider')]
    #[Test]
    public function differentUniqueEvalSettingsDeDuplicateSlugWhenCreatingNewRecords(string $uniqueSetting): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/TestSlugUniqueBase.csv');
        $GLOBALS['TCA']['pages']['columns']['slug']['config']['eval'] = $uniqueSetting;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->start(
            [
                'pages' => [
                    'NEW-1' => [
                        'pid' => 1,
                        'title' => 'Page Two',
                        'slug' => '',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/TestSlugUniqueNewRecordResult.csv');
    }
}
