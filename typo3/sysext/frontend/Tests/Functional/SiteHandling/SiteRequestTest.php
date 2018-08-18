<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataMapFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Test case for frontend requests having site handling configured
 */
class SiteRequestTest extends AbstractRequestTest
{
    /**
     * @var string
     */
    private $siteTitle = 'A Company that Manufactures Everything Inc';

    /**
     * @var InternalRequestContext
     */
    private $internalRequestContext;

    /**
     * @var ActionService
     */
    private $actionService;

    protected function setUp()
    {
        parent::setUp();

        // these settings are forwarded to the frontend sub-request as well
        $this->internalRequestContext = (new InternalRequestContext())
            ->withGlobalSettings(['TYPO3_CONF_VARS' => static::TYPO3_CONF_VARS]);

        $this->setUpBackendUserFromFixture(1);
        $this->actionService = new ActionService();
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/Fixtures/scenario.yaml';
        $factory = DataMapFactory::fromYamlFile($scenarioFile);
        $this->actionService->invoke(
            $factory->getDataMap(),
            [],
            $factory->getSuggestedIds()
        );
        static::failIfArrayIsNotEmpty(
            $this->actionService->getDataHandler()->errorLog
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

    protected function tearDown()
    {
        unset(
            $this->actionService,
            $this->internalRequestContext
        );
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function requestsAreRedirectedDataProvider(): array
    {
        $domainPaths = [
            '/',
            'https://localhost/',
            'https://website.local/',
        ];

        $queries = [
            '?',
            '?id=1000',
            '?id=acme-root'
        ];

        return $this->wrapInArray(
            $this->keysFromValues(
                $this->meltStrings([$domainPaths, $queries])
            )
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider requestsAreRedirectedDataProvider
     */
    public function requestsAreRedirected(string $uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $expectedStatusCode = 307;
        $expectedHeaders = ['location' => ['/?id=acme-first']];

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );
        static::assertSame($expectedStatusCode, $response->getStatusCode());
        static::assertSame($expectedHeaders, $response->getHeaders());
    }

    /**
     * @return array
     */
    public function pageIsRenderedWithPathsDataProvider(): array
    {
        $domainPaths = [
            // @todo currently base needs to be defined with domain
            // '/',
            'https://website.local/',
        ];

        $languagePaths = [
            '',
            'en-en/',
            'fr-fr/',
            'fr-ca/',
        ];

        $queries = [
            '?id=1100',
            '?id=acme-first',
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
            $this->keysFromValues(
                $this->meltStrings([$domainPaths, $languagePaths, $queries])
            )
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

        static::assertSame(
            200,
            $response->getStatusCode()
        );
        static::assertSame(
            $this->siteTitle,
            $responseStructure->getScopePath('template/sitetitle')
        );
        static::assertSame(
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
            'https://website.local/',
            'https://website.us/',
            'https://website.fr/',
            'https://website.ca/',
            'https://website.other/',
        ];

        $queries = [
            '?id=1100',
            '?id=acme-first',
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
            $this->keysFromValues(
                $this->meltStrings([$domainPaths, $queries])
            )
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

        static::assertSame(
            200,
            $response->getStatusCode()
        );
        static::assertSame(
            $this->siteTitle,
            $responseStructure->getScopePath('template/sitetitle')
        );
        static::assertSame(
            $expectedPageTitle,
            $responseStructure->getScopePath('page/title')
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
            '?',
            '?id=1000',
            '?id=acme-root',
            '?id=1100',
            '?id=acme-first',
        ];

        $customQueries = [
            '&testing[value]=1',
            '&testing[value]=1&cHash=',
            '&testing[value]=1&cHash=WRONG',
        ];

        return $this->wrapInArray(
            $this->keysFromValues(
                $this->meltStrings([$domainPaths, $queries, $customQueries])
            )
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider pageRenderingStopsWithInvalidCacheHashDataProvider
     */
    public function pageRequestThrowsExceptionWithInvalidCacheHashWithoutHavingErrorHandling(string $uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $this->expectExceptionCode(1518472189);
        $this->expectException(PageNotFoundException::class);

        $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider pageRenderingStopsWithInvalidCacheHashDataProvider
     */
    public function pageRequestSendsNotFoundResponseWithInvalidCacheHash(string $uri)
    {
        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext->withMergedGlobalSettings([
                'TYPO3_CONF_VARS' => [
                    'FE' => [
                        'pageNotFound_handling' => 'READFILE:typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/PageError.txt',
                    ]
                ]
            ])
        );

        static::assertSame(
            404,
            $response->getStatusCode()
        );
        static::assertThat(
            (string)$response->getBody(),
            static::logicalOr(
                static::stringContains('reason: Request parameters could not be validated (&amp;cHash empty)'),
                static::stringContains('reason: Request parameters could not be validated (&amp;cHash comparison failed)')
            )
        );
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

        static::assertSame(
            404,
            $response->getStatusCode()
        );
        static::assertThat(
            (string)$response->getBody(),
            static::logicalOr(
                static::stringContains('message: Request parameters could not be validated (&amp;cHash empty)'),
                static::stringContains('message: Request parameters could not be validated (&amp;cHash comparison failed)')
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

        static::assertSame(
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

        static::assertSame(
            404,
            $response->getStatusCode()
        );
        static::assertThat(
            $json['message'] ?? null,
            static::logicalOr(
                static::identicalTo('Request parameters could not be validated (&cHash empty)'),
                static::identicalTo('Request parameters could not be validated (&cHash comparison failed)')
            )
        );
    }

    /**
     * @return array
     */
    public function pageIsRenderedWithValidCacheHashDataProvider(): array
    {
        $domainPaths = [
            '/',
            'https://localhost/',
            'https://website.local/',
        ];

        // cHash has been calculated with encryption key set to
        // '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6'
        $queries = [
            // @todo Currently fails since cHash is verified after(!) redirect to page 1100
            // '?&cHash=7d1f13fa91159dac7feb3c824936b39d&id=1000',
            // '?&cHash=7d1f13fa91159dac7feb3c824936b39d=acme-root',
            '?&cHash=f42b850e435f0cedd366f5db749fc1af&id=1100',
            '?&cHash=f42b850e435f0cedd366f5db749fc1af&id=acme-first',
        ];

        $customQueries = [
            '&testing[value]=1',
        ];

        $dataSet = $this->wrapInArray(
            $this->keysFromValues(
                $this->meltStrings([$domainPaths, $queries, $customQueries])
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
        static::assertSame(
            '1',
            $responseStructure->getScopePath('getpost/testing.value')
        );
    }
}
