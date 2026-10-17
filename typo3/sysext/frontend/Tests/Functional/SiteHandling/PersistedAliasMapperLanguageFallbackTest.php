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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Routing\Aspect\PersistedAliasMapper;
use TYPO3\CMS\Core\Routing\PageRouter;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Language ids of the fixture site: EN = 0 (default), FR = 1 (falls back to 0),
 * FR-CA = 2 (falls back to 1, then 0). This mirrors the fallback chain
 * it (8) -> en (1) -> default (0) from the ticket.
 */
final class PersistedAliasMapperLanguageFallbackTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TCA']['tt_content']['columns']['header']['config'] = [
            'type' => 'slug',
            'generatorOptions' => ['prefixParentPageSlug' => false],
            'fallbackCharacter' => '-',
            'eval' => 'uniqueInSite',
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->writeSiteConfiguration(
            'acme-com',
            array_merge(
                $this->buildSiteConfiguration(1000, 'https://acme.com/'),
                [
                    'routeEnhancers' => [
                        'Catalog' => [
                            'type' => 'Extbase',
                            'namespace' => 'shop',
                            'limitToPages' => [1000],
                            'routes' => [
                                ['routePath' => '/{record}', '_controller' => 'Catalog::detail'],
                            ],
                            'aspects' => [
                                'record' => [
                                    'type' => 'PersistedAliasMapper',
                                    'tableName' => 'tt_content',
                                    'routeFieldName' => 'header',
                                ],
                            ],
                        ],
                    ],
                ]
            ),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RouteEnhancerLanguageFallback.csv');
    }

    /**
     * The record 10 is a default language record with a translation into language 1 only.
     * A link generated for language 2 (fallback chain 1, 0) is expected to use the slug
     * of the translation in language 1.
     */
    #[Test]
    public function generateUriForDefaultLanguageRecordUsesSlugOfTranslationInFallbackLanguage(): void
    {
        // Given: language 2 falls back to language 1, which holds a translation of record 10
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme-com');
        $pageRouter = new PageRouter($site);

        // When: the detail link of record 10 is generated in language 2
        $uri = $pageRouter->generateUri(1000, [
            '_language' => $site->getLanguageById(2),
            'shop' => ['controller' => 'Catalog', 'action' => 'detail', 'record' => '10'],
        ]);

        // Then: the speaking slug of the translation in language 1 is used
        self::assertSame('https://acme.ca/fr-slug', (string)$uri);
    }

    /**
     * The record 20 exists only in language 1 (no default language parent). A link
     * generated for language 2, which falls back to language 1, is expected to use the
     * slug of that record like the link generated in language 1 itself does.
     */
    #[Test]
    public function generateUriForRecordExistingOnlyInFallbackLanguageUsesItsSlug(): void
    {
        // Given: record 20 exists only in language 1, language 2 falls back to language 1
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme-com');
        $pageRouter = new PageRouter($site);

        // When: the detail link of record 20 is generated in language 2
        $uri = $pageRouter->generateUri(1000, [
            '_language' => $site->getLanguageById(2),
            'shop' => ['controller' => 'Catalog', 'action' => 'detail', 'record' => '20'],
        ]);

        // Then: the speaking slug of record 20 is used instead of the numeric uid
        self::assertSame('https://acme.ca/fr-only-slug', (string)$uri);
    }

    /**
     * Control for the previous scenario: in the language the record lives in, the slug is used.
     */
    #[Test]
    public function generateUriForRecordExistingOnlyInFallbackLanguageUsesItsSlugInThatLanguage(): void
    {
        // Given: record 20 exists only in language 1
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme-com');
        $pageRouter = new PageRouter($site);

        // When: the detail link of record 20 is generated in language 1
        $uri = $pageRouter->generateUri(1000, [
            '_language' => $site->getLanguageById(1),
            'shop' => ['controller' => 'Catalog', 'action' => 'detail', 'record' => '20'],
        ]);

        // Then: the speaking slug of record 20 is used
        self::assertSame('https://acme.fr/fr-only-slug', (string)$uri);
    }

    /**
     * The opposite direction (slug to uid) honours the fallback chain: the slug of the
     * record existing only in language 1 is resolved while requesting language 2.
     */
    #[Test]
    public function resolveFindsRecordExistingOnlyInFallbackLanguage(): void
    {
        // Given: record 20 exists only in language 1, language 2 falls back to language 1
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme-com');
        $subject = new PersistedAliasMapper([
            'tableName' => 'tt_content',
            'routeFieldName' => 'header',
        ]);
        $subject->setSite($site);
        $subject->setSiteLanguage($site->getLanguageById(2));

        // When: the slug is resolved in language 2
        $result = $subject->resolve('fr-only-slug');

        // Then: the uid of record 20 is returned
        self::assertSame('20', $result);
    }

    /**
     * Comparison with the plain Plugin enhancer (core): same scenario as the Extbase variant for record 10.
     */
    #[Test]
    public function pluginEnhancerForDefaultLanguageRecordUsesSlugOfTranslationInFallbackLanguage(): void
    {
        // Given: a plain Plugin enhancer with the same PersistedAliasMapper aspect, language 2 falls back to 1
        $this->writeSiteConfiguration(
            'acme-com',
            array_merge(
                $this->buildSiteConfiguration(1000, 'https://acme.com/'),
                [
                    'routeEnhancers' => [
                        'Catalog' => [
                            'type' => 'Plugin',
                            'namespace' => 'shop',
                            'limitToPages' => [1000],
                            'routePath' => '/{record}',
                            'aspects' => [
                                'record' => [
                                    'type' => 'PersistedAliasMapper',
                                    'tableName' => 'tt_content',
                                    'routeFieldName' => 'header',
                                ],
                            ],
                        ],
                    ],
                ]
            ),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme-com');
        $pageRouter = new PageRouter($site);

        // When: the link of record 10 is generated in language 2
        $uri = $pageRouter->generateUri(1000, [
            '_language' => $site->getLanguageById(2),
            'shop' => ['record' => '10'],
        ]);

        // Then: the result equals the one of the Extbase enhancer
        self::assertSame('https://acme.ca/fr-slug', (string)$uri);
    }

    /**
     * Comparison with the plain Plugin enhancer (core): same scenario as the Extbase variant for record 20.
     */
    #[Test]
    public function pluginEnhancerForRecordExistingOnlyInFallbackLanguageUsesItsSlug(): void
    {
        // Given: a plain Plugin enhancer with the same PersistedAliasMapper aspect, language 2 falls back to 1
        $this->writeSiteConfiguration(
            'acme-com',
            array_merge(
                $this->buildSiteConfiguration(1000, 'https://acme.com/'),
                [
                    'routeEnhancers' => [
                        'Catalog' => [
                            'type' => 'Plugin',
                            'namespace' => 'shop',
                            'limitToPages' => [1000],
                            'routePath' => '/{record}',
                            'aspects' => [
                                'record' => [
                                    'type' => 'PersistedAliasMapper',
                                    'tableName' => 'tt_content',
                                    'routeFieldName' => 'header',
                                ],
                            ],
                        ],
                    ],
                ]
            ),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme-com');
        $pageRouter = new PageRouter($site);

        // When: the link of record 20 is generated in language 2
        $uri = $pageRouter->generateUri(1000, [
            '_language' => $site->getLanguageById(2),
            'shop' => ['record' => '20'],
        ]);

        // Then: the result equals the one of the Extbase enhancer
        self::assertSame('https://acme.ca/fr-only-slug', (string)$uri);
    }
}
