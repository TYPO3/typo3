<?php

declare(strict_types = 1);

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

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tests related to DataHandler slug unique handling
 */
class SlugUniqueTest extends AbstractDataHandlerActionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendSite(1);
    }

    /**
     * Create a simple site config for the tests that
     * call a frontend page.
     *
     * @param int $pageId
     * @param array $additionalLanguages
     */
    protected function setUpFrontendSite(int $pageId, array $additionalLanguages = [])
    {
        $languages = [
            0 => [
                'title' => 'English',
                'enabled' => true,
                'languageId' => 0,
                'base' => '/',
                'typo3Language' => 'default',
                'locale' => 'en_US.UTF-8',
                'iso-639-1' => 'en',
                'navigationTitle' => '',
                'hreflang' => '',
                'direction' => '',
                'flag' => 'us',
            ]
        ];
        $languages = array_merge($languages, $additionalLanguages);
        $configuration = [
            'rootPageId' => $pageId,
            'base' => '/',
            'languages' => $languages,
            'errorHandling' => [],
            'routes' => [],
        ];
        GeneralUtility::mkdir_deep($this->instancePath . '/typo3conf/sites/testing/');
        $yamlFileContents = Yaml::dump($configuration, 99, 2);
        $fileName = $this->instancePath . '/typo3conf/sites/testing/config.yaml';
        GeneralUtility::writeFile($fileName, $yamlFileContents);
        // Ensure that no other site configuration was cached before
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core');
        if ($cache->has('site-configuration')) {
            $cache->remove('site-configuration');
        }
    }

    /**
     * Data provider for differentUniqueEvalSettingsDeDuplicateSlug
     * @return array
     */
    public function getEvalSettingDataProvider(): array
    {
        return [
            'uniqueInSite' => ['uniqueInSite'],
            'unique' => ['unique'],
            'uniqueInPid' => ['uniqueInPid'],
        ];
    }

    /**
     * @dataProvider getEvalSettingDataProvider
     * @test
     * @param string $uniqueSetting
     */
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
        $this->assertCSVDataSet('typo3/sysext/core/Tests/Functional/DataHandling/DataHandler/DataSet/TestSlugUniqueResult.csv');
    }

    /**
     * @dataProvider getEvalSettingDataProvider
     * @test
     * @param string $uniqueSetting
     */
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

        $this->assertCSVDataSet('typo3/sysext/core/Tests/Functional/DataHandling/DataHandler/DataSet/TestSlugUniqueResult.csv');
    }

    /**
     * @dataProvider getEvalSettingDataProvider
     * @test
     * @param string $uniqueSetting
     */
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

        $this->assertCSVDataSet('typo3/sysext/core/Tests/Functional/DataHandling/DataHandler/DataSet/TestSlugUniqueNewRecordResult.csv');
    }
}
