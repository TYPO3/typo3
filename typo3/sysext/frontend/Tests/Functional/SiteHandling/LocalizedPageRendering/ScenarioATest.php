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
 * Language container test definition
 *
 * Scenario prerequisites:
 *   Site configuration has localizations
 *     first language: DE
 *       no fallbacks configured
 *     second language: DE-CH
 *       fallback to DE
 *
 *   Home page is localized into DE
 *
 * Scenario expectations:
 *   Calling home page in EN renders page in EN
 *   Calling home page in DE renders page in DE
 *   Calling home page in DE-CH renders page in DE as defined in the fallback chain
 *
 *   Calling "headquarter" page in EN renders page in EN
 *   Calling "headquarter" page in DE returns a 404 response because no fallback chain is configured
 *   Calling "headquarter" page in DE-CH renders page in EN
 */
class ScenarioATest extends AbstractLocalizedPagesTestCase
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
                $this->buildLanguageConfiguration('DE-CH', 'https://acme.com/de-ch', ['DE']),
            ]
        );

        $this->setUpDatabaseWithYamlPayload(__DIR__ . '/Fixtures/ScenarioA.yaml');
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
            'home page in DE where page translation exists' => [
                'url' => 'https://acme.com/de/willkommen',
                'scopes' => [
                    'page/title' => 'DE: Willkommen',
                ],
            ],
            'home page in DE-CH where page translation does not exist' => [
                'url' => 'https://acme.com/de-ch/willkommen',
                'scopes' => [
                    'page/title' => 'DE: Willkommen',
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
    public function menuDataProvider(): array
    {
        return [
            [
                'url' => 'https://acme.com/en/hello',
                'menu' => [
                    ['title' => 'EN: Welcome', 'link' => '/en/hello'],
                ],
            ],
            [
                'url' => 'https://acme.com/de/willkommen',
                'menu' => [
                    ['title' => 'DE: Willkommen', 'link' => '/de/willkommen'],
                ],
            ],
            [
                'url' => 'https://acme.com/de-ch/willkommen',
                'menu' => [
                    ['title' => 'DE: Willkommen', 'link' => '/de-ch/willkommen'],
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
