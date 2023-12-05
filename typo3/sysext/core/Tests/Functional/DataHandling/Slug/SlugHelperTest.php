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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for the SlugHelper
 */
final class SlugHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
        'DE' => ['id' => 2, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF-8'],
        'CH' => ['id' => 3, 'title' => 'Schweizer Deutsch', 'locale' => 'de_CH.UTF-8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/Pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
                $this->buildLanguageConfiguration('CH', '/de-CH/', ['DE', 'EN']),
            ],
        );
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();
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
     */
    public static function generateRespectsFallbackLanguageOfParentPageSlugDataProvider(): array
    {
        return [
            'default page / default parent' => [
                '/default-parent/default-page',
                [
                    'uid' => '13',
                    'title' => 'Default Page',
                    'sys_language_uid' => 0,
                ],
            ],
            'Dansk page / default parent' => [
                '/default-parent/dansk-page',
                [
                    'uid' => '13',
                    'title' => 'Dansk Page',
                    'sys_language_uid' => 1,
                ],
            ],
            'german page / german parent' => [
                '/german-parent/german-page',
                [
                    'uid' => '13',
                    'title' => 'German Page',
                    'sys_language_uid' => 2,
                ],
            ],
            'swiss page / german fallback parent' => [
                 '/german-parent/swiss-page',
                 [
                     'uid' => '13',
                     'title' => 'Swiss Page',
                     'sys_language_uid' => 3,
                 ],
             ],
        ];
    }

    /**
     * @dataProvider generateRespectsFallbackLanguageOfParentPageSlugDataProvider
     * @test
     */
    public function generateRespectsFallbackLanguageOfParentPageSlug(string $expected, array $page): void
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
                    'sys_language_uid' => $page['sys_language_uid'],
                ],
                (int)$page['uid']
            )
        );
    }
}
