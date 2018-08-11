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

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Fixtures\PhpError;
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
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
        'FR-CA' => ['id' => 2, 'title' => 'Franco-Canadian', 'locale' => 'fr_CA.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-CA', 'direction' => ''],
    ];

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
            101,
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
            '?id=101',
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
            $this->buildSiteConfiguration(101, 'https://website.local/')
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
            '?id=102',
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
            $this->buildSiteConfiguration(101, 'https://website.local/'),
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
            '?id=102',
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
            $this->buildSiteConfiguration(101, 'https://website.local/'),
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
            '?id=101',
            '?id=acme-root',
            '?id=102',
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
     * @todo In case no error handler is defined, default handler should be used
     * @see PlainRequestTest::pageRequestSendsNotFoundResponseWithInvalidCacheHash
     */
    public function pageRequestThrowsExceptionWithInvalidCacheHashWithoutHavingErrorHandling(string $uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(101, 'https://website.local/')
        );

        $this->expectExceptionCode(1522495914);
        $this->expectException(\RuntimeException::class);

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
    public function pageRequestSendsNotFoundResponseWithInvalidCacheHashWithHavingFluidErrorHandling(string $uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(101, 'https://website.local/'),
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
            $this->buildSiteConfiguration(101, 'https://website.local/'),
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
            $this->buildSiteConfiguration(101, 'https://website.local/'),
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
            // @todo Currently fails since cHash is verified after(!) redirect to page 102
            // '?&cHash=814ea11ad629c7e24cfd031cea2779f4&id=101',
            // '?&cHash=814ea11ad629c7e24cfd031cea2779f4id=acme-root',
            '?&cHash=126d2980c12f4759fed1bb7429db2dff&id=102',
            '?&cHash=126d2980c12f4759fed1bb7429db2dff&id=acme-first',
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
            $this->buildSiteConfiguration(101, 'https://website.local/')
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

    /**
     * @param string $identifier
     * @param array $site
     * @param array $languages
     * @param array $errorHandling
     */
    private function writeSiteConfiguration(
        string $identifier,
        array $site = [],
        array $languages = [],
        array $errorHandling = []
    ) {
        $configuration = [
            'site' => $site,
        ];
        if (!empty($languages)) {
            $configuration['site']['languages'] = $languages;
        }
        if (!empty($errorHandling)) {
            $configuration['site']['errorHandling'] = $errorHandling;
        }

        $siteConfiguration = new SiteConfiguration(
            $this->instancePath . '/typo3conf/sites/'
        );

        try {
            $siteConfiguration->write($identifier, $configuration);
        } catch (\Exception $exception) {
            $this->markTestSkipped($exception->getMessage());
        }
    }

    /**
     * @param int $rootPageId
     * @param string $base
     * @return array
     */
    private function buildSiteConfiguration(
        int $rootPageId,
        string $base = ''
    ): array {
        return [
            'rootPageId' => $rootPageId,
            'base' => $base,
        ];
    }

    /**
     * @param string $identifier
     * @param string $base
     * @return array
     */
    private function buildDefaultLanguageConfiguration(
        string $identifier,
        string $base
    ): array {
        $configuration = $this->buildLanguageConfiguration($identifier, $base);
        $configuration['typo3Language'] = 'default';
        $configuration['flag'] = 'global';
        unset($configuration['fallbackType']);
        return $configuration;
    }

    /**
     * @param string $identifier
     * @param string $base
     * @param array $fallbackIdentifiers
     * @return array
     */
    private function buildLanguageConfiguration(
        string $identifier,
        string $base,
        array $fallbackIdentifiers = []
    ): array {
        $preset = $this->resolveLanguagePreset($identifier);

        $configuration = [
            'languageId' => $preset['id'],
            'title' => $preset['title'],
            'navigationTitle' => $preset['title'],
            'base' => $base,
            'locale' => $preset['locale'],
            'iso-639-1' => $preset['iso'],
            'hreflang' => $preset['hrefLang'],
            'direction' => $preset['direction'],
            'typo3Language' => $preset['iso'],
            'flag' => $preset['iso'],
            'fallbackType' => 'strict',
        ];

        if (!empty($fallbackIdentifiers)) {
            $fallbackIds = array_map(
                function (string $fallbackIdentifier) {
                    $preset = $this->resolveLanguagePreset($fallbackIdentifier);
                    return $preset['id'];
                },
                $fallbackIdentifiers
            );
            $configuration['fallbackType'] = 'fallback';
            $configuration['fallbackType'] = implode(',', $fallbackIds);
        }

        return $configuration;
    }

    /**
     * @param string $handler
     * @param array $codes
     * @return array
     */
    private function buildErrorHandlingConfiguration(
        string $handler,
        array $codes
    ): array {
        if ($handler === 'Page') {
            $baseConfiguration = [
                'errorContentSource' => '404',
            ];
        } elseif ($handler === 'Fluid') {
            $baseConfiguration = [
                'errorFluidTemplate' => 'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/FluidError.html',
                'errorFluidTemplatesRootPath' => '',
                'errorFluidLayoutsRootPath' => '',
                'errorFluidPartialsRootPath' => '',
            ];
        } elseif ($handler === 'PHP') {
            $baseConfiguration = [
                'errorPhpClassFQCN' => PhpError::class,
            ];
        } else {
            throw new \LogicException(
                sprintf('Invalid handler "%s"', $handler),
                1533894782
            );
        }

        $baseConfiguration['errorHandler'] = $handler;

        return array_map(
            function (int $code) use ($baseConfiguration) {
                $baseConfiguration['errorCode'] = $code;
                return $baseConfiguration;
            },
            $codes
        );
    }

    /**
     * @param string $identifier
     * @return mixed
     */
    private function resolveLanguagePreset(string $identifier)
    {
        if (!isset(static::LANGUAGE_PRESETS[$identifier])) {
            throw new \LogicException(
                sprintf('Undefined preset identifier "%s"', $identifier),
                1533893665
            );
        }
        return static::LANGUAGE_PRESETS[$identifier];
    }
}
