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
    /**
     * @var string
     */
    private $siteTitle = 'A Company that Manufactures Everything Inc';

    /**
     * @var InternalRequestContext
     */
    private $internalRequestContext;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // these settings are forwarded to the frontend sub-request as well
        $this->internalRequestContext = (new InternalRequestContext())
            ->withGlobalSettings(['TYPO3_CONF_VARS' => static::TYPO3_CONF_VARS]);

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function setUpDatabase()
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
                'sitetitle' => $this->siteTitle,
            ]
        );
    }

    protected function tearDown(): void
    {
        unset($this->internalRequestContext);
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function requestsAreRedirectedWithoutHavingDefaultSiteLanguageDataProvider(): array
    {
        $domainPaths = [
            'https://website.local/',
            'https://website.local/?',
            'https://website.local//',
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
    public function requestsAreRedirectedWithoutHavingDefaultSiteLanguage(string $uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $expectedStatusCode = 307;
        $expectedHeaders = ['location' => ['https://website.local/welcome']];

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );
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
            'https://website.local//',
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
    public function shortcutsAreRedirectedToDefaultSiteLanguage(string $uri)
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

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );
        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame($expectedHeaders, $response->getHeaders());
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider shortcutsAreRedirectedDataProvider
     */
    public function shortcutsAreRedirectedAndRenderFirstSubPage(string $uri)
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

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext,
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
            $this->siteTitle,
            $responseStructure->getScopePath('template/sitetitle')
        );
        self::assertSame(
            $expectedPageTitle,
            $responseStructure->getScopePath('page/title')
        );
    }

    /**
     * @test
     */
    public function invalidSiteResultsInNotFoundResponse()
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
        $response = $this->executeFrontendRequest(new InternalRequest($uri));
        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function invalidSlugOutsideSiteLanguageResultsInNotFoundResponse()
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/')
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404])
        );

        $uri = 'https://website.local/any/invalid/slug';
        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );

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
    public function invalidSlugInsideSiteLanguageResultsInNotFoundResponse()
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/')
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404])
        );

        $uri = 'https://website.local/en-en/any/invalid/slug';
        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );

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
    public function unconfiguredTypeNumResultsIn404Error()
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/')
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404])
        );

        $uri = 'https://website.local/en-en/?type=13';
        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );

        self::assertSame(
            404,
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
        ];

        return array_map(
            function (string $uri) {
                if (strpos($uri, '/fr-fr/') !== false) {
                    $expectedPageTitle = 'FR: Welcome';
                } elseif (strpos($uri, '/fr-ca/') !== false) {
                    $expectedPageTitle = 'FR-CA: Welcome';
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
    public function pageIsRenderedWithPaths(string $uri, string $expectedPageTitle)
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

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            200,
            $response->getStatusCode()
        );
        self::assertSame(
            $this->siteTitle,
            $responseStructure->getScopePath('template/sitetitle')
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
        ];

        return array_map(
            function (string $uri) {
                if (strpos($uri, '.fr/') !== false) {
                    $expectedPageTitle = 'FR: Welcome';
                } elseif (strpos($uri, '.ca/') !== false) {
                    $expectedPageTitle = 'FR-CA: Welcome';
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
    public function pageIsRenderedWithDomains(string $uri, string $expectedPageTitle)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://website.us/'),
                $this->buildLanguageConfiguration('FR', 'https://website.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://website.ca/', ['FR', 'EN']),
            ]
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            200,
            $response->getStatusCode()
        );
        self::assertSame(
            $this->siteTitle,
            $responseStructure->getScopePath('template/sitetitle')
        );
        self::assertSame(
            $expectedPageTitle,
            $responseStructure->getScopePath('page/title')
        );
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
    public function restrictedPageIsRendered(string $uri, int $frontendUserId, string $expectedPageTitle)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
                ->withFrontendUserId($frontendUserId)
        );
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );

        self::assertSame(
            200,
            $response->getStatusCode()
        );
        self::assertSame(
            $this->siteTitle,
            $responseStructure->getScopePath('template/sitetitle')
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
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorWithoutHavingErrorHandling(string $uri, int $frontendUserId)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
                ->withFrontendUserId($frontendUserId)
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
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorWithHavingFluidErrorHandling(string $uri, int $frontendUserId)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Fluid', [403])
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
                ->withFrontendUserId($frontendUserId)
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
     * @todo Response body cannot be asserted since PageContentErrorHandler::handlePageError executes request via HTTP (not internally)
     */
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorWithHavingPageErrorHandling(string $uri, int $frontendUserId)
    {
        self::markTestSkipped('Skipped until PageContentErrorHandler::handlePageError does not use HTTP anymore');

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Page', [403])
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
                ->withFrontendUserId($frontendUserId)
        );

        self::assertSame(
            403,
            $response->getStatusCode()
        );
    }

    /**
     * @param string $uri
     * @param int $frontendUserId
     *
     * @test
     * @dataProvider restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorDataProvider
     */
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorWithHavingPhpErrorHandling(string $uri, int $frontendUserId)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('PHP', [403])
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
                ->withFrontendUserId($frontendUserId)
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
    public function pageRequestNotFoundInvalidCacheHashWithoutHavingErrorHandling(string $uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );
        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider pageRenderingStopsWithInvalidCacheHashDataProvider
     */
    public function pageRequestSendsNotFoundResponseWithInvalidCacheHashWithHavingFluidErrorHandling(string $uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Fluid', [404])
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );

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
     * @todo Response body cannot be asserted since PageContentErrorHandler::handlePageError executes request via HTTP (not internally)
     */
    public function pageRequestSendsNotFoundResponseWithInvalidCacheHashWithHavingPageErrorHandling(string $uri)
    {
        self::markTestSkipped('Skipped until PageContentErrorHandler::handlePageError does not use HTTP anymore');

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Page', [404])
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );

        self::assertSame(
            404,
            $response->getStatusCode()
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider pageRenderingStopsWithInvalidCacheHashDataProvider
     */
    public function pageRequestSendsNotFoundResponseWithInvalidCacheHashWithHavingPhpErrorHandling(string $uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('PHP', [404])
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );
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
    public function pageIsRenderedWithValidCacheHash($uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );
        $responseStructure = ResponseContent::fromString(
            (string)$response->getBody()
        );
        self::assertSame(
            '1',
            $responseStructure->getScopePath('getpost/testing.value')
        );
    }
}
