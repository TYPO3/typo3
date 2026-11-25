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

namespace TYPO3\CMS\Core\Tests\Functional\Error\PageErrorHandler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\Error\PageErrorHandler\Fixtures\ExternalErrorPageRequestHandler;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\Log\DummyWriter;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageContentErrorHandlerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $configurationToUseInTestInstance = [
        'LOG' => [
            'TYPO3' => [
                'CMS' => [
                    'Core' => [
                        'Error' => [
                            'PageErrorHandler' => [
                                'writerConfiguration' => [
                                    LogLevel::WARNING => [
                                        DummyWriter::class => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        // For a request from an IP address in devIPmask, the frontend throws an exception
        // instead of the response with the status 500.
        'SYS' => [
            'devIPmask' => '123.123.123.123',
        ],
        // The request for an external error page does not go to the network. The HTTP client
        // returns an error status code and does not throw an exception for it. Thus the tests
        // also cover the check of the status code in the handler.
        'HTTP' => [
            'http_errors' => false,
            'handler' => [
                'externalErrorPage' => ExternalErrorPageRequestHandler::class . '::create',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->setUpFrontendRootPage(
            1,
            ['EXT:core/Tests/Functional/Error/PageErrorHandler/Fixtures/PageWithPropagatedNotFoundResponse.typoscript']
        );
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')],
            $this->buildErrorHandlingConfiguration('Page', [404])
        );
    }

    protected function tearDown(): void
    {
        DummyWriter::$logs = [];
        parent::tearDown();
    }

    #[Test]
    public function errorPageSubRequestDoesNotInheritPageRendererStateOfTheRequestItAnswers(): void
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/failing'));
        $body = (string)$response->getBody();

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('contentOfNotFoundPage', $body);
        self::assertStringNotContainsString('contentOfRequestedPage', $body);
        self::assertStringContainsString('headerDataOfNotFoundPage', $body);
        self::assertStringNotContainsString('headerDataOfRequestedPage', $body);
        self::assertStringContainsString('footerDataOfNotFoundPage', $body);
        self::assertStringNotContainsString('footerDataOfRequestedPage', $body);
    }

    #[Test]
    public function errorPageSubRequestDoesNotInheritExtbaseRequestOfThePluginItAnswers(): void
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/failing-extbase-plugin'));
        $body = (string)$response->getBody();

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('contentOfNotFoundPage', $body);
        self::assertStringContainsString('renderedWithPlainRequest', $body);
    }

    public static function errorPageSubRequestKeepsPreviewSimulationOfBackendUserDataProvider(): \Generator
    {
        yield 'simulated date' => [
            'query' => 'ADMCMD_simTime=4102444800',
            'expected' => '<!-- dateOfNotFoundPage:4102444800-->',
        ];
        yield 'simulated frontend user group' => [
            'query' => 'ADMCMD_simUser=7',
            'expected' => '<!-- frontendUserGroupsOfNotFoundPage:0,-2,7-->',
        ];
    }

    #[Test]
    #[DataProvider('errorPageSubRequestKeepsPreviewSimulationOfBackendUserDataProvider')]
    public function errorPageSubRequestKeepsPreviewSimulationOfBackendUser(string $query, string $expected): void
    {
        $request = $this->withBackendUserSession(new InternalRequest('https://website.local/does-not-exist?' . $query));

        $response = $this->executeFrontendSubRequest($request);
        $body = (string)$response->getBody();

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('contentOfNotFoundPage', $body);
        self::assertStringContainsString($expected, $body);
    }

    #[Test]
    public function errorPageSubRequestDoesNotSimulateDateWithoutBackendUser(): void
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/does-not-exist?ADMCMD_simTime=4102444800'));
        $body = (string)$response->getBody();

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('contentOfNotFoundPage', $body);
        self::assertStringNotContainsString('<!-- dateOfNotFoundPage:4102444800-->', $body);
    }

    #[Test]
    public function errorPageSubRequestDropsOtherQueryParameters(): void
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/does-not-exist?example=1'));
        $body = (string)$response->getBody();

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('contentOfNotFoundPage', $body);
        self::assertStringContainsString('<!-- exampleParameterOfNotFoundPage:-->', $body);
    }

    #[Test]
    public function hiddenErrorPageIsShownToBackendUser(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', ['hidden' => 1], ['uid' => 404]);
        $request = $this->withBackendUserSession(new InternalRequest('https://website.local/does-not-exist'));

        $response = $this->executeFrontendSubRequest($request);
        $body = (string)$response->getBody();

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('contentOfNotFoundPage', $body);
        self::assertStringNotContainsString('contentOfRequestedPage', $body);
    }

    public static function inaccessibleErrorPageDataProvider(): array
    {
        return [
            'restricted to a frontend user group' => [
                'errorPageFields' => ['fe_group' => '1'],
                'subRequestStatusCode' => 403,
            ],
            'link to an external URL' => [
                'errorPageFields' => ['doktype' => 3, 'link' => 'https://external.example/'],
                'subRequestStatusCode' => 303,
            ],
            'folder' => [
                'errorPageFields' => ['doktype' => 254],
                'subRequestStatusCode' => 404,
            ],
            'hidden' => [
                'errorPageFields' => ['hidden' => 1],
                'subRequestStatusCode' => 404,
            ],
            'end time in the past' => [
                'errorPageFields' => ['endtime' => 1],
                'subRequestStatusCode' => 404,
            ],
        ];
    }

    #[Test]
    #[DataProvider('inaccessibleErrorPageDataProvider')]
    public function inaccessibleErrorPageIsReplacedByTypo3ErrorPage(array $errorPageFields, int $subRequestStatusCode): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', $errorPageFields, ['uid' => 404]);

        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/does-not-exist'));

        $this->assertTypo3ErrorPage($response);
        $this->assertWarningForErrorPage(['uri' => 'https://website.local/404', 'statusCode' => $subRequestStatusCode]);
    }

    #[Test]
    public function errorPageThatFailsToRenderIsReplacedByTypo3ErrorPage(): void
    {
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:core/Tests/Functional/Error/PageErrorHandler/Fixtures/PageWithPropagatedNotFoundResponse.typoscript',
                'EXT:core/Tests/Functional/Error/PageErrorHandler/Fixtures/NotFoundPageWithoutPageObject.typoscript',
            ]
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/does-not-exist'));

        $this->assertTypo3ErrorPage($response);
        $this->assertWarningForErrorPage(['uri' => 'https://website.local/404', 'statusCode' => 500]);
    }

    #[Test]
    public function requestOfInaccessibleErrorPageItselfGetsTypo3ErrorPage(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', ['doktype' => 254], ['uid' => 404]);

        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/404'));

        $this->assertTypo3ErrorPage($response);
    }

    #[Test]
    public function requestOfExternalErrorPageInLoopGetsTypo3ErrorPage(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')],
            [['errorCode' => 404, 'errorHandler' => 'Page', 'errorContentSource' => 'https://external.example/error-page']]
        );
        // The handler sends this header with the request for an external error page. A request
        // with this header comes from an error handler. Thus the handler does not request the
        // external error page again.
        $request = new InternalRequest('https://website.local/does-not-exist');
        $request = $request->withHeader('Requested-By', 'TYPO3 Error Handler');

        $response = $this->executeFrontendSubRequest($request);

        $this->assertTypo3ErrorPage($response);
    }

    #[Test]
    public function externalErrorPageWithErrorStatusCodeIsReplacedByTypo3ErrorPage(): void
    {
        $this->useExternalErrorPage('https://external.example/status-503');

        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/does-not-exist'));

        $this->assertTypo3ErrorPage($response);
        $this->assertWarningForErrorPage(['uri' => 'https://external.example/status-503', 'statusCode' => 503]);
    }

    #[Test]
    public function externalErrorPageThatCannotBeFetchedIsReplacedByTypo3ErrorPage(): void
    {
        $this->useExternalErrorPage('https://external.example/error-page');

        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/does-not-exist'));

        $this->assertTypo3ErrorPage($response);
        $this->assertWarningForErrorPage(['uri' => 'https://external.example/error-page', 'error' => 'Connection refused']);
    }

    #[Test]
    public function typo3ErrorPageGetsContentSecurityPolicyThatAllowsItsInlineStyle(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')],
            $this->buildErrorHandlingConfiguration('Page', [404]),
            [],
            ['enforce' => true]
        );
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', ['fe_group' => '1'], ['uid' => 404]);

        // The requested page sends the status 404 during the page rendering. At this time, the
        // policy of the middleware for the Content-Security-Policy headers is ready.
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/failing'));

        $this->assertTypo3ErrorPage($response);
        self::assertSame(1, preg_match('/<style nonce="([^"]+)">/', (string)$response->getBody(), $matches));
        self::assertMatchesRegularExpression(
            '/style-src-elem [^;]*\'nonce-' . preg_quote($matches[1], '/') . '\'/',
            $response->getHeaderLine('Content-Security-Policy')
        );
    }

    private function useExternalErrorPage(string $uri): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')],
            [['errorCode' => 404, 'errorHandler' => 'Page', 'errorContentSource' => $uri]]
        );
    }

    private function withBackendUserSession(InternalRequest $request): InternalRequest
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $userRecord = $this->get(ConnectionPool::class)->getConnectionForTable('be_users')
            ->select(['*'], 'be_users', ['uid' => 1])->fetchAssociative();
        $session = new BackendUserAuthentication()->createUserSession($userRecord);
        return $request->withCookieParams([BackendUserAuthentication::getCookieName() => $session->getJwt()]);
    }

    /**
     * The visitor must get the TYPO3 error page with the original status code. The visitor
     * must not get the status 500 or the content of another page.
     */
    private function assertTypo3ErrorPage(ResponseInterface $response): void
    {
        $body = (string)$response->getBody();
        self::assertSame(404, $response->getStatusCode());
        self::assertMatchesRegularExpression('/class="typo3-error-page-statuscode">\s*404\s*</', $body);
        self::assertStringContainsString('<h1 class="typo3-error-page-title">Not Found</h1>', $body);
        self::assertStringContainsString('<p class="typo3-error-page-message">The error page could not be resolved, as the error page itself is not accessible</p>', $body);
        self::assertStringNotContainsString('contentOfNotFoundPage', $body);
        self::assertStringNotContainsString('contentOfRequestedPage', $body);
    }

    /**
     * The log must name the URI of the error page and the cause, so that the integrator can
     * find the problem.
     */
    private function assertWarningForErrorPage(array $expectedData): void
    {
        self::assertCount(1, DummyWriter::$logs);
        self::assertSame(LogLevel::WARNING, DummyWriter::$logs[0]->getLevel());
        self::assertSame($expectedData, DummyWriter::$logs[0]->getData());
    }
}
