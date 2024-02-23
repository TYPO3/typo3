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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\PermutationUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

final class SiteRequestTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withDatabaseSnapshot(function () {
            $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
            $backendUser = $this->setUpBackendUser(1);
            Bootstrap::initializeLanguageObject();
            $scenarioFile = __DIR__ . '/Fixtures/PlainScenario.yaml';
            $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            static::failIfArrayIsNotEmpty($writer->getErrors());
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
        });
    }

    public static function shortcutsAreRedirectedDataProvider(): array
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
        return self::wrapInArray(
            self::keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $queries])
            )
        );
    }

    #[DataProvider('shortcutsAreRedirectedDataProvider')]
    #[Test]
    public function shortcutsAreRedirectedToFirstSubPage(string $uri): void
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

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame($expectedHeaders, $response->getHeaders());
    }

    #[DataProvider('shortcutsAreRedirectedDataProvider')]
    #[Test]
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

    public static function pageIsRenderedWithPathsDataProvider(): array
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
            '简/',
        ];
        $queries = [
            '?id=1100',
        ];
        return array_map(
            static function (string $uri) {
                if (str_contains($uri, '/fr-fr/')) {
                    $expectedPageTitle = 'FR: Welcome';
                } elseif (str_contains($uri, '/fr-ca/')) {
                    $expectedPageTitle = 'FR-CA: Welcome';
                } elseif (str_contains($uri, '/简/')) {
                    $expectedPageTitle = 'ZH-CN: Welcome';
                } else {
                    $expectedPageTitle = 'EN: Welcome';
                }
                return [$uri, $expectedPageTitle];
            },
            self::keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $languagePaths, $queries])
            )
        );
    }

    #[DataProvider('pageIsRenderedWithPathsDataProvider')]
    #[Test]
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

    public static function pageIsRenderedWithPathsAndChineseDefaultLanguageDataProvider(): array
    {
        $domainPaths = [
            // @todo currently base needs to be defined with domain
            // '/',
            'https://website.local/',
        ];
        $languagePaths = [
            '简/',
            'fr-fr/',
            'fr-ca/',
        ];
        $queries = [
            '?id=1110',
        ];
        return array_map(
            static function (string $uri) {
                if (str_contains($uri, '/fr-fr/')) {
                    $expectedPageTitle = 'FR: Welcome ZH Default';
                } elseif (str_contains($uri, '/fr-ca/')) {
                    $expectedPageTitle = 'FR-CA: Welcome ZH Default';
                } else {
                    $expectedPageTitle = 'ZH-CN: Welcome Default';
                }
                return [$uri, $expectedPageTitle];
            },
            self::keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $languagePaths, $queries])
            )
        );
    }

    #[DataProvider('pageIsRenderedWithPathsAndChineseDefaultLanguageDataProvider')]
    #[Test]
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

    public static function pageIsRenderedWithPathsAndChineseBaseDataProvider(): array
    {
        return [
            ['https://website.local/简/简/?id=1110', 'ZH-CN: Welcome Default'],
        ];
    }

    #[DataProvider('pageIsRenderedWithPathsAndChineseBaseDataProvider')]
    #[Test]
    public function pageIsRenderedWithPathsAndChineseBase(string $uri, string $expectedPageTitle): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/简/'),
            [
                $this->buildDefaultLanguageConfiguration('ZH-CN', '/简/'),
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

    public static function pageIsRenderedWithDomainsDataProvider(): array
    {
        $domainPaths = [
            // @todo: This turns into a redirect to the default language (".us") making this function obsolete
            // 'https://website.local/',
            'https://website.us/',
            'https://website.fr/',
            // Explicitly testing umlaut domains
            'https://wäbsite.ca/',
            // Explicitly testing chinese character domains
            'https://website.简/',
            // @todo Implicit strict mode handling when calling non-existent site
            // 'https://website.other/',
        ];
        $queries = [
            '?id=1100',
        ];
        return array_map(
            static function (string $uri) {
                if (str_contains($uri, '.fr/')) {
                    $expectedPageTitle = 'FR: Welcome';
                } elseif (str_contains($uri, '.ca/')) {
                    $expectedPageTitle = 'FR-CA: Welcome';
                } elseif (str_contains($uri, '.简/')) {
                    $expectedPageTitle = 'ZH-CN: Welcome';
                } else {
                    $expectedPageTitle = 'EN: Welcome';
                }
                return [$uri, $expectedPageTitle];
            },
            self::keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $queries])
            )
        );
    }

    #[DataProvider('pageIsRenderedWithDomainsDataProvider')]
    #[Test]
    public function pageIsRenderedWithDomains(string $uri, string $expectedPageTitle): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://website.us/'),
                $this->buildLanguageConfiguration('FR', 'https://website.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://wäbsite.ca/', ['FR', 'EN']),
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

    public static function restrictedPageIsRenderedDataProvider(): array
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
            // frontend user 1 with index
            ['https://website.local/index.php?id=1510', 1, 'Whitepapers'],
            ['https://website.local/index.php?id=1511', 1, 'Products'],
            ['https://website.local/index.php?id=1512', 1, 'Solutions'],
            // frontend user 2
            ['https://website.local/index.php?id=1510', 2, 'Whitepapers'],
            ['https://website.local/index.php?id=1511', 2, 'Products'],
            ['https://website.local/index.php?id=1515', 2, 'Research'],
            ['https://website.local/index.php?id=1520', 2, 'Forecasts'],
            ['https://website.local/index.php?id=1521', 2, 'Current Year'],
            // frontend user 3
            ['https://website.local/index.php?id=1510', 3, 'Whitepapers'],
            ['https://website.local/index.php?id=1511', 3, 'Products'],
            ['https://website.local/index.php?id=1512', 3, 'Solutions'],
            ['https://website.local/index.php?id=1515', 3, 'Research'],
            ['https://website.local/index.php?id=1520', 3, 'Forecasts'],
            ['https://website.local/index.php?id=1521', 3, 'Current Year'],
        ];
        return self::keysFromTemplate($instructions, '%1$s (user:%2$s)');
    }

    #[DataProvider('restrictedPageIsRenderedDataProvider')]
    #[Test]
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

    public static function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorDataProvider(): array
    {
        $instructions = [
            // no frontend user given
            ['https://website.local/?id=1510', 0],
            ['https://website.local/?id=1511', 0],
            ['https://website.local/?id=1512', 0],
            ['https://website.local/?id=1515', 0],
            ['https://website.local/?id=1520', 0],
            ['https://website.local/?id=1521', 0],
            ['https://website.local/?id=2021', 0],
            // frontend user 1
            ['https://website.local/?id=1515', 1],
            ['https://website.local/?id=1520', 1],
            ['https://website.local/?id=1521', 1],
            ['https://website.local/?id=2021', 1],
            // frontend user 2
            ['https://website.local/?id=1512', 2],
            ['https://website.local/?id=2021', 2],
        ];
        return self::keysFromTemplate($instructions, '%1$s (user:%2$s)');
    }

    #[DataProvider('restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorDataProvider')]
    #[Test]
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
     * @todo Response body cannot be asserted since PageContentErrorHandler::handlePageError executes request via HTTP (not internally)
     */
    #[DataProvider('restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorDataProvider')]
    #[Test]
    public function restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorWithHavingPageErrorHandling(string $uri, int $frontendUserId): void
    {
        self::markTestSkipped('Skipped until PageContentErrorHandler::handlePageError does not use HTTP anymore');

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
    }

    #[DataProvider('restrictedPageSendsForbiddenResponseWithUnauthorizedVisitorDataProvider')]
    #[Test]
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

    public static function restrictedPageWithParentSysFolderIsRenderedDataProvider(): array
    {
        $instructions = [
            // frontend user 4
            ['https://website.local/?id=2021', 4, 'FEGroups Restricted'],
        ];
        return self::keysFromTemplate($instructions, '%1$s (user:%2$s)');
    }

    #[DataProvider('restrictedPageWithParentSysFolderIsRenderedDataProvider')]
    #[Test]
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

    public static function restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorDataProvider(): array
    {
        $instructions = [
            // no frontend user given
            ['https://website.local/?id=2021', 0],
            // frontend user 1
            ['https://website.local/?id=2021', 1],
            // frontend user 2
            ['https://website.local/?id=2021', 2],
            // frontend user 3
            ['https://website.local/?id=2021', 3],
        ];
        return self::keysFromTemplate($instructions, '%1$s (user:%2$s)');
    }

    #[DataProvider('restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorDataProvider')]
    #[Test]
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
     * @todo Response body cannot be asserted since PageContentErrorHandler::handlePageError executes request via HTTP (not internally)
     */
    #[DataProvider('restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorDataProvider')]
    #[Test]
    public function restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorWithHavingPageErrorHandling(string $uri, int $frontendUserId): void
    {
        self::markTestSkipped('Skipped until PageContentErrorHandler::handlePageError does not use HTTP anymore');

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
    }

    #[DataProvider('restrictedPageWithParentSysFolderSendsForbiddenResponseWithUnauthorizedVisitorDataProvider')]
    #[Test]
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

    public static function hiddenPageSends404ResponseRegardlessOfVisitorGroupDataProvider(): array
    {
        $instructions = [
            // hidden page, always 404
            ['https://website.local/?id=1800', 0],
            ['https://website.local/?id=1800', 1],
            // hidden fe group restricted and fegroup generally okay
            ['https://website.local/?id=2022', 4],
        ];
        return self::keysFromTemplate($instructions, '%1$s (user:%2$s)');
    }

    #[DataProvider('hiddenPageSends404ResponseRegardlessOfVisitorGroupDataProvider')]
    #[Test]
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

    public static function pageRenderingStopsWithInvalidCacheHashDataProvider(): array
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
        return self::wrapInArray(
            self::keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $queries, $customQueries])
            )
        );
    }

    #[DataProvider('pageRenderingStopsWithInvalidCacheHashDataProvider')]
    #[Test]
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
     * @todo Response body cannot be asserted since PageContentErrorHandler::handlePageError executes request via HTTP (not internally)
     */
    #[DataProvider('pageRenderingStopsWithInvalidCacheHashDataProvider')]
    #[Test]
    public function pageRequestSendsNotFoundResponseWithInvalidCacheHashWithHavingPageErrorHandling(string $uri): void
    {
        self::markTestSkipped('Skipped until PageContentErrorHandler::handlePageError does not use HTTP anymore');

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('Page', [404])
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));

        self::assertSame(
            404,
            $response->getStatusCode()
        );
    }

    #[DataProvider('pageRenderingStopsWithInvalidCacheHashDataProvider')]
    #[Test]
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

    public static function pageIsRenderedWithValidCacheHashDataProvider(): array
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
        return self::wrapInArray(
            self::keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $queries, $customQueries])
            )
        );
    }

    #[DataProvider('pageIsRenderedWithValidCacheHashDataProvider')]
    #[Test]
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

    public static function checkIfIndexPhpReturnsShortcutRedirectWithPageIdAndTypeNumProvidedDataProvider(): array
    {
        $domainPaths = [
            'https://website.local/',
            'https://website.local/index.php',
        ];
        $queries = [
            '',
            '?id=1000',
            '?type=0',
            '?id=1000&type=0',
        ];
        return self::wrapInArray(
            self::keysFromValues(
                PermutationUtility::meltStringItems([$domainPaths, $queries])
            )
        );
    }

    #[DataProvider('checkIfIndexPhpReturnsShortcutRedirectWithPageIdAndTypeNumProvidedDataProvider')]
    #[Test]
    public function checkIfIndexPhpReturnsShortcutRedirectWithPageIdAndTypeNumProvided(string $uri)
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );

        $expectedStatusCode = 307;
        $expectedHeaders = ['X-Redirect-By' => ['TYPO3 Shortcut/Mountpoint'], 'location' => ['https://website.local/en-welcome']];

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame($expectedHeaders, $response->getHeaders());
    }

    public static function crossSiteShortcutsAreRedirectedDataProvider(): array
    {
        return [
            'shortcut is redirected #1' => [
                'https://website.local/index.php?id=2030',
                307,
                [
                    'X-Redirect-By' => ['TYPO3 Shortcut/Mountpoint'],
                    'location' => ['https://blog.local/authors'],
                ],
            ],
            'shortcut is redirected #2' => [
                'https://website.local/?id=2030',
                307,
                [
                    'X-Redirect-By' => ['TYPO3 Shortcut/Mountpoint'],
                    'location' => ['https://blog.local/authors'],
                ],
            ],
            'shortcut is redirected #3' => [
                'https://website.local/index.php?id=2030&type=0',
                307,
                [
                    'X-Redirect-By' => ['TYPO3 Shortcut/Mountpoint'],
                    'location' => ['https://blog.local/authors'],
                ],
            ],
            'shortcut is redirected #4' => [
                'https://website.local/?id=2030&type=0',
                307,
                [
                    'X-Redirect-By' => ['TYPO3 Shortcut/Mountpoint'],
                    'location' => ['https://blog.local/authors'],
                ],
            ],
            'shortcut is redirected #5' => [
                'https://website.local/?id=2030&type=1',
                307,
                [
                    'X-Redirect-By' => ['TYPO3 Shortcut/Mountpoint'],
                    'location' => ['https://blog.local/authors?type=1'],
                ],
            ],
            'shortcut is redirected #6' => [
                'https://website.local/?id=2030&type=1&additional=value',
                307,
                [
                    'X-Redirect-By' => ['TYPO3 Shortcut/Mountpoint'],
                    'location' => ['https://blog.local/authors?additional=value&type=1&cHash=9a534a0ab3d092ac113a3d8b5ea577ba'],
                ],
            ],
        ];
    }

    #[DataProvider('crossSiteShortcutsAreRedirectedDataProvider')]
    #[Test]
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

    public static function crossSiteShortcutsWithWrongSiteHostSendsPageNotFoundWithoutHavingErrorHandlingDataProvider(): array
    {
        return [
            'shortcut requested by id on wrong site #1' => [
                'https://blog.local/index.php?id=2030',
            ],
            'shortcut requested by id on wrong site #2' => [
                'https://blog.local/?id=2030',
            ],
            'shortcut requested by id on wrong site #3' => [
                'https://blog.local/index.php?id=2030&type=0',
            ],
            'shortcut requested by id on wrong site #4' => [
                'https://blog.local/?id=2030&type=0',
            ],
        ];
    }

    #[DataProvider('crossSiteShortcutsWithWrongSiteHostSendsPageNotFoundWithoutHavingErrorHandlingDataProvider')]
    #[Test]
    public function crossSiteShortcutsWithWrongSiteHostSendsPageNotFoundWithoutHavingErrorHandling(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('PHP', [404])
        );
        $this->writeSiteConfiguration(
            'blog-local',
            $this->buildSiteConfiguration(2000, 'https://blog.local/'),
            [],
            $this->buildErrorHandlingConfiguration('PHP', [404])
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
        $json = json_decode((string)$response->getBody(), true);
        self::assertSame(404, $response->getStatusCode());
        self::assertThat(
            $json['message'] ?? null,
            self::stringContains('ID was outside the domain')
        );
    }

    public static function getUrisWithInvalidLegacyQueryParameters(): \Generator
    {
        $uri = new Uri('https://website.local/');
        yield '#0 id with float value having a zero decimal' => [
            'uri' => $uri->withQuery(HttpUtility::buildQueryString(['id' => '1110.0'])),
        ];
        yield '#1 id string value with tailing numbers' => [
            'uri' => $uri->withQuery(HttpUtility::buildQueryString(['id' => 'step1110'])),
        ];
        yield '#2 id string value with leading numbers' => [
            'uri' => $uri->withQuery(HttpUtility::buildQueryString(['id' => '1110step'])),
        ];
        yield '#3 id string value without numbers' => [
            'uri' => $uri->withQuery(HttpUtility::buildQueryString(['id' => 'foobar'])),
        ];
        yield '#4 id string value with a exponent' => [
            'uri' => $uri->withQuery(HttpUtility::buildQueryString(['id' => '11e10'])),
        ];
        yield '#5 id with a zero as value' => [
            'uri' => $uri->withQuery(HttpUtility::buildQueryString(['id' => 0])),
        ];
    }

    #[DataProvider('getUrisWithInvalidLegacyQueryParameters')]
    #[Test]
    public function requestWithInvalidLegacyQueryParametersDisplayPageNotFoundPage(UriInterface $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [],
            $this->buildErrorHandlingConfiguration('PHP', [404])
        );
        $response = $this->executeFrontendSubRequest(
            new InternalRequest((string)$uri),
            new InternalRequestContext()
        );
        $json = json_decode((string)$response->getBody(), true);
        self::assertSame(404, $response->getStatusCode());
        self::assertThat(
            $json['message'] ?? null,
            self::stringContains('The requested page does not exist')
        );
    }
}
