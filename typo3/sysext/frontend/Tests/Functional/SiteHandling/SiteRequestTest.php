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
class SiteRequestTest extends AbstractTestCase
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

        $scenarioFile = __DIR__ . '/Fixtures/PlainScenario.yaml';
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
    public function shortcutsAreRedirectedDataProvider(): array
    {
        $domainPaths = [
            // @todo Implicit strict mode handling when calling non-existent site
            // '/',
            // 'https://localhost/',
            'https://website.local/',
        ];

        $queries = [
            '',
        ];

        return $this->wrapInArray(
            $this->keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $queries])
            )
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider shortcutsAreRedirectedDataProvider
     */
    public function shortcutsAreRedirectedToFirstSubPage(string $uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en-en/'),
            ]
        );

        $expectedStatusCode = 307;
        $expectedHeaders = ['location' => ['https://website.local/en-en/']];

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
            'en-en/',
            'fr-fr/',
            'fr-ca/',
        ];

        $queries = [
            '?id=1100',
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
                PermutationUtility::meltStringItems([$domainPaths, $languagePaths, $queries])
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
            // @todo: This turns into a redirect to the default language (".us") making this function obsolete
            // 'https://website.local/',
            'https://website.us/',
            'https://website.fr/',
            // Explicitly testing umlaut domains
            'https://wäbsite.ca/',
            // @todo Implicit strict mode handling when calling non-existent site
            // 'https://website.other/',
        ];

        $queries = [
            '?id=1100',
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
                PermutationUtility::meltStringItems([$domainPaths, $queries])
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
                $this->buildLanguageConfiguration('FR-CA', 'https://wäbsite.ca/', ['FR', 'EN']),
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
            ['https://website.local/?id=1510', 1, 'Whitepapers'],
            ['https://website.local/?id=1511', 1, 'Products'],
            ['https://website.local/?id=1512', 1, 'Solutions'],
            // frontend user 2
            ['https://website.local/?id=1510', 2, 'Whitepapers'],
            ['https://website.local/?id=1511', 2, 'Products'],
            ['https://website.local/?id=1515', 2, 'Research'],
            ['https://website.local/?id=1520', 2, 'Forecasts'],
            ['https://website.local/?id=1521', 2, 'Current Year'],
            // frontend user 3
            ['https://website.local/?id=1510', 3, 'Whitepapers'],
            ['https://website.local/?id=1511', 3, 'Products'],
            ['https://website.local/?id=1512', 3, 'Solutions'],
            ['https://website.local/?id=1515', 3, 'Research'],
            ['https://website.local/?id=1520', 3, 'Forecasts'],
            ['https://website.local/?id=1521', 3, 'Current Year'],
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
            ['https://website.local/?id=1510', 0],
            ['https://website.local/?id=1511', 0],
            ['https://website.local/?id=1512', 0],
            ['https://website.local/?id=1515', 0],
            ['https://website.local/?id=1520', 0],
            ['https://website.local/?id=1521', 0],
            // frontend user 1
            ['https://website.local/?id=1515', 1],
            ['https://website.local/?id=1520', 1],
            ['https://website.local/?id=1521', 1],
            // frontend user 2
            ['https://website.local/?id=1512', 2],
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
            '?',
            '?id=1000',
            '?id=1100',
        ];

        $customQueries = [
            '&testing[value]=1',
            '&testing[value]=1&cHash=',
            '&testing[value]=1&cHash=WRONG',
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
            // @todo Implicit strict mode handling when calling non-existent site
            // '/',
            // 'https://localhost/',
            'https://website.local/',
        ];

        // cHash has been calculated with encryption key set to
        // '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6'
        $queries = [
            // @todo Currently fails since cHash is verified after(!) redirect to page 1100
            // '?&cHash=7d1f13fa91159dac7feb3c824936b39d&id=1000',
            '?&cHash=f42b850e435f0cedd366f5db749fc1af&id=1100',
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
