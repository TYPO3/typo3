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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Slug;

use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the SlugHelper
 */
class SlugHelperTest extends AbstractDataHandlerActionTestCase
{
    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Slug/DataSet/';

    /**
     * Default Site Configuration
     * @var array
     */
    protected $siteLanguageConfiguration = [
        1 => [
            'title' => 'Dansk',
            'enabled' => true,
            'languageId' => 1,
            'base' => '/dk/',
            'typo3Language' => 'dk',
            'locale' => 'da_DK.UTF-8',
            'iso-639-1' => 'da',
            'flag' => 'dk',
            'fallbackType' => 'fallback',
            'fallbacks' => '0'
        ],
        2 => [
            'title' => 'Deutsch',
            'enabled' => true,
            'languageId' => 2,
            'base' => '/de/',
            'typo3Language' => 'de',
            'locale' => 'de_DE.UTF-8',
            'iso-639-1' => 'de',
            'flag' => 'de',
            'fallbackType' => 'fallback',
            'fallbacks' => '0'
        ],
        3 => [
            'title' => 'Schweizer Deutsch',
            'enabled' => true,
            'languageId' => 3,
            'base' => '/de-ch/',
            'typo3Language' => 'ch',
            'locale' => 'de_CH.UTF-8',
            'iso-639-1' => 'ch',
            'flag' => 'ch',
            'fallbackType' => 'fallback',
            'fallbacks' => '2,0'
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importScenarioDataSet('Pages');
        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    /**
     * DataProvider for testing the language resolving of the parent page.
     * - If the language can be resolved, get the slug of the current language
     * - If not, consecutively try the fallback languages from the site config
     * - As a last resort, fall back to the default language.
     *
     * Example languages:
     * 0 = "Default"
     * 1 = "Dansk" - (Fallback to Default)
     * 2 = "German" - (Fallback to Default)
     * 3 = "Swiss German" - (Fallback to German)
     *
     * @return array
     */
    public function generateRespectsFallbackLanguageOfParentPageSlugDataProvider(): array
    {
        return [
            'default page / default parent' => [
                '/default-parent/default-page',
                [
                    'uid' => '13',
                    'title' => 'Default Page',
                    'sys_language_uid' => 0
                ]
            ],
            'Dansk page / default parent' => [
                '/default-parent/dansk-page',
                [
                    'uid' => '13',
                    'title' => 'Dansk Page',
                    'sys_language_uid' => 1
                ],
            ],
            'german page / german parent' => [
                '/german-parent/german-page',
                [
                    'uid' => '13',
                    'title' => 'German Page',
                    'sys_language_uid' => 2
                ]
            ],
            'swiss page / german fallback parent' => [
                 '/german-parent/swiss-page',
                 [
                     'uid' => '13',
                     'title' => 'Swiss Page',
                     'sys_language_uid' => 3
                 ],
             ],
        ];
    }

    /**
     * @dataProvider generateRespectsFallbackLanguageOfParentPageSlugDataProvider
     * @param string $expected
     * @param array $page
     * @test
     */
    public function generateRespectsFallbackLanguageOfParentPageSlug(string $expected, array $page)
    {
        $slugHelper = GeneralUtility::makeInstance(
            SlugHelper::class,
            'pages',
            'slug',
            [
                'generatorOptions' => [
                    'fields' => ['title'],
                    'prefixParentPageSlug' => true,
                ],
            ]
        );

        self::assertEquals(
            $expected,
            $slugHelper->generate(
                [
                    'title' => $page['title'],
                    'uid' => $page['uid'],
                    'sys_language_uid' => $page['sys_language_uid']
                ],
                (int)$page['uid']
            )
        );
    }
}
