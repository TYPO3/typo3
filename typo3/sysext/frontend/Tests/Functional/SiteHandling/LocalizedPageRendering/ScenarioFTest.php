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
 *   Home page is not localized into any language and has l18n_cfg=3 set
 *   "About" page is localized into DE and has l18n_cfg=3 set
 *   "Products" page is localized into DE-CH, DE and has l18n_cfg=3 set
 *
 * Scenario expectations:
 *   Calling home page in EN returns a 404 response due to l18n_cfg=3
 *   Calling home page in DE renders page in DE
 *   Calling home page in DE-CH returns a 404 response because fallback chain is not processed due to l18n_cfg=3
 *
 *   Calling "about" page in EN returns a 404 response due to l18n_cfg=3
 *   Calling "about" page in DE renders page in DE
 *   Calling "about" page in DE-CH returns a 404 response because fallback chain is not processed due to l18n_cfg=3
 *
 *   Calling "products" page in EN returns a 404 response due to l18n_cfg=3
 *   Calling "products" page in DE renders page in DE
 *   Calling "products" page in DE-CH renders page in DE-CH
 */
class ScenarioFTest extends AbstractLocalizedPagesTestCase
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

        $this->setUpDatabaseWithYamlPayload(__DIR__ . '/Fixtures/ScenarioF.yaml');
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
            'products page in DE where page translation exists' => [
                'url' => 'https://acme.com/de/produkte',
                'scopes' => [
                    'page/title' => 'DE: Produkte',
                ],
            ],
            'products page in DE-CH where page translation exists' => [
                'url' => 'https://acme.com/de-ch/produkte',
                'scopes' => [
                    'page/title' => 'DE-CH: Produkte',
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
            'about page in DE-CH where page translation does not exist and is trapped by l18n_cfg' => [
                'url' => 'https://acme.com/de-ch/ueber-uns',
            ],
            'products page in EN' => [
                'url' => 'https://acme.com/en/products',
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
                    ['title' => 'DE: Willkommen', 'link' => '/de/willkommen'],
                    ['title' => 'DE: Über uns', 'link' => '/de/ueber-uns'],
                    ['title' => 'DE: Produkte', 'link' => '/de/produkte'],
                ],
            ],
            [
                'url' => 'https://acme.com/de/produkte',
                'menu' => [
                    ['title' => 'DE: Willkommen', 'link' => '/de/willkommen'],
                    ['title' => 'DE: Über uns', 'link' => '/de/ueber-uns'],
                    ['title' => 'DE: Produkte', 'link' => '/de/produkte'],
                ],
            ],
            [
                'url' => 'https://acme.com/de-ch/produkte',
                'menu' => [
                    ['title' => 'DE-CH: Produkte', 'link' => '/de-ch/produkte'],
                    // FIXME: Page "EN: Shortcut to welcome" must to be rendered in menu, needs a refactored menu generation
                    ['title' => 'EN: Shortcut to welcome', 'link' => '/de-ch/willkommen'],
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
