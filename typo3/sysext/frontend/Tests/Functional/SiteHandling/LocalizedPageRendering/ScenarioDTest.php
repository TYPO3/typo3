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
 *   Home page is not localized into any language and has l18n_cfg=2 set
 *   "About" page is localized into DE and has l18n_cfg=2 set
 *   "Products" page is localized into DE-CH and has l18n_cfg=2 set
 *
 * Scenario expectations:
 *   Calling home page in EN renders page in EN
 *   Calling home page in DE returns a 404 response
 *   Calling home page in DE-CH returns a 404 response because fallback chain is not processed due to l18n_cfg=2
 *
 *   Calling "about" page in EN renders page in EN
 *   Calling "about" page in DE renders page in DE
 *   Calling "about" page in DE-CH renders page in DE-CH
 */
class ScenarioDTest extends AbstractLocalizedPagesTestCase
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

        $this->setUpDatabaseWithYamlPayload(__DIR__ . '/Fixtures/ScenarioD.yaml');
    }

    /**
     * @return array
     */
    public function resolvablePagesDataProvider(): array
    {
        return [
            'home page in EN' => [
                'url' => 'https://acme.com/en/hello',
                'scopes' => [
                    'page/title' => 'EN: Welcome',
                ],
            ],
            'about page in EN' => [
                'url' => 'https://acme.com/en/about-us',
                'scopes' => [
                    'page/title' => 'EN: About us',
                ],
            ],
            'about page in DE where page translation exists' => [
                'url' => 'https://acme.com/de/ueber-uns',
                'scopes' => [
                    'page/title' => 'DE: Über uns',
                ],
            ],
            'about page in DE-CH where page translation exists' => [
                'url' => 'https://acme.com/de-ch/ueber-uns',
                'scopes' => [
                    'page/title' => 'DE-CH: Über uns',
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
            'home page in DE where page translation does not exist' => [
                'url' => 'https://acme.com/de/hello',
            ],
            'home page in DE-CH where page translation does not exist and is trapped by l18n_cfg' => [
                'url' => 'https://acme.com/de-ch/hello',
            ],
            'DE-CH shortcut to home page where page translation does not exist and is trapped by l18n_cfg' => [
                'url' => 'https://acme.com/de-ch/shortcut-to-welcome',
            ]
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
                'url' => 'https://acme.com/en/hello',
                'menu' => [
                    ['title' => 'EN: Welcome', 'link' => '/en/hello'],
                    ['title' => 'EN: About us', 'link' => '/en/about-us'],
                    ['title' => 'EN: Products', 'link' => '/en/products'],
                    ['title' => 'EN: Shortcut to welcome', 'link' => '/en/hello'],
                ],
            ],
            [
                'url' => 'https://acme.com/en/about-us',
                'menu' => [
                    ['title' => 'EN: Welcome', 'link' => '/en/hello'],
                    ['title' => 'EN: About us', 'link' => '/en/about-us'],
                    ['title' => 'EN: Products', 'link' => '/en/products'],
                    ['title' => 'EN: Shortcut to welcome', 'link' => '/en/hello'],
                ],
            ],
            [
                'url' => 'https://acme.com/de/ueber-uns',
                'menu' => [
                    ['title' => 'DE: Über uns', 'link' => '/de/ueber-uns'],
                ],
            ],
            [
                'url' => 'https://acme.com/de-ch/ueber-uns',
                'menu' => [
                    ['title' => 'DE-CH: Über uns', 'link' => '/de-ch/ueber-uns'],
                    ['title' => 'DE-CH: Produkte', 'link' => '/de-ch/produkte'],
                    ['title' => 'EN: Shortcut to welcome', 'link' => ''],
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
