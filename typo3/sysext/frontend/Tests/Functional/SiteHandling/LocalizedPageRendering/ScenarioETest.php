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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\LocalizedPageRendering;

/**
 * Scenario prerequisites:
 *   Site configuration has localizations
 *     default language: EN
 *     first language: DE
 *       no fallbacks configured
 *     second language: DE-CH
 *       fallback to DE, EN
 *
 *   Home page is not localized into any language and has l18n_cfg=1 set
 *   "About" page is localized into DE and has l18n_cfg=1 set
 *   "Products" page is localized into DE and has l18n_cfg=1 set
 *
 * Scenario expectations:
 *   Calling home page in EN returns a 404 response due to l18n_cfg=1
 *   Calling home page in DE returns a 404 response
 *   Calling home page in DE-CH returns a 404 response because EN is used as fallback but is not processed due to l18n_cfg=1
 *
 *   Calling "about" page in EN returns a 404 response due to l18n_cfg=1
 *   Calling "about" page in DE renders page in DE
 *   Calling "about" page in DE-CH renders page in DE
 *   Calling "about" page in DE with EN slug returns a 404 response
 *   Calling "about" page in DE-CH with EN slug renders page in DE
 */
class ScenarioETest extends AbstractLocalizedPagesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.com/en'),
                $this->buildLanguageConfiguration('DE', 'https://acme.com/de'),
                $this->buildLanguageConfiguration('DE-CH', 'https://acme.com/de-ch', ['DE', 'EN']),
            ]
        );

        $this->setUpDatabaseWithYamlPayload(__DIR__ . '/Fixtures/ScenarioE.yaml');
    }

    /**
     * @return array
     */
    public function resolvablePagesDataProvider(): array
    {
        return [
            'about page in DE where page translation exists' => [
                'url' => 'https://acme.com/de/ueber-uns',
                'scopes' => [
                    'page/title' => 'DE: Über uns',
                ],
            ],
            'about page in DE-CH where page translation does not exist' => [
                'url' => 'https://acme.com/de-ch/ueber-uns',
                'scopes' => [
                    'page/title' => 'DE: Über uns',
                ],
            ],
            'about page in DE-CH with EN slug' => [
                'url' => 'https://acme.com/de-ch/about-us',
                'scopes' => [
                    'page/title' => 'DE: Über uns',
                ],
            ],
        ];
    }

    /**
     * @param string $url
     * @param array $scopes
     *
     * @test
     * @dataProvider resolvablePagesDataProvider
     */
    public function resolvedPagesMatchScopes(string $url, array $scopes): void
    {
        $this->assertScopes($url, $scopes);
    }

    /**
     * @return array
     */
    public function pageNotFoundDataProvider(): array
    {
        return [
            'home page in EN' => [
                'url' => 'https://acme.com/en/hello',
            ],
            'home page in DE where page translation does not exist' => [
                'url' => 'https://acme.com/de/hello',
            ],
            'home page in DE-CH where page translation does not exist and is trapped by l18n_cfg' => [
                'url' => 'https://acme.com/de-ch/hello',
            ],
            'about page in EN' => [
                'url' => 'https://acme.com/en/about-us',
            ],
            'about page in DE with EN slug' => [
                'url' => 'https://acme.com/de/about-us',
            ],
        ];
    }

    /**
     * @param string $url
     *
     * @test
     * @dataProvider pageNotFoundDataProvider
     */
    public function pageNotFound(string $url): void
    {
        $this->assertResponseStatusCode($url, 404);
    }

    /**
     * @return array
     */
    public function menuDataProvider(): array
    {
        return [
            [
                'url' => 'https://acme.com/de/ueber-uns',
                'menu' => [
                    ['title' => 'DE: Über uns', 'link' => '/de/ueber-uns'],
                    ['title' => 'DE: Produkte', 'link' => '/de/produkte'],
                ],
            ],
            [
                'url' => 'https://acme.com/de-ch/ueber-uns',
                'menu' => [
                    ['title' => 'DE: Über uns', 'link' => '/de-ch/ueber-uns'],
                    ['title' => 'DE: Produkte', 'link' => '/de-ch/produkte'],
                    ['title' => 'EN: Shortcut to welcome', 'link' => '/de-ch/hello'],
                ],
            ],
            [
                'url' => 'https://acme.com/de-ch/about-us',
                'menu' => [
                    ['title' => 'DE: Über uns', 'link' => '/de-ch/ueber-uns'],
                    ['title' => 'DE: Produkte', 'link' => '/de-ch/produkte'],
                    ['title' => 'EN: Shortcut to welcome', 'link' => '/de-ch/hello'],
                ],
            ],
        ];
    }

    /**
     * @param string $url
     * @param array $expectedMenu
     *
     * @test
     * @dataProvider menuDataProvider
     */
    public function pageMenuIsRendered(string $url, array $expectedMenu): void
    {
        $this->assertMenu($url, $expectedMenu);
    }
}
