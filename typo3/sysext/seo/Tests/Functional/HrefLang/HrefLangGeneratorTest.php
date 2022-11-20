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

namespace TYPO3\CMS\Seo\Tests\Functional\HrefLang;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class HrefLangGeneratorTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = ['seo'];

    /**
     * @var array
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
        'DE-CH' => ['id' => 2, 'title' => 'Swiss German', 'locale' => 'de_CH.UTF8', 'iso' => 'de', 'hrefLang' => 'de-CH', 'direction' => ''],
        'NL' => ['id' => 3, 'title' => 'Dutch', 'locale' => 'nl_NL.UTF8', 'iso' => 'nl', 'hrefLang' => '', 'direction' => ''],
        'FR' => ['id' => 4, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
        'DK' => ['id' => 5, 'title' => 'Danish', 'locale' => 'da_DK.UTF8', 'iso' => 'da', 'hrefLang' => 'da-DK', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de'),
                $this->buildLanguageConfiguration('DE-CH', '/de-ch', ['DE'], 'fallback'),
                $this->buildLanguageConfiguration('NL', '/nl'),
                $this->buildLanguageConfiguration('FR', '/fr'),
                $this->buildLanguageConfiguration('DK', '/dk', ['EN'], 'free'),
            ]
        );

        $this->withDatabaseSnapshot(function () {
            $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
            $backendUser = $this->setUpBackendUser(1);
            Bootstrap::initializeLanguageObject();
            $factory = DataHandlerFactory::fromYamlFile(__DIR__ . '/../Fixtures/HrefLangScenario.yml');
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            static::failIfArrayIsNotEmpty($writer->getErrors());
        });
    }

    /**
     * @test
     * @dataProvider checkHrefLangOutputDataProvider
     */
    public function checkHrefLangOutput(string $url, array $expectedTags, array $notExpectedTags): void
    {
        $this->setUpFrontendRootPage(
            1000,
            ['typo3/sysext/seo/Tests/Functional/Fixtures/HrefLang.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url)
        );
        $stream = $response->getBody();
        $stream->rewind();
        $content = $stream->getContents();

        foreach ($expectedTags as $expectedTag) {
            self::assertStringContainsString($expectedTag, $content);
        }

        foreach ($notExpectedTags as $notExpectedTag) {
            self::assertStringNotContainsString($notExpectedTag, $content);
        }
    }

    public function checkHrefLangOutputDataProvider(): array
    {
        return [
            'No translation available, so only hreflang tags expected for default language and fallback languages' => [
                'https://acme.com/',
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/"/>',
                    '<link rel="alternate" hreflang="de-CH" href="https://acme.com/de-ch/"/>',
                ],
                [
                    '<link rel="alternate" hreflang="de-DE"',
                ],
            ],
            'English page, with German translation' => [
                'https://acme.com/hello',
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/hello"/>',
                    '<link rel="alternate" hreflang="de-DE" href="https://acme.com/de/willkommen"/>',
                    '<link rel="alternate" hreflang="x-default" href="https://acme.com/hello"/>',
                ],
                [],
            ],
            'German page, with English translation and English default' => [
                'https://acme.com/de/willkommen',
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/hello"/>',
                    '<link rel="alternate" hreflang="de-DE" href="https://acme.com/de/willkommen"/>',
                    '<link rel="alternate" hreflang="x-default" href="https://acme.com/hello"/>',
                ],
                [],
            ],
            'English page, with German and Dutch translation, without Dutch hreflang config' => [
                'https://acme.com/hello',
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/hello"/>',
                    '<link rel="alternate" hreflang="de-DE" href="https://acme.com/de/willkommen"/>',
                    '<link rel="alternate" hreflang="x-default" href="https://acme.com/hello"/>',
                ],
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/nl/welkom"/>',
                    '<link rel="alternate" hreflang="" href="https://acme.com/nl/welkom"/>',
                ],
            ],
            'Dutch page, with German and English translation, without Dutch hreflang config' => [
                'https://acme.com/hello',
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/hello"/>',
                    '<link rel="alternate" hreflang="de-DE" href="https://acme.com/de/willkommen"/>',
                    '<link rel="alternate" hreflang="x-default" href="https://acme.com/hello"/>',
                ],
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/nl/welkom"/>',
                    '<link rel="alternate" hreflang="" href="https://acme.com/nl/welkom"/>',
                ],
            ],
            'English page with canonical' => [
                'https://acme.com/contact',
                [
                    '<link rel="alternate" hreflang="de-DE" href="https://acme.com/de/kontakt"/>',
                    '<link rel="alternate" hreflang="de-CH" href="https://acme.com/de-ch/kontakt"/>',
                ],
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/contact"/>',
                    '<link rel="alternate" hreflang="x-default" href="https://acme.com/contact"/>',
                ],
            ],
            'Swiss german page with canonical' => [
                'https://acme.com/de-ch/uber',
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/about"/>',
                    '<link rel="alternate" hreflang="x-default" href="https://acme.com/about"/>',
                    '<link rel="alternate" hreflang="de-DE" href="https://acme.com/de/uber"/>',
                ],
                [
                    '<link rel="alternate" hreflang="de-CH" href="https://acme.com/de-ch/uber"/>',
                ],
            ],
            'Swiss german page with fallback to German, without content' => [
                'https://acme.com/de-ch/produkte',
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/products"/>',
                    '<link rel="alternate" hreflang="x-default" href="https://acme.com/products"/>',
                    '<link rel="alternate" hreflang="de-DE" href="https://acme.com/de/produkte"/>',
                    '<link rel="alternate" hreflang="de-CH" href="https://acme.com/de-ch/produkte"/>',
                ],
                [],
            ],
            'Languages with fallback should have hreflang even when page record is not translated, strict languages without translations shouldnt' => [
                'https://acme.com/hello',
                [
                    '<link rel="alternate" hreflang="de-CH" href="https://acme.com/de-ch/willkommen"/>',
                ],
                [
                    '<link rel="alternate" hreflang="fr-FR"',
                ],
            ],
            'Pages with disabled hreflang generation should not render any hreflang tag' => [
                'https://acme.com/no-hreflang',
                [],
                [
                    '<link rel="alternate" hreflang="',
                ],
            ],
            'Translated pages with disabled hreflang generation in original language should not render any hreflang tag' => [
                'https://acme.com/de/kein-hreflang',
                [],
                [
                    '<link rel="alternate" hreflang="',
                ],
            ],
            'Languages with fallback type free should not have hreflang when page record is not translated' => [
                'https://acme.com/no-translation',
                [],
                [
                    '<link rel="alternate" hreflang="',
                ],
            ],
        ];
    }

    protected static function failIfArrayIsNotEmpty(array $items): void
    {
        if (empty($items)) {
            return;
        }

        self::fail(
            'Array was not empty as expected, but contained these items:' . LF
            . '* ' . implode(LF . '* ', $items)
        );
    }
}
