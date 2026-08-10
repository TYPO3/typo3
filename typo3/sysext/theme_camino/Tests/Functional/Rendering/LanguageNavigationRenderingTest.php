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

namespace TYPO3\ThemeCamino\Tests\Functional\Rendering;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LanguageNavigationRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['theme_camino'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCsvDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:theme_camino/Configuration/Sets/camino/setup.typoscript',
                'EXT:theme_camino/Tests/Functional/Fixtures/TypoScript/LanguageNavigationRendering.typoscript',
            ]
        );
        $this->writeSiteConfiguration(
            'camino',
            array_replace_recursive(
                $this->buildSiteConfiguration(1, 'https://camino.example/'),
                [
                    'settings' => [
                        'camino' => [
                            'header' => [
                                'fixedPosition' => false,
                            ],
                        ],
                    ],
                ]
            ),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', [], 'free'),
            ]
        );
    }

    #[Test]
    public function languageNavigationIsRenderedInHeader(): void
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://camino.example/')->withPageId(1)
        );

        $content = (string)$response->getBody();

        self::assertStringContainsString('class="header__language-nav"', $content);
        self::assertStringContainsString('aria-label="Language menu"', $content);
        self::assertStringContainsString('JS_header-language-button', $content);
        self::assertStringContainsString('JS_header-language-menu', $content);
        self::assertMatchesRegularExpression('#<span(?=[^>]*class="header__language-button-text JS_header-language-current")[^>]*>\s*English\s*</span>#', $content);
        self::assertMatchesRegularExpression('#<a(?=[^>]*class="header__language-menu-link header__language-menu-link--active")(?=[^>]*aria-current="page")[^>]*>\s*English\s*</a>#', $content);
        self::assertMatchesRegularExpression('#<a(?=[^>]*class="header__language-menu-link")(?=[^>]*href="(?:https://camino\.example)?/de/")[^>]*>\s*German\s*</a>#', $content);
    }
}
