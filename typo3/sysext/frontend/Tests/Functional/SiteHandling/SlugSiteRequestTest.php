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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\PermutationUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Test case for frontend requests having site handling configured
 */
class SlugSiteRequestTest extends AbstractTestCase
{
    // Force subrequest-based errors ON, because some tests can't work otherwise.
    protected $configurationToUseInTestInstance = [
        'SYS' => [
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
            'features' => [
                'subrequestPageErrors' => true,
            ],
        ],
        'FE' => [
            'cacheHash' => [
                'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]'],
                'excludedParameters' => ['tx_testing_link[excludedValue]'],
                'enforceValidation' => true,
            ],
            'debug' => false,
        ],
        'SC_OPTIONS' => [
            'Core/TypoScript/TemplateService' => [
                'runThroughTemplatesPostProcessing' => [
                    'FunctionalTest' => \TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Hook\TypoScriptInstructionModifier::class . '->apply',
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function setUpDatabase(): void
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/Fixtures/SlugScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );

        $this->setUpFrontendRootPage(
            1000,
            [
                'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/JsonRenderer.typoscript',
            ],
            [
                'title' => 'ACME Root',
            ]
        );
    }

    /**
     * @return array
     */
    public function requestsAreRedirectedWithoutHavingDefaultSiteLanguageDataProvider(): array
    {
        $domainPaths = [
            'https://website.local/',
            'https://website.local/?',
            // @todo: See how core should act here and activate this or have an own test for this scenario
            // 'https://website.local//',
        ];

        return $this->wrapInArray(
            $this->keysFromValues($domainPaths)
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider requestsAreRedirectedWithoutHavingDefaultSiteLanguageDataProvider
     */
    public function requestsAreRedirectedWithoutHavingDefaultSiteLanguage(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $expectedStatusCode = 307;
        $expectedHeaders = [
            'X-Redirect-By' => ['TYPO3 Shortcut/Mountpoint'],
            'location' => ['https://website.local/welcome'],
        ];

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame($expectedHeaders, $response->getHeaders());
    }

    /**
     * @return array
     */
    public function shortcutsAreRedirectedDataProvider(): array
    {
        $domainPaths = [
            'https://website.local/',
            'https://website.local/?',
            // @todo: See how core should act here and activate this or have an own test for this scenario
            // 'https://website.local//',
        ];

        return $this->wrapInArray(
            $this->keysFromValues($domainPaths)
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider shortcutsAreRedirectedDataProvider
     */
    public function shortcutsAreRedirectedToDefaultSiteLanguage(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/'),
            ]
        );

        $expectedStatusCode = 307;
        $expectedHeaders = [
            'location' => ['https://website.local/en-en/'],
        ];

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame($expectedHeaders, $response->getHeaders());
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider shortcutsAreRedirectedDataProvider
     */
    public function shortcutsAreRedirectedAndRenderFirstSubPage(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/'),
            ]
        );

        $expectedStatusCode = 200;
        $expectedPageTitle = 'EN: Welcome';

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            null,
            true
        );
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            $expectedStatusCode,
            $response->getStatusCode()
        );
        self::assertSame(
            $expectedPageTitle,
            $responseStructure->getScopePath('page/title')
        );
    }

    /**
     * @return array
     */
    public function shortcutsAreRedirectedDataProviderWithChineseCharacterInBase(): array
    {
        $domainPaths = [
            'https://website.local/简',
            'https://website.local/简?',
            'https://website.local/简/',
            'https://website.local/简/?',
        ];

        return $this->wrapInArray(
            $this->keysFromValues($domainPaths)
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider shortcutsAreRedirectedDataProviderWithChineseCharacterInBase
     */
    public function shortcutsAreRedirectedToDefaultSiteLanguageWithChineseCharacterInBase(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/简/'),
            [
                $this->buildDefaultLanguageConfiguration('ZH-CN', '/'),
            ]
        );

        $expectedStatusCode = 307;
        $expectedHeaders = [
            'X-Redirect-By' => ['TYPO3 Shortcut/Mountpoint'],
            // We cannot expect 简 here directly, as they are rawurlencoded() in the used Symfony UrlGenerator.
            'location' => ['https://website.local/%E7%AE%80/welcome'],
        ];

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame($expectedHeaders, $response->getHeaders());
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider shortcutsAreRedirectedDataProviderWithChineseCharacterInBase
     */
    public function shortcutsAreRedirectedAndRenderFirstSubPageWithChineseCharacterInBase(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/简/'),
            [
                $this->buildDefaultLanguageConfiguration('ZH-CN', '/'),
            ]
        );

        $expectedStatusCode = 200;
        $expectedPageTitle = 'EN: Welcome';

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            null,
            true
        );
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            $expectedStatusCode,
            $response->getStatusCode()
        );
        self::assertSame(
            $expectedPageTitle,
            $responseStructure->getScopePath('page/title')
        );
    }

    /**
     * @test
     */
    public function invalidSiteResultsInNotFoundResponse(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404])
        );

        $uri = 'https://website.other/any/invalid/slug';
        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function invalidSlugOutsideSiteLanguageResultsInNotFoundResponse(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404])
        );

        $uri = 'https://website.local/any/invalid/slug';
        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));

        self::assertSame(
            404,
            $response->getStatusCode()
        );
        self::assertStringContainsString(
            'message: The requested page does not exist',
            (string)$response->getBody()
        );
    }

    /**
     * @test
     */
    public function invalidSlugInsideSiteLanguageResultsInNotFoundResponse(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404])
        );

        $uri = 'https://website.local/en-en/any/invalid/slug';
        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));

        self::assertSame(
            404,
            $response->getStatusCode()
        );
        self::assertStringContainsString(
            'message: The requested page does not exist',
            (string)$response->getBody()
        );
    }

    /**
     * @test
     */
    public function unconfiguredTypeNumResultsIn500Error(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [500])
        );

        $uri = 'https://website.local/en-en/?type=13';
        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));

        self::assertSame(
            500,
            $response->getStatusCode()
        );
        self::assertStringContainsString(
            'message: The page is not configured',
            (string)$response->getBody()
        );
    }

    /**
     * @return array
     */
    public function pageIsRenderedWithPathsDataProvider(): array
    {
        $domainPaths = [
            'https://website.local/en-en/welcome',
            'https://website.local/fr-fr/bienvenue',
            'https://website.local/fr-ca/bienvenue',
            'https://website.local/简/简-bienvenue',
        ];

        return array_map(
            static function (string $uri) {
                if (str_contains($uri, '/fr-fr/')) {
                    $expectedPageTitle = 'FR: Welcome';
                } elseif (str_contains($uri, '/fr-ca/')) {
                    $expectedPageTitle = 'FR-CA: Welcome';
                } elseif (strpos($uri, '/简/') !== false) {
                    $expectedPageTitle = 'ZH-CN: Welcome';
                } else {
                    $expectedPageTitle = 'EN: Welcome';
                }
                return [$uri, $expectedPageTitle];
            },
            $this->keysFromValues($domainPaths)
        );
    }

    /**
     * @param string $uri
     * @param string $expectedPageTitle
     *
     * @test
     * @dataProvider pageIsRenderedWithPathsDataProvider
     */
    public function pageIsRenderedWithPaths(string $uri, string $expectedPageTitle): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/'),
                $this->buildLanguageConfiguration('FR', '/fr-fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca/', ['FR', 'EN']),
                $this->buildLanguageConfiguration('ZH', '/简/', ['EN']),
            ]
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            200,
            $response->getStatusCode()
        );
        self::assertSame(
            $expectedPageTitle,
            $responseStructure->getScopePath('page/title')
        );
    }

    public function pageIsRenderedWithPathsAndChineseDefaultLanguageDataProvider(): array
    {
        $domainPaths = [
            'https://website.local/简/简-bienvenue',
            'https://website.local/fr-fr/zh-bienvenue',
            'https://website.local/fr-ca/zh-bienvenue',
        ];

        return array_map(
            static function (string $uri) {
                if (strpos($uri, '/fr-fr/') !== false) {
                    $expectedPageTitle = 'FR: Welcome ZH Default';
                } elseif (strpos($uri, '/fr-ca/') !== false) {
                    $expectedPageTitle = 'FR-CA: Welcome ZH Default';
                } else {
                    $expectedPageTitle = 'ZH-CN: Welcome Default';
                }
                return [$uri, $expectedPageTitle];
            },
            $this->keysFromValues($domainPaths)
        );
    }

    /**
     * @test
     * @dataProvider pageIsRenderedWithPathsAndChineseDefaultLanguageDataProvider
     */
    public function pageIsRenderedWithPathsAndChineseDefaultLanguage(string $uri, string $expectedPageTitle): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('ZH-CN', '/简/'),
                $this->buildLanguageConfiguration('FR', '/fr-fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca/', ['FR', 'EN']),
            ]
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            200,
            $response->getStatusCode()
        );
        self::assertSame(
            $expectedPageTitle,
            $responseStructure->getScopePath('page/title')
        );
    }

    /**
     * @return array
     */
    public function pageIsRenderedWithDomainsDataProvider(): array
    {
        $domainPaths = [
            'https://website.us/welcome',
            'https://website.fr/bienvenue',
            'https://website.ca/bienvenue',
            // Explicitly testing chinese character domains
            'https://website.简/简-bienvenue',
        ];

        return array_map(
            static function (string $uri) {
                if (str_contains($uri, '.fr/')) {
                    $expectedPageTitle = 'FR: Welcome';
                } elseif (str_contains($uri, '.ca/')) {
                    $expectedPageTitle = 'FR-CA: Welcome';
                } elseif (strpos($uri, '.简/') !== false) {
                    $expectedPageTitle = 'ZH-CN: Welcome';
                } else {
                    $expectedPageTitle = 'EN: Welcome';
                }
                return [$uri, $expectedPageTitle];
            },
            $this->keysFromValues($domainPaths)
        );
    }

    /**
     * @param string $uri
     * @param string $expectedPageTitle
     *
     * @test
     * @dataProvider pageIsRenderedWithDomainsDataProvider
     */
    public function pageIsRenderedWithDomains(string $uri, string $expectedPageTitle): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://website.us/'),
                $this->buildLanguageConfiguration('FR', 'https://website.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://website.ca/', ['FR', 'EN']),
                $this->buildLanguageConfiguration('ZH', 'https://website.简/', ['EN']),
            ]
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            200,
            $response->getStatusCode()
        );
        self::assertSame(
            $expectedPageTitle,
            $responseStructure->getScopePath('page/title')
        );
    }

    /**
     * @test
     */
    public function pageIsRenderedWithTrailingSlash(): void
    {
        $uri = 'https://website.us/features/frontend-editing/';

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://website.us/'),
                $this->buildLanguageConfiguration('FR', 'https://website.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://website.ca/', ['FR', 'EN']),
            ]
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        $responseStructure = ResponseContent::fromString((string)$response->getBody());
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('EN: Frontend Editing', $responseStructure->getScopePath('page/title'));
    }

    /**
     * @return array
     */
    public function restrictedPageIsRenderedDataProvider(): array
    {
        $instructions = [
            // frontend user 1
            ['https://website.local/my-acme/whitepapers', 1, 'Whitepapers'],
            ['https://website.local/my-acme/whitepapers/products', 1, 'Products'],
            ['https://website.local/my-acme/whitepapers/solutions', 1, 'Solutions'],
            // frontend user 2
            ['https://website.local/my-acme/whitepapers', 2, 'Whitepapers'],
            ['https://website.local/my-acme/whitepapers/products', 2, 'Products'],
            ['https://website.local/my-acme/whitepapers/research', 2, 'Research'],
            ['https://website.local/my-acme/forecasts', 2, 'Forecasts'],
            ['https://website.local/my-acme/forecasts/current-year', 2, 'Current Year'],
            // frontend user 3
            ['https://website.local/my-acme/whitepapers', 3, 'Whitepapers'],
            ['https://website.local/my-acme/whitepapers/products', 3, 'Products'],
            ['https://website.local/my-acme/whitepapers/solutions', 3, 'Solutions'],
            ['https://website.local/my-acme/whitepapers/research', 3, 'Research'],
            ['https://website.local/my-acme/forecasts', 3, 'Forecasts'],
            ['https://website.local/my-acme/forecasts/current-year', 3, 'Current Year'],
        ];

        return $this->keysFromTemplate($instructions, '%1$s (user:%2$s)');
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     * @param string $expectedPageTitle
     *
     * @test
     * @dataProvider restrictedPageIsRenderedDataProvider
     */
    public function restrictedPageIsRendered(string $uri, int $frontendUserId, string $expectedPageTitle): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            200,
            $response->getStatusCode()
        );
        self::assertSame(
            $expectedPageTitle,
            $responseStructure->getScopePath('page/title')
        );
    }

    /**
     * @return array
     */
    public function restrictedPageWithParentSysFolderIsRenderedDataProvider(): array
    {
        $instructions = [
            // frontend user 4
            ['https://website.local/sysfolder-restricted', 4, 'FEGroups Restricted'],
        ];

        return $this->keysFromTemplate($instructions, '%1$s (user:%2$s)');
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     * @param string $expectedPageTitle
     *
     * @test
     * @dataProvider restrictedPageWithParentSysFolderIsRenderedDataProvider
     */
    public function restrictedPageWithParentSysFolderIsRendered(string $uri, int $frontendUserId, string $expectedPageTitle): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            200,
            $response->getStatusCode()
        );
        self::assertSame(
            $expectedPageTitle,
            $responseStructure->getScopePath('page/title')
        );
    }

    /**
     * @return array
     */
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorDataProvider(): array
    {
        $instructions = [
            // no frontend user given
            ['https://website.local/my-acme/whitepapers', 0],
            // ['https://website.local/my-acme/whitepapers/products', 0], // @todo extendToSubpages currently missing
            ['https://website.local/my-acme/whitepapers/solutions', 0],
            ['https://website.local/my-acme/whitepapers/research', 0],
            ['https://website.local/my-acme/forecasts', 0],
            // ['https://website.local/my-acme/forecasts/current-year', 0], // @todo extendToSubpages currently missing
            // frontend user 1
            ['https://website.local/my-acme/whitepapers/research', 1],
            ['https://website.local/my-acme/forecasts', 1],
            // ['https://website.local/my-acme/forecasts/current-year', 1], // @todo extendToSubpages currently missing
            // frontend user 2
            ['https://website.local/my-acme/whitepapers/solutions', 2],
        ];

        return $this->keysFromTemplate($instructions, '%1$s (user:%2$s)');
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     *
     * @test
     * @dataProvider restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorDataProvider
     */
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorWithoutHavingErrorHandling(string $uri, int $frontendUserId): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );

        self::assertSame(
            403,
            $response->getStatusCode()
        );
        self::assertThat(
            (string)$response->getBody(),
            self::logicalOr(
                self::stringContains('Reason: ID was not an accessible page'),
                self::stringContains('Reason: Subsection was found and not accessible')
            )
        );
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     *
     * @test
     * @dataProvider restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorDataProvider
     */
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorWithHavingFluidErrorHandling(string $uri, int $frontendUserId): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Fluid', [403])
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );

        self::assertSame(
            403,
            $response->getStatusCode()
        );
        self::assertStringContainsString(
            'reasons: code,fe_group',
            (string)$response->getBody()
        );
        self::assertThat(
            (string)$response->getBody(),
            self::logicalOr(
                self::stringContains('message: ID was not an accessible page'),
                self::stringContains('message: Subsection was found and not accessible')
            )
        );
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     *
     * @test
     * @dataProvider restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorDataProvider
     */
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorWithHavingPageErrorHandling(string $uri, int $frontendUserId): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Page', [403])
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );

        self::assertSame(
            403,
            $response->getStatusCode()
        );
        self::assertThat(
            (string)$response->getBody(),
            self::logicalOr(
                self::stringContains('That page is forbidden to you'),
                self::stringContains('ID was not an accessible page'),
                self::stringContains('Subsection was found and not accessible')
            )
        );
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     *
     * @test
     * @dataProvider restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorDataProvider
     */
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorWithHavingPhpErrorHandling(string $uri, int $frontendUserId): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('PHP', [403])
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );
        $json = json_decode((string)$response->getBody(), true);

        self::assertSame(
            403,
            $response->getStatusCode()
        );
        self::assertThat(
            $json['message'] ?? null,
            self::logicalOr(
                self::identicalTo('ID was not an accessible page'),
                self::identicalTo('Subsection was found and not accessible')
            )
        );
    }

    /**
     * @return array
     */
    public function restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorDataProvider(): array
    {
        $instructions = [
            // no frontend user given
            ['https://website.local/sysfolder-restricted', 0],
            // frontend user 1
            ['https://website.local/sysfolder-restricted', 1],
            // frontend user 2
            ['https://website.local/sysfolder-restricted', 2],
            // frontend user 3
            ['https://website.local/sysfolder-restricted', 3],
        ];

        return $this->keysFromTemplate($instructions, '%1$s (user:%2$s)');
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     *
     * @test
     * @dataProvider restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorDataProvider
     */
    public function restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorWithoutHavingErrorHandling(string $uri, int $frontendUserId): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );

        self::assertSame(
            403,
            $response->getStatusCode()
        );
        self::assertThat(
            (string)$response->getBody(),
            self::logicalOr(
                self::stringContains('Reason: ID was not an accessible page'),
                self::stringContains('Reason: Subsection was found and not accessible')
            )
        );
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     *
     * @test
     * @dataProvider restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorDataProvider
     */
    public function restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorWithHavingFluidErrorHandling(string $uri, int $frontendUserId): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Fluid', [403])
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );

        self::assertSame(
            403,
            $response->getStatusCode()
        );
        self::assertStringContainsString(
            'reasons: code,fe_group',
            (string)$response->getBody()
        );
        self::assertThat(
            (string)$response->getBody(),
            self::logicalOr(
                self::stringContains('message: ID was not an accessible page'),
                self::stringContains('message: Subsection was found and not accessible')
            )
        );
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     *
     * @test
     * @dataProvider restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorDataProvider
     */
    public function restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorWithHavingPageErrorHandling(string $uri, int $frontendUserId): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Page', [403])
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );

        self::assertSame(
            403,
            $response->getStatusCode()
        );
        self::assertThat(
            (string)$response->getBody(),
            self::logicalOr(
                self::stringContains('That page is forbidden to you'),
                self::stringContains('ID was not an accessible page'),
                self::stringContains('Subsection was found and not accessible')
            )
        );
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     *
     * @test
     * @dataProvider restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorDataProvider
     */
    public function restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorWithHavingPhpErrorHandling(string $uri, int $frontendUserId): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('PHP', [403])
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );
        $json = json_decode((string)$response->getBody(), true);

        self::assertSame(
            403,
            $response->getStatusCode()
        );
        self::assertThat(
            $json['message'] ?? null,
            self::logicalOr(
                self::identicalTo('ID was not an accessible page'),
                self::identicalTo('Subsection was found and not accessible')
            )
        );
    }

    /**
     * @return array
     */
    public function hiddenPageSends404ResponseRegardlessOfVisitorGroupDataProvider(): array
    {
        $instructions = [
            // hidden page, always 404
            ['https://website.local/never-visible-working-on-it', 0],
            ['https://website.local/never-visible-working-on-it', 1],
            // hidden fe group restricted and fegroup generally okay
            ['https://website.local/sysfolder-restricted-hidden', 4],
        ];

        return $this->keysFromTemplate($instructions, '%1$s (user:%2$s)');
    }

    /**
     * @test
     * @dataProvider hiddenPageSends404ResponseRegardlessOfVisitorGroupDataProvider
     */
    public function hiddenPageSends404ResponseRegardlessOfVisitorGroup(string $uri, int $frontendUserId): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('PHP', [404])
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId($frontendUserId)
        );
        $json = json_decode((string)$response->getBody(), true);

        self::assertSame(
            404,
            $response->getStatusCode()
        );
        self::assertThat(
            $json['message'] ?? null,
            self::identicalTo('The requested page does not exist!')
        );
    }

    /**
     * @return array
     */
    public function pageRenderingStopsWithInvalidCacheHashDataProvider(): array
    {
        $domainPaths = [
            'https://website.local/',
        ];

        $queries = [
            '',
            'welcome',
        ];

        $customQueries = [
            '?testing[value]=1',
            '?testing[value]=1&cHash=',
            '?testing[value]=1&cHash=WRONG',
        ];

        return $this->wrapInArray(
            $this->keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $queries, $customQueries])
            )
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider pageRenderingStopsWithInvalidCacheHashDataProvider
     */
    public function pageRequestNotFoundInvalidCacheHashWithoutHavingErrorHandling(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider pageRenderingStopsWithInvalidCacheHashDataProvider
     */
    public function pageRequestSendsNotFoundResponseWithInvalidCacheHashWithHavingFluidErrorHandling(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Fluid', [404])
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));

        self::assertSame(
            404,
            $response->getStatusCode()
        );
        self::assertThat(
            (string)$response->getBody(),
            self::logicalOr(
                self::stringContains('message: Request parameters could not be validated (&amp;cHash empty)'),
                self::stringContains('message: Request parameters could not be validated (&amp;cHash comparison failed)')
            )
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider pageRenderingStopsWithInvalidCacheHashDataProvider
     */
    public function pageRequestSendsNotFoundResponseWithInvalidCacheHashWithHavingPageErrorHandling(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Page', [404, 500])
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));

        self::assertSame(
            404,
            $response->getStatusCode()
        );
        self::assertThat(
            (string)$response->getBody(),
            self::stringContains('That page was not found')
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider pageRenderingStopsWithInvalidCacheHashDataProvider
     */
    public function pageRequestSendsNotFoundResponseWithInvalidCacheHashWithHavingPhpErrorHandling(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('PHP', [404])
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        $json = json_decode((string)$response->getBody(), true);

        self::assertSame(
            404,
            $response->getStatusCode()
        );
        self::assertThat(
            $json['message'] ?? null,
            self::logicalOr(
                self::identicalTo('Request parameters could not be validated (&cHash empty)'),
                self::identicalTo('Request parameters could not be validated (&cHash comparison failed)')
            )
        );
    }

    /**
     * @return array
     */
    public function pageIsRenderedWithValidCacheHashDataProvider(): array
    {
        $domainPaths = [
            'https://website.local/',
        ];

        // cHash has been calculated with encryption key set to
        // '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6'
        $queries = [
            // @todo Currently fails since cHash is verified after(!) redirect to page 1100
            // '?cHash=7d1f13fa91159dac7feb3c824936b39d',
            // '?cHash=7d1f13fa91159dac7feb3c824936b39d',
            'welcome?cHash=f42b850e435f0cedd366f5db749fc1af',
        ];

        $customQueries = [
            '&testing[value]=1',
        ];

        $dataSet = $this->wrapInArray(
            $this->keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $queries, $customQueries])
            )
        );

        return $dataSet;
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider pageIsRenderedWithValidCacheHashDataProvider
     */
    public function pageIsRenderedWithValidCacheHash($uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );
        self::assertSame(
            '1',
            $responseStructure->getScopePath('getpost/testing.value')
        );
    }

    public function crossSiteShortcutsAreRedirectedDataProvider(): array
    {
        return [
            'shortcut is redirected' => [
                'https://website.local/cross-site-shortcut',
                307,
                [
                    'X-Redirect-By' => ['TYPO3 Shortcut/Mountpoint'],
                    'location' => ['https://blog.local/authors'],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider crossSiteShortcutsAreRedirectedDataProvider
     */
    public function crossSiteShortcutsAreRedirected(string $uri, int $expectedStatusCode, array $expectedHeaders): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );
        $this->writeSiteConfiguration(
            'blog-local',
            $this->buildSiteConfiguration(2000, 'https://blog.local/')
        );
        $this->setUpFrontendRootPage(
            2000,
            [
                'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/JsonRenderer.typoscript',
            ],
            [
                'title' => 'ACME Blog',
            ]
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame($expectedHeaders, $response->getHeaders());
    }

    public function pageIsRenderedForVersionedPageDataProvider(): \Generator
    {
        yield 'Live page with logged-in user' => [
            'url' => 'https://website.local/en-en/welcome',
            'pageTitle' => 'EN: Welcome',
            'Online Page ID' => 1100,
            'Workspace ID' => 0,
            'Backend User ID' => 1,
            'statusCode' => 200,
        ];
        yield 'Live page with logged-in user accessed even though versioned page slug was changed' => [
            'url' => 'https://website.local/en-en/welcome',
            'pageTitle' => 'EN: Welcome to ACME Inc',
            'Online Page ID' => 1100,
            'Workspace ID' => 1,
            'Backend User ID' => 1,
            'statusCode' => 200,
        ];
        yield 'Versioned page with logged-in user and modified slug' => [
            'url' => 'https://website.local/en-en/welcome-modified',
            'pageTitle' => 'EN: Welcome to ACME Inc',
            'Online Page ID' => 1100,
            'Workspace ID' => 1,
            'Backend User ID' => 1,
            'statusCode' => 200,
        ];
        yield 'Versioned page without logged-in user renders 404' => [
            'url' => 'https://website.local/en-en/welcome-modified',
            'pageTitle' => null,
            'Online Page ID' => null,
            'Workspace ID' => 1,
            'Backend User ID' => 0,
            'statusCode' => 404,
        ];
    }

    /**
     * @test
     * @dataProvider pageIsRenderedForVersionedPageDataProvider
     */
    public function pageIsRenderedForVersionedPage(string $url, ?string $expectedPageTitle, ?int $expectedPageId, int $workspaceId, int $backendUserId, int $expectedStatusCode): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/'),
                $this->buildLanguageConfiguration('FR', '/fr-fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca/', ['FR', 'EN']),
            ]
        );
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($url)),
            (new InternalRequestContext())
                ->withWorkspaceId($backendUserId !== 0 ? $workspaceId : 0)
                ->withBackendUserId($backendUserId)
        );
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame($expectedPageId, $responseStructure->getScopePath('page/uid') ? (int)$responseStructure->getScopePath('page/uid') : null);
        self::assertSame($expectedPageTitle, $responseStructure->getScopePath('page/title'));
    }

    public function defaultLanguagePageNotResolvedForSiteLanguageBaseIfLanguagePageExistsDataProvider(): \Generator
    {
        yield 'Default slug with default base resolves' => [
            'uri' => 'https://website.local/welcome/',
            'recordUpdates' => [],
            'fallbackIdentifiers' => [
                'EN',
            ],
            'fallbackType' => 'strict',
            'expectedStatusCode' => 200,
            'expectedPageTitle' => 'EN: Welcome',
        ];

        yield 'FR slug with FR base resolves' => [
            'uri' => 'https://website.local/fr-fr/bienvenue/',
            'recordUpdates' => [],
            'fallbackIdentifiers' => [
                'EN',
            ],
            'fallbackType' => 'strict',
            'expectedStatusCode' => 200,
            'expectedPageTitle' => 'FR: Welcome',
        ];

        // Using default language slug with language base should be page not found if language page is active.
        yield 'Default slug with default base do not resolve' => [
            'uri' => 'https://website.local/fr-fr/welcome/',
            'recordUpdates' => [],
            'fallbackIdentifiers' => [
                'EN',
            ],
            'fallbackType' => 'strict',
            'expectedStatusCode' => 404,
            'expectedPageTitle' => null,
        ];

        // Using default language slug with language base resolves for inactive / hidden language page
        yield 'Default slug with default base but inactive language page resolves' => [
            'uri' => 'https://website.local/fr-fr/welcome/',
            'recordUpdates' => [
                'pages' => [
                    [
                        'data' => [
                            'hidden' => 1,
                        ],
                        'identifiers' => [
                            'uid' => 1101,
                        ],
                        'types' => [],
                    ],
                ],
            ],
            'fallbackIdentifiers' => [
                'EN',
            ],
            'fallbackType' => 'strict',
            'expectedStatusCode' => 200,
            'expectedPageTitle' => 'EN: Welcome',
        ];
    }

    /**
     * @link https://forge.typo3.org/issues/96010
     * @test
     * @dataProvider defaultLanguagePageNotResolvedForSiteLanguageBaseIfLanguagePageExistsDataProvider
     */
    public function defaultLanguagePageNotResolvedForSiteLanguageBaseIfLanguagePageExists(string $uri, array $recordUpdates, array $fallbackIdentifiers, string $fallbackType, int $expectedStatusCode, ?string $expectedPageTitle): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', 'https://website.local/fr-fr/', ['EN']),
            ]
        );
        if ($recordUpdates !== []) {
            foreach ($recordUpdates as $table => $records) {
                foreach ($records as $record) {
                    $this->getConnectionPool()->getConnectionForTable($table)
                        ->update(
                            $table,
                            $record['data'] ?? [],
                            $record['identifiers'] ?? [],
                            $record['types'] ?? []
                        );
                }
            }
        }

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            $expectedStatusCode,
            $response->getStatusCode()
        );
        if ($expectedPageTitle !== null) {
            self::assertSame(
                $expectedPageTitle,
                $responseStructure->getScopePath('page/title')
            );
        }
    }

    public function defaultLanguagePageNotResolvedForSiteLanguageBaseWithNonDefaultLanguageShorterUriIfLanguagePageExistsDataProvider(): \Generator
    {
        yield 'Default slug with default base resolves' => [
            'uri' => 'https://website.local/en-en/welcome/',
            'recordUpdates' => [],
            'fallbackIdentifiers' => [
                'EN',
            ],
            'fallbackType' => 'strict',
            'expectedStatusCode' => 200,
            'expectedPageTitle' => 'EN: Welcome',
        ];

        yield 'FR slug with FR base resolves' => [
            'uri' => 'https://website.local/bienvenue/',
            'recordUpdates' => [],
            'fallbackIdentifiers' => [
                'EN',
            ],
            'fallbackType' => 'strict',
            'expectedStatusCode' => 200,
            'expectedPageTitle' => 'FR: Welcome',
        ];

        // Using default language slug with language base should be page not found if language page is active.
        yield 'Default slug with default base do not resolve' => [
            'uri' => 'https://website.local/welcome/',
            'recordUpdates' => [],
            'fallbackIdentifiers' => [
                'EN',
            ],
            'fallbackType' => 'strict',
            'expectedStatusCode' => 404,
            'expectedPageTitle' => null,
        ];

        // Using default language slug with language base should be page not found if language page is active.
        yield 'Default slug with default base do not resolve strict without fallback' => [
            'uri' => 'https://website.local/welcome/',
            'recordUpdates' => [],
            'fallbackIdentifiers' => [],
            'fallbackType' => 'fallback',
            'expectedStatusCode' => 404,
            'expectedPageTitle' => null,
        ];

        // Using default language slug with language base should be page not found if language page is active.
        yield 'Default slug with default base do not resolve fallback' => [
            'uri' => 'https://website.local/welcome/',
            'recordUpdates' => [],
            'fallbackIdentifiers' => [
                'EN',
            ],
            'fallbackType' => 'fallback',
            'expectedStatusCode' => 404,
            'expectedPageTitle' => null,
        ];

        // Using default language slug with language base resolves for inactive / hidden language page
        yield 'Default slug with default base but inactive language page resolves' => [
            'uri' => 'https://website.local/welcome/',
            'recordUpdates' => [
                'pages' => [
                    [
                        'data' => [
                            'hidden' => 1,
                        ],
                        'identifiers' => [
                            'uid' => 1101,
                        ],
                        'types' => [],
                    ],
                ],
            ],
            'fallbackIdentifiers' => [
                'EN',
            ],
            'fallbackType' => 'strict',
            'expectedStatusCode' => 200,
            'expectedPageTitle' => 'EN: Welcome',
        ];
    }

    /**
     * @link https://forge.typo3.org/issues/88715
     * @test
     * @dataProvider defaultLanguagePageNotResolvedForSiteLanguageBaseWithNonDefaultLanguageShorterUriIfLanguagePageExistsDataProvider
     */
    public function defaultLanguagePageNotResolvedForSiteLanguageBaseWithNonDefaultLanguageShorterUriIfLanguagePageExists(string $uri, array $recordUpdates, array $fallbackIdentifiers, string $fallbackType, int $expectedStatusCode, ?string $expectedPageTitle): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en'),
                $this->buildLanguageConfiguration('FR', 'https://website.local/', ['EN']),
            ]
        );
        if ($recordUpdates !== []) {
            foreach ($recordUpdates as $table => $records) {
                foreach ($records as $record) {
                    $this->getConnectionPool()->getConnectionForTable($table)
                        ->update(
                            $table,
                            $record['data'] ?? [],
                            $record['identifiers'] ?? [],
                            $record['types'] ?? []
                        );
                }
            }
        }

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            $expectedStatusCode,
            $response->getStatusCode()
        );
        if ($expectedPageTitle !== null) {
            self::assertSame(
                $expectedPageTitle,
                $responseStructure->getScopePath('page/title')
            );
        }
    }
}
