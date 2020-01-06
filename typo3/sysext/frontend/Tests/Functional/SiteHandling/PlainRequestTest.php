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
use TYPO3\CMS\Core\Utility\PermutationUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Test case for frontend requests without having site handling configured
 */
class PlainRequestTest extends AbstractTestCase
{
    /**
     * @var string
     */
    private $siteTitle = 'A Company that Manufactures Everything Inc';

    /**
     * @var InternalRequestContext
     */
    private $internalRequestContext;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass()
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp()
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
        $this->setUpFrontendRootPage(
            3000,
            [
                'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/JsonRenderer.typoscript',
            ],
            [
                'title' => 'ACME Archive',
                'sitetitle' => $this->siteTitle,
            ]
        );
    }

    protected function tearDown()
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
        $expectedStatusCode = 307;
        $expectedHeaders = ['location' => ['index.php?id=acme-first']];

        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );
        static::assertSame($expectedStatusCode, $response->getStatusCode());
        static::assertSame($expectedHeaders, $response->getHeaders());
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider shortcutsAreRedirectedDataProvider
     */
    public function shortcutsAreRedirectedAndRenderFirstSubPage(string $uri)
    {
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

        static::assertSame(
            $expectedStatusCode,
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
    public function pageIsRenderedDataProvider(): array
    {
        $domainPaths = [
            '/',
            'https://localhost/',
            'https://website.local/',
        ];

        $queries = [
            '?id=1100',
            '?id=acme-first',
        ];

        $languageQueries = [
            '',
            '&L=0',
            '&L=1',
            '&L=2',
        ];

        return array_map(
            function (string $uri) {
                if (strpos($uri, '&L=1') !== false) {
                    $expectedPageTitle = 'FR: Welcome';
                } elseif (strpos($uri, '&L=2') !== false) {
                    $expectedPageTitle = 'FR-CA: Welcome';
                } else {
                    $expectedPageTitle = 'EN: Welcome';
                }
                return [$uri, $expectedPageTitle];
            },
            $this->keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $queries, $languageQueries])
            )
        );
    }

    /**
     * @param string $uri
     * @param string $expectedPageTitle
     *
     * @test
     * @dataProvider pageIsRenderedDataProvider
     */
    public function pageIsRendered(string $uri, string $expectedPageTitle)
    {
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
        $instructions = [
            ['https://archive.acme.com/?id=3100', 'EN: Statistics'],
            ['https://archive.acme.com/?id=3110', 'EN: Markets'],
            ['https://archive.acme.com/?id=3120', 'EN: Products'],
            ['https://archive.acme.com/?id=3130', 'EN: Partners'],
        ];

        return $this->keysFromTemplate($instructions, '%1$s');
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
        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
                ->withFrontendUserId($frontendUserId)
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
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorUsingDefaultErrorHandling(string $uri, int $frontendUserId)
    {
        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
                ->withFrontendUserId($frontendUserId)
        );

        static::assertSame(
            403,
            $response->getStatusCode()
        );
        static::assertThat(
            (string)$response->getBody(),
            static::logicalOr(
                static::stringContains('Reason: ID was not an accessible page'),
                static::stringContains('Reason: Subsection was found and not accessible')
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
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorUsingCustomErrorHandling(string $uri, int $frontendUserId)
    {
        $response = $this->executeFrontendRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
                ->withFrontendUserId($frontendUserId)
                ->withMergedGlobalSettings([
                    'TYPO3_CONF_VARS' => [
                        'FE' => [
                            'pageNotFound_handling' => 'READFILE:typo3/sysext/core/Tests/Functional/Fixtures/Frontend/PageError.txt',
                        ]
                    ]
                ])
        );

        static::assertSame(
            403,
            $response->getStatusCode()
        );
        static::assertThat(
            (string)$response->getBody(),
            static::logicalOr(
                static::stringContains('reason: ID was not an accessible page'),
                static::stringContains('reason: Subsection was found and not accessible')
            )
        );
    }

    /**
     * @return array
     */
    public function pageRenderingStopsWithInvalidCacheHashDataProvider(): array
    {
        $domainPaths = [
            '/',
            'https://localhost/',
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
                PermutationUtility::meltStringItems([$domainPaths, $queries, $customQueries])
            )
        );
    }

    /**
     * @param string $uri
     *
     * @test
     * @dataProvider pageRenderingStopsWithInvalidCacheHashDataProvider
     * @todo In TYPO3 v8 this seemed to be rendered, without throwing that exception
     */
    public function pageRequestThrowsExceptionWithInvalidCacheHash(string $uri)
    {
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
                        'pageNotFound_handling' => 'READFILE:typo3/sysext/core/Tests/Functional/Fixtures/Frontend/PageError.txt',
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
