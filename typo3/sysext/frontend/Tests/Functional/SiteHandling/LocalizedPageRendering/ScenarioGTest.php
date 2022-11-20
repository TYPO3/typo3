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
 *   Home page is localized into DE
 *
 *   This scenario covers the issue for https://forge.typo3.org/issues/94677
 *
 *   Generated menu shortcut links, which point to an untranslated page, but
 *   have a fallback in place, should keep the current language and not switch
 *   to the translation language.
 */
class ScenarioGTest extends AbstractLocalizedPagesTestCase
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

        $this->setUpDatabaseWithYamlPayload(__DIR__ . '/Fixtures/ScenarioG.yaml');
    }

    public function menuDataProvider(): array
    {
        return [
            [
                'url' => 'https://acme.com/de-ch/willkommen',
                'menu' => [
                    ['title' => 'DE-CH: Willkommen', 'link' => '/de-ch/willkommen'],
                    ['title' => 'DE: Über uns', 'link' => '/de-ch/ueber-uns'],
                    ['title' => 'DE: Abkürzung zu Über uns', 'link' => '/de-ch/ueber-uns'],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider menuDataProvider
     */
    public function pageMenuIsRendered(string $url, array $expectedMenu): void
    {
        $this->assertMenu($url, $expectedMenu);
    }
}
